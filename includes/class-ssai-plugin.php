<?php
/**
 * Core plugin bootstrap.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires plugin services into WordPress.
 */
final class SSAI_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var SSAI_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin service.
	 *
	 * @var SSAI_Admin
	 */
	private $admin;

	/**
	 * REST service.
	 *
	 * @var SSAI_REST_Controller
	 */
	private $rest;

	/**
	 * Scheduler service.
	 *
	 * @var SSAI_Scheduler
	 */
	private $scheduler;

	/**
	 * Returns singleton instance.
	 *
	 * @return SSAI_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();

		$this->admin     = new SSAI_Admin();
		$this->rest      = new SSAI_REST_Controller();
		$this->scheduler = new SSAI_Scheduler();

		$this->register_hooks();
	}

	/**
	 * Loads service classes.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$files = array(
			'class-ssai-i18n.php',
			'class-ssai-permissions.php',
			'class-ssai-settings.php',
			'class-ssai-encryption.php',
			'class-ssai-logger.php',
			'class-ssai-media.php',
			'class-ssai-ai-provider-interface.php',
			'class-ssai-openai-provider.php',
			'class-ssai-gemini-provider.php',
			'class-ssai-claude-provider.php',
			'class-ssai-ai-manager.php',
			'class-ssai-meta-manager.php',
			'class-ssai-facebook-publisher.php',
			'class-ssai-instagram-publisher.php',
			'class-ssai-brand-intelligence.php',
			'class-ssai-idea-bank.php',
			'class-ssai-calendar.php',
			'class-ssai-scheduler.php',
			'class-ssai-upgrade-hooks.php',
			'class-ssai-admin.php',
			'class-ssai-rest-controller.php',
		);

		foreach ( $files as $file ) {
			require_once SSAI_PLUGIN_DIR . 'includes/' . $file;
		}
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'plugins_loaded', array( 'SSAI_I18n', 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( 'SSAI_Activator', 'maybe_upgrade' ), 11 );
		add_action( 'plugins_loaded', array( 'SSAI_Upgrade_Hooks', 'register_hooks' ), 20 );
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
		add_filter( 'cron_schedules', array( $this->scheduler, 'add_cron_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- Interval is registered in SSAI_Scheduler::add_cron_interval().
		add_action( 'ssai_process_scheduled_jobs', array( $this->scheduler, 'process_due_jobs' ) );
	}

	/**
	 * Returns a whitelisted plugin table name.
	 *
	 * @param string $key Table key.
	 * @return string
	 */
	public static function table( $key ) {
		global $wpdb;

		$tables = array(
			'posts'          => 'ssai_posts',
			'platform_jobs'  => 'ssai_platform_jobs',
			'ideas'          => 'ssai_ideas',
			'logs'           => 'ssai_logs',
			'connections'    => 'ssai_connections',
			'brand_sources'  => 'ssai_brand_sources',
			'brand_profiles' => 'ssai_brand_profiles',
		);

		if ( ! isset( $tables[ $key ] ) ) {
			return '';
		}

		return $wpdb->prefix . $tables[ $key ];
	}
}
