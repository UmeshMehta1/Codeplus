<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Assets manager base class
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 05.11.2017, Webcraftic
 * @version       1.0
 */
class WGZ_Assets_Manager_Public {

	/**
	 * Stores list of all available assets (used in rendering panel)
	 *
	 * @var array
	 */
	public $collection = [];

	public $template_rendered = false;

	private $deregistered = [];

	/**
	 * @param Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct(Wbcr_Factory463_Plugin $plugin)
	{
		$this->plugin = $plugin;

		$this->register_hooks();
	}

	/**
	 * Check user permissions
	 *
	 * User must have administrator or super administrator permissions to use the plugin.
	 *
	 * @return bool
	 * @since  1.1.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	protected function is_user_can()
	{
		return current_user_can('manage_options') || current_user_can('manage_network');
	}

	/**
	 * Initilize entire machine
	 */
	protected function register_hooks()
	{
		if( $this->plugin->getPopulateOption('disable_assets_manager', false) ) {
			return;
		}

		add_action('wp_enqueue_scripts', function () {
			$settings = $this->get_settings();

			foreach($settings as $key => $group) {
				if( "plugins" === $key ) {
					foreach($group as $plugin) {
						if( isset($plugin['js']) ) {
							$this->move_to_footer_js($plugin['js']);
						}
						if( isset($plugin['css']) ) {
							$this->move_to_footer_css($plugin['css']);
						}
					}
				} else if( in_array($key, ['misc', 'theme']) ) {
					if( isset($group['js']) ) {
						$this->move_to_footer_js($group['js']);
					}
					if( isset($group['css']) ) {
						$this->move_to_footer_css($group['css']);
					}
				}
			}
		}, 999);

		add_action('get_footer', function () {
			foreach($this->deregistered as $style) {
				wp_enqueue_style($style->handle);
			}
		});

		$on_frontend = $this->plugin->getPopulateOption('disable_assets_manager_on_front');
		$on_backend = $this->plugin->getPopulateOption('disable_assets_manager_on_backend', true);
		$is_panel = $this->plugin->getPopulateOption('disable_assets_manager_panel');

		if( (!is_admin() && !$on_frontend) || (is_admin() && !$on_backend) ) {
			add_filter('script_loader_src', [$this, 'filter_load_assets'], 10, 2);
			add_filter('style_loader_src', [$this, 'filter_load_assets'], 10, 2);
		}

		if( !$is_panel && ((is_admin() && !$on_backend) || (!is_admin() && !$on_frontend)) ) {
			if( !is_admin() ) {
				add_action('wp_enqueue_scripts', [$this, 'enqueue_plugin_scripts'], -100001);
				add_action('wp_footer', [$this, 'assets_manager_render_template'], 100001);
			} else {
				add_action('admin_enqueue_scripts', [$this, 'enqueue_plugin_scripts'], -100001);
				add_action('admin_footer', [$this, 'assets_manager_render_template'], 100001);
			}

			add_action('wam/views/safe_mode_checkbox', [$this, 'print_save_mode_fake_checkbox']);
			add_action('wam/views/assets/handle_column/after_url', [$this, 'print_move_to_footer_fake_checkbox']);
		}

		if( !is_admin() && !$on_frontend ) {
			add_action('wp_head', [$this, 'collect_assets'], 10000);
			add_action('wp_footer', [$this, 'collect_assets'], 10000);
		}

		if( is_admin() && !$on_backend ) {
			add_action('admin_head', [$this, 'collect_assets'], 10000);
			add_action('admin_footer', [$this, 'collect_assets'], 10000);
		}

		if( !$is_panel && ((is_admin() && !$on_backend) || (!is_admin() && !$on_frontend)) ) {
			if( defined('LOADING_ASSETS_MANAGER_AS_ADDON') ) {
				add_action('wbcr/clearfy/adminbar_menu_items', [$this, 'clearfy_admin_bar_menu_filter']);
			} else {
				add_action('admin_bar_menu', [$this, 'assets_manager_add_admin_bar_menu'], 1000);
			}
		}

		# Reset plugin settings and clean source code
		//add_action( 'template_redirect', [ $this, 'redirects' ], 9999 );
		add_action('init', [$this, 'redirects'], 9999);

		##Login/Logout
		add_action('wp_login', [$this, 'user_logged_in'], 99, 2);
		add_action('wp_logout', [$this, 'user_logged_out']);

		// Stop optimizing scripts and caching the asset manager page.
		//add_action( 'plugins_loaded', [ $this, 'stop_caching_and_script_optimize' ] );
		$this->stop_caching_and_script_optimize();

		// Disable autoptimize on Assets manager page
		add_filter('autoptimize_filter_noptimize', [$this, 'autoptimize_noptimize'], 10, 1);
		add_filter('wmac_filter_noptimize', [$this, 'autoptimize_noptimize'], 10, 1);

		if( wp_doing_ajax() ) {
			require_once WGZ_PLUGIN_DIR . '/admin/ajax/save-settings.php';
		}
	}


	protected function move_to_footer_css($css)
	{
		$wp_styles = wp_styles();

		if( !empty($css) ) {
			foreach((array)$css as $css_handler => $css) {
				if( isset($css['move_to_footer']) && "true" === $css['move_to_footer'] ) {
					if( $style = $wp_styles->query($css_handler, 'registered') ) {
						wp_dequeue_style($css_handler);
						//wp_deregister_style($css_handler);
						$this->deregistered[$css_handler] = $style;
					}

					// A hack to avoid tons of warnings the first time we calculate things.
					$wp_styles->groups[$css_handler] = 1;
				}
			}
		}
	}

	protected function move_to_footer_js($js)
	{
		$wp_scripts = wp_scripts();

		if( !empty($js) ) {
			foreach((array)$js as $js_handler => $js) {
				if( isset($js['move_to_footer']) && "true" === $js['move_to_footer'] ) {
					if( $script = $wp_scripts->query($js_handler, 'registered') ) {
						wp_deregister_script($js_handler);
						wp_register_script($script->handle, $script->src, $script->deps, $script->ver, true);
					}
				}
			}
		}
	}


	/**
	 * Render a fake checkbox to show for user, it is pro feature.
	 *
	 * @param array $data Temlate data
	 * @since  1.1
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function print_save_mode_fake_checkbox($data)
	{
		if( defined('WGZP_PLUGIN_ACTIVE') ) {
			return;
		}
		?>
		<label class="wam-float-panel__checkbox  wam-tooltip  wam-tooltip--bottom" data-tooltip="<?php _e('In test mode, you can experiment with disabling unused scripts safely for your site. The resources that you disabled will be visible only to you (the administrator), and all other users will receive an unoptimized version of the site, until you remove this tick', 'gonzales') ?>.">
			<input class="wam-float-panel__checkbox-input visually-hidden" type="checkbox"<?php checked($data['save_mode']) ?>>
			<span class="wam-float-panel__checkbox-text-premium"><?php _e('Safe mode <b>PRO</b>', 'gonzales') ?></span>
		</label>
		<?php
	}

	/**
	 * Render a fake checkbox to show for user, it is pro feature.
	 *
	 * @param array $data Temlate data
	 * @since  1.1
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function print_move_to_footer_fake_checkbox($item)
	{
		if( defined('WGZP_PLUGIN_ACTIVE') ) {
			return;
		}
		?>
		<div>
			<label class="wam-table__label-move-to-footer-premium" style="display: inline-block">
				<input type="checkbox" class="wam-checkbox wam-table__checkbox" disabled="disabled" <?php checked($item['move_to_footer']) ?>>
				<?php _e('Move to footer (PRO)', 'gonzales') ?>
				<i class="wam-help-hint wam-tooltip wam-tooltip--bottom" data-tooltip="<?php _e('This function will force a script or style from the header to the footer. This can fix problems of blocking page rendering.', 'gonzales') ?>"></i>
			</label>
		</div>
		<?php
	}


	/**
	 * Write cookie with user roles
	 *
	 * MU plugin will use cookie for identity user role. We can't use all wordpress
	 * features before full wp load.
	 *
	 * @param string $login
	 * @param string $user
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	public function user_logged_in($login, $user = null)
	{
		if( is_null($user) ) {
			$user = wp_get_current_user();
		}

		foreach($user->roles as $key => $role) {
			setcookie('wam_assigned_roles[' . $key . ']', $role, 0, "/");
		}
	}

	/**
	 * Delete cookie with user roles when user logged out
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	public function user_logged_out()
	{
		if( isset($_COOKIE['wam_assigned_roles']) && is_array($_COOKIE['wam_assigned_roles']) ) {
			foreach($_COOKIE['wam_assigned_roles'] as $key => $cookie_val) {
				setcookie('wam_assigned_roles[' . $key . ']', '', time() - 999999, "/");
			}
		}
	}

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
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.8
	 */
	public function stop_caching_and_script_optimize()
	{
		if( !isset($_GET['wbcr_assets_manager']) ) {
			return;
		}

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

	/**
	 * Disable autoptimize on Assets manager page
	 *
	 * @return bool
	 * @since  1.0.8
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function autoptimize_noptimize($result)
	{
		if( !isset($_GET['wbcr_assets_manager']) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Adds two actions "Reset options" and "Clean source code"
	 *
	 * The method will call in init action.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.1.0
	 */
	public function redirects()
	{
		if( !isset($_GET['wbcr_assets_manager']) || (defined('DOING_AJAX') && DOING_AJAX) ) {
			return;
		}

		$this->reset_plugin_settings_redirect();
		$this->clean_source_code();
	}

	/**
	 * Adds a link in Clearfy admin bar menu to go to the Assets manager
	 *
	 * @param array $menu_items Array links of Clearfy menu
	 *
	 * @return mixed
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.1.0
	 *
	 */
	public function clearfy_admin_bar_menu_filter($menu_items)
	{
		$current_url = esc_url(add_query_arg(['wbcr_assets_manager' => 1]));

		$menu_items['assets_manager_render_template'] = [
			'title' => '<span class="dashicons dashicons-list-view"></span> ' . __('Assets Manager', 'gonzales'),
			'href' => $current_url
		];

		return $menu_items;
	}

	/**
	 * Add Assets Manager menu to admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @since  1.1.0
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function assets_manager_add_admin_bar_menu($wp_admin_bar)
	{
		$current_url = esc_url(add_query_arg(['wbcr_assets_manager' => 1]));

		$args = [
			'id' => 'assets_manager_render_template',
			'title' => __('Assets Manager', 'gonzales'),
			'href' => $current_url
		];
		$wp_admin_bar->add_node($args);
	}

	/**
	 * Render Assets Manager view in the page body.
	 *
	 * This is callback function for admin_footer and wp_footer hooks.
	 *
	 * @throws \Exception
	 * @since  2.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function assets_manager_render_template()
	{
		if( !$this->is_user_can() || !isset($_GET['wbcr_assets_manager']) || $this->template_rendered ) {
			return;
		}

		$this->template_rendered = true;

		$settings = $this->get_settings();

		$views = new WGZ_Views(WGZ_PLUGIN_DIR);
		$views->print_template('assets-manager', [
			'save_mode' => isset($settings['save_mode']) ? (bool)$settings['save_mode'] : false,
			'collection' => $this->collection,
			'loaded_plugins' => $this->get_loaded_plugins(),
			'theme_assets' => $this->get_collected_assets('theme'),
			'misc_assets' => $this->get_collected_assets('misc'),
			'conditions_logic_params' => $this->get_conditions_login_params(true),
			'settings' => $settings
		]);

		$this->print_plugin_scripts();
	}

	/**
	 * Filter loaded assets by user conditions.
	 *
	 * If enabled save mode or onened Assets Manager panel, assets will not be filtered.     *
	 *
	 * @param string $src
	 * @param string $handle
	 *
	 * @return bool
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	public function filter_load_assets($src, $handle)
	{
		$settings = $this->get_settings();

		if( isset($_GET['wbcr_assets_manager']) || empty($settings) || (true === $settings['save_mode'] && !$this->is_user_can()) ) {
			return $src;
		}

		require_once WGZ_PLUGIN_DIR . '/includes/classes/class-check-conditions.php';

		$resource_type = (current_filter() == 'script_loader_src') ? 'js' : 'css';
		$resource_visability = "";

		if( !empty($settings['plugins']) ) {
			foreach((array)$settings['plugins'] as $plugin_name => $plugin) {
				if( !empty($plugin[$resource_type]) && isset($plugin[$resource_type][$handle]) ) {
					if( 'disable_assets' === $plugin['load_mode'] ) {
						$resource_visability = $plugin['visability'];
					} else if( 'disable_plugin' === $plugin['load_mode'] ) {
						return $src;
					} else {
						$resource_visability = $plugin[$resource_type][$handle]['visability'];
					}
					break;
				}
			}
		}

		foreach(['theme', 'misc'] as $group_name) {
			if( !empty($settings[$group_name]) && !empty($settings[$group_name][$resource_type]) && isset($settings[$group_name][$resource_type][$handle]) ) {
				$resource_visability = $settings[$group_name][$resource_type][$handle]['visability'];
				break;
			}
		}

		if( !empty($resource_visability) ) {
			$condition = new WGZ_Check_Conditions($resource_visability);
			if( $condition->validate() ) {
				return false;
			}
		}

		return $src;
	}

	/**
	 * Get information regarding used assets
	 *
	 * @return bool
	 */
	public function collect_assets()
	{
		if( !isset($_GET['wbcr_assets_manager']) || (defined('DOING_AJAX') && DOING_AJAX) ) {
			return false;
		}

		$denied = [
			'js' => ['wam-assets-manager', 'wam-assets-conditions', 'admin-bar', 'wam-pnotify'],
			'css' => [
				'wam-pnotify',
				'wbcr-clearfy-adminbar-styles',
				'wam-assets-conditions',
				'wam-assets-manager',
				'admin-bar',
				'dashicons'
			],
		];
		$denied = apply_filters('wbcr_gnz_denied_assets', $denied);

		/**
		 * Imitate full untouched list without dequeued assets
		 * Appends part of original table. Safe approach.
		 */
		$data_assets = [
			'js' => wp_scripts(),
			'css' => wp_styles(),
		];

		foreach($data_assets as $type => $data) {
			foreach($data->done as $el) {
				if( isset($data->registered[$el]) ) {

					if( !in_array($el, $denied[$type]) ) {
						if( isset($data->registered[$el]->src) ) {
							$url = $this->prepare_url($data->registered[$el]->src);
							$url_short = str_replace(get_home_url(), '', $url);

							if( false !== strpos($url, get_theme_root_uri()) ) {
								$resource_type = 'theme';
							} else if( false !== strpos($url, plugins_url()) ) {
								$resource_type = 'plugins';
							} else {
								$resource_type = 'misc';
							}

							$collection = &$this->collection[$resource_type];

							if( 'plugins' == $resource_type ) {
								$clean_url = str_replace(WP_PLUGIN_URL . '/', '', $url);
								$url_parts = explode('/', $clean_url);
								$resource_name = !empty($url_parts[0]) ? $url_parts[0] : null;

								if( empty($resource_name) ) {
									continue;
								}
								$collection = &$this->collection[$resource_type][$resource_name];
							}

							if( !isset($collection[$type][$el]) ) {
								$collection[$type][$el] = [
									'url_full' => $url,
									'url_short' => $url_short,
									//'state' => $this->get_visibility($type, $el),
									'size' => $this->get_asset_size($url),
									'ver' => $data->registered[$el]->ver,
									'deps' => (isset($data->registered[$el]->deps) ? $data->registered[$el]->deps : []),
								];

								# Deregister scripts, styles so that they do not conflict with assets managers.
								# ------------------------------------------------
								$no_js = [
									'jquery',
									'jquery-core',
									'jquery-migrate',
									'jquery-ui-core',
									'wam-jquery-core',
									'wam-jquery-migrate'
								];

								if( "js" == $type && !in_array($el, $no_js) ) {
									wp_deregister_script($el);
								}

								if( "css" == $type ) {
									wp_deregister_style($el);
								}
								#-------------------------------------------------
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Enqueue scripts and styles of the plugin
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	public function enqueue_plugin_scripts()
	{
		if( $this->is_user_can() && isset($_GET['wbcr_assets_manager']) ) {
			$plugin_ver = $this->plugin->getPluginVersion();

			wp_enqueue_style('wam-assets-manager', WGZ_PLUGIN_URL . '/assets/css/assets-manager.css', [], $plugin_ver);
			wp_enqueue_style('wam-assets-conditions', WGZ_PLUGIN_URL . '/assets/css/assets-conditions.css', [], $plugin_ver);
			wp_enqueue_style('wam-pnotify', WGZ_PLUGIN_URL . '/assets/css/PNotifyBrightTheme.css', [], $plugin_ver);
		}
	}

	/**
	 * Hardcode? Because, other plugins disable scripts or manipulate them.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	public function print_plugin_scripts()
	{
		$scope = 'frontend';

		if( $this->plugin->isNetworkActive() && $this->plugin->isNetworkAdmin() ) {
			$scope = 'networkadmin';
		} else if( is_admin() ) {
			$scope = 'admin';
		}
		?>
		<script>
			var wam_localize_data = <?php echo json_encode([
				'ajaxurl' => admin_url('admin-ajax.php', is_ssl() ? 'admin' : 'http'),
				'scope' => $scope,
				'i18n' => [
					'asset_canbe_required_title' => __('Warning', 'gonzales'),
					'asset_canbe_required_text' => __('The asset is required for %s. If you will disable the asset, other assets which in dependence on this asset also will disabled.', 'gonzales'),
					'reset_settings_warning_title' => __('Are you sure you want to reset all plugin settings?', 'gonzales'),
					'reset_settings_warning_text' => __('If you click OK, all conditions settings will be reset, including settings that you made on other pages and in the admin panel. ', 'gonzales')
				]
			]) ?>;
		</script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/libs/wam-jquery.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/libs/wam-jquery-migrate.min.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/libs/wam-pnotify.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/libs/wam-pnotify-confirm.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/libs/wam-pnotify-history.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/wam-assets-conditions.js'; ?>'></script>
		<script type='text/javascript' src='<?php echo WGZ_PLUGIN_URL . '/assets/js/wam-assets-manager.js'; ?>'></script>
		<?php
		do_action("wam/plugin_print_scripts");
	}

	/**
	 * We remove scripts and styles of themes, plugins to avoidE
	 * unnecessary conflicts during the use of the asset manager.
	 *
	 * todo: the method requires better study. Sorry, I don't have time for this.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.8
	 */
	private function clean_source_code()
	{
		ob_start(function ($html) {

			$raw_html = $html;

			$html = preg_replace([
				"'<\s*style.*?<\s*/\s*style\s*>'is",
			], [
				""
			], $html);

			$html = preg_replace_callback([
				"'<\s*link.*?>'is",
			], function ($matches) {
				$doc = new DOMDocument();
				$doc->loadHTML($matches[0]);
				$imageTags = $doc->getElementsByTagName('link');

				foreach($imageTags as $tag) {
					$src = $tag->getAttribute('href');

					$white_list_js = [
						'wp-includes/css/dashicons.min.css',
						'wp-includes/css/admin-bar.min.css',
						'assets/css/assets-manager.css',
						'assets/css/assets-conditions.css',
						'clearfy/assets/css/admin-bar.css',
						'assets/css/PNotifyBrightTheme.css'
					];

					if( !empty($src) ) {
						foreach($white_list_js as $js) {
							if( false !== strpos($src, $js) ) {
								return $matches[0];
							}
						}
					}

					return '';
				}
			}, $html);

			$html = preg_replace_callback([
				"'<\s*script.*?<\s*\/\s*script\s*>'is",
			], function ($matches) {
				if( false !== strpos($matches[0], 'wam_localize_data') ) {
					return $matches[0];
				}
				if( false !== strpos($matches[0], 'wam-conditions-builder-template') ) {
					return $matches[0];
				}

				$doc = new DOMDocument();
				$doc->loadHTML($matches[0]);
				$imageTags = $doc->getElementsByTagName('script');

				foreach($imageTags as $tag) {
					$src = $tag->getAttribute('src');

					$white_list_js = [
						'wam-jquery.js',
						'wam-jquery-migrate.min.js',
						'wp-includes/js/admin-bar.min.js',
						// --
						'assets/js/wam-assets-manager.js',
						'assets/js/wam-assets-manager-pro.js',
						'assets/js/wam-assets-conditions.js',
						// --
						'assets/js/libs/wam-pnotify.js',
						'assets/js/libs/wam-pnotify-confirm.js',
						'assets/js/libs/wam-pnotify-history.js',

					];

					if( !empty($src) ) {
						foreach($white_list_js as $js) {
							if( false !== strpos($src, $js) ) {
								return $matches[0];
							}
						}
					}

					return '';
				}
				//return $matches[0];
			}, $html);

			if( empty($html) ) {
				return $raw_html;
			}

			return $html;
		});
	}

	/**
	 * If exists GET var, the method make redirect to Assets Manager
	 *
	 * Before redirecting, the method will clear some options to completely reset settings.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	private function reset_plugin_settings_redirect()
	{
		// Reset settings
		if( isset($_GET['wbcr_assets_manager']) && isset($_GET['wam_reset_settings']) ) {
			check_admin_referer('wam_reset_settings');
			$this->plugin->updateOption('assets_states', []);
			$this->plugin->updateOption('backend_assets_states', []);

			if( $this->plugin->isNetworkActive() ) {
				$this->plugin->updateNetworkOption('backend_assets_states', []);
			}

			wp_redirect(esc_url_raw(remove_query_arg(['wam_reset_settings', '_wpnonce'])));
			die();
		}
	}

	/**
	 * @param string $type
	 *
	 * @return array
	 * @throws \Exception
	 * @since  2.0.0
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function get_collected_assets($type)
	{
		$assets = [];

		if( empty($this->collection) ) {
			return $assets;
		}

		foreach((array)$this->collection as $resource_type => $resources) {
			if( $type == $resource_type ) {
				$assets = $this->get_parsed_asset_settings($resources, $resource_type);
			}
		}

		return $assets;
	}

	private function get_asset_requires($handle)
	{
		$requires = [];

		if( empty($this->collection) ) {
			return $requires;
		}

		foreach((array)$this->collection as $resource_type => $resources) {
			if( empty($resources) ) {
				continue;
			}

			if( 'plugins' == $resource_type ) {
				foreach((array)$resources as $plugin_name => $plugin_types) {
					if( !empty($plugin_types) ) {
						foreach((array)$plugin_types as $plugin_type_name => $plugin_resources) {
							foreach((array)$plugin_resources as $plugin_resource_name => $plugin_resource) {
								if( !empty($plugin_resource['deps']) && in_array($handle, $plugin_resource['deps']) ) {
									$requires[] = '<a class="js-wam-require-handle-tag" data-tag-handle="' . esc_attr($plugin_resource_name . '-' . $plugin_type_name) . '" href="#">' . esc_attr($plugin_resource_name) . '</a>';
								}
							}
						}
					}
				}
			} else {
				foreach((array)$resources as $other_type_name => $other_resources) {
					foreach((array)$other_resources as $other_resource_name => $other_resource) {
						if( !empty($other_resource['deps']) && in_array($handle, $other_resource['deps']) ) {
							$requires[] = '<a class="js-wam-require-handle-tag" data-tag-handle="' . esc_attr($other_resource_name . '-' . $other_type_name) . '" href="#">' . esc_attr($other_resource_name) . '</a>';
						}
					}
				}
			}
		}

		return $requires;
	}

	/**
	 * Позволяет получить список плагинов, которые загружаются на странице
	 *
	 * Каждый элемент списка имеет собственные настройки, которые будут
	 * переданы в шаблон для печати.
	 *
	 * @return array
	 * @throws \Exception
	 * @since  2.0.0
	 */
	private function get_loaded_plugins()
	{
		$plugins = [];

		if( empty($this->collection) ) {
			return $plugins;
		}

		foreach((array)$this->collection as $resource_type => $resources) {
			foreach($resources as $resource_name => $types) {
				if( 'plugins' == $resource_type && !empty($resource_name) ) {
					$plugins[$resource_name]['name'] = $resource_name;
					$plugins[$resource_name]['info'] = $this->get_plugin_data($resource_name);
					$plugins[$resource_name]['assets'] = $this->get_parsed_asset_settings($types, 'plugins', $resource_name);
					$plugins[$resource_name]['load_mode'] = $this->get_parsed_plugin_settings($resource_name, 'load_mode');
					$plugins[$resource_name]['visability'] = $this->get_parsed_plugin_settings($resource_name, 'visability');
					$plugins[$resource_name]['select_control_classes'] = $this->get_parsed_plugin_settings($resource_name, 'select_control_classes');
					$plugins[$resource_name]['settings_button_classes'] = $this->get_parsed_plugin_settings($resource_name, 'settings_button_classes');
				}
			}
		}

		return $plugins;
	}

	/**
	 * Подготовка настроек плагина к выводу в шаблоне
	 *
	 * Устанавливаем ключи и значения по умолчанию или берем сохраненные
	 * значения из базы данных. Тем самым мы гарантируем, что в шаблоне
	 * всегда будет существовать используемый элемент массива из настроек
	 * плагина.
	 *
	 * @param string $plugin_name Имя плагина, для которого подготавливаются настройки
	 * @param null $setting_name Имя настройки, заполняется, если нужно извлечь только
	 *                               1 конкретную настройку
	 *
	 * @return array|mixed
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	private function get_parsed_plugin_settings($plugin_name, $setting_name = null)
	{
		$settings = $this->get_settings();
		$default_settings = [
			'load_mode' => 'enable',
			'visability' => "",
			'js' => [],
			'css' => [],
			'select_control_classes' => " js-wam-select--enable",
			'settings_button_classes' => " js-wam-button--hidden",
		];

		$settings_formated = $default_settings;

		if( !empty($settings['plugins']) && isset($settings['plugins'][$plugin_name]) ) {
			$plugin_settings = $settings['plugins'][$plugin_name];
			$settings_formated['load_mode'] = !empty($plugin_settings['load_mode']) ? $plugin_settings['load_mode'] : "enable";
			$settings_formated['visability'] = !empty($plugin_settings['visability']) ? stripslashes($plugin_settings['visability']) : "";
			$settings_formated['js'] = !empty($plugin_settings['js']) ? $plugin_settings['js'] : "";
			$settings_formated['css'] = !empty($plugin_settings['css']) ? $plugin_settings['css'] : "";

			if( "enable" === $settings_formated['load_mode'] ) {
				$settings_formated['select_control_classes'] = " js-wam-select--enable";
				$settings_formated['settings_button_classes'] = " js-wam-button--hidden";
			} else {
				$settings_formated['select_control_classes'] = " js-wam-select--disable";
				$settings_formated['settings_button_classes'] = "";
			}
		}

		if( $setting_name && isset($settings_formated[$setting_name]) ) {
			return $settings_formated[$setting_name];
		}

		return $settings_formated;
	}

	/**
	 * Подготовка настроек ресурсов к выводу в шаблоне
	 *
	 * Устанавливаем ключи и значения по умолчанию или берем сохраненные
	 * значения из базы данных. Тем самым мы гарантируем, что в шаблоне
	 * всегда будет существовать используемый элемент массива из настроек
	 * ресурсов.
	 *
	 * @param array $assets Массив с загружаемыми ресурсами, к которому будут
	 *                              добавлены настройки по умолчанию и сохраненные настройки
	 * @param string $plugin_name Имя плагина, если нужно сфокусироваться на группе ресурсов,
	 *                              которые относятся к определенному плагину
	 *
	 * @return array
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	private function get_parsed_asset_settings(array $assets, $group_name, $plugin_name = null)
	{
		$plugin_group = false;
		$settings_formated = [];
		$settings = $this->get_settings();

		if( !isset($assets['js']) ) {
			$assets['js'] = [];
		}
		if( !isset($assets['css']) ) {
			$assets['css'] = [];
		}

		if( !empty($settings[$group_name]) ) {
			if( !empty($plugin_name) ) {
				$settings = isset($settings[$group_name][$plugin_name]) ? $settings[$group_name][$plugin_name] : [];
				$plugin_group = true;
			} else if( 'plugins' !== $group_name ) {
				$settings = $settings[$group_name];
			}
		}

		foreach((array)$assets as $type => $resources) {
			$settings_formated[$type] = [];

			foreach((array)$resources as $name => $attrs) {
				$s = &$settings_formated[$type][$name];

				if( isset($settings[$type]) && isset($settings[$type][$name]) && !empty($settings[$type][$name]['visability']) ) {
					$s['load_mode'] = "disable";
					$s['visability'] = stripslashes($settings[$type][$name]['visability']);
				} else {
					if( $plugin_group ) {
						$plugin_load_mode = !empty($settings['load_mode']) ? $settings['load_mode'] : 'enable';

						$s['load_mode'] = "enable" === $plugin_load_mode ? 'enable' : 'disable';
					} else {
						$s['load_mode'] = "enable";
					}
					$s['visability'] = "";
				}

				if( isset($settings[$type]) && isset($settings[$type][$name]) && !empty($settings[$type][$name]['move_to_footer']) ) {
					$s['move_to_footer'] = "true" === $settings[$type][$name]['move_to_footer'];
				} else {
					$s['move_to_footer'] = false;
				}

				if( isset($settings[$type]) && isset($settings[$type][$name]) && !empty($settings[$type][$name]['dns_prefetch']) ) {
					$s['dns_prefetch'] = "true" === $settings[$type][$name]['dns_prefetch'];
				} else {
					$s['dns_prefetch'] = false;
				}

				if( 'disable' === $s['load_mode'] ) {
					$s['row_classes'] = " js-wam-table__tr--disabled-section";
					$s['select_control_classes'] = " js-wam-select--disable";
					$s['settings_button_classes'] = "";

					if( isset($plugin_load_mode) && 'enable' !== $plugin_load_mode ) {
						$s['settings_button_classes'] = " js-wam-button--hidden";
					}
				} else {
					$s['row_classes'] = "";
					$s['select_control_classes'] = " js-wam-select--enable";
					$s['settings_button_classes'] = " js-wam-button--hidden";
				}

				$s['requires'] = $this->get_asset_requires($name);
				$s = array_merge($s, $attrs);
			}
		}

		return $settings_formated;
	}

	/**
	 * Get plugin data from folder name
	 *
	 * @param $name
	 *
	 * @return array
	 */
	private function get_plugin_data($name)
	{
		$data = [];

		if( $name ) {
			if( !function_exists('get_plugins') ) {
				// подключим файл с функцией get_plugins()
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins = get_plugins();
			if( !empty($all_plugins) ) {
				foreach($all_plugins as $plugin_path => $plugin_data) {
					if( strpos($plugin_path, $name . '/') !== false ) {
						$data = $plugin_data;
						$data['basename'] = $plugin_path;
						break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Get all plugin settings in dependence on logic.
	 *
	 * If plugin loaded in admin area, the method will return settings for the admin area.     *
	 *
	 * @return array All plugin settings
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.1
	 */
	private function get_settings()
	{
		if( is_admin() ) {
			if( $this->plugin->isNetworkActive() && $this->plugin->isNetworkAdmin() ) {
				return $this->plugin->getNetworkOption('backend_assets_states', []);
			}

			return $this->plugin->getOption('backend_assets_states', []);
		}

		return $this->plugin->getOption('assets_states', []);
	}

	/**
	 * Exception for address starting from "//example.com" instead of
	 * "http://example.com". WooCommerce likes such a format
	 *
	 * @param string $url Incorrect URL.
	 *
	 * @return string      Correct URL.
	 */
	private function prepare_url($url)
	{
		if( isset($url[0]) && isset($url[1]) && '/' == $url[0] && '/' == $url[1] ) {
			$out = (is_ssl() ? 'https:' : 'http:') . $url;
		} else {
			$out = $url;
		}

		return $out;
	}

	/**
	 * Get current URL
	 *
	 * @return string
	 */
	private function get_current_url_path()
	{
		if( !is_admin() ) {
			$url = explode('?', $_SERVER['REQUEST_URI'], 2);
			if( strlen($url[0]) > 1 ) {
				$out = rtrim($url[0], '/');
			} else {
				$out = $url[0];
			}

			return "/" === $out ? "/" : untrailingslashit($out);
		}

		$removeble_args = array_merge(['wbcr_assets_manager'], wp_removable_query_args());

		$url = remove_query_arg($removeble_args, $_SERVER['REQUEST_URI']);

		return esc_url_raw(untrailingslashit($url));
	}

	/**
	 * Checks how heavy is file
	 *
	 * @param string $src URL.
	 *
	 * @return int    Size in KB.
	 */
	private function get_asset_size($src)
	{
		$weight = 0;

		$home = get_theme_root() . '/../..';
		$src = explode('?', $src);

		if( !filter_var($src[0], FILTER_VALIDATE_URL) === false && strpos($src[0], get_home_url()) === false ) {
			return 0;
		}

		$src_relative = $home . str_replace(get_home_url(), '', $this->prepare_url($src[0]));

		if( file_exists($src_relative) ) {
			$weight = round(filesize($src_relative) / 1024, 1);
		}

		return $weight;
	}

	private function get_conditions_login_params($group = false)
	{
		global $wp_roles, $wp;

		# Add User Roles
		#---------------------------------------------------------------
		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);
		$roles_param_values = [
			[
				'value' => 'guest',
				'title' => __('Guest', 'gonzales'),
			]
		];

		if( !empty($editable_roles) ) {
			foreach($editable_roles as $role_ID => $role) {
				$roles_param_values[] = ['value' => $role_ID, 'title' => $role['name']];
			}
		}

		# Add Post Types
		#---------------------------------------------------------------
		$post_types = get_post_types([
			'public' => true
		], 'objects');
		$post_types_param_values = [];

		if( !empty($post_types) ) {
			foreach($post_types as $type) {
				if( isset($type->name) ) {
					$post_types_param_values[] = ['value' => $type->name, 'title' => $type->label];
				}
			}
		}

		# Add Taxonomies
		#---------------------------------------------------------------
		$taxonomies = get_taxonomies([
			'public' => true
		], 'objects');
		$taxonomies_param_values = [];

		if( !empty($taxonomies) ) {
			foreach($taxonomies as $tax) {
				$taxonomies_param_values[] = ['value' => $tax->name, 'title' => $tax->label];
			}
		}

		$pro_label = !defined('WGZP_PLUGIN_ACTIVE') ? ' (Pro)' : '';

		$location_items = [
			[
				'id' => 'current-url',
				'title' => __('Current URL', 'gonzales'),
				'type' => 'default',
				'default_value' => $this->get_current_url_path(),
				'description' => __('Current Url', 'gonzales')
			],
			[
				'id' => 'query-string',
				'title' => __('Query string var', 'gonzales'),
				'type' => 'equals',
				'placeholder' => [__('Var name', 'gonzales'), __('Value', 'gonzales')],
				'description' => __('You can set this rule if the page url contains a query string. For example ?editor=classic&<b>type</b>=<b>float</b>. For example add the variable <b>"type"</b> to the first input field, and add <b>"float"</b> to the second input field. So field1: <b>type</b> = field2: <b>float</b>. Now, if you go to a page where there is a query string with the <b>"type"</b> variable equal to the <b>"float"</b> value, the script or style file will not be loaded.', 'gonzales')
			],
			[
				'id' => 'location-page',
				'title' => __('Custom URL', 'gonzales') . $pro_label,
				'type' => 'text',
				'description' => __('An URL of the current page where a user who views your website is located. If you use the equals operator, paste in field the request url without the query string. For example: "/my-page" or "/my-page/subpage". If you use the "Contains" operator, use the request url part. For example: "page" will match 2 in the pages "/my-page/subpage" and "/my-page".', 'gonzales'),
				'disabled' => !defined('WGZP_PLUGIN_ACTIVE')
			],
			[
				'id' => 'regular-expression',
				'title' => __('Regular Expression', 'gonzales') . $pro_label,
				'type' => 'regexp',
				'placeholder' => '^(about-page-[0-9]+|contacts-[0-9]{,2})',
				'description' => __('Regular expressions can be used by experts. This tool creates flexible conditions to disable the resource. For example, if you specify this expression: ^([A-z0-9]+-)?gifts? then the resource will be disabled at the following pages http://yoursite.test/get-gift/, http://yoursite.test/gift/, http://yoursite.test/get-gifts/, http://yoursite.test/gifts/. The plugin ignores the backslash at the beginning of the query string, so you can dismiss it. Check your regular expressions in here: https://regex101.com, this will prevent you from the mistakes. This feature is available at the paid version.', 'gonzales'),
				'disabled' => !defined('WGZP_PLUGIN_ACTIVE')
			]
		];

		if( !is_admin() ) {
			$location_items[] = [
				'id' => 'location-some-page',
				'title' => __('Page', 'gonzales'),
				'type' => 'select',
				'params' => [
					'Basic' => [
						[
							'value' => 'base_web',
							'title' => __('Entire Website', 'gonzales'),
						],
						[
							'value' => 'base_sing',
							'title' => __('All Singulars', 'gonzales'),
						],
						[
							'value' => 'base_arch',
							'title' => __('All Archives', 'gonzales'),
						],
					],
					'Special Pages' => [
						[
							'value' => 'spec_404',
							'title' => __('404 Page', 'gonzales')
						],
						[
							'value' => 'spec_search',
							'title' => __('Search Page', 'gonzales')
						],
						[
							'value' => 'spec_blog',
							'title' => __('Blog / Posts Page', 'gonzales')
						],
						[
							'value' => 'spec_front',
							'title' => __('Front Page', 'gonzales')
						],
						[
							'value' => 'spec_date',
							'title' => __('Date Archive', 'gonzales')
						],
						[
							'value' => 'spec_auth',
							'title' => __('Author Archive', 'gonzales')
						],
					],
					'Posts' => [
						[
							'value' => 'post_all',
							'title' => __('All Posts', 'gonzales')
						],
						[
							'value' => 'post_arch',
							'title' => __('All Posts Archive', 'gonzales')
						],
						[
							'value' => 'post_cat',
							'title' => __('All Categories Archive', 'gonzales')
						],
						[
							'value' => 'post_tag',
							'title' => __('All Tags Archive', 'gonzales')
						],
					],
					'Pages' => [
						[
							'value' => 'page_all',
							'title' => __('All Pages', 'gonzales')
						],
						[
							'value' => 'page_arch',
							'title' => __('All Pages Archive', 'gonzales')
						],
					],

				],
				'description' => __('List of specific pages.', 'gonzales')
			];
			$location_items[] = [
				'id' => 'location-post-type',
				'title' => __('Post type', 'gonzales'),
				'type' => 'select',
				'params' => $post_types_param_values,
				'description' => __('A post type of the current page.', 'gonzales'),
			];
			$location_items[] = [
				'id' => 'location-taxonomy',
				'title' => __('Taxonomy', 'gonzales'),
				'type' => 'select',
				'params' => $taxonomies_param_values,
				'description' => __('A taxonomy of the current page.', 'gonzales'),
			];
		} else {
			$location_items[] = [
				'id' => 'location-some-page',
				'title' => __('Page', 'gonzales'),
				'type' => 'select',
				'params' => [
					'Basic' => [
						[
							'value' => 'all_admin_area',
							'title' => __('All Admin Pages', 'gonzales'),
						],
						[
							'value' => 'posts_all',
							'title' => __('All Posts', 'gonzales')
						],
						[
							'value' => 'posts_add_new',
							'title' => __('Add New Post', 'gonzales')
						],
						[
							'value' => 'posts_taxonomies',
							'title' => __('All Taxonomies', 'gonzales')
						]
					],
					'Dashboard' => [
						[
							'value' => 'dashboard_home',
							'title' => __('Home', 'gonzales')
						],
						[
							'value' => 'dashboard_wordpress_updates',
							'title' => __('WordPress Updates', 'gonzales')
						],
					],
					'Media' => [
						[
							'value' => 'media_library',
							'title' => __('Library', 'gonzales')
						],
						[
							'value' => 'media_library_add_new',
							'title' => __('Add new', 'gonzales')
						]
					],
					'Appearance' => [
						[
							'value' => 'appearance_themes',
							'title' => __('Themes', 'gonzales')
						],
						[
							'value' => 'appearance_customize',
							'title' => __('Customize', 'gonzales')
						],
						[
							'value' => 'appearance_widgets',
							'title' => __('Widgets', 'gonzales')
						],
						[
							'value' => 'appearance_menus',
							'title' => __('Menus', 'gonzales')
						],
						[
							'value' => 'appearance_theme_editor',
							'title' => __('Theme Editor', 'gonzales')
						]
					],
					'Plugins' => [
						[
							'value' => 'plugins_installed',
							'title' => __('Installed Plugins', 'gonzales')
						],
						[
							'value' => 'plugins_add_new',
							'title' => __('Add New', 'gonzales')
						],
						[
							'value' => 'plugins_editor',
							'title' => __('Plugin Editor', 'gonzales')
						]
					],
					'Users' => [
						[
							'value' => 'users_all',
							'title' => __('All Users', 'gonzales')
						],
						[
							'value' => 'users_add_new',
							'title' => __('Add New', 'gonzales')
						],
						[
							'value' => 'users_your_profile',
							'title' => __('Your profile', 'gonzales')
						]
					],
					'Tools' => [
						[
							'value' => 'tools_available',
							'title' => __('Available Tools', 'gonzales')
						],
						[
							'value' => 'tools_import',
							'title' => __('Import', 'gonzales')
						],
						[
							'value' => 'tools_export',
							'title' => __('Export', 'gonzales')
						],
						[
							'value' => 'tools_site_health',
							'title' => __('Site Health', 'gonzales')
						],
						[
							'value' => 'tools_export_personal_data',
							'title' => __('Export Personal Data', 'gonzales')
						],
						[
							'value' => 'tools_erase_personal_data',
							'title' => __('Erase Personal Data', 'gonzales')
						]
					],
					'Settings' => [
						[
							'value' => 'settings_general',
							'title' => __('General', 'gonzales')
						],
						[
							'value' => 'settings_writing',
							'title' => __('Writing', 'gonzales')
						],
						[
							'value' => 'settings_reading',
							'title' => __('Reading', 'gonzales')
						],
						[
							'value' => 'settings_media',
							'title' => __('Media', 'gonzales')
						],
						[
							'value' => 'settings_permalinks',
							'title' => __('Permalinks', 'gonzales')
						],
						[
							'value' => 'settings_privacy',
							'title' => __('Privacy', 'gonzales')
						]
					],
				],
				'description' => __('List of specific pages.', 'gonzales')
			];
		}

		$grouped_filter_params = [
			[
				'id' => 'user',
				'title' => __('User', 'gonzales'),
				'items' => [
					[
						'id' => 'user-role',
						'title' => __('Role', 'gonzales') . $pro_label,
						'type' => 'select',
						'params' => $roles_param_values,
						'description' => __('A role of the user who views your website. The role "guest" is applied to unregistered users.', 'gonzales'),
						'disabled' => !defined('WGZP_PLUGIN_ACTIVE')
					],
					/*[
						'id'          => 'user-registered',
						'title'       => __( 'Registration Date', 'gonzales' ),
						'type'        => 'date',
						'description' => __( 'The date when the user who views your website was registered. For unregistered users this date always equals to 1 Jan 1970.', 'gonzales' )
					],*/
					[
						'id' => 'user-mobile',
						'title' => __('Mobile Device', 'gonzales') . $pro_label,
						'type' => 'select',
						'params' => [
							['value' => 'yes', 'title' => __('Yes', 'gonzales')],
							['value' => 'no', 'title' => __('No', 'gonzales')]
						],
						'description' => __('Determines whether the user views your website from mobile device or not.', 'gonzales'),
						'disabled' => !defined('WGZP_PLUGIN_ACTIVE')
					],
					[
						'id' => 'user-cookie-name',
						'title' => __('Cookie Name', 'gonzales') . $pro_label,
						'type' => 'text',
						'only_equals' => true,
						'description' => __('Determines whether the user\'s browser has a cookie with a given name.', 'gonzales'),
						'disabled' => !defined('WGZP_PLUGIN_ACTIVE')
					]
				]
			],
			[
				'id' => 'location',
				'title' => __('Location', 'gonzales'),
				'items' => $location_items
			]
		];

		$filterParams = [];
		foreach((array)$grouped_filter_params as $filter_group) {
			$filterParams = array_merge($filterParams, $filter_group['items']);
		}

		return $group ? $grouped_filter_params : $filterParams;
	}
}