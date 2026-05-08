<?php
/**
 * Secret encryption.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts and decrypts sensitive values.
 */
class SSAI_Encryption {
	// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Base64 is used here for binary-safe storage of encrypted payload parts.
	/**
	 * Encrypts plaintext.
	 *
	 * @param string $plaintext Plain value.
	 * @return string
	 */
	public static function encrypt( $plaintext ) {
		$plaintext = (string) $plaintext;
		if ( '' === $plaintext ) {
			return '';
		}

		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plaintext, $nonce, self::key() );

			return base64_encode(
				wp_json_encode(
					array(
						'engine' => 'sodium',
						'nonce'  => base64_encode( $nonce ),
						'value'  => base64_encode( $cipher ),
					)
				)
			);
		}

		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plaintext, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv );

		return base64_encode(
			wp_json_encode(
				array(
					'engine' => 'openssl',
					'nonce'  => base64_encode( $iv ),
					'value'  => base64_encode( (string) $cipher ),
				)
			)
		);
	}

	/**
	 * Decrypts a value.
	 *
	 * @param string $payload Encrypted payload.
	 * @return string
	 */
	public static function decrypt( $payload ) {
		if ( '' === $payload ) {
			return '';
		}

		$json = base64_decode( $payload, true );
		if ( false === $json ) {
			return '';
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['engine'] ) || empty( $data['nonce'] ) || empty( $data['value'] ) ) {
			return '';
		}

		$nonce = base64_decode( (string) $data['nonce'], true );
		$value = base64_decode( (string) $data['value'], true );
		if ( false === $nonce || false === $value ) {
			return '';
		}

		if ( 'sodium' === $data['engine'] && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$plain = sodium_crypto_secretbox_open( $value, $nonce, self::key() );
			return false === $plain ? '' : (string) $plain;
		}

		if ( 'openssl' === $data['engine'] ) {
			$plain = openssl_decrypt( $value, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $nonce );
			return false === $plain ? '' : (string) $plain;
		}

		return '';
	}

	/**
	 * Derives a 32-byte encryption key from WordPress salts.
	 *
	 * @return string
	 */
	private static function key() {
		$material = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . 'sociaspark-ai-social-poster-v1';
		return hash( 'sha256', $material, true );
	}
}
