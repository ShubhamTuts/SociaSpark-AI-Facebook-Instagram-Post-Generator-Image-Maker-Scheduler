<?php
/**
 * Calendar helper.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar query service.
 */
class SSAI_Calendar {
	/**
	 * Gets scheduled and published jobs for calendar.
	 *
	 * @param string $from From date.
	 * @param string $to To date.
	 * @return array
	 */
	public static function events( $from = '', $to = '' ) {
		global $wpdb;

		$jobs  = SSAI_Plugin::table( 'platform_jobs' );
		$posts = SSAI_Plugin::table( 'posts' );
		$from  = $from ? gmdate( 'Y-m-d H:i:s', strtotime( $from ) ) : gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$to    = $to ? gmdate( 'Y-m-d H:i:s', strtotime( $to ) ) : gmdate( 'Y-m-d H:i:s', strtotime( '+90 days' ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.*, p.title, p.media_id, p.media_url FROM {$jobs} j INNER JOIN {$posts} p ON p.id = j.ssai_post_id WHERE j.scheduled_at BETWEEN %s AND %s ORDER BY j.scheduled_at ASC",
				$from,
				$to
			),
			ARRAY_A
		);
	}
}
