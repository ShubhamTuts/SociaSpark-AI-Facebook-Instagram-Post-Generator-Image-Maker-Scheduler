<?php
/**
 * Facebook publishing.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publishes Facebook Page posts.
 */
class SSAI_Facebook_Publisher {
	/**
	 * Publishes a post.
	 *
	 * @param array $connection Connection.
	 * @param array $post Post row.
	 * @return array|WP_Error
	 */
	public function publish( $connection, $post ) {
		$page_id = sanitize_text_field( $connection['account_id'] );
		$caption = ! empty( $post['content_facebook'] ) ? $post['content_facebook'] : $post['content_long'];
		$caption = wp_strip_all_tags( (string) $caption );
		$url     = $this->media_url( $post );
		$version = sanitize_text_field( SSAI_Settings::get( 'meta_graph_version', 'v24.0' ) );

		if ( $url ) {
			$endpoint = 'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $page_id ) . '/photos';
			$body     = array(
				'url'          => esc_url_raw( $url ),
				'caption'      => $caption,
				'access_token' => $connection['access_token'],
			);
		} else {
			$endpoint = 'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $page_id ) . '/feed';
			$body     = array(
				'message'      => $caption,
				'access_token' => $connection['access_token'],
			);
		}

		return $this->post_to_meta( $endpoint, $body, 'facebook' );
	}

	/**
	 * Resolves media URL.
	 *
	 * @param array $post Post row.
	 * @return string
	 */
	private function media_url( $post ) {
		if ( ! empty( $post['media_id'] ) ) {
			$url = wp_get_attachment_url( absint( $post['media_id'] ) );
			if ( $url ) {
				return $url;
			}
		}

		return ! empty( $post['media_url'] ) ? esc_url_raw( $post['media_url'] ) : '';
	}

	/**
	 * Posts to Meta.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $body Body.
	 * @param string $platform Platform.
	 * @return array|WP_Error
	 */
	private function post_to_meta( $endpoint, $body, $platform ) {
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 45,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssai_meta_request_failed', __( 'Meta publishing request failed.', 'sociaspark-ai-social-poster' ), array( 'status' => 500, 'transient' => true ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			SSAI_Logger::log( 'warning', 'ssai_meta_publish_error', 'Meta publish error', array( 'platform' => $platform, 'status' => $status, 'body' => $data ) );
			return new WP_Error(
				'ssai_meta_publish_error',
				__( 'Meta returned a publishing error. Check account permissions and token status.', 'sociaspark-ai-social-poster' ),
				array( 'status' => $status, 'transient' => in_array( absint( $status ), array( 408, 409, 429, 500, 502, 503, 504 ), true ) )
			);
		}

		return array(
			'id'       => sanitize_text_field( $data['id'] ?? $data['post_id'] ?? '' ),
			'response' => $data,
		);
	}
}
