<?php
/**
 * Meta connection manager.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores Meta platform connections.
 */
class SSAI_Meta_Manager {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service manages plugin-owned custom tables directly.
	/**
	 * Saves or updates a connection.
	 *
	 * @param array $data Connection data.
	 * @return array|WP_Error
	 */
	public static function save_connection( $data ) {
		global $wpdb;

		$platform = sanitize_key( $data['platform'] ?? '' );
		if ( ! in_array( $platform, array( 'facebook', 'instagram' ), true ) ) {
			return new WP_Error( 'ssai_bad_platform', __( 'Unsupported platform.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$account_id = sanitize_text_field( $data['account_id'] ?? '' );
		$token      = is_scalar( $data['access_token'] ?? '' ) ? trim( (string) wp_unslash( $data['access_token'] ) ) : '';
		$refresh    = is_scalar( $data['refresh_token'] ?? '' ) ? trim( (string) wp_unslash( $data['refresh_token'] ) ) : '';
		if ( '' === $account_id || '' === $token ) {
			return new WP_Error( 'ssai_connection_missing', __( 'Account ID and access token are required.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$table = SSAI_Plugin::table( 'connections' );
		$now   = current_time( 'mysql' );
		$meta  = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();

		$insert = array(
			'platform'                => $platform,
			'account_label'           => sanitize_text_field( $data['account_label'] ?? $platform . ' ' . $account_id ),
			'account_id'              => $account_id,
			'encrypted_access_token'  => SSAI_Encryption::encrypt( $token ),
			'encrypted_refresh_token' => '' !== $refresh ? SSAI_Encryption::encrypt( $refresh ) : null,
			'token_expires_at'        => self::normalize_site_datetime( $data['token_expires_at'] ?? '' ),
			'status'                  => 'connected',
			'meta'                    => wp_json_encode( SSAI_Logger::redact_context( $meta ) ),
			'created_at'              => $now,
			'updated_at'              => $now,
		);

		$existing_id = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM %i WHERE platform = %s AND account_id = %s LIMIT 1', $table, $platform, $account_id )
		);

		if ( $existing_id ) {
			unset( $insert['created_at'] );
			$wpdb->update( $table, $insert, array( 'id' => absint( $existing_id ) ) );
			return self::get_connection_public( (int) $existing_id );
		}

		$wpdb->insert( $table, $insert );
		return self::get_connection_public( (int) $wpdb->insert_id );
	}

	/**
	 * Tests Meta token/account reachability.
	 *
	 * @param array $data Test data.
	 * @return array|WP_Error
	 */
	public static function test_connection( $data ) {
		$connection = array();
		if ( ! empty( $data['connection_id'] ) ) {
			$connection = self::get_connection_private_by_id( absint( $data['connection_id'] ) );
			if ( is_wp_error( $connection ) ) {
				return $connection;
			}
		} else {
			$platform   = sanitize_key( $data['platform'] ?? '' );
			$account_id = sanitize_text_field( $data['account_id'] ?? '' );
			$token      = is_scalar( $data['access_token'] ?? '' ) ? trim( (string) wp_unslash( $data['access_token'] ) ) : '';
			if ( ! in_array( $platform, array( 'facebook', 'instagram' ), true ) || '' === $account_id || '' === $token ) {
				return new WP_Error( 'ssai_meta_test_missing', __( 'Platform, account ID, and access token are required for a Meta test.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
			}
			$connection = array(
				'platform'     => $platform,
				'account_id'   => $account_id,
				'access_token' => $token,
			);
		}

		$version = sanitize_text_field( SSAI_Settings::get( 'meta_graph_version', 'v24.0' ) );
		$fields  = 'instagram' === $connection['platform'] ? 'id,username' : 'id,name';
		$url     = add_query_arg(
			array(
				'fields'       => $fields,
				'access_token' => $connection['access_token'],
			),
			'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $connection['account_id'] )
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ssai_meta_test_request_failed',
				__( 'Meta connection test failed before reaching Meta.', 'sociaspark-ai-social-poster' ),
				array(
					'status'    => 500,
					'transient' => true,
				)
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = ! empty( $body['error']['message'] ) ? SSAI_Logger::redact_string( wp_strip_all_tags( (string) $body['error']['message'] ) ) : __( 'Meta returned an account test error.', 'sociaspark-ai-social-poster' );
			SSAI_Logger::log(
				'warning',
				'ssai_meta_connection_test_failed',
				$message,
				array(
					'status'     => $status,
					'platform'   => $connection['platform'],
					'account_id' => $connection['account_id'],
				)
			);
			return new WP_Error(
				'ssai_meta_connection_test_failed',
				$message,
				array(
					'status'      => $status,
					'provider'    => 'meta',
					'remediation' => __( 'Check token validity, app mode, Page/Instagram account ID, and required publishing permissions.', 'sociaspark-ai-social-poster' ),
				)
			);
		}

		return array(
			'ok'         => true,
			'platform'   => $connection['platform'],
			'account_id' => sanitize_text_field( $body['id'] ?? $connection['account_id'] ),
			'name'       => sanitize_text_field( $body['name'] ?? $body['username'] ?? '' ),
			'message'    => __( 'Meta account is reachable with this token.', 'sociaspark-ai-social-poster' ),
		);
	}

	/**
	 * Lists public connection metadata.
	 *
	 * @return array
	 */
	public static function list_connections() {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, platform, account_label, account_id, token_expires_at, status, meta, created_at, updated_at FROM %i ORDER BY id DESC',
				$table
			),
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$row['meta'] = ! empty( $row['meta'] ) ? json_decode( $row['meta'], true ) : array();
		}

		return $rows;
	}

	/**
	 * Gets connection with decrypted token for server-side use.
	 *
	 * @param string $platform Platform.
	 * @param string $account_id Account ID.
	 * @return array|WP_Error
	 */
	public static function get_connection_for_publish( $platform, $account_id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE platform = %s AND account_id = %s AND status = %s LIMIT 1',
				$table,
				sanitize_key( $platform ),
				sanitize_text_field( $account_id ),
				'connected'
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_Error(
				'ssai_connection_not_found',
				__( 'A connected platform account was not found.', 'sociaspark-ai-social-poster' ),
				array(
					'status'    => 404,
					'transient' => false,
				)
			);
		}

		if ( ! empty( $row['token_expires_at'] ) && self::is_past_site_datetime( $row['token_expires_at'] ) ) {
			self::mark_status( (int) $row['id'], 'expired' );
			return new WP_Error(
				'ssai_connection_expired',
				__( 'The platform token has expired. Reconnect the account.', 'sociaspark-ai-social-poster' ),
				array(
					'status'    => 401,
					'transient' => false,
				)
			);
		}

		$row['access_token'] = SSAI_Encryption::decrypt( (string) $row['encrypted_access_token'] );
		$row['meta']         = ! empty( $row['meta'] ) ? json_decode( $row['meta'], true ) : array();

		return $row;
	}

	/**
	 * Deletes a connection.
	 *
	 * @param int $id Connection ID.
	 * @return bool
	 */
	public static function delete_connection( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		return false !== $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Marks connection status.
	 *
	 * @param int    $id ID.
	 * @param string $status Status.
	 * @return void
	 */
	public static function mark_status( $id, $status ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		$wpdb->update(
			$table,
			array(
				'status'     => sanitize_key( $status ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Gets public connection row.
	 *
	 * @param int $id ID.
	 * @return array
	 */
	private static function get_connection_public( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT id, platform, account_label, account_id, token_expires_at, status, meta, created_at, updated_at FROM %i WHERE id = %d', $table, absint( $id ) ),
			ARRAY_A
		);

		if ( ! $row ) {
			return array();
		}

		$row['meta'] = ! empty( $row['meta'] ) ? json_decode( $row['meta'], true ) : array();
		return $row;
	}

	/**
	 * Gets private connection by ID.
	 *
	 * @param int $id ID.
	 * @return array|WP_Error
	 */
	private static function get_connection_private_by_id( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'connections' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, absint( $id ) ),
			ARRAY_A
		);
		if ( ! $row ) {
			return new WP_Error( 'ssai_connection_not_found', __( 'A connected platform account was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
		}
		$row['access_token'] = SSAI_Encryption::decrypt( (string) $row['encrypted_access_token'] );
		return $row;
	}

	/**
	 * Normalizes a dashboard datetime into the site timezone.
	 *
	 * @param mixed $value Raw datetime value.
	 * @return string|null
	 */
	private static function normalize_site_datetime( $value ) {
		$value = is_scalar( $value ) ? trim( (string) wp_unslash( $value ) ) : '';
		if ( '' === $value ) {
			return null;
		}

		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
		} catch ( Exception $exception ) {
			return null;
		}

		return $date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Checks whether a stored site-local datetime is already in the past.
	 *
	 * @param string $value Stored datetime.
	 * @return bool
	 */
	private static function is_past_site_datetime( $value ) {
		try {
			$expires_at = new DateTimeImmutable( (string) $value, wp_timezone() );
			$now        = new DateTimeImmutable( 'now', wp_timezone() );
		} catch ( Exception $exception ) {
			return false;
		}

		return $expires_at < $now;
	}
}
