<?php
/**
 * Plugin Name: HTML Мinify
 * Plugin URI: https://webcraftic.com
 * Description: Ever look at the HTML markup of your website and notice how sloppy and amateurish it looks? The HTML Мinify options cleans up sloppy looking markup and minifies, which also speeds up download.
 * Author: Webcraftic <wordpress.webraftic@gmail.com>
 * Version: 1.1.1
 * Text Domain: html-minify
 * Domain Path: /languages/
 * Author URI: https://webcraftic.com
 * Framework Version: FACTORY_463_VERSION
 */

/*
 * #### CREDITS ####
 * This plugin is based on the plugin Autoptimize by the author Frank Goossens, we have finalized this code for our project and our goals.
 * Many thanks to Frank Goossens for the quality solution for optimizing scripts in Wordpress.
 *
 * Public License is a GPLv2 compatible license allowing you to change and use this version of the plugin for free.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */

require_once( dirname( __FILE__ ) . '/libs/factory/core/includes/class-factory-requirements.php' );

// @formatter:off
$whtml_plugin_info = array(
	'prefix'         => 'wbcr_htm_', // префикс для базы данных и полей формы
	'plugin_name'    => 'wbcr_html_minify', // имя плагина, как уникальный идентификатор
	'plugin_title'   => __( 'Webcraftic HTML Minify', 'html-minify' ), // заголовок плагина

	// PLUGIN SUPPORT
	'support_details'      => array(
		'url'       => 'https://webcraftic.com',
		'pages_map' => array(
			'support'  => 'support',           // {site}/support
			'docs'     => 'docs'               // {site}/docs
		)
	),

	// PLUGIN ADVERTS
	'render_adverts' => true,
	'adverts_settings'    => array(
		'dashboard_widget' => true, // show dashboard widget (default: false)
		'right_sidebar'    => true, // show adverts sidebar (default: false)
		'notice'           => true, // show notice message (default: false)
	),

	// FRAMEWORK MODULES
	'load_factory_modules' => array(
		array( 'libs/factory/bootstrap', 'factory_bootstrap_464', 'admin' ),
		array( 'libs/factory/forms', 'factory_forms_460', 'admin' ),
		array( 'libs/factory/pages', 'factory_pages_463', 'admin' ),
		array( 'libs/factory/clearfy', 'factory_templates_113', 'all' ),
		array( 'libs/factory/adverts', 'factory_adverts_140', 'admin')
	)
);

$whtml_compatibility = new Wbcr_Factory463_Requirements( __FILE__, array_merge( $whtml_plugin_info, array(
	'plugin_already_activate'          => defined( 'WHTM_PLUGIN_ACTIVE' ),
	'required_php_version'             => '5.4',
	'required_wp_version'              => '4.2.0',
	'required_clearfy_check_component' => false
) ) );


/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $whtml_compatibility->check() ) {
	return;
}

/**
 * -----------------------------------------------------------------------------
 * CONSTANTS
 * Install frequently used constants and constants for debugging, which will be
 * removed after compiling the plugin.
 * -----------------------------------------------------------------------------
 */

// This plugin is activated
define( 'WHTM_PLUGIN_ACTIVE', true );
define( 'WHTM_PLUGIN_VERSION', $whtml_compatibility->get_plugin_version() );
define( 'WHTM_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'WHTM_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WHTM_PLUGIN_URL', plugins_url( null, __FILE__ ) );




/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once( WHTM_PLUGIN_DIR . '/libs/factory/core/boot.php' );
require_once( WHTM_PLUGIN_DIR . '/includes/class-plugin.php' );

try {
	new WHTM_Plugin( __FILE__, array_merge( $whtml_plugin_info, array(
		'plugin_version'     => WHTM_PLUGIN_VERSION,
		'plugin_text_domain' => $whtml_compatibility->get_text_domain(),
	) ) );
} catch( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define( 'WHTM_PLUGIN_THROW_ERROR', true );

	$whtml_plugin_error_func = function () use ( $e ) {
		$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Html minify', $e->getMessage(), $e->getCode() );
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action( 'admin_notices', $whtml_plugin_error_func );
	add_action( 'network_admin_notices', $whtml_plugin_error_func );
}
// @formatter:on