<?php
/**
 * Admin boot
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright     Webcraftic 25.05.2017
 * @version       1.0
 */

require_once WUPM_PLUGIN_DIR . '/admin/includes/class-plugin-filters.php';
require_once WUPM_PLUGIN_DIR . '/admin/includes/class-theme-filters.php';

if ( ! defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) ) {
	function wbcr_upm_set_plugin_meta( $links, $file ) {
		if ( $file == WUPM_PLUGIN_BASE ) {

			$url = 'https://clearfy.pro';

			if ( get_locale() == 'ru_RU' ) {
				$url = 'https://ru.clearfy.pro';
			}

			$url .= '?utm_source=wordpress.org&utm_campaign=' . WUPM_Plugin::app()->getPluginName();

			$links[] = '<a href="' . $url . '" style="color: #FF5722;font-weight: bold;" target="_blank">' . __( 'Get ultimate plugin free', 'webcraftic-updates-manager' ) . '</a>';
		}

		return $links;
	}

	if ( ! defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) ) {
		add_filter( 'plugin_row_meta', 'wbcr_upm_set_plugin_meta', 10, 2 );
	}

	/**
	 * Rating widget url
	 *
	 * @param string $page_url
	 * @param string $plugin_name
	 *
	 * @return string
	 */
	function wbcr_upm_rating_widget_url( $page_url, $plugin_name ) {
		if ( ! defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) && ( $plugin_name == WUPM_Plugin::app()->getPluginName() ) ) {
			return 'https://goo.gl/Be2hQU';
		}

		return $page_url;
	}

	add_filter( 'wbcr_factory_pages_463_imppage_rating_widget_url', 'wbcr_upm_rating_widget_url', 10, 2 );

	/**
	 * Удаляем лишние виджеты из правого сайдбара в интерфейсе плагина
	 *
	 * - Виджет с премиум рекламой
	 * - Виджет с рейтингом
	 * - Виджет с маркерами информации
	 */
	add_filter( 'wbcr/factory/pages/impressive/widgets', function ( $widgets, $position, $plugin ) {
		if ( WUPM_Plugin::app()->getPluginName() == $plugin->getPluginName() && 'right' == $position ) {
			unset( $widgets['business_suggetion'] );
			unset( $widgets['rating_widget'] );
			unset( $widgets['info_widget'] );
		}

		return $widgets;
	}, 20, 3 );
} else {
	function wbcr_upm_group_options( $options ) {
		$options[] = [
			'name'   => 'plugin_updates',
			'title'  => __( 'Disable plugin updates', 'webcraftic-updates-manager' ),
			'tags'   => [ 'disable_all_updates' ],
			'values' => [ 'disable_all_updates' => 'disable_plugin_updates' ]
		];
		$options[] = [
			'name'   => 'theme_updates',
			'title'  => __( 'Disable theme updates', 'webcraftic-updates-manager' ),
			'tags'   => [ 'disable_all_updates' ],
			'values' => [ 'disable_all_updates' => 'disable_theme_updates' ]
		];
		$options[] = [
			'name'  => 'auto_tran_update',
			'title' => __( 'Disable Automatic Translation Updates', 'webcraftic-updates-manager' ),
			'tags'  => [ 'disable_all_updates' ]
		];
		$options[] = [
			'name'   => 'wp_update_core',
			'title'  => __( 'Disable wordPress core updates', 'webcraftic-updates-manager' ),
			'tags'   => [ 'disable_all_updates' ],
			'values' => [ 'disable_all_updates' => 'disable_core_updates' ]
		];
		$options[] = [
			'name'  => 'enable_update_vcs',
			'title' => __( 'Enable updates for VCS Installations', 'webcraftic-updates-manager' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'plugins_update_filters',
			'title' => __( 'Plugin filters', 'webcraftic-updates-manager' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'updates_nags_only_for_admin',
			'title' => __( 'Updates nags only for Admin', 'webcraftic-updates-manager' ),
			'tags'  => [ 'recommended' ]
		];
		$options[] = [
			'name'  => 'disable_core_notifications',
			'title' => __( 'Core notifications', 'webcraftic-updates-manager' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'notify_updated',
			'title' => __( 'Notify me when update successful installed', 'webcraftic-updates-manager' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'notify_email',
			'title' => __( 'Email address', 'webcraftic-updates-manager' ),
			'tags'  => []
		];

		return $options;
	}

	add_filter( "wbcr_clearfy_group_options", 'wbcr_upm_group_options' );

	function wbcr_upm_allow_quick_mods( $mods ) {
		$mods['disable_all_updates'] = [
			'title' => __( 'One click disable all updates', 'webcraftic-updates-manager' ),
			'icon'  => 'dashicons-update'
		];

		return $mods;
	}

	add_filter( "wbcr_clearfy_allow_quick_mods", 'wbcr_upm_allow_quick_mods' );
}

/**
 * Ошибки совместимости с похожими плагинами
 */
function wbcr_upm_admin_conflict_notices_error( $notices, $plugin_name ) {
	if ( $plugin_name != WUPM_Plugin::app()->getPluginName() ) {
		return $notices;
	}

	$warnings = [];

	$default_notice = WUPM_Plugin::app()->getPluginTitle() . ': ' . __( 'We found that you have the plugin %s installed. The functions of this plugin already exist in %s. Please deactivate plugin %s to avoid conflicts between plugins\' functions.', 'webcraftic-updates-manager' );
	$default_notice .= ' ' . __( 'If you do not want to deactivate the plugin %s for some reason, we strongly recommend do not use the same plugins\' functions at the same time!', 'webcraftic-updates-manager' );

	if ( is_plugin_active( 'companion-auto-update/companion-auto-update.php' ) ) {
		$warnings[] = sprintf( $default_notice, 'Companion Auto Update', WUPM_Plugin::app()->getPluginTitle(), 'Companion Auto Update', 'Companion Auto Update' );
	}

	if ( is_plugin_active( 'disable-updates/disable-updates.php' ) ) {
		$warnings[] = sprintf( $default_notice, 'Disable Updates', WUPM_Plugin::app()->getPluginTitle(), 'Disable Updates', 'Disable Updates' );
	}

	if ( is_plugin_active( 'disable-wordpress-updates/disable-updates.php' ) ) {
		$warnings[] = sprintf( $default_notice, 'Disable All WordPress Updates', WUPM_Plugin::app()->getPluginTitle(), 'Disable All WordPress Updates', 'Disable All WordPress Updates' );
	}

	if ( is_plugin_active( 'stops-core-theme-and-plugin-updates/main.php' ) ) {
		$warnings[] = sprintf( $default_notice, 'Easy Updates Manager', WUPM_Plugin::app()->getPluginTitle(), 'Easy Updates Manager', 'Easy Updates Manager' );
	}

	if ( empty( $warnings ) ) {
		return $notices;
	}
	$notice_text = '';
	foreach ( (array) $warnings as $warning ) {
		$notice_text .= '<p>' . $warning . '</p>';
	}

	$notices[] = [
		'id'              => 'ump_plugin_compatibility',
		'type'            => 'error',
		'dismissible'     => true,
		'dismiss_expires' => 0,
		'text'            => $notice_text
	];

	return $notices;
}

add_filter( 'wbcr/factory/admin_notices', 'wbcr_upm_admin_conflict_notices_error', 10, 2 );

/**
 * Подключаем скрипты для создания лейблов для списка плагинов
 */
add_action( 'admin_enqueue_scripts', function ( $handles ) {
	if ( ! current_user_can( 'install_plugins' ) || ! in_array( $handles, [ 'plugins.php', 'plugins-network.php' ] ) ) {
		return;
	}

	wp_enqueue_style( 'wbcr-upm-plugins', WUPM_PLUGIN_URL . '/admin/assets/css/plugins.css', [], WUPM_Plugin::app()->getPluginVersion() );
	wp_enqueue_script( 'wbcr-upm-plugins-js', WUPM_PLUGIN_URL . '/admin/assets/js/plugins.js', [ 'jquery' ], WUPM_Plugin::app()->getPluginVersion() );
} );

/**
 * Добавляем код локализации скриптов для создания лейблов в списке плагинов
 */
add_action( 'admin_footer', function () {
	if ( ! current_user_can( 'install_plugins' ) || ! in_array( get_current_screen()->id, [
			'plugins',
			'plugins-network'
		] ) ) {
		return;
	}

	$pluginFilters = new WUPM_PluginFilters( WUPM_Plugin::app() );
	$filters       = $pluginFilters->getPlugins();

	$l10n = [
		'default'             => __( "Auto-update disabled", 'webcraftic-updates-manager' ),
		'auto_update'         => __( "Auto-update enabled", 'webcraftic-updates-manager' ),
		'disable_updates'     => __( "Update disabled", 'webcraftic-updates-manager' ),
		'disable_tran_update' => __( "Translation update disabled", 'webcraftic-updates-manager' )
	];

	?>
    <script>
		// l10n strings
		window.wbcr_upm_plugins_label_l10n = window.wbcr_upm_plugins_label_l10n || <?php echo wp_json_encode( $l10n ); ?>;

		jQuery(function($) {
			var info = <?= json_encode( [
				'filters' => $filters,
			] ); ?>;
			um_add_plugin_icons(info);
		});
    </script>
	<?php
} );