<?php
/**
 * REST API controller.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles SociaSpark REST routes.
 */
class SSAI_REST_Controller {
	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$this->route( '/settings', WP_REST_Server::READABLE, 'get_settings' );
		$this->route( '/settings', WP_REST_Server::CREATABLE, 'save_settings' );
		$this->route( '/dashboard', WP_REST_Server::READABLE, 'dashboard' );
		$this->route( '/connections', WP_REST_Server::READABLE, 'connections' );
		$this->route( '/connections/meta/save', WP_REST_Server::CREATABLE, 'save_connection' );
		$this->route( '/connections/meta/test', WP_REST_Server::CREATABLE, 'test_connection' );
		$this->route( '/connections/(?P<id>[\d]+)', WP_REST_Server::DELETABLE, 'delete_connection' );

		$this->route( '/ideas', WP_REST_Server::READABLE, 'ideas' );
		$this->route( '/ideas', WP_REST_Server::CREATABLE, 'create_idea' );
		$this->route( '/ideas/(?P<id>[\d]+)', WP_REST_Server::EDITABLE, 'update_idea' );
		$this->route( '/ideas/(?P<id>[\d]+)', WP_REST_Server::DELETABLE, 'delete_idea' );
		$this->route( '/ideas/(?P<id>[\d]+)/create-post', WP_REST_Server::CREATABLE, 'idea_create_post' );

		$this->route( '/posts', WP_REST_Server::READABLE, 'posts' );
		$this->route( '/posts', WP_REST_Server::CREATABLE, 'create_post' );
		$this->route( '/posts/(?P<id>[\d]+)', WP_REST_Server::READABLE, 'get_post' );
		$this->route( '/posts/(?P<id>[\d]+)', WP_REST_Server::EDITABLE, 'update_post' );
		$this->route( '/posts/(?P<id>[\d]+)', WP_REST_Server::DELETABLE, 'delete_post' );

		$this->route( '/ai/generate-caption', WP_REST_Server::CREATABLE, 'generate_caption' );
		$this->route( '/ai/generate-variations', WP_REST_Server::CREATABLE, 'generate_caption' );
		$this->route( '/ai/generate-image', WP_REST_Server::CREATABLE, 'generate_image' );
		$this->route( '/ai/generate-video-script', WP_REST_Server::CREATABLE, 'generate_video_script' );
		$this->route( '/ai/repurpose-wp-post', WP_REST_Server::CREATABLE, 'repurpose_wp_post' );
		$this->route( '/ai/models', WP_REST_Server::READABLE, 'ai_models' );
		$this->route( '/ai/test-provider', WP_REST_Server::CREATABLE, 'test_provider' );

		$this->route( '/wp-content/search', WP_REST_Server::READABLE, 'wp_content_search' );
		$this->route( '/media/save-generated', WP_REST_Server::CREATABLE, 'save_generated_media' );
		$this->route( '/calendar', WP_REST_Server::READABLE, 'calendar' );
		$this->route( '/schedule', WP_REST_Server::CREATABLE, 'schedule' );
		$this->route( '/publish-now', WP_REST_Server::CREATABLE, 'publish_now' );
		$this->route( '/jobs', WP_REST_Server::READABLE, 'jobs' );
		$this->route( '/jobs/retry', WP_REST_Server::CREATABLE, 'retry_job' );
		$this->route( '/logs', WP_REST_Server::READABLE, 'logs' );

		$this->route( '/brand/profile', WP_REST_Server::READABLE, 'brand_profile' );
		$this->route( '/brand/sources/scan', WP_REST_Server::CREATABLE, 'brand_scan_sources' );
		$this->route( '/brand/sources', WP_REST_Server::READABLE, 'brand_sources' );
		$this->route( '/brand/sources', WP_REST_Server::CREATABLE, 'brand_add_source' );
		$this->route( '/brand/sources/upload', WP_REST_Server::CREATABLE, 'brand_upload_source' );
		$this->route( '/brand/sources/(?P<id>[\d]+)', WP_REST_Server::DELETABLE, 'brand_delete_source' );
		$this->route( '/brand/analyze', WP_REST_Server::CREATABLE, 'brand_analyze' );
		$this->route( '/brand/rebuild', WP_REST_Server::CREATABLE, 'brand_analyze' );
		$this->route( '/brand/apply-to-generation', WP_REST_Server::CREATABLE, 'brand_apply_to_generation' );
	}

	/**
	 * Route helper.
	 *
	 * @param string $path Path.
	 * @param string $methods Methods.
	 * @param string $callback Callback method.
	 * @return void
	 */
	private function route( $path, $methods, $callback ) {
		register_rest_route(
			SSAI_REST_NAMESPACE,
			$path,
			array(
				'methods'             => $methods,
				'callback'            => array( $this, $callback ),
				'permission_callback' => array( 'SSAI_Permissions', 'rest_permission' ),
			)
		);
	}

	/**
	 * Gets settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response( SSAI_Settings::public_settings() );
	}

	/**
	 * Saves settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( $request ) {
		return rest_ensure_response( SSAI_Settings::update_from_rest( $this->params( $request ) ) );
	}

	/**
	 * Dashboard metrics.
	 *
	 * @return WP_REST_Response
	 */
	public function dashboard() {
		global $wpdb;

		$posts       = SSAI_Plugin::table( 'posts' );
		$jobs        = SSAI_Plugin::table( 'platform_jobs' );
		$conns       = SSAI_Plugin::table( 'connections' );
		$drafts      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts} WHERE status = 'draft'" );
		$scheduled   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts} WHERE status = 'scheduled'" );
		$published   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts} WHERE status = 'published'" );
		$failed      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs} WHERE status = 'failed'" );
		$connections = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$conns} WHERE status = 'connected'" );

		return rest_ensure_response(
			array(
				'counts'        => compact( 'drafts', 'scheduled', 'published', 'failed', 'connections' ),
				'activity'      => SSAI_Logger::recent( 8 ),
				'brand'         => SSAI_Brand_Intelligence::latest_profile(),
				'brand_sources' => SSAI_Brand_Intelligence::sources(),
				'connections'   => SSAI_Meta_Manager::list_connections(),
				'provider'      => $this->provider_status(),
				'recent_posts'  => $this->post_rows( '', 6, true ),
				'upcoming_jobs' => $this->job_rows( '', 6 ),
				'next_actions'  => $this->next_actions(),
			)
		);
	}

	/**
	 * Lists connections.
	 *
	 * @return WP_REST_Response
	 */
	public function connections() {
		return rest_ensure_response( SSAI_Meta_Manager::list_connections() );
	}

	/**
	 * Saves connection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_connection( $request ) {
		$result = SSAI_Meta_Manager::save_connection( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Tests a Meta connection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_connection( $request ) {
		$result = SSAI_Meta_Manager::test_connection( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Deletes connection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_connection( $request ) {
		return rest_ensure_response( array( 'deleted' => SSAI_Meta_Manager::delete_connection( absint( $request['id'] ) ) ) );
	}

	/**
	 * Lists ideas.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function ideas( $request ) {
		return rest_ensure_response( SSAI_Idea_Bank::all( sanitize_key( $request->get_param( 'status' ) ) ) );
	}

	/**
	 * Creates idea.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function create_idea( $request ) {
		$id = SSAI_Idea_Bank::create( $this->params( $request ) );
		return rest_ensure_response( array( 'id' => $id ) );
	}

	/**
	 * Updates idea.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_idea( $request ) {
		global $wpdb;

		$data  = $this->params( $request );
		$table = SSAI_Plugin::table( 'ideas' );
		$wpdb->update(
			$table,
			array(
				'title'      => sanitize_text_field( $data['title'] ?? '' ),
				'idea_text'  => sanitize_textarea_field( $data['idea_text'] ?? '' ),
				'tags'       => sanitize_text_field( $data['tags'] ?? '' ),
				'status'     => sanitize_key( $data['status'] ?? 'active' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $request['id'] ) ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return rest_ensure_response( array( 'updated' => true ) );
	}

	/**
	 * Deletes idea.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_idea( $request ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'ideas' );
		$wpdb->delete( $table, array( 'id' => absint( $request['id'] ) ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Creates post from idea.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function idea_create_post( $request ) {
		global $wpdb;

		$ideas = SSAI_Plugin::table( 'ideas' );
		$idea  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$ideas} WHERE id = %d", absint( $request['id'] ) ), ARRAY_A );
		if ( ! $idea ) {
			return new WP_Error( 'ssai_idea_not_found', __( 'Idea was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
		}

		$id = $this->insert_post_row(
			array(
				'title'        => $idea['title'],
				'source_type'  => 'idea_bank',
				'content_long' => $idea['idea_text'],
			)
		);

		return rest_ensure_response( array( 'id' => $id ) );
	}

	/**
	 * Lists SociaSpark posts.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function posts( $request ) {
		$status = sanitize_key( $request->get_param( 'status' ) );
		return rest_ensure_response( $this->post_rows( $status, 100, true ) );
	}

	/**
	 * Creates post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function create_post( $request ) {
		$id = $this->insert_post_row( $this->params( $request ) );
		return rest_ensure_response( array( 'id' => $id ) );
	}

	/**
	 * Gets post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post( $request ) {
		$row = $this->post_row( absint( $request['id'] ) );
		if ( $row ) {
			$row['jobs'] = $this->jobs_for_post( (int) $row['id'] );
		}
		return $row ? rest_ensure_response( $row ) : new WP_Error( 'ssai_post_missing', __( 'Post was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
	}

	/**
	 * Updates post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_post( $request ) {
		global $wpdb;

		$data  = $this->params( $request );
		$table = SSAI_Plugin::table( 'posts' );
		$wpdb->update(
			$table,
			$this->sanitize_post_data( $data, false ),
			array( 'id' => absint( $request['id'] ) )
		);

		return rest_ensure_response( $this->post_row( absint( $request['id'] ) ) );
	}

	/**
	 * Deletes post and jobs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_post( $request ) {
		global $wpdb;

		$post_id = absint( $request['id'] );
		$wpdb->delete( SSAI_Plugin::table( 'platform_jobs' ), array( 'ssai_post_id' => $post_id ), array( '%d' ) );
		$wpdb->delete( SSAI_Plugin::table( 'posts' ), array( 'id' => $post_id ), array( '%d' ) );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * AI caption.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_caption( $request ) {
		$result = ( new SSAI_AI_Manager() )->generate_caption( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * AI image.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_image( $request ) {
		$result = ( new SSAI_AI_Manager() )->generate_image( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * AI video script.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_video_script( $request ) {
		$result = ( new SSAI_AI_Manager() )->generate_video_script( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Repurpose WP post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function repurpose_wp_post( $request ) {
		$data   = $this->params( $request );
		$result = ( new SSAI_AI_Manager() )->repurpose_wp_post( absint( $data['wp_post_id'] ?? 0 ), $data );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * AI model catalog.
	 *
	 * @return WP_REST_Response
	 */
	public function ai_models() {
		return rest_ensure_response( SSAI_AI_Manager::model_catalog() );
	}

	/**
	 * Tests selected AI provider/model.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_provider( $request ) {
		$result = ( new SSAI_AI_Manager() )->test_provider( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Searches WordPress content.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function wp_content_search( $request ) {
		$query = sanitize_text_field( $request->get_param( 'q' ) );
		$types = array( 'post', 'page' );
		if ( post_type_exists( 'product' ) ) {
			$types[] = 'product';
		}

		$posts = get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				's'              => $query,
				'posts_per_page' => 20,
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'      => $post->ID,
				'type'    => $post->post_type,
				'title'   => get_the_title( $post ),
				'excerpt' => wp_html_excerpt( wp_strip_all_tags( $post->post_content ), 180, '...' ),
			);
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Saves generated media from dashboard canvas.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_generated_media( $request ) {
		$data   = $this->params( $request );
		$result = SSAI_Media::save_data_url( (string) ( $data['data_url'] ?? '' ), sanitize_text_field( $data['title'] ?? 'SociaSpark image' ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Calendar events.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function calendar( $request ) {
		return rest_ensure_response(
			SSAI_Calendar::events(
				sanitize_text_field( $request->get_param( 'from' ) ),
				sanitize_text_field( $request->get_param( 'to' ) )
			)
		);
	}

	/**
	 * Schedules platform jobs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function schedule( $request ) {
		$data    = $this->params( $request );
		$post_id = absint( $data['post_id'] ?? 0 );
		$post    = $this->post_row( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'ssai_post_missing', __( 'Post was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
		}

		$jobs = isset( $data['jobs'] ) && is_array( $data['jobs'] ) ? $data['jobs'] : array();
		if ( empty( $jobs ) ) {
			return new WP_Error( 'ssai_jobs_missing', __( 'At least one platform job is required.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$created = $this->create_jobs( $post_id, $jobs );
		if ( empty( $created ) ) {
			return new WP_Error( 'ssai_jobs_invalid', __( 'No valid platform jobs could be created. Choose a connected Facebook or Instagram account.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$this->set_post_status( $post_id, 'scheduled', $this->earliest_schedule( $jobs ) );

		return rest_ensure_response( array( 'jobs' => $created ) );
	}

	/**
	 * Publishes immediately.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function publish_now( $request ) {
		$data = $this->params( $request );
		if ( empty( $data['jobs'] ) || ! is_array( $data['jobs'] ) ) {
			return new WP_Error( 'ssai_jobs_missing', __( 'At least one platform job is required.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$post_id = absint( $data['post_id'] ?? 0 );
		if ( ! $this->post_row( $post_id ) ) {
			return new WP_Error( 'ssai_post_missing', __( 'Post was not found.', 'sociaspark-ai-social-poster' ), array( 'status' => 404 ) );
		}

		foreach ( $data['jobs'] as &$job ) {
			$job['scheduled_at'] = current_time( 'mysql' );
		}

		$job_ids   = $this->create_jobs( $post_id, $data['jobs'] );
		if ( empty( $job_ids ) ) {
			return new WP_Error( 'ssai_jobs_invalid', __( 'No valid platform jobs could be created. Choose a connected Facebook or Instagram account.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$this->set_post_status( $post_id, 'scheduled', current_time( 'mysql' ) );
		$scheduler = new SSAI_Scheduler();
		foreach ( $job_ids as $job_id ) {
			$scheduler->process_job( (int) $job_id );
		}

		return rest_ensure_response( array( 'queued' => $job_ids ) );
	}

	/**
	 * Lists platform jobs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function jobs( $request ) {
		return rest_ensure_response(
			$this->job_rows(
				sanitize_key( $request->get_param( 'status' ) ),
				absint( $request->get_param( 'limit' ) ?: 100 )
			)
		);
	}

	/**
	 * Retries job.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function retry_job( $request ) {
		global $wpdb;

		$data   = $this->params( $request );
		$job_id = absint( $data['job_id'] ?? 0 );
		$table  = SSAI_Plugin::table( 'platform_jobs' );
		$wpdb->update(
			$table,
			array(
				'status'          => 'scheduled',
				'scheduled_at'    => current_time( 'mysql' ),
				'next_attempt_at' => null,
				'error_message'   => null,
				'error_code'      => null,
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $job_id )
		);

		$result = ( new SSAI_Scheduler() )->process_job( $job_id );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Logs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function logs( $request ) {
		return rest_ensure_response( SSAI_Logger::recent( absint( $request->get_param( 'limit' ) ?: 50 ) ) );
	}

	/**
	 * Brand profile.
	 *
	 * @return WP_REST_Response
	 */
	public function brand_profile() {
		return rest_ensure_response(
			array(
				'profile' => SSAI_Brand_Intelligence::latest_profile(),
				'sources' => SSAI_Brand_Intelligence::sources(),
			)
		);
	}

	/**
	 * Brand source scan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function brand_scan_sources( $request ) {
		$data = $this->params( $request );
		return rest_ensure_response( SSAI_Brand_Intelligence::scan_sources( $data['search'] ?? '' ) );
	}

	/**
	 * Brand sources.
	 *
	 * @return WP_REST_Response
	 */
	public function brand_sources() {
		return rest_ensure_response( SSAI_Brand_Intelligence::sources() );
	}

	/**
	 * Adds brand source.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function brand_add_source( $request ) {
		$result = SSAI_Brand_Intelligence::add_source( $this->params( $request ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Uploads a brand source file.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function brand_upload_source( $request ) {
		$files = $request->get_file_params();
		$file  = $files['file'] ?? array();
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'ssai_brand_upload_missing', __( 'Choose a brand source file to upload.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$title  = sanitize_text_field( $request->get_param( 'title' ) ?: ( $file['name'] ?? __( 'Uploaded brand source', 'sociaspark-ai-social-poster' ) ) );
		$result = SSAI_Brand_Intelligence::add_uploaded_file( $file, $title );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Deletes brand source.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function brand_delete_source( $request ) {
		return rest_ensure_response( array( 'deleted' => SSAI_Brand_Intelligence::delete_source( absint( $request['id'] ) ) ) );
	}

	/**
	 * Analyzes brand.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function brand_analyze( $request ) {
		$data    = $this->params( $request );
		$sources = SSAI_Brand_Intelligence::sources();
		if ( empty( $sources ) ) {
			return new WP_Error( 'ssai_brand_no_sources', __( 'Add at least one brand source first.', 'sociaspark-ai-social-poster' ), array( 'status' => 400 ) );
		}

		$source_ids = wp_list_pluck( $sources, 'id' );
		$use_ai     = ! empty( $data['use_ai'] );
		if ( $use_ai ) {
			$result = ( new SSAI_AI_Manager() )->analyze_brand( $sources, $data['provider'] ?? '' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$profile = SSAI_Brand_Intelligence::save_profile( $result, $source_ids, sanitize_key( $data['provider'] ?? SSAI_Settings::get( 'default_provider', 'openai' ) ) );
		} else {
			$profile = SSAI_Brand_Intelligence::save_profile( SSAI_Brand_Intelligence::build_local_profile( $sources ), $source_ids, 'local' );
		}

		SSAI_Logger::log( 'info', 'ssai_brand_profile_built', 'Brand Intelligence Profile created.', array( 'source_count' => count( $sources ), 'mode' => $use_ai ? 'ai' : 'local' ) );
		return rest_ensure_response( $profile );
	}

	/**
	 * Applies current brand profile to generation.
	 *
	 * @return WP_REST_Response
	 */
	public function brand_apply_to_generation() {
		return rest_ensure_response( array( 'profile' => SSAI_Brand_Intelligence::latest_profile() ) );
	}

	/**
	 * Gets request params.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array
	 */
	private function params( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}
		return is_array( $params ) ? wp_unslash( $params ) : array();
	}

	/**
	 * Inserts post row.
	 *
	 * @param array $data Data.
	 * @return int
	 */
	private function insert_post_row( $data ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'posts' );
		$row   = $this->sanitize_post_data( $data, true );
		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Sanitizes post data.
	 *
	 * @param array $data Data.
	 * @param bool  $insert Insert mode.
	 * @return array
	 */
	private function sanitize_post_data( $data, $insert ) {
		$now = current_time( 'mysql' );
		$row = array(
			'title'             => sanitize_text_field( $data['title'] ?? '' ),
			'wp_post_id'        => ! empty( $data['wp_post_id'] ) ? absint( $data['wp_post_id'] ) : null,
			'source_type'       => sanitize_key( $data['source_type'] ?? 'manual' ),
			'content_long'      => sanitize_textarea_field( $data['content_long'] ?? '' ),
			'content_facebook'  => sanitize_textarea_field( $data['content_facebook'] ?? '' ),
			'content_instagram' => sanitize_textarea_field( $data['content_instagram'] ?? '' ),
			'media_id'          => ! empty( $data['media_id'] ) ? absint( $data['media_id'] ) : null,
			'media_url'         => ! empty( $data['media_url'] ) ? esc_url_raw( $data['media_url'] ) : null,
			'status'            => sanitize_key( $data['status'] ?? 'draft' ),
			'scheduled_at'      => ! empty( $data['scheduled_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( (string) $data['scheduled_at'] ) ) : null,
			'updated_at'        => $now,
		);

		if ( $insert ) {
			$row['created_by'] = get_current_user_id();
			$row['created_at'] = $now;
		}

		return $row;
	}

	/**
	 * Gets post row.
	 *
	 * @param int $id ID.
	 * @return array|null
	 */
	private function post_row( $id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'posts' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), ARRAY_A );
	}

	/**
	 * Lists post rows.
	 *
	 * @param string $status Status.
	 * @param int    $limit Limit.
	 * @param bool   $with_jobs Include jobs.
	 * @return array
	 */
	private function post_rows( $status = '', $limit = 100, $with_jobs = false ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'posts' );
		$limit = min( 200, max( 1, absint( $limit ) ) );

		if ( $status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", sanitize_key( $status ), $limit ),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ),
				ARRAY_A
			);
		}

		if ( $with_jobs ) {
			foreach ( $rows as &$row ) {
				$row['jobs'] = $this->jobs_for_post( (int) $row['id'] );
			}
		}

		return $rows;
	}

	/**
	 * Creates jobs.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $jobs Jobs.
	 * @return array
	 */
	private function create_jobs( $post_id, $jobs ) {
		global $wpdb;

		$table   = SSAI_Plugin::table( 'platform_jobs' );
		$created = array();
		$now     = current_time( 'mysql' );

		foreach ( $jobs as $job ) {
			$platform = sanitize_key( $job['platform'] ?? '' );
			if ( ! in_array( $platform, array( 'facebook', 'instagram' ), true ) ) {
				continue;
			}

			$account_id = sanitize_text_field( $job['platform_account_id'] ?? '' );
			if ( '' === $account_id ) {
				continue;
			}

			$scheduled = ! empty( $job['scheduled_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( (string) $job['scheduled_at'] ) ) : $now;
			$wpdb->insert(
				$table,
				array(
					'ssai_post_id'        => absint( $post_id ),
					'platform'            => $platform,
					'platform_account_id' => $account_id,
					'status'              => 'scheduled',
					'scheduled_at'        => $scheduled,
					'attempts'            => 0,
					'created_at'          => $now,
					'updated_at'          => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
			$created[] = (int) $wpdb->insert_id;
		}

		return $created;
	}

	/**
	 * Jobs for a SociaSpark post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function jobs_for_post( $post_id ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'platform_jobs' );
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE ssai_post_id = %d ORDER BY scheduled_at ASC, id ASC", absint( $post_id ) ),
			ARRAY_A
		);
	}

	/**
	 * Lists jobs with post title.
	 *
	 * @param string $status Status.
	 * @param int    $limit Limit.
	 * @return array
	 */
	private function job_rows( $status = '', $limit = 100 ) {
		global $wpdb;

		$jobs  = SSAI_Plugin::table( 'platform_jobs' );
		$posts = SSAI_Plugin::table( 'posts' );
		$limit = min( 200, max( 1, absint( $limit ) ) );

		if ( $status ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT j.*, p.title, p.media_id, p.media_url FROM {$jobs} j INNER JOIN {$posts} p ON p.id = j.ssai_post_id WHERE j.status = %s ORDER BY j.scheduled_at DESC, j.id DESC LIMIT %d",
					sanitize_key( $status ),
					$limit
				),
				ARRAY_A
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.*, p.title, p.media_id, p.media_url FROM {$jobs} j INNER JOIN {$posts} p ON p.id = j.ssai_post_id ORDER BY j.scheduled_at DESC, j.id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Provider configuration status.
	 *
	 * @return array
	 */
	private function provider_status() {
		$settings = SSAI_Settings::public_settings();

		return array(
			'openai' => ! empty( $settings['openai_api_key_configured'] ),
			'gemini' => ! empty( $settings['gemini_api_key_configured'] ),
			'claude' => ! empty( $settings['claude_api_key_configured'] ),
			'default_provider' => $settings['default_provider'] ?? 'openai',
		);
	}

	/**
	 * Builds setup next actions.
	 *
	 * @return array
	 */
	private function next_actions() {
		$settings = $this->provider_status();
		$connections = SSAI_Meta_Manager::list_connections();
		$brand = SSAI_Brand_Intelligence::latest_profile();
		$actions = array();

		if ( empty( $settings['openai'] ) && empty( $settings['gemini'] ) && empty( $settings['claude'] ) ) {
			$actions[] = array( 'id' => 'settings', 'label' => __( 'Add at least one AI provider key', 'sociaspark-ai-social-poster' ) );
		}
		if ( empty( $connections ) ) {
			$actions[] = array( 'id' => 'connections', 'label' => __( 'Connect a Facebook Page or Instagram account', 'sociaspark-ai-social-poster' ) );
		}
		if ( empty( $brand ) ) {
			$actions[] = array( 'id' => 'brand', 'label' => __( 'Build Brand Intelligence from approved sources', 'sociaspark-ai-social-poster' ) );
		}

		if ( empty( $actions ) ) {
			$actions[] = array( 'id' => 'create', 'label' => __( 'Create and schedule the next social post', 'sociaspark-ai-social-poster' ) );
		}

		return $actions;
	}

	/**
	 * Sets post status.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status Status.
	 * @param string $scheduled_at Schedule.
	 * @return void
	 */
	private function set_post_status( $post_id, $status, $scheduled_at = '' ) {
		global $wpdb;

		$wpdb->update(
			SSAI_Plugin::table( 'posts' ),
			array(
				'status'       => sanitize_key( $status ),
				'scheduled_at' => $scheduled_at ?: null,
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => absint( $post_id ) )
		);
	}

	/**
	 * Earliest schedule in jobs.
	 *
	 * @param array $jobs Jobs.
	 * @return string
	 */
	private function earliest_schedule( $jobs ) {
		$times = array();
		foreach ( $jobs as $job ) {
			if ( ! empty( $job['scheduled_at'] ) ) {
				$times[] = strtotime( (string) $job['scheduled_at'] );
			}
		}
		if ( empty( $times ) ) {
			return current_time( 'mysql' );
		}
		return gmdate( 'Y-m-d H:i:s', min( $times ) );
	}
}
