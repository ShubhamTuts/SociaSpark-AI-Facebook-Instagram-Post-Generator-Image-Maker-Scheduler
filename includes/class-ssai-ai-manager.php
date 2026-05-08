<?php
/**
 * AI manager and prompt builder.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates AI providers and prompt templates.
 */
class SSAI_AI_Manager {
	/**
	 * Returns provider instance.
	 *
	 * @param string $provider Provider key.
	 * @return SSAI_AI_Provider_Interface|WP_Error
	 */
	public function provider( $provider = '' ) {
		$provider = $provider ? sanitize_key( $provider ) : SSAI_Settings::get( 'default_text_provider', SSAI_Settings::get( 'default_provider', 'openai' ) );

		$providers = apply_filters(
			'ssai_pro_register_ai_providers',
			array(
				'openai' => 'SSAI_OpenAI_Provider',
				'gemini' => 'SSAI_Gemini_Provider',
				'claude' => 'SSAI_Claude_Provider',
			)
		);

		if ( empty( $providers[ $provider ] ) || ! class_exists( $providers[ $provider ] ) ) {
			return new WP_Error( 'ssai_provider_unknown', __( 'Unknown AI provider.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		return new $providers[ $provider ]();
	}

	/**
	 * Returns selectable model presets.
	 *
	 * @return array
	 */
	public static function model_catalog() {
		return apply_filters(
			'ssai_ai_model_catalog',
			array(
				'defaults' => array(
					'text_provider'  => SSAI_Settings::get( 'default_text_provider', 'openai' ),
					'text_model'     => SSAI_Settings::get( 'default_text_model', 'gpt-5.4-mini' ),
					'image_provider' => SSAI_Settings::get( 'default_image_provider', 'openai' ),
					'image_model'    => SSAI_Settings::get( 'default_image_model', 'gpt-image-2' ),
				),
				'text'     => array(
					'openai' => array(
						array(
							'id'    => 'gpt-5.5',
							'label' => 'GPT-5.5 - best quality',
						),
						array(
							'id'    => 'gpt-5.4-mini',
							'label' => 'GPT-5.4 mini - balanced',
						),
						array(
							'id'    => 'gpt-5.4-nano',
							'label' => 'GPT-5.4 nano - low cost',
						),
					),
					'gemini' => array(
						array(
							'id'    => 'gemini-2.5-flash',
							'label' => 'Gemini 2.5 Flash',
						),
						array(
							'id'    => 'gemini-2.5-pro',
							'label' => 'Gemini 2.5 Pro',
						),
					),
					'claude' => array(
						array(
							'id'    => 'claude-sonnet-4-6',
							'label' => 'Claude Sonnet 4.6',
						),
						array(
							'id'    => 'claude-opus-4-7',
							'label' => 'Claude Opus 4.7',
						),
						array(
							'id'    => 'claude-haiku-4-5',
							'label' => 'Claude Haiku 4.5',
						),
					),
				),
				'image'    => array(
					'openai' => array(
						array(
							'id'    => 'gpt-image-2',
							'label' => 'GPT Image 2 - best quality',
						),
						array(
							'id'    => 'gpt-image-1.5',
							'label' => 'GPT Image 1.5 - compatibility',
						),
					),
				),
			)
		);
	}

	/**
	 * Tests a provider/model combination.
	 *
	 * @param array $data Request data.
	 * @return array|WP_Error
	 */
	public function test_provider( $data ) {
		$mode     = sanitize_key( $data['mode'] ?? 'text' );
		$provider = sanitize_key( $data['provider'] ?? ( 'image' === $mode ? 'openai' : SSAI_Settings::get( 'default_text_provider', 'openai' ) ) );
		$model    = sanitize_text_field( $data['model'] ?? '' );

		if ( 'image' === $mode ) {
			$provider = 'openai';
			if ( '' === $model ) {
				$model = SSAI_Settings::get( 'default_image_model', SSAI_Settings::get( 'openai_image_model', 'gpt-image-2' ) );
			}
		}

		$instance = $this->provider( $provider );
		if ( is_wp_error( $instance ) ) {
			return $instance;
		}
		if ( ! $instance->is_configured() ) {
			return new WP_Error( 'ssai_provider_missing_key', __( 'Configure this provider before testing it.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		if ( 'image' === $mode && method_exists( $instance, 'test_model' ) ) {
			$result = $instance->test_model( $model, 'image' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return array(
				'ok'       => true,
				'provider' => $provider,
				'model'    => $model,
				'message'  => __( 'Image model is reachable for this OpenAI key.', 'sociaspark-ai-social-poster' ),
			);
		}

		$result = $instance->generate_text(
			'Return exactly this JSON: {"ok":true,"message":"provider ready"}',
			array(
				'model'      => '' !== $model ? $model : $this->default_text_model( $provider ),
				'system'     => 'You are checking API connectivity. Return compact valid JSON only.',
				'json'       => true,
				'max_tokens' => 80,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'ok'       => true,
			'provider' => $provider,
			'model'    => '' !== $model ? $model : $this->default_text_model( $provider ),
			'message'  => __( 'Text provider responded successfully.', 'sociaspark-ai-social-poster' ),
		);
	}

	/**
	 * Generates platform captions.
	 *
	 * @param array $data Input data.
	 * @return array|WP_Error
	 */
	public function generate_caption( $data ) {
		$rate = $this->check_rate_limit( 'text' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$provider_key = sanitize_key( $data['provider'] ?? SSAI_Settings::get( 'default_text_provider', 'openai' ) );
		$provider     = $this->provider( $provider_key );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$system = $this->system_prompt();
		$prompt = $this->brand_context() . "\n" . $this->caption_prompt( $data );
		$text   = $provider->generate_text(
			$prompt,
			array(
				'system' => $system,
				'json'   => true,
				'model'  => $this->text_model_from_request( $provider_key, $data ),
				'schema' => $this->caption_schema(),
			)
		);

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		return $this->decode_json_or_wrap( $text, 'options' );
	}

	/**
	 * Generates a short-form video script.
	 *
	 * @param array $data Input data.
	 * @return array|WP_Error
	 */
	public function generate_video_script( $data ) {
		$rate = $this->check_rate_limit( 'text' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$provider_key = sanitize_key( $data['provider'] ?? SSAI_Settings::get( 'default_text_provider', 'openai' ) );
		$provider     = $this->provider( $provider_key );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$prompt = $this->brand_context() . "\n" . $this->video_script_prompt( $data );
		$text   = $provider->generate_text(
			$prompt,
			array(
				'system'     => $this->system_prompt(),
				'json'       => true,
				'model'      => $this->text_model_from_request( $provider_key, $data ),
				'max_tokens' => 5000,
				'schema'     => $this->video_schema(),
			)
		);

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		return $this->decode_json_or_wrap( $text, 'script' );
	}

	/**
	 * Generates and saves an image.
	 *
	 * @param array $data Input data.
	 * @return array|WP_Error
	 */
	public function generate_image( $data ) {
		$rate = $this->check_rate_limit( 'image' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$provider = $this->provider( 'openai' );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$prompt = $this->image_prompt( $data );
		$image  = $provider->generate_image(
			$prompt,
			array(
				'model'   => sanitize_text_field( $data['image_model'] ?? SSAI_Settings::get( 'default_image_model', SSAI_Settings::get( 'openai_image_model', 'gpt-image-2' ) ) ),
				'size'    => $this->normalize_image_size( $data['format'] ?? '' ),
				'quality' => SSAI_Settings::get( 'openai_image_quality', 'auto' ),
			)
		);

		if ( is_wp_error( $image ) ) {
			return $image;
		}

		$title = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'SociaSpark AI image', 'sociaspark-ai-social-poster' );
		return SSAI_Media::save_generated_image( $image, $title );
	}

	/**
	 * Repurposes a WordPress post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data Extra data.
	 * @return array|WP_Error
	 */
	public function repurpose_wp_post( $post_id, $data = array() ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || ! in_array( $post->post_type, $this->allowed_source_post_types(), true ) ) {
			return new WP_Error( 'ssai_post_not_found', __( 'Selected WordPress content was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
		}

		$rate = $this->check_rate_limit( 'text' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$provider_key = sanitize_key( $data['provider'] ?? SSAI_Settings::get( 'default_text_provider', 'openai' ) );
		$provider     = $this->provider( $provider_key );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$content = wp_html_excerpt( $content, 8000, '...' );

		$prompt = $this->brand_context() . "\n" . sprintf(
			"Repurpose this WordPress %s into social media content.\nTitle: %s\nExcerpt: %s\nContent summary source: %s\n\nReturn valid JSON with: facebook_caption, instagram_caption, hooks, cta_options, image_prompt, video_script_idea.",
			sanitize_key( $post->post_type ),
			get_the_title( $post ),
			wp_strip_all_tags( get_the_excerpt( $post ) ),
			$content
		);

		$text = $provider->generate_text(
			$prompt,
			array(
				'system' => $this->system_prompt(),
				'json'   => true,
				'model'  => $this->text_model_from_request( $provider_key, $data ),
				'schema' => $this->repurpose_schema(),
			)
		);

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		return $this->decode_json_or_wrap( $text, 'repurposed' );
	}

	/**
	 * Analyzes brand profile with AI provider.
	 *
	 * @param array  $sources Sources.
	 * @param string $provider_key Provider key.
	 * @return array|WP_Error
	 */
	public function analyze_brand( $sources, $provider_key = '' ) {
		$rate = $this->check_rate_limit( 'text' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		if ( '' === $provider_key ) {
			$provider_key = SSAI_Settings::get( 'default_text_provider', 'openai' );
		}
		$provider = $this->provider( $provider_key );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		if ( ! $provider->is_configured() ) {
			return new WP_Error( 'ssai_provider_missing_key', __( 'Configure the selected AI provider before AI brand analysis.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$chunks = array();
		foreach ( $sources as $source ) {
			$chunks[] = sprintf(
				"Source: %s\nTitle: %s\nExcerpt:\n%s",
				sanitize_key( $source['source_type'] ?? 'manual' ),
				sanitize_text_field( $source['title'] ?? '' ),
				wp_html_excerpt( wp_strip_all_tags( $source['excerpt'] ?? '' ), 2500, '...' )
			);
		}

		$prompt = "Create a local Brand Intelligence Profile from these approved WordPress/admin sources. This is not model fine-tuning.\n\n" .
			implode( "\n\n---\n\n", $chunks ) .
			"\n\nReturn valid JSON with: voice, audience_segments, offers, proof_points, banned_phrases, approved_phrases, cta_style, hashtag_banks, visual_direction, content_pillars, compliance_cautions, platform_rules.";

		$text = $provider->generate_text(
			$prompt,
			array(
				'system'     => $this->system_prompt(),
				'json'       => true,
				'max_tokens' => 6000,
				'model'      => $this->default_text_model( $provider_key ),
				'schema'     => $this->brand_schema(),
			)
		);

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		return $this->decode_json_or_wrap( $text, 'brand_profile' );
	}

	/**
	 * Returns system prompt.
	 *
	 * @return string
	 */
	public function system_prompt() {
		return 'You are a senior social media strategist. Write platform-native content that sounds human, sharp, useful, and conversion-focused. Avoid generic AI wording. Avoid overused phrases like "unlock", "game-changer", "in today\'s digital world", and "elevate" unless the user specifically asks. Keep tone natural. No fake claims. No medical, legal, or financial guarantees.';
	}

	/**
	 * Caption prompt.
	 *
	 * @param array $data Data.
	 * @return string
	 */
	private function caption_prompt( $data ) {
		return sprintf(
			"Create 3 distinct social media post options for:\nIdea: %s\nAudience: %s\nPain point: %s\nDesired result: %s\nTone: %s\nCTA: %s\nPlatform: %s\nContent type: %s\nBusiness/site context: %s, %s\nRules:\n- Make it sound like a real founder or marketer wrote it.\n- Use a strong first line hook.\n- Include 3 to 7 relevant hashtags only if suitable.\n- No fake claims.\n- Return valid JSON as an object with an options array. Each option must include: title, hook, caption, facebook_caption, instagram_caption, hashtags, cta, angle, platform_notes, image_prompt.",
			sanitize_textarea_field( $data['idea'] ?? '' ),
			sanitize_text_field( $data['audience'] ?? SSAI_Settings::get( 'audience', '' ) ),
			sanitize_text_field( $data['pain_point'] ?? '' ),
			sanitize_text_field( $data['desired_result'] ?? '' ),
			sanitize_text_field( $data['tone'] ?? SSAI_Settings::get( 'tone', '' ) ),
			sanitize_text_field( $data['cta'] ?? SSAI_Settings::get( 'default_cta', '' ) ),
			sanitize_text_field( $data['platform'] ?? 'facebook_instagram' ),
			sanitize_text_field( $data['content_type'] ?? 'standard post' ),
			get_bloginfo( 'name' ),
			get_bloginfo( 'description' )
		);
	}

	/**
	 * Video script prompt.
	 *
	 * @param array $data Data.
	 * @return string
	 */
	private function video_script_prompt( $data ) {
		return sprintf(
			"Create a short-form video script for Instagram Reels/Facebook Reels:\nIdea: %s\nAudience: %s\nTone: %s\nDuration: 30 seconds\nOutput JSON with hook, scene_by_scene_script, on_screen_text, voiceover, b_roll_ideas, caption, hashtags, shot_list, editing_notes.\nRules: Human, direct, premium, no generic AI wording.",
			sanitize_textarea_field( $data['idea'] ?? '' ),
			sanitize_text_field( $data['audience'] ?? SSAI_Settings::get( 'audience', '' ) ),
			sanitize_text_field( $data['tone'] ?? SSAI_Settings::get( 'tone', '' ) )
		);
	}

	/**
	 * Image prompt.
	 *
	 * @param array $data Data.
	 * @return string
	 */
	private function image_prompt( $data ) {
		return sprintf(
			"Generate a premium social media creative image.\nIdea: %s\nBrand mood: %s\nFormat: %s\nRules: No text inside the image unless explicitly requested. Premium lighting. Clean composition. Commercial quality. Avoid copyrighted characters, logos, and trademarked brands.",
			sanitize_textarea_field( $data['idea'] ?? '' ),
			sanitize_text_field( $data['brand_mood'] ?? SSAI_Settings::get( 'tone', 'premium, clear, calm' ) ),
			sanitize_text_field( $data['format'] ?? 'square' )
		);
	}

	/**
	 * Adds latest brand context.
	 *
	 * @return string
	 */
	private function brand_context() {
		$profile = SSAI_Brand_Intelligence::latest_profile();
		if ( empty( $profile['profile_json'] ) ) {
			return '';
		}

		$data = json_decode( $profile['profile_json'], true );
		if ( ! is_array( $data ) ) {
			return '';
		}

		return "Approved Brand Intelligence Profile:\n" . wp_json_encode( $data );
	}

	/**
	 * Decodes JSON provider output or wraps raw text.
	 *
	 * @param string $text Text.
	 * @param string $fallback_key Fallback key.
	 * @return array
	 */
	private function decode_json_or_wrap( $text, $fallback_key ) {
		$clean = trim( (string) $text );
		$clean = preg_replace( '/^```(?:json)?\s*/i', '', $clean );
		$clean = preg_replace( '/\s*```$/', '', $clean );

		$decoded = json_decode( $clean, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$first_object = strpos( $clean, '{' );
		$last_object  = strrpos( $clean, '}' );
		if ( false !== $first_object && false !== $last_object && $last_object > $first_object ) {
			$decoded = json_decode( substr( $clean, $first_object, $last_object - $first_object + 1 ), true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$first_array = strpos( $clean, '[' );
		$last_array  = strrpos( $clean, ']' );
		if ( false !== $first_array && false !== $last_array && $last_array > $first_array ) {
			$decoded = json_decode( substr( $clean, $first_array, $last_array - $first_array + 1 ), true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array( $fallback_key => $clean );
	}

	/**
	 * Checks per-admin AI rate limits.
	 *
	 * @param string $type text|image.
	 * @return true|WP_Error
	 */
	private function check_rate_limit( $type ) {
		$user_id = get_current_user_id();
		$limit   = 'image' === $type ? 10 : 30;
		$key     = 'ssai_rate_' . $type . '_' . absint( $user_id );
		$count   = absint( get_transient( $key ) );

		if ( $count >= $limit ) {
			return new WP_Error(
				'ssai_rate_limited',
				'Apply the brakes for a bit: this admin has reached the hourly AI generation limit.',
				array( 'status' => 429 )
			);
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Normalizes image format to provider size.
	 *
	 * @param string $format Format.
	 * @return string
	 */
	private function normalize_image_size( $format ) {
		if ( 'portrait' === $format ) {
			return '1024x1536';
		}
		if ( 'story' === $format ) {
			return '1024x1536';
		}
		if ( empty( $format ) ) {
			return SSAI_Settings::get( 'openai_image_size', '1024x1024' );
		}
		return '1024x1024';
	}

	/**
	 * Gets a text model from request/settings.
	 *
	 * @param string $provider Provider.
	 * @param array  $data Request data.
	 * @return string
	 */
	private function text_model_from_request( $provider, $data ) {
		$model = sanitize_text_field( $data['text_model'] ?? $data['model'] ?? '' );
		return '' !== $model ? $model : $this->default_text_model( $provider );
	}

	/**
	 * Gets default text model for a provider.
	 *
	 * @param string $provider Provider.
	 * @return string
	 */
	private function default_text_model( $provider ) {
		if ( 'gemini' === $provider ) {
			return SSAI_Settings::get( 'gemini_model', 'gemini-2.5-flash' );
		}
		if ( 'claude' === $provider ) {
			return SSAI_Settings::get( 'claude_model', 'claude-sonnet-4-6' );
		}
		return SSAI_Settings::get( 'default_text_model', SSAI_Settings::get( 'openai_text_model', 'gpt-5.4-mini' ) );
	}

	/**
	 * Caption JSON schema.
	 *
	 * @return array
	 */
	private function caption_schema() {
		return array(
			'name'   => 'ssai_caption_options',
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'options' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'title'             => array( 'type' => 'string' ),
								'hook'              => array( 'type' => 'string' ),
								'caption'           => array( 'type' => 'string' ),
								'facebook_caption'  => array( 'type' => 'string' ),
								'instagram_caption' => array( 'type' => 'string' ),
								'hashtags'          => array(
									'type'  => 'array',
									'items' => array( 'type' => 'string' ),
								),
								'cta'               => array( 'type' => 'string' ),
								'angle'             => array( 'type' => 'string' ),
								'platform_notes'    => array( 'type' => 'string' ),
								'image_prompt'      => array( 'type' => 'string' ),
							),
						),
					),
				),
				'required'   => array( 'options' ),
			),
		);
	}

	/**
	 * Video script schema.
	 *
	 * @return array
	 */
	private function video_schema() {
		return array(
			'name'   => 'ssai_video_script',
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'hook'                  => array( 'type' => 'string' ),
					'scene_by_scene_script' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'on_screen_text'        => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'voiceover'             => array( 'type' => 'string' ),
					'b_roll_ideas'          => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'caption'               => array( 'type' => 'string' ),
					'hashtags'              => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'shot_list'             => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'editing_notes'         => array( 'type' => 'string' ),
				),
				'required'   => array( 'hook', 'voiceover', 'caption' ),
			),
		);
	}

	/**
	 * Repurposing schema.
	 *
	 * @return array
	 */
	private function repurpose_schema() {
		return array(
			'name'   => 'ssai_repurposed_content',
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'facebook_caption'  => array( 'type' => 'string' ),
					'instagram_caption' => array( 'type' => 'string' ),
					'hooks'             => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'cta_options'       => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'image_prompt'      => array( 'type' => 'string' ),
					'video_script_idea' => array( 'type' => 'string' ),
				),
				'required'   => array( 'facebook_caption', 'instagram_caption' ),
			),
		);
	}

	/**
	 * Brand profile schema.
	 *
	 * @return array
	 */
	private function brand_schema() {
		return array(
			'name'   => 'ssai_brand_profile',
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'voice'               => array( 'type' => 'object' ),
					'audience_segments'   => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'offers'              => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'proof_points'        => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'banned_phrases'      => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'approved_phrases'    => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'cta_style'           => array( 'type' => 'string' ),
					'hashtag_banks'       => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'visual_direction'    => array( 'type' => 'object' ),
					'content_pillars'     => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'compliance_cautions' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'platform_rules'      => array( 'type' => 'object' ),
				),
			),
		);
	}

	/**
	 * Allowed source post types for repurposing.
	 *
	 * @return array
	 */
	private function allowed_source_post_types() {
		$types = array( 'post', 'page' );
		if ( post_type_exists( 'product' ) ) {
			$types[] = 'product';
		}
		return $types;
	}
}
