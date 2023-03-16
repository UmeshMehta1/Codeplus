<?php
/**
 * Helpers functions
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 11.05.2019, Webcraftic
 * @version       1.0
 */

/**
 * Assets manager MU dynamically activated only plugins that you have selected in each page.
 * This method installs the MU plugin if it does not exist or its current version does not
 * match the current version.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.0.7
 */
function wbcr_gnz_deploy_mu_plugin() {
	if ( wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
		if ( ! file_exists( WPMU_PLUGIN_DIR . "/assets-manager.php" ) ) {
			@copy( WGZ_PLUGIN_DIR . '/mu-plugins/assets-manager.php', WPMU_PLUGIN_DIR . '/assets-manager.php' );
		} else {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$dp = get_plugin_data( WPMU_PLUGIN_DIR . "/assets-manager.php", false, false );
			$sp = get_plugin_data( WGZ_PLUGIN_DIR . '/mu-plugins/assets-manager.php', false, false );
			if ( version_compare( $dp['Version'], $sp['Version'], '!=' ) ) {
				@copy( WGZ_PLUGIN_DIR . '/mu-plugins/assets-manager.php', WPMU_PLUGIN_DIR . '/assets-manager.php' );
			}
		}
	}
}

/**
 * Remove MU plugin
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.0.7
 */
function wbcr_gnz_remove_mu_plugin() {
	if ( file_exists( WPMU_PLUGIN_DIR . "/assets-manager.php" ) ) {
		@unlink( WPMU_PLUGIN_DIR . '/assets-manager.php' );
	}
}
