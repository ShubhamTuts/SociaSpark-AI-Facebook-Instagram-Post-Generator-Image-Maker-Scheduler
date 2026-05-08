<?php
/**
 * Internationalization helpers.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads text domain.
 */
class SSAI_I18n {
	/**
	 * Loads plugin translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			'sociaspark-ai-social-poster',
			false,
			dirname( plugin_basename( SSAI_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
