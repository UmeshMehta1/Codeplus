<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
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
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */
class WGA_CachePage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "ga_cache";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-testimonial';

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
	 * @param Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory463_Plugin $plugin ) {
		$this->menu_title = __( 'Local Google Analytics', 'simple-google-analytics' );

		if ( ! defined( 'LOADING_GA_CACHE_AS_ADDON' ) ) {
			$this->internal                   = false;
			$this->menu_target                = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
		}

		parent::__construct( $plugin );

		$this->plugin = $plugin;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  3.1.0
	 * @return string|void
	 */
	public function getPageTitle() {
		return __( 'General', 'simple-google-analytics' );
	}

	/**
	 * Permalinks options.
	 *
	 * @since 1.0.0
	 * @return mixed[]
	 */
	public function getPageOptions() {
		$options = wbcr_ga_get_plugin_options();

		$formOptions = [];

		$formOptions[] = [
			'type'  => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return apply_filters( 'wbcr_ga_notices_form_options', $formOptions, $this );
	}
}
