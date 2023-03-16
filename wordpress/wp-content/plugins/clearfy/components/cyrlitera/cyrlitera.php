<?php
/**
 * Plugin Name: Webcraftic Cyrlitera – transliteration of links and file names
 * Plugin URI: https://webcraftic.com
 * Description: The plugin converts Cyrillic, Georgian links, filenames into Latin. It is necessary for correct work of WordPress plugins and improve links readability.
 * Author: Webcraftic <wordpress.webraftic@gmail.com>
 * Version: 1.1.6
 * Text Domain: cyrlitera
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
$wctr_plugin_info = array(
	'prefix' => 'wbcr_cyrlitera_',
	'plugin_name' => 'wbcr_cyrlitera',
	'plugin_title' => __('Webcraftic Cyrlitera', 'cyrlitera'),

	// PLUGIN SUPPORT
	'support_details' => array(
		'url' => 'https://webcraftic.com',
		'pages_map' => array(
			'support' => 'support',           // {site}/support
			'docs' => 'docs'               // {site}/docs
		)
	),

	// PLUGIN SUBSCRIBE FORM
	'subscribe_widget' => true,
	'subscribe_settings' => ['group_id' => '105408892'],

	// PLUGIN ADVERTS
	'render_adverts' => true,
	'adverts_settings' => array(
		'dashboard_widget' => true, // show dashboard widget (default: false)
		'right_sidebar' => true, // show adverts sidebar (default: false)
		'notice' => true, // show notice message (default: false)
	),

	// FRAMEWORK MODULES
	'load_factory_modules' => array(
		array('libs/factory/bootstrap', 'factory_bootstrap_464', 'admin'),
		array('libs/factory/forms', 'factory_forms_460', 'admin'),
		array('libs/factory/pages', 'factory_pages_463', 'admin'),
		array('libs/factory/templates', 'factory_templates_113', 'all'),
		array('libs/factory/adverts', 'factory_adverts_140', 'admin')
	)
);

$wctr_compatibility = new Wbcr_Factory463_Requirements(__FILE__, array_merge($wctr_plugin_info, array(
	'plugin_already_activate' => defined('WCTR_PLUGIN_ACTIVE'),
	'required_php_version' => '5.4',
	'required_wp_version' => '4.2.0',
	'required_clearfy_check_component' => false
)));

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if( !$wctr_compatibility->check() ) {
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
define('WCTR_PLUGIN_ACTIVE', true);
define('WCTR_PLUGIN_VERSION', $wctr_compatibility->get_plugin_version());
define('WCTR_PLUGIN_DIR', dirname(__FILE__));
define('WCTR_PLUGIN_BASE', plugin_basename(__FILE__));
define('WCTR_PLUGIN_URL', plugins_url(null, __FILE__));



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once(WCTR_PLUGIN_DIR . '/libs/factory/core/boot.php');
require_once(WCTR_PLUGIN_DIR . '/includes/class-helpers.php');
require_once(WCTR_PLUGIN_DIR . '/includes/class-plugin.php');

try {
	new WCTR_Plugin(__FILE__, array_merge($wctr_plugin_info, array(
		'plugin_version' => WCTR_PLUGIN_VERSION,
		'plugin_text_domain' => $wctr_compatibility->get_text_domain(),
	)));
} catch( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define('WCTR_PLUGIN_THROW_ERROR', true);

	$wctr_plugin_error_func = function () use ($e) {
		$error = sprintf("The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Cyrlitera', $e->getMessage(), $e->getCode());
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action('admin_notices', $wctr_plugin_error_func);
	add_action('network_admin_notices', $wctr_plugin_error_func);
}
// @formatter:on