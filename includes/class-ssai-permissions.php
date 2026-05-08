<?php
/**
 * Permission helpers.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralizes admin and REST permission checks.
 */
class SSAI_Permissions {
	/**
	 * Returns the capability required to manage the plugin.
	 *
	 * @return string
	 */
	public static function capability() {
		return (string) apply_filters( 'ssai_admin_capability', 'manage_options' );
	}

	/**
	 * Checks whether current user can manage SociaSpark.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return is_user_logged_in() && current_user_can( self::capability() );
	}

	/**
	 * REST permission callback with nonce verification for wp-admin requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public static function rest_permission( $request ) {
		if ( ! self::can_manage() ) {
			return new WP_Error(
				'ssai_forbidden',
				__( 'You do not have permission to manage SociaSpark AI.', 'sociaspark-ai-social-poster' ),
				array( 'status' => 403 )
			);
		}

		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_header( 'x-wp-nonce' );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'wp_rest' ) ) {
			return new WP_Error(
				'ssai_bad_nonce',
				__( 'The security token is missing or invalid. Refresh the dashboard and try again.', 'sociaspark-ai-social-poster' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
