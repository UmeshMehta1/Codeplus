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

if ( ! defined( 'WMAC_PLUGIN_ACTIVE' ) ) {
	define( 'WMAC_PLUGIN_VERSION', '1.1.0' );
	define( 'WMAC_TEXT_DOMAIN', 'minify-and-combine' );
	define( 'WMAC_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Clearfy
	define( 'LOADING_MINIFY_AND_COMBINE_AS_ADDON', true );

	if ( ! defined( 'WMAC_PLUGIN_DIR' ) ) {
		define( 'WMAC_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WMAC_PLUGIN_BASE' ) ) {
		define( 'WMAC_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WMAC_PLUGIN_URL' ) ) {
		define( 'WMAC_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	try {
		// Global scripts
		require_once( WMAC_PLUGIN_DIR . '/includes/3rd-party/class-clearfy-plugin.php' );
		new WMAC_Plugin();
	} catch( Exception $e ) {
		$wmac_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Webcraftic Minify and Combine', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $wmac_plugin_error_func );
		add_action( 'network_admin_notices', $wmac_plugin_error_func );
	}
}


