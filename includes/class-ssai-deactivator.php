<?php
/**
 * Plugin deactivation.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deactivation tasks.
 */
class SSAI_Deactivator {
	/**
	 * Clears scheduled cron event.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ssai_process_scheduled_jobs' );
	}
}
