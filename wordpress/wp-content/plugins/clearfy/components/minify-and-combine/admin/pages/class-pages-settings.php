<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * The page Settings.
 *
 * @since 1.0.0
 */
class WMAC_MinifyAndCombineSettingsPage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "minify_and_combine"; // Уникальный идентификатор страницы

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-testimonial'; // Иконка для закладки страницы, дашикон

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_parent_page = "performance"; // Уникальный идентификатор родительской страницы

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $available_for_multisite = true;

	/**
	 * {@inheritDoc}
	 *
	 * @since  1.1.0
	 * @var bool
	 */
	public $show_right_sidebar_in_options = true;

	/**
	 * WMAC_MinifyAndCombineSettingsPage constructor.
	 *
	 * @param \Wbcr_Factory463_Plugin $plugin
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 */
	public function __construct(Wbcr_Factory463_Plugin $plugin)
	{
		$this->menu_title = __('Optimize CSS & JS', 'minify-and-combine');
		$this->page_menu_short_description = __('Optimize CSS & JS', 'minify-and-combine');

		if( !defined('LOADING_MINIFY_AND_COMBINE_AS_ADDON') ) {
			$this->internal = false;
			$this->menu_target = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
			$this->page_parent_page = null;
		}

		parent::__construct($plugin);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string|void
	 * @since  1.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function getMenuTitle()
	{
		return defined('LOADING_MINIFY_AND_COMBINE_AS_ADDON') ? __('Optimize CSS & JS', 'minify-and-combine') : __('General', 'minify-and-combine');
	}

	/**
	 * Requests assets (js and css) for the page.
	 *
	 * @param Wbcr_Factory463_ScriptList $scripts
	 * @param Wbcr_Factory463_StyleList $styles
	 *
	 * @return void
	 * @see Wbcr_FactoryPages463_AdminPage
	 *
	 */
	public function assets($scripts, $styles)
	{
		parent::assets($scripts, $styles);

		$this->styles->add(WMAC_PLUGIN_URL . '/admin/assets/css/settings.css');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param $notices
	 *
	 * @return array
	 */
	public function getActionNotices($notices)
	{
		$notices[] = [
			'conditions' => [
				'wbcr_mac_clear_cache_success' => 1
			],
			'type' => 'success',
			'message' => __('The cache has been successfully cleaned.', 'minify-and-combine')
		];

		return $notices;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed[]
	 * @since 1.0.0
	 */
	public function getPageOptions()
	{
		$options = [];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('Misc Options', 'minify-and-combine') . '</strong><p></p></div>'
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'optimize_scripts_for_logged',
			'title' => __('Also optimize JS/CSS for logged in editors/administrators?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
			'hint' => __('By default plugin is also active for logged on editors/administrators, uncheck this option if you don\'t want Autoptimize to optimize when logged in e.g. to use a pagebuilder.', 'minify-and-combine'),
			'default' => false
		];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('Optimize CSS', 'minify-and-combine') . '</strong><p></p></div>'
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'css_optimize',
			'title' => __('Optimize CSS Code?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'green'],
			'hint' => __('If your scripts break because of a JS-error, you might want to try this.', 'minify-and-combine'),
			'default' => false,
			'eventsOn' => [
				'show' => '#wbcr-clr-optimize-css-fields'
			],
			'eventsOff' => [
				'hide' => '#wbcr-clr-optimize-css-fields'
			]
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'css_aggregate',
			'title' => __('Aggregate CSS-files?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'red'],
			'hint' => __('Aggregate all linked CSS-files? If this option is off, the individual CSS-files will remain in place but will be minified.', 'minify-and-combine'),
			'default' => false,
			'eventsOn' => [
				'show' => '#wbcr-mac-optimization-danger-message-4'
			],
			'eventsOff' => [
				'hide' => '#wbcr-mac-optimization-danger-message-4'
			]
		];

		$options[] = [
			'type' => 'html',
			'html' => [$this, 'optimizationDangerMessage4']
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'remove_style_version',
			'title' => __('Remove Version from Stylesheet', 'clearfy') . ' <span class="wbcr-clearfy-recomended-text">(' . __('Recommended', 'clearfy') . ')</span>',
			'layout' => ['hint-type' => 'icon'],
			'hint' => __('To make it more difficult for others to hack your website you can remove the WordPress version number from your site, your css and js. Without that number it\'s not possible to see if you run not the current version to exploit bugs from the older versions. <br><br>
					Additionally it can improve the loading speed of your site, because without query strings in the URL the css and js files can be cached.', 'clearfy') . '<br><br><b>Clearfy: </b>' . __('Removes the wordpress version number from stylesheets (not logged in user only).', 'clearfy'),
			'default' => false,
			'eventsOn' => array(
				'show' => '.factory-control-disable_remove_style_version_for_auth_users'
			),
			'eventsOff' => array(
				'hide' => '.factory-control-disable_remove_style_version_for_auth_users'
			)
		];

		$options[] = [
			'type' => 'textarea',
			'name' => 'css_exclude',
			'title' => __('Exclude CSS from Мinify And Combine:', 'minify-and-combine'),
			//'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'grey'),
			'hint' => __('A comma-separated list of CSS you want to exclude from being optimized.', 'minify-and-combine'),
			'default' => 'wp-content/cache/, wp-content/uploads/, admin-bar.min.css, dashicons.min.css'
		];

		$options[] = [
			'type' => 'more-link',
			'name' => 'css-group',
			'title' => __('Advanced options', 'clearfy'),
			'count' => 6,
			'items' => [
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'css_include_inline',
					'title' => __('Also aggregate inline CSS?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Check this option for Мinify And Combine to also aggregate CSS in the HTML.', 'minify-and-combine'),
					'default' => false,
					'eventsOn' => [
						'show' => '#wbcr-mac-optimization-danger-message-5'
					],
					'eventsOff' => [
						'hide' => '#wbcr-mac-optimization-danger-message-5'
					]
				],

				[
					'type' => 'html',
					'html' => [$this, 'optimizationDangerMessage5']
				],

				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'css_datauris',
					'title' => __('Generate data: URIs for images?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Enable this to include small background-images in the CSS itself instead of as separate downloads.', 'minify-and-combine'),
					'default' => false
				],

				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'css_defer',
					'title' => __('Inline and Defer CSS?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Inline "above the fold CSS" while loading the main auto optimized CSS only after page load. Check the FAQ for more info.
This can be fully automated for different types of pages with the Мinify And Combine CriticalCSS Power-Up.', 'minify-and-combine'),
					'default' => false
				],

				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'css_inline',
					'title' => __('Inline all CSS?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Inlining all CSS can improve performance for sites with a low pageviews/ visitor-rate, but may slow down performance otherwise.', 'minify-and-combine'),
					'default' => false
				],

				/*[
					'type' => 'html',
					'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('Critical CSS', 'minify-and-combine') . '</strong><p></p></div>'
				],*/

				[
					'type' => 'textarea',
					'name' => 'css_critical',
					'title' => __('Critical CSS files:', 'minify-and-combine'),
					'hint' => __('A comma-separated list of Critical CSS files. (You can use the * mask in file names. * - these are any characters.)', 'minify-and-combine'),
					'htmlAttrs' => ['placeholder'=>'style.css, themes/*/style.css, style.min.css, themes/*/style.min.css'],
					'default' => ''
				],

				[
					'type' => 'textarea',
					'name' => 'css_critical_style',
					'title' => __('Critical CSS code:', 'minify-and-combine'),
					'hint' => htmlspecialchars(__('Add critical CSS here. We will insert it into <style> tags in your <head> section of each page.', 'minify-and-combine')),
					'default' => ''
				]

			]
		];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('Optimize JavaScript', 'minify-and-combine') . '</strong><p></p></div>'
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'js_optimize',
			'title' => __('Optimize JavaScript Code?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'green'],
			'hint' => __('Minify JavaScript removes whitespace and comments to reduce the file size.', 'minify-and-combine'),
			'default' => false,
			'eventsOn' => [
				'show' => '#wbcr-mac-optimize-js-fields,#wbcr-mac-optimization-danger-message-1'
			],
			'eventsOff' => [
				'hide' => '#wbcr-mac-optimize-js-fields,#wbcr-mac-optimization-danger-message-1'
			]
		];

		$mtf_checkbox = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'move_js_to_footer',
			'title' => __('Move js to footer?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
			//'hint' => __('Optimize JavaScript Code.', 'minify-and-combine'),
			'default' => false,
			'eventsOn' => [
				'show' => '.factory-control-dont_move_jquery_to_footer, .factory-control-excluded_move_to_footer_scripts'
			],
			'eventsOff' => [
				'hide' => '.factory-control-dont_move_jquery_to_footer, .factory-control-excluded_move_to_footer_scripts'
			]
		];
		if( !($this->plugin->has_premium() && $this->plugin->premium->is_activate()) ) {
			$mtf_checkbox['value'] = false;
			$mtf_checkbox['cssClass'] = ['factory-checkbox-disabled wbcr-factory-clearfy-icon-pro'];
		}

		/*$options[] = [
			'type' => 'html',
			'html' => [$this, 'move_to_footer_js_options']
		];*/

		/*$options[] = array(
			'type' => 'html',
			'html' => array( $this, 'optimizationDangerMessage1' )
		);*/

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'js_aggregate',
			'title' => __('Aggregate JS-files?', 'minify-and-combine'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'red'],
			'hint' => __('Aggregate all linked JS-files to have them loaded non-render blocking? If this option is off, the individual JS-files will remain in place but will be minified.', 'minify-and-combine'),
			'default' => false,
			'eventsOn' => [
				'show' => '#wbcr-mac-optimization-danger-message-2'
			],
			'eventsOff' => [
				'hide' => '#wbcr-mac-optimization-danger-message-2'
			]
		];

		$options[] = [
			'type' => 'html',
			'html' => [$this, 'optimizationDangerMessage2']
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'remove_js_version',
			'title' => __('Remove Version from Script', 'clearfy') . ' <span class="wbcr-clearfy-recomended-text">(' . __('Recommended', 'clearfy') . ')</span>',
			'layout' => ['hint-type' => 'icon'],
			'hint' => __('To make it more difficult for others to hack your website you can remove the WordPress version number from your site, your css and js. Without that number it\'s not possible to see if you run not the current version to exploit bugs from the older versions. <br><br>
					Additionally it can improve the loading speed of your site, because without query strings in the URL the css and js files can be cached.', 'clearfy') . '<br><br><b>Clearfy: </b>' . __('Removes wordpress version number from scripts (not logged in user only).', 'clearfy'),
			'default' => false
		];

		$options[] = [
			'type' => 'textarea',
			'name' => 'js_exclude',
			'title' => __('Exclude scripts from Мinify And Combine:', 'minify-and-combine'),
			//'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'grey'),
			'hint' => __('A comma-separated list of scripts you want to exclude from being optimized, for example \'whatever.js, another.js\' (without the quotes) to exclude those scripts from being aggregated and minimized by Мinify And Combine.', 'minify-and-combine'),
			'default' => 'seal.js, js/jquery/jquery.js'
		];

		/*$options[] = [
			'type' => 'div',
			'id' => 'wbcr-mac-optimize-js-fields',
			'items' => $js_options
		];*/

		$options[] = [
			'type' => 'more-link',
			'name' => 'js-group',
			'title' => __('Advanced options', 'clearfy'),
			'count' => 4,
			'items' => [
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'js_include_inline',
					'title' => __('Also aggregate inline JS?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Let Мinify And Combine also extract JS from the HTML. Warning: this can make Мinify And Combine\'s cache size grow quickly, so only enable this if you know what you\'re doing.', 'minify-and-combine'),
					'default' => false,
					'eventsOn' => [
						'show' => '#wbcr-mac-optimization-danger-message-3'
					],
					'eventsOff' => [
						'hide' => '#wbcr-mac-optimization-danger-message-3'
					]
				],

				[
					'type' => 'html',
					'html' => [$this, 'optimizationDangerMessage3']
				],
				$mtf_checkbox,
				[
					'type' => 'checkbox',
					'name' => 'dont_move_jquery_to_footer',
					'title' => "",
					'hint' => __("Don't move jQuery to footer", 'minify-and-combine'),
					'default' => true
				],
				[
					'type' => 'textarea',
					'name' => 'excluded_move_to_footer_scripts',
					'title' => "",
					'hint' => __("A comma-separated list of scripts you want to exclude from being optimized, for example 'whatever.js, another.js' (without the quotes) to exclude those scripts from being aggregated and minimized by Мinify And Combine.", 'minify-and-combine'),
					'default' => ''
				],
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'js_forcehead',
					'title' => __('Force JavaScript in &lt;head&gt;?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Load JavaScript early, this can potentially fix some JS-errors, but makes the JS render blocking.', 'minify-and-combine'),
					'default' => false
				],
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'js_trycatch',
					'title' => __('Add try-catch wrapping?', 'minify-and-combine'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('If your scripts break because of a JS-error, you might want to try this.', 'minify-and-combine'),
					'default' => false
				]
			]
		];

		/*$options[] = [
			'type' => 'div',
			'id' => 'wbcr-clr-optimize-css-fields',
			'items' => $css_options
		];*/

		/*$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('JS & CSS files cache details', 'minify-and-combine') . '</strong><p></p></div>'
		];*/

		// Произвольный html код
		/*$options[] = [
			'type' => 'html',
			'html' => [$this, 'cacheInfo']
		];*/

		$formOptions = [];

		$formOptions[] = [
			'type' => 'form-group',
			'items' => apply_filters('wmac/pages/settings_form_options', $options),
			//'cssClass' => 'postbox'
		];

		/**
		 * @since 1.1.1 - является устаревшим
		 */
		return wbcr_factory_463_apply_filters_deprecated('wbcr_mac_settings_form_options', [$formOptions], '1.1.1', 'wmac/pages/settings_form_options');
	}

	public function cacheInfo()
	{
		$is_network = is_network_admin();

		$cache = $is_network ? WMAC_PluginCache::getUsedCacheMultisite() : WMAC_PluginCache::getUsedCache();
		?>
		<div class="form-group">
			<label for="wbcr_mac_css_optimize" class="col-sm-4 control-label">
				Cache folder<?php echo $is_network ? 's' : '' ?>
			</label>
			<div class="control-group col-sm-8">
				<?php echo $is_network ? WP_CONTENT_DIR . WMAC_CACHE_CHILD_DIR . '[...]/' : WMAC_PluginCache::getCacheDir() ?>
			</div>
		</div>
		<div class="form-group">
			<label for="wbcr_mac_css_optimize" class="col-sm-4 control-label">
				Can we write?
			</label>
			<div class="control-group col-sm-8">
				Yes
			</div>
		</div>
		<div class="form-group">
			<label for="wbcr_mac_css_optimize" class="col-sm-4 control-label">
				Cached styles and scripts<?php echo $is_network ? ' (all sites)' : '' ?>
			</label>
			<div class="control-group col-sm-8">
				<?php if( $is_network ) : ?>
					<?php echo $cache['files'] ?> files, totalling <?php echo $cache['size'] ?> (calculated
					at <?php echo gmdate('H:i') ?> UTC)
				<?php else: ?>
					<?php echo $cache['percent'] . '%, ' . $cache['files'] ?> files,
					totalling <?php echo $cache['size'] ?> (calculated at <?php echo gmdate('H:i') ?> UTC)
				<?php endif; ?>
			</div>
		</div>
		<div class="form-group">
			<label for="wbcr_mac_css_optimize" class="col-sm-4 control-label">
			</label>
			<div class="control-group col-sm-8">
				<a class="btn btn-default"
				   href="<?= wp_nonce_url($this->getActionUrl('clear-cache'), 'clear_cache_' . $this->getResultId()); ?>">
					<?php _e('Clear cache', 'minify-and-combine') ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds an html warning notification html markup.
	 *
	 * @param int $selector_id
	 */
	public function optimizationDangerMessage($selector_id = 1)
	{
		?>
		<div class="form-group">
			<label class="col-sm-4 control-label"></label>
			<div class="control-group col-sm-8">
				<div id="wbcr-mac-optimization-danger-message-<?= $selector_id ?>" class="wbcr-clearfy-danger-message">
					<?php _e('<b>This could break things!</b><br>If you notice any errors on your website after having activated this setting, just deactivate it again, and your site will be back to normal.', 'clearfy') ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function optimizationDangerMessage1()
	{
		$this->optimizationDangerMessage(1);
	}

	public function optimizationDangerMessage2()
	{
		$this->optimizationDangerMessage(2);
	}

	public function optimizationDangerMessage3()
	{
		$this->optimizationDangerMessage(3);
	}

	public function optimizationDangerMessage4()
	{
		$this->optimizationDangerMessage(4);
	}

	public function optimizationDangerMessage5()
	{
		$this->optimizationDangerMessage(5);
	}


	/**
	 * Действие rollback
	 * Если мы перейдем по ссылке, которую мы создали для кнопки "Восстановить" для метода rollbackButton,
	 * То выполнится это действие
	 */
	public function clearCacheAction()
	{
		check_admin_referer('clear_cache_' . $this->getResultId());

		if( is_network_admin() ) {
			WMAC_PluginCache::clearAllMultisite();
		} else {
			WMAC_PluginCache::clearAll();
		}

		// редирект с выводом уведомления
		$this->redirectToAction('index', ['wbcr_mac_clear_cache_success' => 1]);
	}
}