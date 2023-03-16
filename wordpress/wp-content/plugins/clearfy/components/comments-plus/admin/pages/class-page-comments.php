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
 *
 * @copyright (c) 2018 Webraftic Ltd
 */
class WbcrCmp_CommentsPage extends WBCR\Factory_Templates_113\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "comments";

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
	 * WbcrCmp_CommentsPage constructor.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param \Wbcr_Factory463_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory463_Plugin $plugin ) {
		$this->menu_title                  = __( 'Disable comments', 'comments-plus' );
		$this->page_menu_short_description = __( 'Manage site comments', 'comments-plus' );

		if ( ! defined( 'LOADING_COMMENTS_PLUS_AS_ADDON' ) ) {
			$this->internal                   = false;
			$this->menu_target                = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
			$this->show_search_options_form = false;
		}

		parent::__construct( $plugin );
	}

	/*public function getMenuTitle() {
		return defined( 'LOADING_COMMENTS_PLUS_AS_ADDON' ) ? __( 'Comments', 'comments-plus' ) : __( 'General', 'comments-plus' );
	}*/

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @return string
	 */
	public function getPageTitle() {
		return defined( 'LOADING_COMMENTS_PLUS_AS_ADDON' ) ? __( 'Comments', 'comments-plus' ) : __( 'General', 'comments-plus' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 * @return mixed[]
	 */
	public function getPageOptions() {
		$options = [];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'Global disabling of comments', 'comments-plus' ) . '</strong><p>' . __( 'What is the difference between these and native WordPress functions? WordPress disables comments only for new posts! Using the functions below, you can disable comments globally, even for old posts, and you can choose which post types comments to disable. The plugin also disables the comment functionality itself, which creates a certain load on the site.', 'comments-plus' ) . '</p></div>'
		];

		$args = [ 'public' => true ];

		if ( $this->plugin->isNetworkActive() ) {
			$args['_builtin'] = true;
		}

		$types = get_post_types( $args, 'objects' );

		/*foreach( array_keys( $types ) as $type ) {
			if( ! in_array( $type, $this->modified_types ) && ! post_type_supports( $type, 'comments' ) )	// the type doesn't support comments anyway
				unset( $types[$type] );
		}*/

		$post_types = [];
		foreach ( $types as $type_name => $type ) {
			$post_types[] = [ $type_name, $type->label ];
		}

		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'disable_comments',
			'way'     => 'buttons',
			'title'   => __( 'Disable comments', 'comments-plus' ),
			'data'    => [
				[ 'enable_comments', __( 'Not disable', 'comments-plus' ) ],
				[
					'disable_comments',
					__( 'Everywhere', 'comments-plus' ),
					sprintf( __( 'You can delete all comments in the database by clicking on this link (<a href="%s">cleaning comments in database</a>).', 'comments-plus' ), admin_url( 'admin.php?page=delete_comments-' . $this->plugin->getPluginName() ) )
				],
				[
					'disable_certain_post_types_comments',
					__( 'On certain post types', 'comments-plus' ),
					sprintf( __( 'You can delete all comments for the selected post types. Select the post types below and save the settings. After that, click the link (<a href="%s">delete all comments for the selected post types in database</a>).', 'comments-plus' ), admin_url( 'admin.php?page=delete_comments-' . $this->plugin->getPluginName() ) )
				]
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Everywhere - Warning: This option is global and will affect your entire site. Use it only if you want to disable comments everywhere. A complete description of what this option does is available here', 'comments-plus' ) . '<br><br>' . __( 'On certain post types - Disabling comments will also disable trackbacks and pingbacks. All comment-related fields will also be hidden from the edit/quick-edit screens of the affected posts. These settings cannot be overridden for individual posts.', 'comments-plus' ),
			'default' => 'enable_comments',
			'events'  => [
				'disable_certain_post_types_comments' => [
					'show' => '.factory-control-disable_comments_for_post_types, #wbcr-clearfy-comments-base-options,.factory-control-disable_comments_permanent,.factory-control-disable_comments_extra_post_types'
				],
				'enable_comments'                     => [
					'show' => '#wbcr-clearfy-comments-base-options',
					'hide' => '.factory-control-disable_comments_for_post_types,.factory-control-disable_comments_permanent,.factory-control-disable_comments_extra_post_types'
				],
				'disable_comments'                    => [
					'show' => '.factory-control-disable_comments_permanent',
					'hide' => '.factory-control-disable_comments_for_post_types, #wbcr-clearfy-comments-base-options,.factory-control-disable_comments_extra_post_types'
				]
			]
		];

		$options[] = [
			'type'    => 'list',
			'way'     => 'checklist',
			'name'    => 'disable_comments_for_post_types',
			'title'   => __( 'Select post types', 'comments-plus' ),
			'data'    => $post_types,
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Select the post types for which comments will be disabled', 'comments-plus' ),
			'default' => 'post,page,attachment'
		];

		if ( $this->plugin->isNetworkActive() ) {
			$options[] = [
				'type'    => 'textbox',
				'name'    => 'disable_comments_extra_post_types',
				'title'   => __( 'Custom post types', 'comments-plus' ),
				'data'    => $post_types,
				'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
				'hint'    => __( 'Only the built-in post types appear above. If you want to disable comments on other custom post types on the entire network, you can supply a comma-separated list of post types below (use the slug that identifies the post type).', 'comments-plus' ),
				'default' => ''
			];
		}
		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'disable_comments_permanent',
			'title'   => __( 'Use persistent mode', 'comments-plus' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'This will make persistent changes to your database &mdash; comments will remain closed even if you later disable the plugin! You should not use it if you only want to disable comments temporarily.', 'comments-plus' ),
			'default' => false
		];

		$options[] = [
			'type'  => 'div',
			'id'    => 'wbcr-clearfy-comments-base-options',
			'items' => [
				[
					'type' => 'html',
					'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'General settings for comments', 'comments-plus' ) . '</strong><p>' . __( 'These settings will help you improve SEO and reduce the amount of spam.', 'comments-plus' ) . '</p></div>'
				],
				[
					'type'    => 'checkbox',
					'way'     => 'buttons',
					'name'    => 'remove_url_from_comment_form',
					'title'   => __( 'Remove field "site" in comment form', 'comments-plus' ),
					'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
					'hint'    => __( 'Tired of spam in the comments? Do visitors leave "blank" comments for the sake of a link to their site?', 'comments-plus' ) . '<br><b>Clearfy: </b>' . __( 'Removes the "Site" field from the comment form.', 'comments-plus' ) . '<br>--<br><span class="wbcr-factory-light-orange-color"> *' . __( 'Works with the standard comment form, if the form is manually written in your theme-it probably will not work!', 'comments-plus' ) . '</span>',
					'default' => false
				],
				[
					'type'    => 'checkbox',
					'way'     => 'buttons',
					'name'    => 'comment_text_convert_links_pseudo',
					'title'   => __( 'Replace external links in comments on the JavaScript code', 'comments-plus' ),
					'layout'  => [ 'hint-type' => 'icon' ],
					'hint'    => __( 'Superfluous external links from comments, which can be typed from a dozen and more for one article, do not bring anything good for promotion.', 'comments-plus' ) . '<br><br><b>Clearfy: </b>' . sprintf( __( 'Replaces the links of this kind of %s, on links of this kind %s', 'comments-plus' ), '<code>a href="http://yourdomain.com" rel="nofollow"</code>', '<code>span data-uri="http://yourdomain.com"</code>' ),
					'default' => false
				],
				[
					'type'    => 'checkbox',
					'way'     => 'buttons',
					'name'    => 'pseudo_comment_author_link',
					'title'   => __( 'Replace external links from comment authors on the JavaScript code', 'comments-plus' ),
					'layout'  => [ 'hint-type' => 'icon' ],
					'hint'    => __( 'Up to 90 percent of comments in the blog can be left for the sake of an external link. Even nofollow from page weight loss here does not help.', 'comments-plus' ) . '<br><br><b>Clearfy: </b>' . __( 'Replaces the links of the authors of comments on the JavaScript code, it is impossible to distinguish it from usual links.', 'comments-plus' ) . '<br>--<br><i>' . __( 'In some Wordpress topics this may not work.', 'comments-plus' ) . '</i>',
					'default' => false
				]
			]
		];

		$formOptions = [];

		$formOptions[] = [
			'type'  => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return apply_filters( 'wbcr_cmp_comments_form_options', $formOptions );
	}
}
