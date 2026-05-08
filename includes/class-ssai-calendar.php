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
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service reads plugin-owned custom tables for admin calendar views.
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
		$from  = self::normalize_site_datetime( $from );
		$to    = self::normalize_site_datetime( $to );

		if ( null === $from ) {
			$from = wp_date( 'Y-m-d H:i:s', strtotime( '-30 days' ), wp_timezone() );
		}

		if ( null === $to ) {
			$to = wp_date( 'Y-m-d H:i:s', strtotime( '+90 days' ), wp_timezone() );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT j.*, p.title, p.media_id, p.media_url FROM %i j INNER JOIN %i p ON p.id = j.ssai_post_id WHERE j.scheduled_at BETWEEN %s AND %s ORDER BY j.scheduled_at ASC',
				$jobs,
				$posts,
				$from,
				$to
			),
			ARRAY_A
		);
	}

	/**
	 * Normalizes request datetimes into the site timezone.
	 *
	 * @param mixed $value Raw datetime value.
	 * @return string|null
	 */
	private static function normalize_site_datetime( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
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
}
