<?php
/**
 * Media Library helpers.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves generated media into WordPress.
 */
class SSAI_Media {
	// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Base64 decoding is required to handle provider image payloads and admin canvas data URLs.
	/**
	 * Allowed image mimes.
	 *
	 * @return array
	 */
	public static function allowed_mimes() {
		return array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/webp' => 'webp',
		);
	}

	/**
	 * Returns extension-to-mime map for WordPress file checks.
	 *
	 * @return array
	 */
	private static function wp_mimes() {
		return array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		);
	}

	/**
	 * Saves generated image payload.
	 *
	 * @param array  $image Image payload.
	 * @param string $title Attachment title.
	 * @return array|WP_Error
	 */
	public static function save_generated_image( $image, $title = 'SociaSpark generated image' ) {
		if ( empty( $image['type'] ) ) {
			return new WP_Error( 'ssai_media_invalid_payload', __( 'Invalid generated image payload.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		if ( 'base64' === $image['type'] ) {
			$binary = base64_decode( (string) $image['data'], true );
			if ( false === $binary ) {
				return new WP_Error( 'ssai_media_bad_base64', __( 'Generated image data was invalid.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
			}

			$mime = ! empty( $image['mime'] ) ? sanitize_mime_type( $image['mime'] ) : 'image/png';
			return self::save_binary_image( $binary, $mime, $title );
		}

		if ( 'url' === $image['type'] ) {
			return self::save_provider_image_url( esc_url_raw( $image['url'] ), $title );
		}

		return new WP_Error( 'ssai_media_unknown_payload', __( 'Unsupported generated image payload.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
	}

	/**
	 * Saves an image from a base64 string supplied by the admin app.
	 *
	 * @param string $data_url Data URL or raw base64.
	 * @param string $title Title.
	 * @return array|WP_Error
	 */
	public static function save_data_url( $data_url, $title = 'SociaSpark canvas image' ) {
		$mime = 'image/png';
		$data = (string) $data_url;

		if ( preg_match( '/^data:(image\/(?:png|jpeg|webp));base64,(.+)$/', $data, $matches ) ) {
			$mime = sanitize_mime_type( $matches[1] );
			$data = $matches[2];
		}

		$binary = base64_decode( $data, true );
		if ( false === $binary ) {
			return new WP_Error( 'ssai_media_bad_data_url', __( 'Image data was invalid.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		return self::save_binary_image( $binary, $mime, $title );
	}

	/**
	 * Saves image bytes to Media Library.
	 *
	 * @param string $binary Image bytes.
	 * @param string $mime Mime type.
	 * @param string $title Title.
	 * @return array|WP_Error
	 */
	private static function save_binary_image( $binary, $mime, $title ) {
		$allowed = self::allowed_mimes();
		if ( ! isset( $allowed[ $mime ] ) ) {
			return new WP_Error( 'ssai_media_bad_mime', __( 'Only JPG, PNG, and WebP images are allowed.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		if ( strlen( $binary ) > 10 * MB_IN_BYTES ) {
			return new WP_Error( 'ssai_media_too_large', __( 'Image is too large to save.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$extension = $allowed[ $mime ];
		$filename  = sanitize_file_name( sanitize_title( $title ) . '-' . gmdate( 'Ymd-His' ) . '.' . $extension );
		$upload    = wp_upload_bits( $filename, null, $binary );

		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'ssai_media_upload_failed', __( 'Could not save image to the Media Library.', 'sociaspark-ai-social-poster' ), array( 'status' => 500 ) );
		}

		$filetype = wp_check_filetype_and_ext( $upload['file'], $filename, self::wp_mimes() );
		if ( empty( $filetype['type'] ) || ! isset( $allowed[ $filetype['type'] ] ) ) {
			wp_delete_file( $upload['file'] );
			return new WP_Error( 'ssai_media_invalid_filetype', __( 'Generated file type is not allowed.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_text_field( $title ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $upload['file'] );
			return $attachment_id;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return array(
			'id'   => $attachment_id,
			'url'  => wp_get_attachment_url( $attachment_id ),
			'mime' => $filetype['type'],
		);
	}

	/**
	 * Saves provider-generated URL while avoiding arbitrary server-side fetches.
	 *
	 * @param string $url URL.
	 * @param string $title Title.
	 * @return array|WP_Error
	 */
	private static function save_provider_image_url( $url, $title ) {
		$parts = wp_parse_url( $url );
		$host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';

		$allowed_hosts = apply_filters(
			'ssai_allowed_provider_image_hosts',
			array(
				'api.openai.com',
				'oaidalleapiprodscus.blob.core.windows.net',
				'cdn.openai.com',
			)
		);

		if ( 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( $host, $allowed_hosts, true ) ) {
			return new WP_Error( 'ssai_media_url_not_allowed', __( 'Generated image URL host is not allowed.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => 30,
				'limit_response_size' => 10 * MB_IN_BYTES,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'ssai_media_download_failed', __( 'Could not download generated image.', 'sociaspark-ai-social-poster' ), array( 'status' => 500 ) );
		}

		$mime = wp_remote_retrieve_header( $response, 'content-type' );
		if ( is_array( $mime ) ) {
			$mime = reset( $mime );
		}
		$mime = sanitize_mime_type( strtok( (string) $mime, ';' ) );

		return self::save_binary_image( wp_remote_retrieve_body( $response ), $mime, $title );
	}
}
