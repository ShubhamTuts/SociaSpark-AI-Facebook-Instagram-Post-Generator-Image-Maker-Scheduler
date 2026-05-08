<?php
/**
 * Instagram publishing.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publishes Instagram Business/Creator image posts.
 */
class SSAI_Instagram_Publisher {
	/**
	 * Publishes an image post.
	 *
	 * @param array $connection Connection.
	 * @param array $post Post row.
	 * @return array|WP_Error
	 */
	public function publish( $connection, $post ) {
		$account_id = sanitize_text_field( $connection['account_id'] );
		$caption    = ! empty( $post['content_instagram'] ) ? $post['content_instagram'] : $post['content_long'];
		$image_url  = $this->media_url( $post );

		if ( ! $image_url || 'https' !== wp_parse_url( $image_url, PHP_URL_SCHEME ) ) {
			return new WP_Error( 'ssai_instagram_requires_https_image', __( 'Instagram publishing requires a public HTTPS image URL.', 'sociaspark-ai-social-poster' ), array( 'status' => 400, 'transient' => false ) );
		}

		$version = sanitize_text_field( SSAI_Settings::get( 'meta_graph_version', 'v24.0' ) );
		$base    = 'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $account_id );

		$container = $this->post_to_meta(
			$base . '/media',
			array(
				'image_url'    => esc_url_raw( $image_url ),
				'caption'      => wp_strip_all_tags( (string) $caption ),
				'access_token' => $connection['access_token'],
			),
			'instagram_create'
		);

		if ( is_wp_error( $container ) ) {
			return $container;
		}

		$creation_id = $container['id'] ?? '';
		if ( '' === $creation_id ) {
			return new WP_Error( 'ssai_instagram_no_container', __( 'Instagram did not return a media container.', 'sociaspark-ai-social-poster' ), array( 'status' => 502, 'transient' => true ) );
		}

		return $this->post_to_meta(
			$base . '/media_publish',
			array(
				'creation_id'  => $creation_id,
				'access_token' => $connection['access_token'],
			),
			'instagram_publish'
		);
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
	 * Posts to Meta endpoint.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $body Body.
	 * @param string $event Event.
	 * @return array|WP_Error
	 */
	private function post_to_meta( $endpoint, $body, $event ) {
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
			SSAI_Logger::log( 'warning', 'ssai_' . $event . '_error', 'Meta publish error', array( 'status' => $status, 'body' => $data ) );
			return new WP_Error(
				'ssai_meta_publish_error',
				__( 'Meta returned a publishing error. Check account permissions and token status.', 'sociaspark-ai-social-poster' ),
				array( 'status' => $status, 'transient' => in_array( absint( $status ), array( 408, 409, 429, 500, 502, 503, 504 ), true ) )
			);
		}

		return array(
			'id'       => sanitize_text_field( $data['id'] ?? '' ),
			'response' => $data,
		);
	}
}
