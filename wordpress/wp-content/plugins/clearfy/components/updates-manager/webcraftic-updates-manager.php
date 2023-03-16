<?php
/**
 * Plugin Name: Webcraftic Updates manager
 * Plugin URI: https://wordpress.org/plugins/webcraftic-updates-manager/
 * Description: Manage all your WordPress updates, automatic updates, logs, and loads more.
 * Author: Webcraftic <wordpress.webraftic@gmail.com>
 * Version: 1.1.5
 * Text Domain: webcraftic-updates-manager
 * Domain Path: /languages/
 * Author URI: https://webcraftic.com
 * Framework Version: FACTORY_463_VERSION
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Developers who contributions in the development plugin:
 *
 * Alexander Kovalev
 * ---------------------------------------------------------------------------------
 * Full plugin development.
 *
 * Email:         alex.kovalevv@gmail.com
 * Personal card: https://alexkovalevv.github.io
 * Personal repo: https://github.com/alexkovalevv
 * ---------------------------------------------------------------------------------
 */

/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */

require_once(dirname(__FILE__) . '/libs/factory/core/includes/class-factory-requirements.php');

// @formatter:off
$wupm_plugin_info = [
	'prefix' => 'wbcr_updates_manager_',//wbcr_upm_
	'plugin_name' => 'wbcr_updates_manager',
	'plugin_title' => __('Webcraftic Updates Manager', 'webcraftic-updates-manager'),

	// PLUGIN SUPPORT
	'support_details' => [
		'url' => 'https://webcraftic.com',
		'pages_map' => [
			'support' => 'support',           // {site}/support
			'docs' => 'docs'               // {site}/docs
		]
	],

	// PLUGIN SUBSCRIBE FORM
	'subscribe_widget' => true,
	'subscribe_settings' => ['group_id' => '105408901'],

	// PLUGIN ADVERTS
	'render_adverts' => true,
	'adverts_settings' => [
		'dashboard_widget' => true, // show dashboard widget (default: false)
		'right_sidebar' => true, // show adverts sidebar (default: false)
		'notice' => true, // show notice message (default: false)
	],

	// FRAMEWORK MODULES
	'load_factory_modules' => [
		['libs/factory/bootstrap', 'factory_bootstrap_464', 'admin'],
		['libs/factory/forms', 'factory_forms_460', 'admin'],
		['libs/factory/pages', 'factory_pages_463', 'admin'],
		['libs/factory/templates', 'factory_templates_113', 'all'],
		['libs/factory/adverts', 'factory_adverts_140', 'admin']
	]
];

$wupm_compatibility = new Wbcr_Factory463_Requirements(__FILE__, array_merge($wupm_plugin_info, [
	'plugin_already_activate' => defined('WUPM_PLUGIN_ACTIVE'),
	'required_php_version' => '5.4',
	'required_wp_version' => '4.2.0',
	'required_clearfy_check_component' => false
]));

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if( !$wupm_compatibility->check() ) {
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
define('WUPM_PLUGIN_ACTIVE', true);
define('WUPM_PLUGIN_VERSION', $wupm_compatibility->get_plugin_version());
define('WUPM_PLUGIN_DIR', dirname(__FILE__));
define('WUPM_PLUGIN_BASE', plugin_basename(__FILE__));
define('WUPM_PLUGIN_URL', plugins_url(null, __FILE__));



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once(WUPM_PLUGIN_DIR . '/libs/factory/core/boot.php');
require_once(WUPM_PLUGIN_DIR . '/includes/class-plugin.php');

try {
	new WUPM_Plugin(__FILE__, array_merge($wupm_plugin_info, [
		'plugin_version' => WUPM_PLUGIN_VERSION,
		'plugin_text_domain' => $wupm_compatibility->get_text_domain(),
	]));
} catch( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define('WUPM_PLUGIN_THROW_ERROR', true);

	$wupm_plugin_error_func = function () use ($e) {
		$error = sprintf("The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Updates manager', $e->getMessage(), $e->getCode());
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action('admin_notices', $wupm_plugin_error_func);
	add_action('network_admin_notices', $wupm_plugin_error_func);
}
// @formatter:on