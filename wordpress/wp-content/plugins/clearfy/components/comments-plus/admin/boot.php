<?php
/**
 * Admin boot
 *
 * @author    Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright Webcraftic 25.05.2017
 * @version   1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Массово проходится по комментарием в базе данных и отключает, какждый индивидуально.
 */
function wbcr_cmp_enter_permanent_mode() {
	global $wpdb;

	if ( ! WCM_Plugin::app()->getPopulateOption( 'disable_comments_permanent' ) ) {
		return;
	}

	$types = wbcr_cmp_get_disabled_post_types();

	if ( empty( $types ) ) {
		return;
	}

	if ( WCM_Plugin::app()->isNetworkActive() ) {
		// NOTE: this can be slow on large networks!
		$blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id
							FROM $wpdb->blogs
							WHERE site_id = %d
							AND public = '1'
							AND archived = '0'
							AND deleted = '0'", $wpdb->siteid ) );

		foreach ( $blogs as $id ) {
			switch_to_blog( $id );
			wbcr_close_comments_in_db( $types );
			restore_current_blog();
		}
	} else {
		wbcr_close_comments_in_db( $types );
	}
}

add_action( 'wbcr/factory/pages/impressive/after_form_save', 'wbcr_cmp_enter_permanent_mode' );
add_action( 'wbcr_clearfy_configurated_quick_mode', 'wbcr_cmp_enter_permanent_mode' );

/**
 * Закрывает комментарии в базе данных
 *
 * @param $types
 */
function wbcr_close_comments_in_db( $types ) {
	global $wpdb;
	$bits = implode( ', ', array_pad( [], count( $types ), '%s' ) );

	$wpdb->query( $wpdb->prepare( "UPDATE `$wpdb->posts`
				SET `comment_status` = 'closed', ping_status = 'closed'
				WHERE `post_type`
				IN ( $bits )", $types ) );
}

if ( ! defined( 'LOADING_COMMENTS_PLUS_AS_ADDON' ) ) {
	function wbcr_cmp_set_plugin_meta( $links, $file ) {
		if ( $file == WCM_PLUGIN_BASE ) {

			$url = 'https://clearfy.pro';

			if ( get_locale() == 'ru_RU' ) {
				$url = 'https://ru.clearfy.pro';
			}

			$url .= '?utm_source=wordpress.org&utm_campaign=' . WCM_Plugin::app()->getPluginName();

			$links[] = '<a href="' . $url . '" style="color: #FF5722;font-weight: bold;" target="_blank">' . __( 'Get ultimate plugin free', 'comments-plus' ) . '</a>';
		}

		return $links;
	}

	add_filter( 'plugin_row_meta', 'wbcr_cmp_set_plugin_meta', 10, 2 );

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param string $page_url
	 * @param string $plugin_name
	 *
	 * @return string
	 */
	function wbcr_cmp_rating_widget_url( $page_url, $plugin_name ) {
		if ( ! defined( 'LOADING_COMMENTS_PLUS_AS_ADDON' ) && ( $plugin_name == WCM_Plugin::app()->getPluginName() ) ) {
			return 'https://goo.gl/v4QkW5';
		}

		return $page_url;
	}

	add_filter( 'wbcr_factory_pages_463_imppage_rating_widget_url', 'wbcr_cmp_rating_widget_url', 10, 2 );

	/**
	 * Удаляем лишние виджеты из правого сайдбара в интерфейсе плагина
	 *
	 * - Виджет с премиум рекламой
	 * - Виджет с рейтингом
	 * - Виджет с маркерами информации
	 */
	add_filter( 'wbcr/factory/pages/impressive/widgets', function ( $widgets, $position, $plugin ) {
		if ( WCM_Plugin::app()->getPluginName() == $plugin->getPluginName() && 'right' == $position ) {
			unset( $widgets['business_suggetion'] );
			unset( $widgets['rating_widget'] );
			unset( $widgets['info_widget'] );
		}

		return $widgets;
	}, 20, 3 );
} else {

	function wbcr_cmp_group_options( $options ) {
		$options[] = [
			'name'   => 'disable_comments',
			'title'  => __( 'Disable comments on the entire site', 'comments-plus' ),
			'tags'   => [ 'disable_all_comments' ],
			'values' => [ 'disable_all_comments' => 'disable_comments' ]
		];
		$options[] = [
			'name'  => 'disable_comments_for_post_types',
			'title' => __( 'Select post types', 'comments-plus' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'disable_comments_extra_post_types',
			'title' => __( 'Custom post types', 'comments-plus' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'disable_comments_permanent',
			'title' => __( 'Use persistent mode', 'comments-plus' ),
			'tags'  => []
		];
		$options[] = [
			'name'  => 'comment_text_convert_links_pseudo',
			'title' => __( 'Replace external links in comments on the JavaScript code', 'comments-plus' ),
			'tags'  => [ 'recommended', 'seo_optimize' ]
		];
		$options[] = [
			'name'  => 'pseudo_comment_author_link',
			'title' => __( 'Replace external links from comment authors on the JavaScript code', 'comments-plus' ),
			'tags'  => [ 'recommended', 'seo_optimize' ]
		];
		$options[] = [
			'name'  => 'remove_url_from_comment_form',
			'title' => __( 'Remove field "site" in comment form', 'comments-plus' ),
			'tags'  => []
		];

		return $options;
	}

	add_filter( "wbcr_clearfy_group_options", 'wbcr_cmp_group_options' );

	function wbcr_cmp_allow_quick_mods( $mods ) {
		$mods['disable_all_comments'] = [
			'title' => __( 'One click disable all comments', 'comments-plus' ),
			'icon'  => 'dashicons-testimonial'
		];

		return $mods;
	}

	add_filter( "wbcr_clearfy_allow_quick_mods", 'wbcr_cmp_allow_quick_mods' );
}

