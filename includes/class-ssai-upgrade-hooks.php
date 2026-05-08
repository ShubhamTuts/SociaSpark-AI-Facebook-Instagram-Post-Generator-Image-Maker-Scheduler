<?php
/**
 * Future add-on hooks.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers free-core extension points.
 */
class SSAI_Upgrade_Hooks {
	/**
	 * Fires registration hooks for add-on plugins.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		do_action( 'ssai_pro_register_platforms' );
		do_action( 'ssai_pro_register_ai_providers' );
		do_action( 'ssai_pro_register_media_renderers' );
		do_action( 'ssai_pro_register_training_sources' );
	}

	/**
	 * Checks whether a pro feature is available.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public static function has_feature( $feature ) {
		return (bool) apply_filters( 'ssai_pro_has_feature', false, sanitize_key( $feature ) );
	}

	/**
	 * Allows add-ons to score content without changing free-core behavior.
	 *
	 * @param string $content Content.
	 * @param array  $context Context.
	 * @return mixed
	 */
	public static function content_score( $content, $context = array() ) {
		return apply_filters( 'ssai_pro_content_score', null, (string) $content, (array) $context );
	}

	/**
	 * Allows add-ons to alter roadmap/feature card metadata.
	 *
	 * @param string $feature Feature key.
	 * @param array  $card Card data.
	 * @return array
	 */
	public static function feature_card( $feature, $card = array() ) {
		return (array) apply_filters( 'ssai_pro_feature_card', (array) $card, sanitize_key( $feature ) );
	}
}
