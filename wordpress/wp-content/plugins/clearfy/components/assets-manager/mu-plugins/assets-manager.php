<?php
/**
 * Webcraftic AM plugin load filter
 * Dynamically activated only plugins that you have selected in each page. [Note]  Webcraftic AM has been automatically installed/deleted by Activate/Deactivate of "load filter plugin".
 * Version: 1.1.2
 * Framework Version: FACTORY_463_VERSION
 */

// todo: проверить, как работает кеширование
// todo: замерить, скорость работы этого решения

defined('ABSPATH') || exit;

/**
 * Stop optimizing scripts and caching the asset manager page.
 *
 * For some types of pages it is imperative to not be cached. Think of an e-commerce scenario:
 * when a customer enters checkout, they wouldn’t want to see a cached page with some previous
 * customer’s payment data.
 *
 * Elaborate plugins like WooCommerce (and many others) use the DONOTCACHEPAGE constant to let
 * caching plugins know about certain pages or endpoints that should not be cached in any case.
 * Accordingly, all popular caching plugins, including WP Rocket, support the constant and would
 * not cache a request for which DONOTCACHEPAGE is defined as true.
 */
if( isset($_GET['wbcr_assets_manager']) ) {
	//  Disable Query monitor plugin on the assets manager pages to avoid conflicts.
	if( !defined('QM_DISABLED') ) {
		define('QM_DISABLED', true);
	}

	//  Disable Cache Plugins
	if( !defined('DONOTCACHEPAGE') ) {
		define('DONOTCACHEPAGE', true);
	}

	if( !defined('DONOTCACHCEOBJECT') ) {
		define('DONOTCACHCEOBJECT', true);
	}

	if( !defined('DONOTMINIFY') ) {
		define('DONOTMINIFY', true);
	}

	if( !defined('DONOTROCKETOPTIMIZE') ) {
		define('DONOTROCKETOPTIMIZE', true);
	}

	if( !defined('DONOTMINIFYJS') ) {
		define('DONOTMINIFYJS', true);
	}

	if( !defined('DONOTASYNCCSS') ) {
		define('DONOTASYNCCSS', true);
	}

	if( !defined('DONOTMINIFYCSS') ) {
		define('DONOTMINIFYCSS', true);
	}

	if( !defined('WHM_DO_NOT_HIDE_WP') ) {
		define('WHM_DO_NOT_HIDE_WP', true);
	}
}

if( defined('WP_SETUP_CONFIG') || defined('WP_INSTALLING') || isset($_GET['wbcr_assets_manager']) ) {
	return;
}

// @formatter:off
//-------------------------------------------------------------------------------------------
// Plugins load filter
//-------------------------------------------------------------------------------------------

class WGNZ_Plugins_Loader {

	const DEFAULT_OPTIONS_PREFIX = 'wbcr_gnz_';
	const CLEARFY_OPTIONS_PREFIX = 'wbcr_clearfy_';

	protected $parent_plugin_dir;
	protected $settings;
	protected $active_plugins = array();

	public function __construct()
	{
		# We must always load the plugin if it is an ajax request, a cron
		# task or a rest api request. Otherwise, the user may have problems
		# with the work of plugins.
		if( $this->doing_ajax() || $this->doing_cron() || $this->doing_rest_api() ) {
			return;
		}

		$is_clearfy_active = false;

		$this->active_plugins = $this->get_active_plugins();

		add_filter('wam/conditions/call_method', [$this, 'check_conditions_method'], 10, 4);

		if( $this->is_active_clearfy() ) {
			$deactivate_components = $this->get_clearfy_deactivate_components();

			if( empty($deactivate_components) || !in_array('assets_manager', $deactivate_components) ) {
				$is_clearfy_active = true;
			}
		}

		$parent_plugin_dir = $this->get_parent_plugin_dir();

		if( empty($parent_plugin_dir) || !file_exists($parent_plugin_dir) ) {
			return;
		}

		# Disable plugins only if Asset Manager and Clearfy are activated
		if( $is_clearfy_active || $this->is_active_assets_manager_standalone() ) {
			$this->settings = $this->get_assets_manager_options();

			if( !empty($this->settings) ) {
				if( is_multisite() ) {
					add_filter('site_option_active_sitewide_plugins', array($this, 'disable_network_plugins'), 1);
				}

				add_filter('option_active_plugins', array($this, 'disable_plugins'), 1);
				add_filter('option_hack_file', array($this, 'hack_file_filter'), 1);
				add_action('plugins_loaded', array($this, 'remove_plugin_filters'), 1);
			}
		}
	}

	/**
	 * @param $hackFile
	 *
	 * @return mixed
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 *
	 */
	public function hack_file_filter($hackFile)
	{
		$this->remove_plugin_filters();

		return $hackFile;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	public function remove_plugin_filters()
	{
		remove_action('option_active_plugins', array($this, 'disable_plugins'), 1);
	}

	/**
	 * We control the disabling of plugins that are activated for the network.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	public function disable_network_plugins($plugins_list)
	{
		$new_plugin_list = $plugins_list;

		if( is_array($plugins_list) && !empty($plugins_list) ) {
			$temp_plugin_list = array_keys($plugins_list);
			$temp_plugin_list = $this->disable_plugins($temp_plugin_list);

			$new_plugin_list = array();
			foreach((array)$temp_plugin_list as $plugin_file) {
				$new_plugin_list[$plugin_file] = $plugins_list[$plugin_file];
			}
		}

		return $new_plugin_list;
	}

	/**
	 * We control the disabling of plugins that are activated for blog.
	 *
	 * @param $plugins_list
	 *
	 * @return mixed
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 *
	 */
	public function disable_plugins($plugins_list)
	{
		if( !is_array($plugins_list) || empty($plugins_list) ) {
			return $plugins_list;
		}

		foreach((array)$plugins_list as $key => $plugin_base) {
			if( $this->is_disabled_plugin($plugin_base) ) {
				unset($plugins_list[$key]);
			}
		}

		return $plugins_list;
	}

	/**
	 * Extra method for extend WGZ_Check_Conditions class.
	 *
	 * @param mixed $default
	 * @param string $method_name
	 * @param string $operator
	 * @param mixed $value
	 *
	 * @return mixed
	 * @since  1.0.7
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function check_conditions_method($default, $method_name, $operator, $value)
	{
		$premium_plugin_dir = $this->get_parent_premium_plugin_dir();

		if( $premium_plugin_dir && file_exists($premium_plugin_dir) ) {
			require_once $premium_plugin_dir . '/includes/class-check-conditions.php';
			if( class_exists('WGNZP_Check_Conditions') ) {
				$conditions = new WGNZP_Check_Conditions();

				if( method_exists($conditions, $method_name) ) {
					return $conditions->$method_name($operator, $value);
				}
			}
		}

		return $default;
	}

	/**
	 * Get a list of active plugins.
	 *
	 * @return array
	 * @since  1.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function get_active_plugins()
	{
		if( is_multisite() ) {
			$active_network_plugins = (array)get_site_option('active_sitewide_plugins');
			$active_network_plugins = array_keys($active_network_plugins);
			$active_blog_plugins = (array)get_option('active_plugins');

			return array_unique(array_merge($active_network_plugins, $active_blog_plugins));
		}

		return (array)get_option('active_plugins');
	}

	/**
	 * Determines whether the current plugin is disabled
	 *
	 * @param $plugin_base
	 *
	 * @return bool
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 *
	 */
	private function is_disabled_plugin($plugin_base)
	{

		$white_plgins_list = array(
			'clearfy', // prod
			'wp-plugin-clearfy', // dev
			'gonzales', // prod
			'wp-plugin-gonzales', // dev
			'clearfy_package' // premium package
		);

		$plugin_base_part = explode('/', $plugin_base);

		# If plugin base is incorrect or plugin name in the white list
		if( 2 !== sizeof($plugin_base_part) || in_array($plugin_base_part[0], $white_plgins_list) ) {
			return false;
		}

		if( !empty($this->settings['plugins']) && isset($this->settings['plugins'][$plugin_base_part[0]]) && 'disable_plugin' === $this->settings['plugins'][$plugin_base_part[0]]['load_mode'] ) {
			require_once $this->get_parent_plugin_dir() . '/includes/classes/class-check-conditions.php';

			if( !empty($this->settings['plugins'][$plugin_base_part[0]]['visability']) ) {
				$condition = new WGZ_Check_Conditions($this->settings['plugins'][$plugin_base_part[0]]['visability']);
				if( $condition->validate() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings
	 * Case #3: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in subfolders
	 *
	 * @author matzeeable https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
	 * @since  1.0.0
	 * @return boolean
	 */
	private function doing_rest_api()
	{
		$prefix = rest_get_url_prefix();

		$rest_route = isset($_GET['rest_route']) ? $_GET['rest_route'] : null;

		if( defined('REST_REQUEST') && REST_REQUEST // (#1)
			|| !is_null($rest_route) // (#2)
			&& strpos(trim($rest_route, '\\/'), $prefix, 0) === 0 ) {
			return true;
		}

		// (#3)
		$rest_url = wp_parse_url(site_url($prefix));
		$current_url = wp_parse_url(add_query_arg(array()));

		return strpos($current_url['path'], $rest_url['path'], 0) === 0;
	}

	/**
	 * Determines whether the current request is a WordPress Ajax request.
	 *
	 * @return bool True if it's a WordPress Ajax request, false otherwise.
	 * @since 1.0.0
	 */
	private function doing_ajax()
	{
		if( function_exists('wp_doing_ajax') ) {
			return wp_doing_ajax();
		}

		return defined('DOING_AJAX') && DOING_AJAX;
	}

	/**
	 * Determines whether the current request is a WordPress cron request.
	 *
	 * @return bool True if it's a WordPress cron request, false otherwise.
	 * @since 1.0.0
	 */
	private function doing_cron()
	{
		if( function_exists('wp_doing_cron') ) {
			return wp_doing_cron();
		}

		return defined('DOING_CRON') && DOING_CRON;
	}

	/**
	 * Is Clearfy plugin actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_clearfy()
	{
		return $this->is_active_clearfy_dev() || $this->is_active_clearfy_prod();
	}

	/**
	 * Is Clearfy Dev plugin actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_clearfy_dev()
	{
		return in_array('wp-plugin-clearfy/clearfy.php', $this->active_plugins);
	}

	/**
	 * Is Clearfy Prod plugin actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_clearfy_prod()
	{
		return in_array('clearfy/clearfy.php', $this->active_plugins);
	}

	/**
	 * Is Assets Manager standalone actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_assets_manager_standalone()
	{
		return $this->is_active_assets_manager_standalone_prod() || $this->is_active_assets_manager_standalone_dev();
	}

	/**
	 * Is Assets Manager standalone prod actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_assets_manager_standalone_prod()
	{
		return in_array('gonzales/gonzales.php', $this->active_plugins);
	}

	/**
	 * Is Assets Manager standalone dev actives?
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function is_active_assets_manager_standalone_dev()
	{
		return in_array('wp-plugin-gonzales/gonzales.php', $this->active_plugins);
	}

	/**
	 * Get options prefix
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function get_options_prefix()
	{
		if( $this->is_active_clearfy() ) {
			return self::CLEARFY_OPTIONS_PREFIX;
		}

		return self::DEFAULT_OPTIONS_PREFIX;
	}

	/**
	 * Get Clearfy deactivated components
	 *
	 * @return array|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function get_clearfy_deactivate_components()
	{
		if( is_multisite() ) {
			return get_site_option($this->get_options_prefix() . 'deactive_preinstall_components', array());
		}

		return get_option($this->get_options_prefix() . 'deactive_preinstall_components', array());
	}

	/**
	 * Get Assets Manager options
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function get_assets_manager_options()
	{
		if( is_multisite() && is_network_admin() ) {
			return get_site_option($this->get_options_prefix() . 'backend_assets_states', array());
		} else if( is_admin() ) {
			return get_option($this->get_options_prefix() . 'backend_assets_states', array());
		}

		return get_option($this->get_options_prefix() . 'assets_states', array());
	}

	/**
	 * Get parent plugin dir
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function get_parent_plugin_dir()
	{
		if( $this->is_active_clearfy() ) {
			if( $this->is_active_clearfy_dev() ) {
				return WP_PLUGIN_DIR . '/wp-plugin-clearfy/components/assets-manager/';
			}

			return WP_PLUGIN_DIR . '/clearfy/components/assets-manager/';
		} else if( $this->is_active_assets_manager_standalone() ) {
			if( $this->is_active_assets_manager_standalone_dev() ) {
				return WP_PLUGIN_DIR . '/wp-plugin-gonzales/';
			}

			return WP_PLUGIN_DIR . '/gonzales/';
		}

		return null;
	}

	/**
	 * Get premium plugin dir in dependence on environment
	 *
	 * @return string|null
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since 1.0.7
	 */
	private function get_parent_premium_plugin_dir()
	{
		$is_active_prod = in_array('clearfy_package/clearfy-package.php', $this->active_plugins);
		$is_active_dev = in_array('wp-plugin-clearfy-package/clearfy-package.php', $this->active_plugins);
		$is_active_stand_alone_prod = in_array('assets-manager-premium/assets-manager-premium.php', $this->active_plugins);
		$is_active_stand_alone_dev = in_array('wp-plugin-assets-manager-premium/assets-manager-premium.php', $this->active_plugins);

		if( $is_active_dev ) {
			$premium_plugin_dir = WP_PLUGIN_DIR . '/wp-plugin-clearfy-package/plugins/assets-manager-premium';
		} else if( $is_active_prod ) {
			$premium_plugin_dir = WP_PLUGIN_DIR . '/clearfy_package/plugins/assets-manager-premium';
		} else if( $is_active_stand_alone_prod ) {
			$premium_plugin_dir = WP_PLUGIN_DIR . '/assets-manager-premium/';
		} else if( $is_active_stand_alone_dev ) {
			$premium_plugin_dir = WP_PLUGIN_DIR . '/wp-plugin-assets-manager-premium/';
		} else {
			return null;
		}

		return wp_normalize_path($premium_plugin_dir);
	}
}

new WGNZ_Plugins_Loader();
// @formatter:on
