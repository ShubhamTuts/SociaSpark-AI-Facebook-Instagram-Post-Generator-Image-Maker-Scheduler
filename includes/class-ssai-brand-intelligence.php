<?php
/**
 * Brand Intelligence.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds local brand profiles from approved sources.
 */
class SSAI_Brand_Intelligence {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service manages plugin-owned custom tables directly.
	/**
	 * Scans available source candidates without storing them.
	 *
	 * @param string $search Search term.
	 * @return array
	 */
	public static function scan_sources( $search = '' ) {
		$search = sanitize_text_field( $search );
		$items  = array();

		$items[] = array(
			'type'    => 'site',
			'id'      => 'site-settings',
			'title'   => get_bloginfo( 'name' ),
			'excerpt' => get_bloginfo( 'description' ),
		);

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
					'number'     => 25,
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$labels  = wp_list_pluck( $terms, 'name' );
			$items[] = array(
				'type'    => 'taxonomy',
				'id'      => $taxonomy->name,
				'title'   => $taxonomy->label,
				'excerpt' => implode( ', ', array_map( 'sanitize_text_field', $labels ) ),
			);
		}

		$post_types = array( 'post', 'page' );
		if ( post_type_exists( 'product' ) ) {
			$post_types[] = 'product';
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				's'              => $search,
				'posts_per_page' => 40,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( $posts as $post ) {
			$content = self::clean_content( $post->post_content );
			$items[] = array(
				'type'    => $post->post_type,
				'id'      => (string) $post->ID,
				'title'   => get_the_title( $post ),
				'excerpt' => wp_html_excerpt( $content, 280, '...' ),
			);
		}

		$media = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 20,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( $media as $attachment ) {
			$items[] = array(
				'type'    => 'media',
				'id'      => (string) $attachment->ID,
				'title'   => get_the_title( $attachment ),
				'excerpt' => trim( $attachment->post_excerpt . ' ' . $attachment->post_content ),
			);
		}

		return $items;
	}

	/**
	 * Adds a brand source.
	 *
	 * @param array $data Source data.
	 * @return array|WP_Error
	 */
	public static function add_source( $data ) {
		global $wpdb;

		$type = sanitize_key( $data['source_type'] ?? $data['type'] ?? 'manual' );
		$id   = sanitize_text_field( $data['source_id'] ?? $data['id'] ?? '' );

		$source = self::resolve_source( $type, $id, $data );
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$table = SSAI_Plugin::table( 'brand_sources' );
		$now   = current_time( 'mysql' );
		$hash  = hash( 'sha256', $source['source_type'] . '|' . $source['source_id'] . '|' . $source['excerpt'] );

		$wpdb->insert(
			$table,
			array(
				'source_type' => $source['source_type'],
				'source_id'   => $source['source_id'],
				'title'       => $source['title'],
				'excerpt'     => $source['excerpt'],
				'source_hash' => $hash,
				'meta'        => wp_json_encode( $source['meta'] ),
				'status'      => 'active',
				'created_by'  => get_current_user_id(),
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return self::get_source( (int) $wpdb->insert_id );
	}

	/**
	 * Adds an uploaded brand file.
	 *
	 * @param array  $file Uploaded file.
	 * @param string $title Optional title.
	 * @return array|WP_Error
	 */
	public static function add_uploaded_file( $file, $title = '' ) {
		$name = sanitize_file_name( $file['name'] ?? $title );
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'txt', 'md', 'csv', 'json' ), true ) ) {
			return new WP_Error( 'ssai_brand_file_type', __( 'Only TXT, MD, CSV, and JSON brand files are supported.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$size = absint( $file['size'] ?? 0 );
		if ( $size > 256 * KB_IN_BYTES ) {
			return new WP_Error( 'ssai_brand_file_large', __( 'Brand source file is too large.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a validated local upload temp file, not a remote URL.
		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content || '' === trim( $content ) ) {
			return new WP_Error( 'ssai_brand_source_empty', __( 'Brand source content cannot be empty.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		if ( 'json' === $ext ) {
			$decoded = json_decode( $content, true );
			if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
				return new WP_Error( 'ssai_brand_json_invalid', __( 'Uploaded JSON could not be parsed.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
			}
			$content = wp_json_encode( $decoded );
		}

		return self::add_source(
			array(
				'source_type' => 'upload',
				'title'       => '' !== $title ? $title : $name,
				'file_name'   => $name,
				'content'     => $content,
			)
		);
	}

	/**
	 * Returns sources.
	 *
	 * @param bool $active_only Active only.
	 * @return array
	 */
	public static function sources( $active_only = true ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'brand_sources' );
		if ( $active_only ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY id DESC', $table, 'active' ),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC', $table ), ARRAY_A );
		}

		foreach ( $rows as &$row ) {
			$row['meta'] = ! empty( $row['meta'] ) ? json_decode( $row['meta'], true ) : array();
		}

		return $rows;
	}

	/**
	 * Deletes a source.
	 *
	 * @param int $id Source ID.
	 * @return bool
	 */
	public static function delete_source( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'brand_sources' );
		$done  = $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
		return false !== $done;
	}

	/**
	 * Builds a local profile without external AI.
	 *
	 * @param array $sources Sources.
	 * @return array
	 */
	public static function build_local_profile( $sources ) {
		$text = '';
		foreach ( $sources as $source ) {
			$text .= ' ' . ( $source['title'] ?? '' ) . ' ' . ( $source['excerpt'] ?? '' );
		}

		$keywords = self::keywords( $text, 18 );

		return array(
			'voice'               => array(
				'primary' => SSAI_Settings::get( 'tone', 'clear, warm, expert' ),
				'notes'   => 'Local profile inferred from selected WordPress/admin sources.',
			),
			'audience_segments'   => array_filter( array_map( 'trim', explode( ',', SSAI_Settings::get( 'audience', '' ) ) ) ),
			'offers'              => array_slice( $keywords, 0, 6 ),
			'proof_points'        => array_slice( $keywords, 6, 5 ),
			'banned_phrases'      => array_filter( array_map( 'trim', explode( ',', SSAI_Settings::get( 'words_to_avoid', '' ) ) ) ),
			'approved_phrases'    => array_filter( array_map( 'trim', explode( ',', SSAI_Settings::get( 'brand_words', '' ) ) ) ),
			'cta_style'           => SSAI_Settings::get( 'default_cta', 'Direct, helpful, low-pressure' ),
			'hashtag_banks'       => array_map(
				static function ( $word ) {
					return '#' . preg_replace( '/[^A-Za-z0-9]/', '', ucwords( $word ) );
				},
				array_slice( $keywords, 0, 8 )
			),
			'visual_direction'    => array(
				'style' => 'premium light, clean composition, practical product context',
			),
			'content_pillars'     => array_slice( $keywords, 0, 6 ),
			'compliance_cautions' => array( 'Avoid fake claims, guarantees, and unsupported before/after promises.' ),
			'platform_rules'      => array(
				'facebook'  => 'Use useful context and a clear CTA.',
				'instagram' => 'Lead with a sharp hook, keep captions scannable, use focused hashtags.',
			),
		);
	}

	/**
	 * Saves profile.
	 *
	 * @param array  $profile Profile.
	 * @param array  $source_ids Source IDs.
	 * @param string $generated_by Generator.
	 * @return array
	 */
	public static function save_profile( $profile, $source_ids, $generated_by = 'local' ) {
		global $wpdb;

		$table   = SSAI_Plugin::table( 'brand_profiles' );
		$version = 1 + (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(version) FROM %i', $table ) );

		$wpdb->insert(
			$table,
			array(
				'version'      => $version,
				'profile_json' => wp_json_encode( $profile ),
				'source_ids'   => wp_json_encode( array_map( 'absint', $source_ids ) ),
				'generated_by' => sanitize_key( $generated_by ),
				'created_by'   => get_current_user_id(),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return self::latest_profile();
	}

	/**
	 * Returns latest profile.
	 *
	 * @return array
	 */
	public static function latest_profile() {
		global $wpdb;

		$table = SSAI_Plugin::table( 'brand_profiles' );
		$row   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT 1', $table ), ARRAY_A );
		if ( ! $row ) {
			return array();
		}

		$row['profile']    = json_decode( $row['profile_json'], true );
		$row['source_ids'] = ! empty( $row['source_ids'] ) ? json_decode( $row['source_ids'], true ) : array();

		return $row;
	}

	/**
	 * Gets source by ID.
	 *
	 * @param int $id Source ID.
	 * @return array
	 */
	private static function get_source( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'brand_sources' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, absint( $id ) ),
			ARRAY_A
		);

		if ( ! $row ) {
			return array();
		}

		$row['meta'] = ! empty( $row['meta'] ) ? json_decode( $row['meta'], true ) : array();
		return $row;
	}

	/**
	 * Resolves source content.
	 *
	 * @param string $type Type.
	 * @param string $id Source ID.
	 * @param array  $data Data.
	 * @return array|WP_Error
	 */
	private static function resolve_source( $type, $id, $data ) {
		if ( in_array( $type, array( 'post', 'page', 'product' ), true ) ) {
			$post = get_post( absint( $id ) );
			if ( ! $post || $post->post_type !== $type ) {
				return new WP_Error( 'ssai_brand_source_missing', __( 'Selected brand source was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
			}

			return array(
				'source_type' => $type,
				'source_id'   => (string) $post->ID,
				'title'       => get_the_title( $post ),
				'excerpt'     => wp_html_excerpt( self::clean_content( $post->post_content ), 6000, '...' ),
				'meta'        => array(
					'post_status' => $post->post_status,
					'modified'    => $post->post_modified_gmt,
				),
			);
		}

		if ( 'site' === $type ) {
			return array(
				'source_type' => 'site',
				'source_id'   => 'site-settings',
				'title'       => get_bloginfo( 'name' ),
				'excerpt'     => trim( get_bloginfo( 'name' ) . "\n" . get_bloginfo( 'description' ) ),
				'meta'        => array(),
			);
		}

		if ( 'taxonomy' === $type ) {
			$taxonomy = sanitize_key( $id );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return new WP_Error( 'ssai_brand_taxonomy_missing', __( 'Selected taxonomy was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'number'     => 100,
				)
			);
			if ( is_wp_error( $terms ) ) {
				return $terms;
			}

			return array(
				'source_type' => 'taxonomy',
				'source_id'   => $taxonomy,
				'title'       => $taxonomy,
				'excerpt'     => implode( ', ', wp_list_pluck( $terms, 'name' ) ),
				'meta'        => array(),
			);
		}

		if ( 'media' === $type ) {
			$attachment = get_post( absint( $id ) );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error( 'ssai_brand_media_missing', __( 'Selected media item was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
			}

			return array(
				'source_type' => 'media',
				'source_id'   => (string) $attachment->ID,
				'title'       => get_the_title( $attachment ),
				'excerpt'     => wp_html_excerpt( trim( $attachment->post_excerpt . "\n" . $attachment->post_content ), 4000, '...' ),
				'meta'        => array( 'mime' => $attachment->post_mime_type ),
			);
		}

		$title   = sanitize_text_field( $data['title'] ?? __( 'Manual brand source', 'sociaspark-ai-social-poster' ) );
		$content = sanitize_textarea_field( $data['content'] ?? $data['excerpt'] ?? '' );

		if ( 'upload' === $type ) {
			$filename = sanitize_file_name( $data['file_name'] ?? $title );
			$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, array( 'txt', 'md', 'csv', 'json' ), true ) ) {
				return new WP_Error( 'ssai_brand_file_type', __( 'Only TXT, MD, CSV, and JSON brand files are supported.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
			}
			if ( strlen( $content ) > 256 * KB_IN_BYTES ) {
				return new WP_Error( 'ssai_brand_file_large', __( 'Brand source file is too large.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
			}
			$title = $filename;
		}

		if ( '' === trim( $content ) ) {
			return new WP_Error( 'ssai_brand_source_empty', __( 'Brand source content cannot be empty.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		return array(
			'source_type' => in_array( $type, array( 'manual', 'upload' ), true ) ? $type : 'manual',
			'source_id'   => '',
			'title'       => $title,
			'excerpt'     => wp_html_excerpt( $content, 6000, '...' ),
			'meta'        => array(),
		);
	}

	/**
	 * Cleans post content.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	private static function clean_content( $content ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $content ) ) ) );
	}

	/**
	 * Extracts simple keywords.
	 *
	 * @param string $text Text.
	 * @param int    $limit Limit.
	 * @return array
	 */
	private static function keywords( $text, $limit = 12 ) {
		$text  = strtolower( wp_strip_all_tags( $text ) );
		$words = preg_split( '/[^a-z0-9]+/', $text );
		$stop  = array_flip( array( 'the', 'and', 'for', 'with', 'that', 'this', 'from', 'your', 'you', 'are', 'our', 'was', 'have', 'has', 'not', 'but', 'can', 'all', 'into', 'about', 'more', 'will', 'their', 'they', 'what', 'when', 'where', 'how', 'why', 'a', 'an', 'to', 'of', 'in', 'on', 'by', 'is', 'it', 'as', 'at', 'be', 'or' ) );
		$count = array();

		foreach ( $words as $word ) {
			if ( strlen( $word ) < 4 || isset( $stop[ $word ] ) ) {
				continue;
			}
			$count[ $word ] = ( $count[ $word ] ?? 0 ) + 1;
		}

		arsort( $count );
		return array_slice( array_keys( $count ), 0, $limit );
	}
}
