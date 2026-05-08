<?php
/**
 * Plugin activation.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation tasks.
 */
class SSAI_Activator {
	const DB_VERSION = '1.0.1';

	/**
	 * Creates tables and schedules cron.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'ssai_version', SSAI_VERSION, false );
		update_option( 'ssai_db_version', self::DB_VERSION, false );

		if ( ! wp_next_scheduled( 'ssai_process_scheduled_jobs' ) ) {
			wp_schedule_event( time() + 300, 'ssai_every_five_minutes', 'ssai_process_scheduled_jobs' );
		}
	}

	/**
	 * Creates custom database tables.
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		$sql = array();

		$sql[] = "CREATE TABLE {$prefix}ssai_posts (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_post_id BIGINT UNSIGNED NULL,
			title VARCHAR(255) NOT NULL DEFAULT '',
			source_type VARCHAR(50) NOT NULL DEFAULT 'manual',
			content_long LONGTEXT NULL,
			content_facebook LONGTEXT NULL,
			content_instagram LONGTEXT NULL,
			media_id BIGINT UNSIGNED NULL,
			media_url TEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'draft',
			scheduled_at DATETIME NULL,
			published_at DATETIME NULL,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY scheduled_at (scheduled_at),
			KEY wp_post_id (wp_post_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_platform_jobs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ssai_post_id BIGINT UNSIGNED NOT NULL,
			platform VARCHAR(30) NOT NULL,
			platform_account_id VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'queued',
			scheduled_at DATETIME NULL,
			external_post_id VARCHAR(255) NULL,
			error_message TEXT NULL,
			error_code VARCHAR(80) NULL,
			attempts INT NOT NULL DEFAULT 0,
			last_attempt_at DATETIME NULL,
			next_attempt_at DATETIME NULL,
			lock_token VARCHAR(64) NULL,
			locked_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY ssai_post_id (ssai_post_id),
			KEY platform (platform),
			KEY status_schedule (status, scheduled_at),
			KEY lock_token (lock_token)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_ideas (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL DEFAULT '',
			idea_text LONGTEXT NOT NULL,
			source VARCHAR(50) NOT NULL DEFAULT 'manual',
			tags TEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'active',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY source (source)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			level VARCHAR(20) NOT NULL DEFAULT 'info',
			event VARCHAR(100) NOT NULL DEFAULT '',
			message LONGTEXT NOT NULL,
			context LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY event (event),
			KEY created_at (created_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_connections (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			platform VARCHAR(30) NOT NULL,
			account_label VARCHAR(255) NOT NULL DEFAULT '',
			account_id VARCHAR(255) NOT NULL DEFAULT '',
			encrypted_access_token LONGTEXT NOT NULL,
			encrypted_refresh_token LONGTEXT NULL,
			token_expires_at DATETIME NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'connected',
			meta LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY platform (platform),
			KEY account_id (account_id),
			KEY status (status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_brand_sources (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source_type VARCHAR(50) NOT NULL DEFAULT 'manual',
			source_id VARCHAR(255) NULL,
			title VARCHAR(255) NOT NULL DEFAULT '',
			excerpt LONGTEXT NOT NULL,
			source_hash VARCHAR(64) NOT NULL DEFAULT '',
			meta LONGTEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'active',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY source_type (source_type),
			KEY source_hash (source_hash),
			KEY status (status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}ssai_brand_profiles (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			version INT NOT NULL DEFAULT 1,
			profile_json LONGTEXT NOT NULL,
			source_ids LONGTEXT NULL,
			generated_by VARCHAR(50) NOT NULL DEFAULT 'local',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY version (version),
			KEY created_at (created_at)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Runs lightweight schema upgrades for existing installs.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$current = get_option( 'ssai_db_version', '' );
		if ( self::DB_VERSION === $current ) {
			return;
		}

		self::create_tables();
		update_option( 'ssai_db_version', self::DB_VERSION, false );
	}
}
