<?php
/**
 * Admin dashboard.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers wp-admin dashboard.
 */
class SSAI_Admin {
	/**
	 * Menu hook suffix.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->hook = add_menu_page(
			__( 'SociaSpark AI', 'sociaspark-ai-social-poster' ),
			__( 'SociaSpark AI', 'sociaspark-ai-social-poster' ),
			SSAI_Permissions::capability(),
			'sociaspark-ai',
			array( $this, 'render_app' ),
			'dashicons-share',
			58
		);
	}

	/**
	 * Enqueues dashboard assets only on plugin page.
	 *
	 * @param string $hook Current hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook ) {
			return;
		}

		wp_enqueue_media();

		$script_path = SSAI_PLUGIN_DIR . 'admin/build/index.js';
		if ( ! file_exists( $script_path ) ) {
			wp_enqueue_style( 'ssai-admin-style', SSAI_PLUGIN_URL . 'admin/src/styles/admin.css', array(), SSAI_VERSION );
			return;
		}

		$asset_file = SSAI_PLUGIN_DIR . 'admin/build/index.asset.php';
		$asset      = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ),
			'version'      => SSAI_VERSION,
		);

		wp_enqueue_style( 'wp-components' );
		$style_candidates = array(
			'admin/build/style-index.css',
			'admin/build/index.css',
		);
		$style_path       = '';
		$style_url        = '';

		foreach ( $style_candidates as $candidate ) {
			$candidate_path = SSAI_PLUGIN_DIR . $candidate;
			if ( ! file_exists( $candidate_path ) ) {
				continue;
			}
			if ( '' === $style_path || filemtime( $candidate_path ) > filemtime( $style_path ) ) {
				$style_path = $candidate_path;
				$style_url  = SSAI_PLUGIN_URL . $candidate;
			}
		}

		if ( '' === $style_path ) {
			$style_path = SSAI_PLUGIN_DIR . 'admin/src/styles/admin.css';
			$style_url  = SSAI_PLUGIN_URL . 'admin/src/styles/admin.css';
		}

		wp_enqueue_script(
			'ssai-admin-app',
			SSAI_PLUGIN_URL . 'admin/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_enqueue_style(
			'ssai-admin-style',
			$style_url,
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : $asset['version']
		);

		wp_add_inline_script(
			'ssai-admin-app',
			'window.ssaiAdmin = ' . wp_json_encode(
				array(
					'restUrl'    => esc_url_raw( rest_url( SSAI_REST_NAMESPACE ) ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'version'    => SSAI_VERSION,
					'timezone'   => wp_timezone_string(),
					'pluginUrl'  => SSAI_PLUGIN_URL,
					'capability' => SSAI_Permissions::capability(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Renders root container.
	 *
	 * @return void
	 */
	public function render_app() {
		echo '<div class="wrap ssai-wrap">';
		if ( ! file_exists( SSAI_PLUGIN_DIR . 'admin/build/index.js' ) ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'SociaSpark AI admin assets are missing. Run npm ci and npm run build, then reload this page.', 'sociaspark-ai-social-poster' );
			echo '</p></div>';
		}
		echo '<div id="ssai-admin-root"></div></div>';
	}
}
