<?php
/**
 * Idea Bank helper.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idea Bank service.
 */
class SSAI_Idea_Bank {
	/**
	 * Creates an idea.
	 *
	 * @param array $data Idea data.
	 * @return int
	 */
	public static function create( $data ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'ideas' );
		$now   = current_time( 'mysql' );
		$wpdb->insert(
			$table,
			array(
				'title'      => sanitize_text_field( $data['title'] ?? '' ),
				'idea_text'  => sanitize_textarea_field( $data['idea_text'] ?? '' ),
				'source'     => sanitize_key( $data['source'] ?? 'manual' ),
				'tags'       => sanitize_text_field( $data['tags'] ?? '' ),
				'status'     => sanitize_key( $data['status'] ?? 'active' ),
				'created_by' => get_current_user_id(),
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Lists ideas.
	 *
	 * @param string $status Status.
	 * @return array
	 */
	public static function all( $status = '' ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'ideas' );
		if ( $status ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC", sanitize_key( $status ) ), ARRAY_A );
		}

		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );
	}
}
