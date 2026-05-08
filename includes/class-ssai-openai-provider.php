<?php
/**
 * OpenAI provider.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI text and image provider.
 */
class SSAI_OpenAI_Provider implements SSAI_AI_Provider_Interface {
	/**
	 * API base.
	 *
	 * @var string
	 */
	private $base = 'https://api.openai.com/v1';

	/**
	 * Checks credentials.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== SSAI_Settings::get_secret( 'openai_api_key' );
	}

	/**
	 * Generates text through the Responses API.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return string|WP_Error
	 */
	public function generate_text( $prompt, $options = array() ) {
		$key = SSAI_Settings::get_secret( 'openai_api_key' );
		if ( '' === $key ) {
			return new WP_Error( 'ssai_openai_missing_key', __( 'OpenAI API key is not configured.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'model'        => ! empty( $options['model'] ) ? sanitize_text_field( $options['model'] ) : SSAI_Settings::get( 'default_text_model', SSAI_Settings::get( 'openai_text_model', 'gpt-5.4-mini' ) ),
			'instructions' => ! empty( $options['system'] ) ? (string) $options['system'] : '',
			'input'        => (string) $prompt,
			'store'        => false,
		);

		if ( ! empty( $options['max_tokens'] ) ) {
			$payload['max_output_tokens'] = absint( $options['max_tokens'] );
		}

		if ( ! empty( $options['json'] ) ) {
			if ( ! empty( $options['schema'] ) && is_array( $options['schema'] ) ) {
				$payload['text'] = array(
					'format' => array(
						'type'        => 'json_schema',
						'name'        => sanitize_key( $options['schema']['name'] ?? 'ssai_response' ),
						'schema'      => $options['schema']['schema'],
						'description' => 'Structured SociaSpark AI response.',
						'strict'      => false,
					),
				);
			} else {
				$payload['text'] = array(
					'format' => array(
						'type' => 'json_object',
					),
				);
			}
		}

		$response = wp_remote_post(
			$this->base . '/responses',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		return $this->parse_text_response( $response, 'openai', $payload['model'] );
	}

	/**
	 * Generates image through the Images API.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return array|WP_Error
	 */
	public function generate_image( $prompt, $options = array() ) {
		$key = SSAI_Settings::get_secret( 'openai_api_key' );
		if ( '' === $key ) {
			return new WP_Error( 'ssai_openai_missing_key', __( 'OpenAI API key is not configured.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$size = ! empty( $options['size'] ) ? sanitize_text_field( $options['size'] ) : '1024x1024';
		if ( ! preg_match( '/^\d{3,4}x\d{3,4}$/', $size ) ) {
			$size = '1024x1024';
		}

		$payload = array(
			'model'  => ! empty( $options['model'] ) ? sanitize_text_field( $options['model'] ) : SSAI_Settings::get( 'default_image_model', SSAI_Settings::get( 'openai_image_model', 'gpt-image-2' ) ),
			'prompt' => (string) $prompt,
			'n'      => 1,
			'size'   => $size,
		);

		if ( ! empty( $options['quality'] ) ) {
			$payload['quality'] = sanitize_key( $options['quality'] );
		}

		$response = wp_remote_post(
			$this->base . '/images/generations',
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssai_openai_image_request_failed', __( 'OpenAI image request failed.', 'sociaspark-ai-social-poster' ), array( 'status' => 500, 'transient' => true ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			return $this->provider_error( $body, 'ssai_openai_image_error', $status, $payload['model'], 'image' );
		}

		$item = isset( $body['data'][0] ) && is_array( $body['data'][0] ) ? $body['data'][0] : array();
		if ( ! empty( $item['b64_json'] ) ) {
			return array(
				'type' => 'base64',
				'data' => (string) $item['b64_json'],
				'mime' => 'image/png',
			);
		}

		if ( ! empty( $item['url'] ) ) {
			return array(
				'type' => 'url',
				'url'  => esc_url_raw( $item['url'] ),
				'mime' => 'image/png',
			);
		}

		return new WP_Error( 'ssai_openai_image_empty', __( 'OpenAI returned no image data.', 'sociaspark-ai-social-poster' ), array( 'status' => 502 ) );
	}

	/**
	 * Tests model access without generating paid image output.
	 *
	 * @param string $model Model.
	 * @param string $mode Mode.
	 * @return true|WP_Error
	 */
	public function test_model( $model, $mode = 'text' ) {
		$key = SSAI_Settings::get_secret( 'openai_api_key' );
		if ( '' === $key ) {
			return new WP_Error( 'ssai_openai_missing_key', __( 'OpenAI API key is not configured.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$model = sanitize_text_field( $model ?: ( 'image' === $mode ? SSAI_Settings::get( 'default_image_model', 'gpt-image-2' ) : SSAI_Settings::get( 'default_text_model', 'gpt-5.4-mini' ) ) );
		$response = wp_remote_get(
			$this->base . '/models/' . rawurlencode( $model ),
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssai_openai_model_request_failed', __( 'OpenAI model check failed.', 'sociaspark-ai-social-poster' ), array( 'status' => 500, 'transient' => true ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			return $this->provider_error( $body, 'ssai_openai_model_error', $status, $model, $mode );
		}

		return true;
	}

	/**
	 * Parses text response.
	 *
	 * @param array|WP_Error $response Response.
	 * @param string         $provider Provider name.
	 * @param string         $model Model.
	 * @return string|WP_Error
	 */
	private function parse_text_response( $response, $provider, $model = '' ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssai_' . $provider . '_request_failed', __( 'AI provider request failed.', 'sociaspark-ai-social-poster' ), array( 'status' => 500, 'transient' => true ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			return $this->provider_error( $body, 'ssai_' . $provider . '_error', $status, $model, 'text' );
		}

		if ( ! empty( $body['output_text'] ) ) {
			return (string) $body['output_text'];
		}

		if ( ! empty( $body['output'] ) && is_array( $body['output'] ) ) {
			$text = '';
			foreach ( $body['output'] as $output ) {
				if ( empty( $output['content'] ) || ! is_array( $output['content'] ) ) {
					continue;
				}
				foreach ( $output['content'] as $content ) {
					if ( isset( $content['text'] ) ) {
						$text .= (string) $content['text'];
					}
				}
			}

			if ( '' !== $text ) {
				return $text;
			}
		}

		return new WP_Error( 'ssai_openai_empty_response', __( 'OpenAI returned an empty response.', 'sociaspark-ai-social-poster' ), array( 'status' => 502 ) );
	}

	/**
	 * Builds safe provider error.
	 *
	 * @param array|null $body Response body.
	 * @param string     $code Error code.
	 * @param int        $status HTTP status.
	 * @return WP_Error
	 */
	private function provider_error( $body, $code, $status, $model = '', $mode = '' ) {
		$details = $this->safe_provider_error_details( $body );
		$message = $details['message'] ? $details['message'] : __( 'OpenAI returned an error. Check credentials, model access, and provider permissions.', 'sociaspark-ai-social-poster' );
		$transient = in_array( absint( $status ), array( 408, 409, 429, 500, 502, 503, 504 ), true );

		SSAI_Logger::log(
			'warning',
			$code,
			'OpenAI provider error',
			array(
				'status'     => $status,
				'provider'   => 'openai',
				'mode'       => $mode,
				'model'      => $model,
				'error_code' => $details['error_code'],
				'message'    => $message,
			)
		);

		return new WP_Error(
			$code,
			$message,
			array(
				'status'      => $status,
				'transient'   => $transient,
				'provider'    => 'openai',
				'mode'        => $mode,
				'model'       => $model,
				'error_code'  => $details['error_code'],
				'remediation' => $this->remediation( $status, $details['error_code'] ),
			)
		);
	}

	/**
	 * Extracts safe provider error details.
	 *
	 * @param array|null $body Body.
	 * @return array
	 */
	private function safe_provider_error_details( $body ) {
		$error = is_array( $body ) && isset( $body['error'] ) && is_array( $body['error'] ) ? $body['error'] : array();
		return array(
			'message'    => ! empty( $error['message'] ) ? SSAI_Logger::redact_string( wp_strip_all_tags( (string) $error['message'] ) ) : '',
			'error_code' => ! empty( $error['code'] ) ? sanitize_key( $error['code'] ) : '',
		);
	}

	/**
	 * Returns admin-safe remediation guidance.
	 *
	 * @param int    $status HTTP status.
	 * @param string $provider_code Provider code.
	 * @return string
	 */
	private function remediation( $status, $provider_code ) {
		if ( in_array( absint( $status ), array( 401, 403 ), true ) ) {
			return __( 'Check that the OpenAI API key is valid and has access to the selected model.', 'sociaspark-ai-social-poster' );
		}
		if ( 404 === absint( $status ) || 'model_not_found' === $provider_code ) {
			return __( 'Choose a currently available model in SociaSpark AI settings, then test the provider again.', 'sociaspark-ai-social-poster' );
		}
		if ( 429 === absint( $status ) ) {
			return __( 'The OpenAI account is rate limited or out of quota. Wait, upgrade quota, or choose another provider.', 'sociaspark-ai-social-poster' );
		}
		return __( 'Review the selected model, account permissions, and provider status before retrying.', 'sociaspark-ai-social-poster' );
	}
}
