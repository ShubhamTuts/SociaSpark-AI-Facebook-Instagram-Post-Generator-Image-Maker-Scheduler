<?php
/**
 * Claude provider.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anthropic Claude text provider.
 */
class SSAI_Claude_Provider implements SSAI_AI_Provider_Interface {
	/**
	 * Checks credentials.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== SSAI_Settings::get_secret( 'claude_api_key' );
	}

	/**
	 * Generates text through Anthropic Messages API.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return string|WP_Error
	 */
	public function generate_text( $prompt, $options = array() ) {
		$key = SSAI_Settings::get_secret( 'claude_api_key' );
		if ( '' === $key ) {
			return new WP_Error( 'ssai_claude_missing_key', __( 'Claude API key is not configured.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'model'      => ! empty( $options['model'] ) ? sanitize_text_field( $options['model'] ) : SSAI_Settings::get( 'claude_model', 'claude-sonnet-4-6' ),
			'max_tokens' => ! empty( $options['max_tokens'] ) ? absint( $options['max_tokens'] ) : 4000,
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => (string) $prompt,
				),
			),
		);

		if ( ! empty( $options['system'] ) ) {
			$payload['system'] = (string) $options['system'];
		}

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ssai_claude_request_failed',
				__( 'Claude request failed.', 'sociaspark-ai-social-poster' ),
				array(
					'status'    => 500,
					'transient' => true,
				)
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = __( 'Claude returned an error. Check credentials, model name, and quota.', 'sociaspark-ai-social-poster' );
			if ( is_array( $body ) && ! empty( $body['error']['message'] ) ) {
				$message = SSAI_Logger::redact_string( wp_strip_all_tags( (string) $body['error']['message'] ) );
				SSAI_Logger::log(
					'warning',
					'ssai_claude_error',
					'Provider error',
					array(
						'status'  => $status,
						'model'   => $payload['model'],
						'message' => $message,
					)
				);
			}

			return new WP_Error(
				'ssai_claude_error',
				$message,
				array(
					'status'      => $status,
					'transient'   => in_array( absint( $status ), array( 408, 409, 429, 500, 502, 503, 504 ), true ),
					'provider'    => 'claude',
					'mode'        => 'text',
					'model'       => $payload['model'],
					'error_code'  => sanitize_key( $body['error']['type'] ?? '' ),
					'remediation' => __( 'Check the Claude API key, selected model, workspace permissions, and quota.', 'sociaspark-ai-social-poster' ),
				)
			);
		}

		if ( ! empty( $body['content'] ) && is_array( $body['content'] ) ) {
			$text = '';
			foreach ( $body['content'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= (string) $part['text'];
				}
			}
			if ( '' !== $text ) {
				return $text;
			}
		}

		return new WP_Error( 'ssai_claude_empty_response', __( 'Claude returned an empty response.', 'sociaspark-ai-social-poster' ), array( 'status' => 502 ) );
	}

	/**
	 * Claude image generation is not part of v1 free core.
	 *
	 * @param string $prompt Prompt.
	 * @param array  $options Options.
	 * @return WP_Error
	 */
	public function generate_image( $prompt, $options = array() ) {
		return new WP_Error( 'ssai_claude_image_unavailable', __( 'Image generation is available through OpenAI in the free core.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
	}
}
