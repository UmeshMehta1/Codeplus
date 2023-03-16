<?php
/**
 * Save settings ajax action
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 21.09.2019, Webcraftic
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Ajax action for save plugin settings.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  2.0.0
 */
function wam_save_settings_action()
{
	check_ajax_referer('wam_save_settigns');

	if( !current_user_can('manage_options') ) {
		wp_send_json_error([
			'error_message_title' => __('Save settings failed!', 'gonzales'),
			'error_message_content' => __('You don\'t have enough capability to edit this information.', 'gonzales')
		]);
	}

	$save_message_title = __('Settings saved successfully!', 'clearfy');
	$save_message_content = __('If you use test mode, do not forget to disable it. We also recommend that you flush the cache if you use caching plugins.', 'clearfy');
	$scope = WGZ_Plugin::app()->request->post('scope', 'frontend');
	$raw_updated_settings = WGZ_Plugin::app()->request->post('settings', [], true);

	if( !empty($raw_updated_settings) ) {
		if( 'networkadmin' === $scope ) {
			$settings = WGZ_Plugin::app()->getNetworkOption('backend_assets_states', []);
		} else if( 'admin' === $scope ) {
			$settings = WGZ_Plugin::app()->getOption('backend_assets_states', []);
		} else {
			$settings = WGZ_Plugin::app()->getOption('assets_states', []);
		}

		if( !defined('WGZP_PLUGIN_ACTIVE') || (is_array($settings) && !isset($settings['save_mode'])) ) {
			$settings['save_mode'] = false;
		}

		if( !empty($raw_updated_settings['plugins']) ) {
			foreach((array)$raw_updated_settings['plugins'] as $plugin_name => $plugin_group) {
				if( !empty($plugin_group['load_mode']) ) {
					if( 'enable' == $plugin_group['load_mode'] ) {
						$plugin_group['visability'] = "";
					} else {
						foreach(['js', 'css'] as $assets_type) {
							if( !empty($plugin_group[$assets_type]) ) {
								foreach($plugin_group[$assets_type] as $resource_handle => $resource_params) {
									$plugin_group[$assets_type][$resource_handle]['visability'] = "";
								}
							}
						}
					}
				}

				$settings['plugins'][$plugin_name] = $plugin_group;
			}
		}

		if( !empty($raw_updated_settings['theme']) ) {
			$settings['theme'] = $raw_updated_settings['theme'];
		}

		if( !empty($raw_updated_settings['misc']) ) {
			$settings['misc'] = $raw_updated_settings['misc'];
		}

		/**
		 * Filter run before save settings.
		 *
		 * @param array $settings
		 * @param array $raw_updated_settings
		 * @param string $scope
		 */
		$settings = apply_filters('wam/before_save_settings', $settings, $raw_updated_settings, $scope);

		if( 'networkadmin' === $scope ) {
			WGZ_Plugin::app()->updateNetworkOption('backend_assets_states', $settings);
		} else if( 'admin' === $scope ) {
			WGZ_Plugin::app()->updateOption('backend_assets_states', $settings);
		} else {
			WGZ_Plugin::app()->updateOption('assets_states', $settings);
		}

		// If mu  plugin does not exist, install it.
		wbcr_gnz_deploy_mu_plugin();

		// Flush cache for all cache plugins
		WBCR\Factory_Templates_113\Helpers::flushPageCache();
	}

	wp_send_json_success([
		'save_massage_title' => $save_message_title,
		'save_message_content' => $save_message_content
	]);
}

add_action('wp_ajax_nopriv_wam-save-settings', 'wam_save_settings_action');
add_action('wp_ajax_wam-save-settings', 'wam_save_settings_action');