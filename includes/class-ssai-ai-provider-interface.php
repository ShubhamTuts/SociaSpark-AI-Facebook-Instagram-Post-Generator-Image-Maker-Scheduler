<?php
/**
 * AI provider contract.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for AI providers.
 */
interface SSAI_AI_Provider_Interface {
	/**
	 * Whether the provider has the required credentials.
	 *
	 * @return bool
	 */
	public function is_configured();

	/**
	 * Generates text.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return string|WP_Error
	 */
	public function generate_text( $prompt, $options = array() );

	/**
	 * Generates an image.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return array|WP_Error
	 */
	public function generate_image( $prompt, $options = array() );
}
