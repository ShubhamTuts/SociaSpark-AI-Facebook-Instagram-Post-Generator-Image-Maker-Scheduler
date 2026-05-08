<?php
/**
 * Settings and secret storage.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings.
 */
class SSAI_Settings {
	const OPTION = 'ssai_settings';

	/**
	 * Returns default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'default_provider'         => 'openai',
			'default_text_provider'    => 'openai',
			'default_text_model'       => 'gpt-5.4-mini',
			'default_image_provider'   => 'openai',
			'default_image_model'      => 'gpt-image-2',
			'openai_text_model'        => 'gpt-5.4-mini',
			'openai_image_model'       => 'gpt-image-2',
			'openai_image_size'        => '1024x1024',
			'openai_image_quality'     => 'auto',
			'gemini_model'             => 'gemini-2.5-flash',
			'claude_model'             => 'claude-sonnet-4-6',
			'meta_graph_version'       => 'v24.0',
			'business_name'            => '',
			'audience'                 => '',
			'tone'                     => 'clear, warm, expert',
			'default_cta'              => '',
			'brand_words'              => '',
			'words_to_avoid'           => 'unlock, game-changer, in today\'s digital world, elevate',
			'default_posting_time'     => '09:00',
			'retry_attempts'           => 3,
			'delete_data_on_uninstall' => false,
			'configured'               => array(),
			'secrets'                  => array(),
		);
	}

	/**
	 * Returns all raw settings.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Gets a public setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Default value.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$settings = self::all();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Gets decrypted secret value.
	 *
	 * @param string $key Secret key.
	 * @return string
	 */
	public static function get_secret( $key ) {
		$settings = self::all();
		$secrets  = isset( $settings['secrets'] ) && is_array( $settings['secrets'] ) ? $settings['secrets'] : array();

		if ( empty( $secrets[ $key ] ) ) {
			return '';
		}

		return SSAI_Encryption::decrypt( (string) $secrets[ $key ] );
	}

	/**
	 * Returns settings safe for REST responses.
	 *
	 * @return array
	 */
	public static function public_settings() {
		$settings = self::all();
		unset( $settings['secrets'] );

		$configured  = isset( $settings['configured'] ) && is_array( $settings['configured'] ) ? $settings['configured'] : array();
		$secret_keys = self::secret_keys();

		foreach ( $secret_keys as $secret_key ) {
			$settings[ $secret_key . '_configured' ] = ! empty( $configured[ $secret_key ] );
		}

		$settings['timezone'] = wp_timezone_string();

		return $settings;
	}

	/**
	 * Updates settings from REST payload.
	 *
	 * @param array $input Request data.
	 * @return array
	 */
	public static function update_from_rest( $input ) {
		$settings   = self::all();
		$public_map = array(
			'default_provider'         => 'sanitize_key',
			'default_text_provider'    => 'sanitize_key',
			'default_text_model'       => 'sanitize_text_field',
			'default_image_provider'   => 'sanitize_key',
			'default_image_model'      => 'sanitize_text_field',
			'openai_text_model'        => 'sanitize_text_field',
			'openai_image_model'       => 'sanitize_text_field',
			'openai_image_size'        => 'sanitize_text_field',
			'openai_image_quality'     => 'sanitize_text_field',
			'gemini_model'             => 'sanitize_text_field',
			'claude_model'             => 'sanitize_text_field',
			'meta_graph_version'       => 'sanitize_text_field',
			'business_name'            => 'sanitize_text_field',
			'audience'                 => 'sanitize_textarea_field',
			'tone'                     => 'sanitize_text_field',
			'default_cta'              => 'sanitize_text_field',
			'brand_words'              => 'sanitize_textarea_field',
			'words_to_avoid'           => 'sanitize_textarea_field',
			'default_posting_time'     => 'sanitize_text_field',
			'retry_attempts'           => 'absint',
			'delete_data_on_uninstall' => 'rest_sanitize_boolean',
		);

		foreach ( $public_map as $key => $callback ) {
			if ( array_key_exists( $key, $input ) ) {
				$settings[ $key ] = call_user_func( $callback, $input[ $key ] );
			}
		}

		if ( ! in_array( $settings['default_provider'], array( 'openai', 'gemini', 'claude' ), true ) ) {
			$settings['default_provider'] = 'openai';
		}
		if ( empty( $settings['default_text_provider'] ) ) {
			$settings['default_text_provider'] = $settings['default_provider'];
		}
		if ( ! in_array( $settings['default_text_provider'], array( 'openai', 'gemini', 'claude' ), true ) ) {
			$settings['default_text_provider'] = 'openai';
		}
		$settings['default_provider'] = $settings['default_text_provider'];
		if ( ! in_array( $settings['default_image_provider'], array( 'openai' ), true ) ) {
			$settings['default_image_provider'] = 'openai';
		}
		if ( ! preg_match( '/^\d{3,4}x\d{3,4}$/', (string) $settings['openai_image_size'] ) ) {
			$settings['openai_image_size'] = '1024x1024';
		}
		if ( ! in_array( $settings['openai_image_quality'], array( 'auto', 'low', 'medium', 'high' ), true ) ) {
			$settings['openai_image_quality'] = 'auto';
		}

		$settings['retry_attempts'] = min( 10, max( 1, absint( $settings['retry_attempts'] ) ) );

		if ( ! isset( $settings['secrets'] ) || ! is_array( $settings['secrets'] ) ) {
			$settings['secrets'] = array();
		}
		if ( ! isset( $settings['configured'] ) || ! is_array( $settings['configured'] ) ) {
			$settings['configured'] = array();
		}

		foreach ( self::secret_keys() as $secret_key ) {
			if ( ! array_key_exists( $secret_key, $input ) ) {
				continue;
			}

			$value = is_scalar( $input[ $secret_key ] ) ? trim( (string) wp_unslash( $input[ $secret_key ] ) ) : '';
			if ( '' === $value ) {
				continue;
			}

			if ( '__ssai_clear__' === $value ) {
				unset( $settings['secrets'][ $secret_key ], $settings['configured'][ $secret_key ] );
				continue;
			}

			$settings['secrets'][ $secret_key ]    = SSAI_Encryption::encrypt( $value );
			$settings['configured'][ $secret_key ] = true;
		}

		update_option( self::OPTION, $settings, false );

		return self::public_settings();
	}

	/**
	 * Secret keys stored encrypted.
	 *
	 * @return array
	 */
	public static function secret_keys() {
		return array(
			'openai_api_key',
			'gemini_api_key',
			'claude_api_key',
			'meta_app_secret',
			'meta_page_access_token',
		);
	}
}
