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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WCM_PLUGIN_ACTIVE' ) ) {
	define( 'WCM_PLUGIN_VERSION', '1.1.6' );
	define( 'WCM_TEXT_DOMAIN', 'comments-plus' );
	define( 'WCM_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Clearfy
	define( 'LOADING_COMMENTS_PLUS_AS_ADDON', true );

	if ( ! defined( 'WCM_PLUGIN_DIR' ) ) {
		define( 'WCM_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WCM_PLUGIN_BASE' ) ) {
		define( 'WCM_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WCM_PLUGIN_URL' ) ) {
		define( 'WCM_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	try {
		// Global scripts
		require_once( WCM_PLUGIN_DIR . '/includes/3rd-party/class-clearfy-plugin.php' );
		new WCM_Plugin();
	} catch( Exception $e ) {
		$wcm_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Disable Comments', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $wcm_plugin_error_func );
		add_action( 'network_admin_notices', $wcm_plugin_error_func );
	}
}


