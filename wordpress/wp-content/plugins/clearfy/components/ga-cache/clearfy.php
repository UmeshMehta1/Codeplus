<?php
/**
 * Этот файл инициализирует этот плагин, как аддон для плагина Clearfy.
 *
 * Файл будет подключен только в плагине Clearfy, используя особый вариант загрузки. Это более простое решение
 * пришло на смену встроенной системы подключения аддонов в фреймворке.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

if( !defined('WGA_PLUGIN_ACTIVE') ) {
	define('WGA_PLUGIN_VERSION', '3.2.5');
	define('WGA_TEXT_DOMAIN', 'simple-google-analytics');
	define('WGA_PLUGIN_ACTIVE', true);

	// Этот плагин загружен, как аддон для плагина Clearfy
	define('LOADING_GA_CACHE_AS_ADDON', true);

	if( !defined('WGA_PLUGIN_DIR') ) {
		define('WGA_PLUGIN_DIR', dirname(__FILE__));
	}

	if( !defined('WGA_PLUGIN_BASE') ) {
		define('WGA_PLUGIN_BASE', plugin_basename(__FILE__));
	}

	if( !defined('WGA_PLUGIN_URL') ) {
		define('WGA_PLUGIN_URL', plugins_url(null, __FILE__));
	}

	if( !defined('WGA_PLUGIN_CACHE_FOLDER') ) {
		define('WGA_PLUGIN_CACHE_FOLDER', 'wga-cache');
	}

	try {
		// Global scripts
		require_once(WGA_PLUGIN_DIR . '/includes/3rd-party/class-clearfy-plugin.php');
		new WGA_Plugin();
	} catch( Exception $e ) {
		$wga_plugin_error_func = function () use ($e) {
			$error = sprintf("The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Local Google Analytic', $e->getMessage(), $e->getCode());
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action('admin_notices', $wga_plugin_error_func);
		add_action('network_admin_notices', $wga_plugin_error_func);
	}
}


