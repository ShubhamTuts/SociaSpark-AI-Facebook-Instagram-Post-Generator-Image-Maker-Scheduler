<?php
/**
 * Uninstall cleanup.
 *
 * @package SociaSpark_AI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'ssai_settings', array() );
if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'ssai_posts',
	$wpdb->prefix . 'ssai_platform_jobs',
	$wpdb->prefix . 'ssai_ideas',
	$wpdb->prefix . 'ssai_logs',
	$wpdb->prefix . 'ssai_connections',
	$wpdb->prefix . 'ssai_brand_sources',
	$wpdb->prefix . 'ssai_brand_profiles',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

delete_option( 'ssai_settings' );
delete_option( 'ssai_version' );
wp_clear_scheduled_hook( 'ssai_process_scheduled_jobs' );
