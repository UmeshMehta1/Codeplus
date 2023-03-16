<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Assets manager base class
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 10.09.20198, Webcraftic
 * @since         2.0
 */
class WGZ_Check_Conditions {

	protected $condition;

	public function __construct($condition)
	{
		if( empty($condition) ) {
			$this->condition = [];
		} else {
			$condition = @json_decode(stripslashes($condition));
			$this->condition = $condition;
		}
	}

	/**
	 * Проверяем в правильном ли формате нам передано условие
	 *
	 * @param \stdClass $condition
	 *
	 * @return bool
	 * @since  2.2.0
	 *
	 */
	protected function validate_condition_schema($condition)
	{
		$isset_attrs = !empty($condition->param) && !empty($condition->operator) && !empty($condition->type) && isset($condition->value);

		$allow_params = in_array($condition->param, [
			'user-role',
			'user-mobile',
			'user-cookie-name',
			'current-url',
			'query-string',
			'location-page',
			'regular-expression',
			'location-some-page',
			'location-post-type',
			'location-taxonomy'
		]);

		$allow_operators = in_array($condition->operator, [
			'equals',
			'notequal',
			'less',
			'older',
			'greater',
			'younger',
			'contains',
			'notcontain',
			'between'
		]);

		$allow_types = in_array($condition->type, ['select', 'text', 'default', 'regexp', 'equals']);

		return $isset_attrs && $allow_params && $allow_operators && $allow_types;
	}

	/**
	 * @return bool
	 * @since  2.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function validate()
	{
		if( empty($this->condition) && !is_array($this->condition) ) {
			return false;
		}

		$or = null;
		foreach($this->condition as $group_OR) {
			if( !empty($group_OR->conditions) && is_array($group_OR->conditions) ) {
				$and = null;
				foreach($group_OR->conditions as $condition) {
					if( $this->validate_condition_schema($condition) ) {
						$method_name = str_replace('-', '_', $condition->param);
						if( is_null($and) ) {
							$and = $this->call_method($method_name, $condition->operator, $condition->value);
						} else {
							$and = $and && $this->call_method($method_name, $condition->operator, $condition->value);
						}
					}
				}

				$or = is_null($or) ? $and : $or || $and;
			}
		}

		return is_null($or) ? false : $or;
	}

	/**
	 * Call specified method
	 *
	 * @param $method_name
	 * @param $operator
	 * @param $value
	 *
	 * @return bool
	 */
	protected function call_method($method_name, $operator, $value)
	{
		if( method_exists($this, $method_name) ) {
			return $this->$method_name($operator, $value);
		} else {
			return apply_filters('wam/conditions/call_method', false, $method_name, $operator, $value);
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	protected function get_admin_url_path($path)
	{
		return str_replace(site_url(), '', admin_url($path));
	}

	/**
	 * Get referer URL
	 *
	 * @return string
	 */
	protected function get_referer_url()
	{
		$out = "";
		$url = explode('?', str_replace(site_url(), '', $_SERVER['HTTP_REFERER']), 2);
		if( isset($url[0]) ) {
			$out = trim($url[0], '/');
		}

		return $out ? urldecode($out) : '/';
	}

	/**
	 * Get current URL
	 *
	 * @return string
	 */
	protected function get_current_url_path($clear_query = false)
	{
		if( !is_admin() && !$clear_query ) {
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
	 * Check by operator
	 *
	 * @param $operator
	 * @param $first
	 * @param $second
	 * @param $third
	 *
	 * @return bool
	 */
	public function apply_operator($operator, $first, $second, $third = false)
	{
		switch( $operator ) {
			case 'equals':
				return $first === $second;
			case 'notequal':
				return $first !== $second;
			case 'less':
			case 'older':
				return $first > $second;
			case 'greater':
			case 'younger':
				return $first < $second;
			case 'contains':
				return strpos($first, $second) !== false;
			case 'notcontain':
				return strpos($first, $second) === false;
			case 'between':
				return $first < $second && $second < $third;

			default:
				return $first === $second;
		}
	}

	/**
	 * Get timestamp
	 *
	 * @param $units
	 * @param $count
	 *
	 * @return integer
	 */
	protected function get_timestamp($units, $count)
	{
		switch( $units ) {
			case 'seconds':
				return $count;
			case 'minutes':
				return $count * MINUTE_IN_SECONDS;
			case 'hours':
				return $count * HOUR_IN_SECONDS;
			case 'days':
				return $count * DAY_IN_SECONDS;
			case 'weeks':
				return $count * WEEK_IN_SECONDS;
			case 'months':
				return $count * MONTH_IN_SECONDS;
			case 'years':
				return $count * YEAR_IN_SECONDS;

			default:
				return $count;
		}
	}

	/**
	 * Get date timestamp
	 *
	 * @param $value
	 *
	 * @return integer
	 */
	public function get_date_timestamp($value)
	{
		if( is_object($value) ) {
			return (current_time('timestamp') - $this->get_timestamp($value->units, $value->unitsCount)) * 1000;
		} else {
			return $value;
		}
	}

	/**
	 * A some selected page
	 *
	 * @param $operator
	 * @param $value
	 *
	 * @return boolean
	 */
	protected function location_some_page($operator, $value)
	{
		if( !is_admin() ) {
			// For frontend area
			switch( $value ) {
				case 'base_web':    // Basic - Entire Website
					$result = !is_admin();
					break;
				case 'base_sing':   // Basic - All Singulars
					$result = is_singular();
					break;
				case 'base_arch':   // Basic - All Archives
					$result = is_archive();
					break;
				case 'spec_404':    // Special Pages - 404 Page
					$result = is_404();
					break;
				case 'spec_search': // Special Pages - Search Page
					$result = is_search();
					break;
				case 'spec_blog':   // Special Pages - Blog / Posts Page
					$result = is_home();
					break;
				case 'spec_front':  // Special Pages - Front Page
					$result = is_front_page();
					break;
				case 'spec_date':   // Special Pages - Date Archive
					$result = is_date();
					break;
				case 'spec_auth':   // Special Pages - Author Archive
					$result = is_author();
					break;
				case 'post_all':    // Posts - All Posts
				case 'page_all':    // Pages - All Pages
					$result = false;
					$post_id = (!is_404() && !is_search() && !is_archive() && !is_home()) ? get_the_ID() : false;

					if( false !== $post_id ) {
						$post_type = 'post_all' == $value ? 'post' : 'page';
						$result = $post_type == get_post_type($post_id);
					}
					break;
				case 'post_arch':   // Posts - All Posts Archive
				case 'page_arch':   // Pages - All Pages Archive
					$result = false;
					if( is_archive() ) {
						$post_type = 'post_arch' == $value ? 'post' : 'page';
						$result = $post_type == get_post_type();
					}
					break;
				case 'post_cat':    // Posts - All Categories Archive
				case 'post_tag':    // Posts - All Tags Archive
					$result = false;
					if( is_archive() && 'post' == get_post_type() ) {
						$taxonomy = 'post_tag' == $value ? 'post_tag' : 'category';
						$obj = get_queried_object();

						$current_taxonomy = '';
						if( '' !== $obj && null !== $obj ) {
							$current_taxonomy = $obj->taxonomy;
						}

						if( $current_taxonomy == $taxonomy ) {
							$result = true;
						}
					}
					break;

				default:
					$result = true;
			}
		} else {
			// For admin area
			switch( $value ) {
				case 'all_admin_area':
					$result = is_admin();
					break;
				case 'dashboard_home':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('index.php');
					break;
				case 'dashboard_wordpress_updates':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('update-core.php');
					break;
				case 'posts_all':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('edit.php');
					break;
				case 'posts_add_new':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('post-new.php');
					break;
				case 'posts_taxonomies':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('edit-tags.php');
					break;
				case 'media_library':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('upload.php');
					break;
				case 'media_library_add_new':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('media-new.php');
					break;
				case 'appearance_themes':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('themes.php');
					break;
				case 'appearance_customize':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('customize.php');
					break;
				case 'appearance_widgets':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('widgets.php');
					break;
				case 'appearance_menus':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('nav-menus.php');
					break;
				case 'appearance_theme_editor':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('theme-editor.php');
					break;
				case 'plugins_installed':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('plugins.php');
					break;
				case 'plugins_add_new':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('plugin-install.php');
					break;
				case 'plugins_editor':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('plugin-editor.php');
					break;
				case 'users_all':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('users.php');
					break;
				case 'users_add_new':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('user-new.php');
					break;
				case 'users_your_profile':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('profile.php');
					break;
				case 'tools_available':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('tools.php');
					break;
				case 'tools_import':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('import.php');
					break;
				case 'tools_export':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('export.php');
					break;
				case 'tools_site_health':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('site-health.php');
					break;
				case 'tools_export_personal_data':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('tools.php');
					break;
				case 'tools_erase_personal_data':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('tools.php');
					break;
				case 'settings_general':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('options-general.php');
					break;
				case 'settings_writing':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('options-writing.php');
					break;
				case 'settings_reading':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('options-reading.php');
					break;
				case 'settings_media':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('options-media.php');
					break;
				case 'settings_permalinks':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('options-permalink.php');
					break;
				case 'settings_privacy':
					$result = $this->get_current_url_path(true) === $this->get_admin_url_path('privacy.php');
					break;
				default:
					$result = true;
			}
		}

		return $this->apply_operator($operator, $result, true);
	}

	/**
	 * Проверяет текущий URL страницы.
	 *
	 * Если url в условии и url текущей страницы совпадают,
	 * условие будет выполнено успешно.
	 *
	 * @param string $operator
	 * @param string $value
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 */
	protected function current_url($operator, $value)
	{
		$value = ("/" === $value) ? "/" : untrailingslashit($value);
		$current_url = $this->get_current_url_path();

		return $this->apply_operator($operator, $value, $current_url);
	}

	/**
	 * A post type of the current page
	 *
	 * @param $operator
	 * @param $value
	 *
	 * @return boolean
	 */
	protected function location_post_type($operator, $value)
	{
		if( is_singular() ) {
			return $this->apply_operator($operator, $value, get_post_type());
		}

		return false;
	}

	/**
	 * A taxonomy of the current page
	 *
	 * @param $operator
	 * @param $value
	 *
	 * @return boolean
	 * @since 2.2.8 The bug is fixed, the condition was not checked
	 *              for tachonomies, only posts.
	 *
	 */
	protected function location_taxonomy($operator, $value)
	{
		$taxonomy = null;

		if( is_tax() || is_tag() || is_category() ) {
			$taxonomy = get_queried_object()->taxonomy;
		}

		return $this->apply_operator($operator, $taxonomy, $value);
	}

	/**
	 * Checking for the existence of a variable in a query string
	 *
	 * @param $operator
	 * @param $value
	 *
	 * @return boolean
	 * @since 2.2.8 The bug is fixed, the condition was not checked
	 *              for tachonomies, only posts.
	 *
	 */
	protected function query_string($operator, $value)
	{
		if( is_object($value) ) {
			if( !empty($value->var_name) && isset($_GET[$value->var_name]) ) {
				return $this->apply_operator($operator, $_GET[$value->var_name], $value->var_value);
			}
		}

		return false;
	}
}