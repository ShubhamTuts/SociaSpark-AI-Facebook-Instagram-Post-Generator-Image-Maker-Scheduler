<?php
/**
 * Plugin Name: SociaSpark AI - Facebook & Instagram Post Generator, Image Maker & Scheduler
 * Plugin URI: https://codefreex.com/sociaspark-ai/
 * Description: Generate AI captions, images and video scripts, then schedule Facebook and Instagram posts from WordPress.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Tested up to: 6.9
 * Author: Codefreex
 * Author URI: https://codefreex.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sociaspark-ai-social-poster
 * Domain Path: /languages
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSAI_VERSION', '1.0.0' );
define( 'SSAI_PLUGIN_FILE', __FILE__ );
define( 'SSAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSAI_REST_NAMESPACE', 'ssai/v1' );

require_once SSAI_PLUGIN_DIR . 'includes/class-ssai-activator.php';
require_once SSAI_PLUGIN_DIR . 'includes/class-ssai-deactivator.php';
require_once SSAI_PLUGIN_DIR . 'includes/class-ssai-plugin.php';

register_activation_hook( __FILE__, array( 'SSAI_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SSAI_Deactivator', 'deactivate' ) );

/**
 * Returns the loaded SociaSpark plugin instance.
 *
 * @return SSAI_Plugin
 */
function ssai() {
	return SSAI_Plugin::instance();
}

ssai();
