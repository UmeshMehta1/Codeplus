<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Страница общих настроек для этого плагина.
 *
 * Может быть использована только, если этот плагин используется как отдельный плагин, а не как аддон
 * дя плагина Clearfy. Если плагин загружен, как аддон для Clearfy, эта страница не будет подключена.
 *
 * Поддерживает режим работы с мультисаймами. Вы можете увидеть эту страницу в панели настройки сети.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 *
 * @copyright (c) 2018 Webraftic Ltd
 */
class WGZ_AssetsManagerPage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * The id of the page in the admin menu.
	 *
	 * Mainly used to navigate between pages.
	 *
	 * @since 1.0.0
	 * @see   FactoryPages463_AdminPage
	 *
	 * @var string
	 */
	public $id = "gonzales";

	/**
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-image-filter';

	/**
	 * @var int
	 */
	public $page_menu_position = 95;

	/**
	 * Доступена для мультисайтов
	 *
	 * @var bool
	 */
	public $available_for_multisite = true;

	/**
	 * @param Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct(Wbcr_Factory463_Plugin $plugin)
	{
		$this->menu_title = __('Assets manager', 'gonzales');

		if( !defined('LOADING_ASSETS_MANAGER_AS_ADDON') ) {
			$this->internal = false;
			$this->menu_target = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
			$this->show_search_options_form = false;
		} else {
			$this->page_parent_page = 'performance';
		}

		parent::__construct($plugin);
	}

	/**
	 * Метод позволяет менять заголовок меню, в зависимости от сборки плагина.
	 *
	 * @return string|void
	 */
	public function getMenuTitle()
	{
		return defined('LOADING_ASSETS_MANAGER_AS_ADDON') ? __('General', 'hide-login-page') : __('Assets manager', 'gonzales');
	}

	/**
	 * @return string|void         *
	 */
	public function getPageTitle()
	{
		return defined('LOADING_ASSETS_MANAGER_AS_ADDON') ? __('Assets manager', 'gonzales') : __('General', 'hide-login-page');
	}

	/**
	 * Permalinks options.
	 *
	 * @return mixed[]
	 * @since 1.0.0
	 */
	public function getPageOptions()
	{
		$options = [];
		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __('Disable unused scripts, styles, and fonts', 'gonzales') . '</strong><p>' . __('There is a button in the adminbar called "Script Manager". If you click on it you will see a list of loaded scripts, styles and fonts on the current page of your site. If you think that one of the assets is superfluous on this page, you can disable it individually, so that it does not create unnecessary queries when page loading. Use the script manager very carefull to non-corrupt your website. We recommend to test this function at a local server.', 'gonzales') . '</p></div>'
		];

		$options[] = [
			'type' => 'checkbox',
			'way' => 'buttons',
			'name' => 'disable_assets_manager',
			'title' => __('Disable assets manager', 'gonzales'),
			'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
			'hint' => __('Full disable of the module.', 'gonzales'),
			'eventsOn' => [
				'hide' => '#wbcr-gnz-asset-manager-extend-options'
			],
			'eventsOff' => [
				'show' => '#wbcr-gnz-asset-manager-extend-options'
			],
			'default' => false
		];

		$options[] = [
			'type' => 'div',
			'id' => 'wbcr-gnz-asset-manager-extend-options',
			'items' => [
				[
					'type' => 'separator',
					'cssClass' => 'factory-separator-dashed'
				],
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'disable_assets_manager_panel',
					'title' => __('Disable assets manager panel', 'gonzales'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'green'],
					'hint' => __('By default in your admin bar there is a button for control the assets scripts and styles. With this option, you can turn off the script manager on front and back-end.', 'gonzales'),
					'default' => false
				],
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'disable_assets_manager_on_front',
					'title' => __('Disable assets manager on front', 'gonzales'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Disables assets manager initialization for frontend.', 'gonzales'),
					'default' => false
				],
				[
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'disable_assets_manager_on_backend',
					'title' => __('Disable assets manager on back-end', 'gonzales'),
					'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
					'hint' => __('Disables assets manager initialization for backend.', 'gonzales'),
					'default' => true
				]
			]
		];

		$options[] = [
			'type' => 'separator',
			'cssClass' => 'factory-separator-dashed'
		];

		$formOptions = [];

		$formOptions[] = [
			'type' => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return apply_filters('wbcr_gnz_assets_manager_options', $formOptions);
	}
}
