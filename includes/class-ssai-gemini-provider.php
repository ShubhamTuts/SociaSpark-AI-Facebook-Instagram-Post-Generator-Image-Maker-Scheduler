<?php
/**
 * Gemini provider.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Gemini text provider.
 */
class SSAI_Gemini_Provider implements SSAI_AI_Provider_Interface {
	/**
	 * Checks credentials.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== SSAI_Settings::get_secret( 'gemini_api_key' );
	}

	/**
	 * Generates text.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return string|WP_Error
	 */
	public function generate_text( $prompt, $options = array() ) {
		$key = SSAI_Settings::get_secret( 'gemini_api_key' );
		if ( '' === $key ) {
			return new WP_Error( 'ssai_gemini_missing_key', __( 'Gemini API key is not configured.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$model = ! empty( $options['model'] ) ? sanitize_text_field( $options['model'] ) : SSAI_Settings::get( 'gemini_model', 'gemini-2.5-flash' );
		$url   = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent';

		$payload = array(
			'contents' => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array(
							'text' => (string) $prompt,
						),
					),
				),
			),
		);

		if ( ! empty( $options['system'] ) ) {
			$payload['systemInstruction'] = array(
				'parts' => array(
					array( 'text' => (string) $options['system'] ),
				),
			);
		}

		if ( ! empty( $options['json'] ) ) {
			$payload['generationConfig'] = array(
				'responseMimeType' => 'application/json',
			);
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array(
					'x-goog-api-key' => $key,
					'Content-Type'   => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ssai_gemini_request_failed',
				__( 'Gemini request failed.', 'sociaspark-ai-social-poster' ),
				array(
					'status'    => 500,
					'transient' => true,
				)
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$this->log_provider_error( $body, $status );
			$message = ! empty( $body['error']['message'] ) ? SSAI_Logger::redact_string( wp_strip_all_tags( (string) $body['error']['message'] ) ) : __( 'Gemini returned an error. Check credentials, model name, and quota.', 'sociaspark-ai-social-poster' );
			return new WP_Error(
				'ssai_gemini_error',
				$message,
				array(
					'status'      => $status,
					'transient'   => in_array( absint( $status ), array( 408, 409, 429, 500, 502, 503, 504 ), true ),
					'provider'    => 'gemini',
					'mode'        => 'text',
					'model'       => $model,
					'error_code'  => sanitize_key( $body['error']['status'] ?? '' ),
					'remediation' => __( 'Check the Gemini API key, selected model, region availability, and quota.', 'sociaspark-ai-social-poster' ),
				)
			);
		}

		if ( ! empty( $body['candidates'][0]['content']['parts'] ) && is_array( $body['candidates'][0]['content']['parts'] ) ) {
			$text = '';
			foreach ( $body['candidates'][0]['content']['parts'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= (string) $part['text'];
				}
			}
			if ( '' !== $text ) {
				return $text;
			}
		}

		return new WP_Error( 'ssai_gemini_empty_response', __( 'Gemini returned an empty response.', 'sociaspark-ai-social-poster' ), array( 'status' => 502 ) );
	}

	/**
	 * Gemini image generation is not part of v1 free core.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return WP_Error
	 */
	public function generate_image( $prompt, $options = array() ) {
		return new WP_Error( 'ssai_gemini_image_unavailable', __( 'Image generation is available through OpenAI in the free core.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
	}

	/**
	 * Logs a safe provider error.
	 *
	 * @param array|null $body Body.
	 * @param int        $status Status.
	 * @return void
	 */
	private function log_provider_error( $body, $status ) {
		if ( is_array( $body ) && ! empty( $body['error']['message'] ) ) {
			SSAI_Logger::log(
				'warning',
				'ssai_gemini_error',
				'Provider error',
				array(
					'status'  => $status,
					'message' => $body['error']['message'],
				)
			);
		}
	}
}
