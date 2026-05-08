<?php
/**
 * Scheduler.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes scheduled publishing jobs.
 */
class SSAI_Scheduler {
	/**
	 * Adds cron interval.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['ssai_every_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every five minutes', 'sociaspark-ai-social-poster' ),
		);

		return $schedules;
	}

	/**
	 * Processes due jobs.
	 *
	 * @return void
	 */
	public function process_due_jobs() {
		if ( get_transient( 'ssai_scheduler_lock' ) ) {
			return;
		}

		set_transient( 'ssai_scheduler_lock', 1, 4 * MINUTE_IN_SECONDS );

		try {
			$job_ids = $this->due_job_ids();
			foreach ( $job_ids as $job_id ) {
				$this->process_job( (int) $job_id );
			}
		} finally {
			delete_transient( 'ssai_scheduler_lock' );
		}
	}

	/**
	 * Processes a single job.
	 *
	 * @param int $job_id Job ID.
	 * @return array|WP_Error
	 */
	public function process_job( $job_id ) {
		$token = wp_generate_password( 32, false, false );
		$job   = $this->claim_job( $job_id, $token );
		if ( is_wp_error( $job ) ) {
			return $job;
		}

		global $wpdb;

		$jobs_table  = SSAI_Plugin::table( 'platform_jobs' );
		$posts_table = SSAI_Plugin::table( 'posts' );
		$post        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$posts_table} WHERE id = %d", absint( $job['ssai_post_id'] ) ),
			ARRAY_A
		);

		if ( ! $post ) {
			return $this->fail_job( $job, 'Post draft was not found.', false );
		}

		$connection = SSAI_Meta_Manager::get_connection_for_publish( $job['platform'], $job['platform_account_id'] );
		if ( is_wp_error( $connection ) ) {
			return $this->fail_job( $job, $connection->get_error_message(), (bool) ( $connection->get_error_data()['transient'] ?? false ) );
		}

		do_action( 'ssai_pro_before_publish', $job, $post, $connection );

		if ( 'facebook' === $job['platform'] ) {
			$publisher = new SSAI_Facebook_Publisher();
		} elseif ( 'instagram' === $job['platform'] ) {
			$publisher = new SSAI_Instagram_Publisher();
		} else {
			return $this->fail_job( $job, 'Unsupported platform.', false );
		}

		$result = $publisher->publish( $connection, $post );
		if ( is_wp_error( $result ) ) {
			return $this->fail_job( $job, $result->get_error_message(), (bool) ( $result->get_error_data()['transient'] ?? false ), $result->get_error_code() );
		}

		$wpdb->update(
			$jobs_table,
			array(
				'status'           => 'published',
				'external_post_id' => sanitize_text_field( $result['id'] ?? '' ),
				'error_message'    => null,
				'error_code'       => null,
				'last_attempt_at'  => current_time( 'mysql' ),
				'lock_token'       => null,
				'locked_at'        => null,
				'updated_at'       => current_time( 'mysql' ),
			),
			array( 'id' => absint( $job['id'] ) )
		);

		$this->refresh_post_status( (int) $job['ssai_post_id'] );
		SSAI_Logger::log( 'info', 'ssai_publish_success', 'Published scheduled job.', array( 'job_id' => $job['id'], 'platform' => $job['platform'] ) );
		do_action( 'ssai_pro_after_publish', $job, $post, $connection, $result );

		return $result;
	}

	/**
	 * Returns due job IDs.
	 *
	 * @return array
	 */
	private function due_job_ids() {
		global $wpdb;

		$table = SSAI_Plugin::table( 'platform_jobs' );
		$now   = current_time( 'mysql' );

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE status IN ('queued', 'scheduled')
				AND scheduled_at <= %s
				AND (next_attempt_at IS NULL OR next_attempt_at <= %s)
				AND (lock_token IS NULL OR locked_at < DATE_SUB(%s, INTERVAL 10 MINUTE))
				ORDER BY scheduled_at ASC
				LIMIT 1",
				$now,
				$now,
				$now
			)
		);
	}

	/**
	 * Claims job for processing.
	 *
	 * @param int    $job_id Job ID.
	 * @param string $token Lock token.
	 * @return array|WP_Error
	 */
	private function claim_job( $job_id, $token ) {
		global $wpdb;

		$table = SSAI_Plugin::table( 'platform_jobs' );
		$now   = current_time( 'mysql' );

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET status = 'publishing', lock_token = %s, locked_at = %s, attempts = attempts + 1, last_attempt_at = %s, updated_at = %s
				WHERE id = %d
				AND status IN ('queued', 'scheduled')
				AND (lock_token IS NULL OR locked_at < DATE_SUB(%s, INTERVAL 10 MINUTE))",
				$token,
				$now,
				$now,
				$now,
				absint( $job_id ),
				$now
			)
		);

		if ( ! $updated ) {
			return new WP_Error( 'ssai_job_locked', __( 'Job is already being processed.', 'sociaspark-ai-social-poster' ), array( 'status' => 409 ) );
		}

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND lock_token = %s", absint( $job_id ), $token ),
			ARRAY_A
		);
	}

	/**
	 * Fails or retries a job.
	 *
	 * @param array  $job Job.
	 * @param string $message Message.
	 * @param bool   $transient Whether retryable.
	 * @param string $code Error code.
	 * @return WP_Error
	 */
	private function fail_job( $job, $message, $transient, $code = 'ssai_publish_failed' ) {
		global $wpdb;

		$table        = SSAI_Plugin::table( 'platform_jobs' );
		$max_attempts = absint( SSAI_Settings::get( 'retry_attempts', 3 ) );
		$attempts     = absint( $job['attempts'] );
		$status       = ( $transient && $attempts < $max_attempts ) ? 'scheduled' : 'failed';
		$next         = null;

		if ( 'scheduled' === $status ) {
			$delay = min( 60, 5 * max( 1, $attempts ) );
			$next  = gmdate( 'Y-m-d H:i:s', time() + ( $delay * MINUTE_IN_SECONDS ) );
		}

		$wpdb->update(
			$table,
			array(
				'status'          => $status,
				'error_message'   => sanitize_textarea_field( $message ),
				'error_code'      => sanitize_key( $code ),
				'next_attempt_at' => $next,
				'lock_token'      => null,
				'locked_at'       => null,
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => absint( $job['id'] ) )
		);

		$this->refresh_post_status( (int) $job['ssai_post_id'] );
		SSAI_Logger::log( 'error', 'ssai_publish_failed', $message, array( 'job_id' => $job['id'], 'platform' => $job['platform'], 'retrying' => 'scheduled' === $status ) );

		return new WP_Error( $code, $message, array( 'status' => 'scheduled' === $status ? 202 : 500, 'transient' => $transient ) );
	}

	/**
	 * Refreshes aggregate post status.
	 *
	 * @param int $post_id SociaSpark post ID.
	 * @return void
	 */
	private function refresh_post_status( $post_id ) {
		global $wpdb;

		$jobs_table  = SSAI_Plugin::table( 'platform_jobs' );
		$posts_table = SSAI_Plugin::table( 'posts' );
		$statuses    = $wpdb->get_col(
			$wpdb->prepare( "SELECT status FROM {$jobs_table} WHERE ssai_post_id = %d", absint( $post_id ) )
		);

		if ( empty( $statuses ) ) {
			return;
		}

		if ( count( $statuses ) === count( array_filter( $statuses, static fn( $status ) => 'published' === $status ) ) ) {
			$wpdb->update(
				$posts_table,
				array(
					'status'       => 'published',
					'published_at' => current_time( 'mysql' ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'id' => absint( $post_id ) )
			);
			return;
		}

		if ( in_array( 'failed', $statuses, true ) ) {
			$wpdb->update(
				$posts_table,
				array(
					'status'     => 'failed',
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => absint( $post_id ) )
			);
		}
	}
}
