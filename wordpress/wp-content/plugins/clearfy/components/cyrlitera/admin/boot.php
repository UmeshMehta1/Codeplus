<?php
/**
 * Admin boot
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright     Webcraftic 25.05.2017
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array
 */
function wbcr_cyrlitera_install_conflict_plugins() {
	$install_plugins = [];

	if ( is_plugin_active( 'wp-translitera/wp-translitera.php' ) ) {
		$install_plugins[] = 'WP Translitera';
	}

	if ( is_plugin_active( 'cyr3lat/cyr-to-lat.php' ) ) {
		$install_plugins[] = 'Cyr to Lat enhanced';
	}

	if ( is_plugin_active( 'cyr2lat/cyr-to-lat.php' ) ) {
		$install_plugins[] = 'Cyr to Lat';
	}

	if ( is_plugin_active( 'cyr-and-lat/cyr-and-lat.php' ) ) {
		$install_plugins[] = 'Cyr-And-Lat';
	}

	if ( is_plugin_active( 'rustolat/rus-to-lat.php' ) ) {
		$install_plugins[] = 'RusToLat';
	}

	if ( is_plugin_active( 'rus-to-lat-advanced/ru-translit.php' ) ) {
		$install_plugins[] = 'Rus filename and link translit';
	}

	return $install_plugins;
}

/**
 * @return array
 */
function wbcr_cyrlitera_get_conflict_notices_error() {
	$notices      = [];
	$plugin_title = WCTR_Plugin::app()->getPluginTitle();

	$default_notice = $plugin_title . ': ' . __( 'We found that you have the plugin %s installed. The functions of this plugin already exist in %s. Please deactivate plugin %s to avoid conflicts between plugins functions.', 'cyrlitera' );
	$default_notice .= ' ' . __( 'If you do not want to deactivate the plugin %s for some reason, we strongly recommend do not use the same plugins functions at the same time!', 'cyrlitera' );

	$install_conflict_plugins = wbcr_cyrlitera_install_conflict_plugins();

	if ( ! empty( $install_conflict_plugins ) ) {
		foreach ( (array) $install_conflict_plugins as $plugin_name ) {
			$notices[] = sprintf( $default_notice, $plugin_name, $plugin_title, $plugin_name, $plugin_name );
		}
	}

	return $notices;
}

add_filter( 'wbcr_clr_seo_page_warnings', 'wbcr_cyrlitera_get_conflict_notices_error' );

/**
 * Печатает ошибки совместимости с похожими плагинами
 */
function wbcr_cyrlitera_admin_conflict_notices_error( $notices, $plugin_name ) {
	if ( $plugin_name != WCTR_Plugin::app()->getPluginName() ) {
		return $notices;
	}

	$warnings = wbcr_cyrlitera_get_conflict_notices_error();

	if ( empty( $warnings ) ) {
		return $notices;
	}
	$notice_text = '';
	foreach ( (array) $warnings as $warning ) {
		$notice_text .= '<p>' . $warning . '</p>';
	}

	$notices[] = [
		'id'              => 'cyrlitera_plugin_compatibility',
		'type'            => 'error',
		'dismissible'     => true,
		'dismiss_expires' => 0,
		'text'            => $notice_text
	];

	return $notices;
}

add_action( 'wbcr/factory/admin_notices', 'wbcr_cyrlitera_admin_conflict_notices_error', 10, 2 );

if ( ! defined( 'LOADING_CYRLITERA_AS_ADDON' ) ) {
	function wbcr_cyrlitera_set_plugin_meta( $links, $file ) {
		if ( $file == WCTR_PLUGIN_BASE ) {

			$url = 'https://clearfy.pro';

			if ( get_locale() == 'ru_RU' ) {
				$url = 'https://ru.clearfy.pro';
			}

			$url .= '?utm_source=wordpress.org&utm_campaign=' . WCTR_Plugin::app()->getPluginName();

			$links[] = '<a href="' . $url . '" style="color: #FF5722;font-weight: bold;" target="_blank">' . __( 'Get ultimate plugin free', 'cyrlitera' ) . '</a>';
		}

		return $links;
	}

	add_filter( 'plugin_row_meta', 'wbcr_cyrlitera_set_plugin_meta', 10, 2 );

	/**
	 * Виджет отзывов
	 *
	 * @param string $page_url
	 * @param string $plugin_name
	 *
	 * @return string
	 */
	function wbcr_cyrlitera_rating_widget_url( $page_url, $plugin_name ) {
		if ( ! defined( 'LOADING_CYRLITERA_AS_ADDON' ) && ( $plugin_name == WCTR_Plugin::app()->getPluginName() ) ) {
			return 'https://goo.gl/ecaj2V';
		}

		return $page_url;
	}

	add_filter( 'wbcr_factory_pages_463_imppage_rating_widget_url', 'wbcr_cyrlitera_rating_widget_url', 10, 2 );

	/**
	 * Удаляем лишние виджеты из правого сайдбара в интерфейсе плагина
	 *
	 * - Виджет с премиум рекламой
	 * - Виджет с рейтингом
	 * - Виджет с маркерами информации
	 */
	add_filter( 'wbcr/factory/pages/impressive/widgets', function ( $widgets, $position, $plugin ) {
		if ( WCTR_Plugin::app()->getPluginName() == $plugin->getPluginName() && 'right' == $position ) {
			unset( $widgets['business_suggetion'] );
			unset( $widgets['rating_widget'] );
			unset( $widgets['info_widget'] );
		}

		return $widgets;
	}, 20, 3 );
} else {
	/**
	 * Когда в CLearfy пользователь выполняет быструю настройку "ONE CLICK SEO OPTIMIZATION",
	 * мы включаем транслитерацию и преобразовываем слаги для уже существующих страниц, терминов
	 *
	 * @param string $mode_name   - имя режима быстрой настройки
	 */
	add_action( 'wbcr_clearfy_configurated_quick_mode', function ( $mode_name ) {
		if ( $mode_name == 'seo_optimize' ) {
			$use_transliterations         = WCTR_Plugin::app()->getPopulateOption( 'use_transliteration' );
			$transliterate_existing_slugs = WCTR_Plugin::app()->getPopulateOption( 'transliterate_existing_slugs' );

			if ( ! $use_transliterations || $transliterate_existing_slugs ) {
				return;
			}

			WCTR_Helper::convertExistingSlugs();

			WCTR_Plugin::app()->updatePopulateOption( 'transliterate_existing_slugs', 1 );
		}
	} );

	function wbcr_cyrlitera_group_options( $options ) {
		$install_conflict_plugins = wbcr_cyrlitera_install_conflict_plugins();
		$is_cyrilic               = in_array( get_locale(), [ 'ru_RU', 'bel', 'kk', 'uk', 'bg', 'bg_BG', 'ka_GE' ] );

		if ( ! empty( $install_conflict_plugins ) || ! $is_cyrilic ) {
			$tags = [];
		} else {
			$tags = [ 'recommended', 'seo_optimize' ];
		}

		$options[] = [
			'name'  => 'use_transliteration',
			'title' => __( 'Use transliteration', 'cyrlitera' ),
			'tags'  => $tags
		];

		$options[] = [
			'name'  => 'use_force_transliteration',
			'title' => __( 'Force transliteration', 'cyrlitera' ),
			'tags'  => []
		];

		$options[] = [
			'name'  => 'dont_use_transliteration_on_frontend',
			'title' => __( 'Don\'t use transliteration in frontend', 'cyrlitera' ),
			'tags'  => []
		];

		$options[] = [
			'name'  => 'use_transliteration_filename',
			'title' => __( 'Convert file names', 'cyrlitera' ),
			'tags'  => $tags
		];

		$options[] = [
			'name'  => 'filename_to_lowercase',
			'title' => __( 'Convert file names into lowercase', 'cyrlitera' ),
			'tags'  => $tags
		];

		$options[] = [
			'name'  => 'redirect_from_old_urls',
			'title' => __( 'Redirection old URLs to new ones', 'cyrlitera' ),
			'tags'  => []
		];

		$options[] = [
			'name'  => 'custom_symbols_pack',
			'title' => __( 'Character Sets', 'cyrlitera' ),
			'tags'  => []
		];

		return $options;
	}

	add_filter( "wbcr_clearfy_group_options", 'wbcr_cyrlitera_group_options' );
}





