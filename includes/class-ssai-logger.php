<?php
/**
 * Safe logging.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes operational logs without secrets.
 */
class SSAI_Logger {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service writes and reads plugin-owned log tables directly.
	/**
	 * Writes a log entry.
	 *
	 * @param string $level Level.
	 * @param string $event Event code.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public static function log( $level, $event, $message, $context = array() ) {
		global $wpdb;

		$level = in_array( $level, array( 'info', 'warning', 'error', 'debug' ), true ) ? $level : 'info';
		$table = SSAI_Plugin::table( 'logs' );

		$wpdb->insert(
			$table,
			array(
				'level'      => $level,
				'event'      => sanitize_key( $event ),
				'message'    => sanitize_textarea_field( self::redact_string( $message ) ),
				'context'    => wp_json_encode( self::redact_context( $context ) ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Lists recent logs.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function recent( $limit = 50 ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'logs' );
		$limit = min( 200, max( 1, absint( $limit ) ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY id DESC LIMIT %d',
				$table,
				$limit
			),
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$row['context'] = ! empty( $row['context'] ) ? json_decode( $row['context'], true ) : array();
		}

		return $rows;
	}

	/**
	 * Redacts sensitive context.
	 *
	 * @param mixed $context Context.
	 * @return mixed
	 */
	public static function redact_context( $context ) {
		if ( is_array( $context ) ) {
			$clean = array();
			foreach ( $context as $key => $value ) {
				if ( self::is_secret_key( (string) $key ) ) {
					$clean[ $key ] = '[redacted]';
				} else {
					$clean[ $key ] = self::redact_context( $value );
				}
			}
			return $clean;
		}

		if ( is_string( $context ) ) {
			return self::redact_string( $context );
		}

		return $context;
	}

	/**
	 * Redacts likely secret strings.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function redact_string( $value ) {
		$value = (string) $value;
		$value = preg_replace( '/Bearer\s+[A-Za-z0-9_\-\.]+/i', 'Bearer [redacted-token]', $value );
		$value = preg_replace( '/(sk-[A-Za-z0-9_\-]{12,})/', '[redacted-key]', $value );
		$value = preg_replace( '/([A-Za-z0-9_\-]{32,}\.[A-Za-z0-9_\-]{12,}\.[A-Za-z0-9_\-]{12,})/', '[redacted-token]', $value );
		if ( strlen( $value ) > 2000 ) {
			$value = substr( $value, 0, 2000 ) . '...';
		}
		return (string) $value;
	}

	/**
	 * Checks secret-like key names.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	private static function is_secret_key( $key ) {
		return (bool) preg_match( '/token|secret|key|password|authorization|auth/i', $key );
	}
}
