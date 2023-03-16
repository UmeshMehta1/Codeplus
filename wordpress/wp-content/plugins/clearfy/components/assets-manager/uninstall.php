<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// remove plugin options
global $wpdb;

if ( ! defined( 'WGZ_PLUGIN_DIR' ) ) {
	define( 'WGZ_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

function uninstall() {
	// remove plugin options
	global $wpdb;

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wbcr_gonzales_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wbcr_gnz_%';" );
}

if ( is_multisite() ) {
	global $wpdb, $wp_version;

	$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'wbcr_gonzales_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'wbcr_gnz_%';" );

	$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

	if ( ! empty( $blogs ) ) {
		foreach ( $blogs as $id ) {

			switch_to_blog( $id );

			uninstall();

			restore_current_blog();
		}
	}
} else {
	uninstall();
}

// Remove mu plugin
require_once WGZ_PLUGIN_DIR . '/includes/functions.php';
// todo: for the function require the constant WGZ_PLUGIN_DIR
wbcr_gnz_remove_mu_plugin();