<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page Settings.
 *
 * @since 1.0.0
 */
class WHTM_SettingsPage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "html_minify";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-testimonial';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_parent_page = "performance";

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
	 * WHTM_SettingsPage constructor.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param \Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory463_Plugin $plugin ) {
		// Заголовок страницы
		$this->menu_title = __( 'HTML Minify', 'html-minify' );

		if ( ! defined( 'LOADING_HTML_MINIFY_AS_ADDON' ) ) {
			$this->internal                   = false;
			$this->menu_target                = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
			$this->page_parent_page           = null;
		}

		parent::__construct( $plugin );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public function getMenuTitle() {
		return defined( 'LOADING_HTML_MINIFY_AS_ADDON' ) ? __( 'HTML Minify', 'html-minify' ) : __( 'General', 'html-minify' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 * @return mixed[]
	 */
	public function getPageOptions() {
		$options = wbcr_htm_settings_form_options();

		$formOptions = [];

		$formOptions[] = [
			'type'  => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return apply_filters( 'wbcr_htm_settings_form_options', $formOptions );
	}
}