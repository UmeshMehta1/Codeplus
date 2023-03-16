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
 */
class WUPM_AdvancedPage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "updates_advanced";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $type = "page";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_parent_page = 'updates';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-cloud';

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
	 * WUPM_AdvancedPage constructor.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param \Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory463_Plugin $plugin ) {
		$this->menu_title = __( 'Advanced', 'webcraftic-updates-manager' );

		parent::__construct( $plugin );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param \Wbcr_Factory463_ScriptList $scripts
	 * @param \Wbcr_Factory463_StyleList  $styles
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		// Add Clearfy styles for HMWP pages
		if ( defined( 'WCL_PLUGIN_ACTIVE' ) ) {
			$this->styles->add( WCL_PLUGIN_URL . '/admin/assets/css/general.css' );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function warningNotice() {
		parent::warningNotice();

		if ( isset( $_GET['wbcr_force_update'] ) ) {
			$concat = __( 'Please, wait 90 sec. to see the forced automatic update result.', 'webcraftic-updates-manager' ) . '<br>';

			$this->printWarningNotice( $concat );
		}
	}

	public function showPageContent() {
		?>
        <div style="padding: 20px;">
            <h4><?php _e( 'Force Automatic Updates', 'webcraftic-updates-manager' ); ?></h4>
            <p><?php _e( 'This will attempt to force automatic updates. This is useful for debugging.', 'webcraftic-updates-manager' ); ?></p>
            <a href="<?php $this->actionUrl( 'force-plugins-update' ) ?>" class="button button-default"><?php _e( 'Force update', 'webcraftic-updates-manager' ); ?></a>
        </div>
		<?php
	}

	public function forcePluginsUpdateAction() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$shedule_auto_update = function () {
			wp_schedule_single_event( time() + 10, 'wp_update_plugins' );
			wp_schedule_single_event( time() + 10, 'wp_version_check' );
			wp_schedule_single_event( time() + 10, 'wp_update_themes' );
			wp_schedule_single_event( time() + 45, 'wp_maybe_auto_update' );

			if ( get_option( 'auto_updater.lock', false ) ) {
				update_option( 'auto_updater.lock', time() - HOUR_IN_SECONDS * 2 );
			}
		};

		if ( WUPM_Plugin::app()->isNetworkAdmin() ) {
			foreach ( WUPM_Plugin::app()->getActiveSites() as $site ) {
				switch_to_blog( $site->blog_id );

				$shedule_auto_update();

				restore_current_blog();
			}
		} else {
			$shedule_auto_update();
		}

		$this->redirectToAction( 'index', [ 'wbcr_force_update' => 1 ] );
	}
}

