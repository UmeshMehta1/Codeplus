<?php

namespace WGA\Busting;

/**
 * This class configures the google analytics cache
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2017 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

class Facebook_Pixel_Cache {

	/**
	 * Regex pattern to capture a locale.
	 *
	 * @var    string
	 * @since  3.2
	 */
	const LOCALE_CAPTURE = '(?<locale>[a-zA-Z_-]+)';

	/**
	 * Regex pattern to capture a version.
	 *
	 * @var    string
	 * @since  3.2
	 */
	const VERSION_CAPTURE = '(?<version>[\d\.]+)';

	/**
	 * Regex pattern to capture an app ID.
	 *
	 * @var    string
	 * @since  3.2
	 */
	const APP_ID_CAPTURE = '(?<app_id>\d+)';

	/**
	 * Cache busting files base path.
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $busting_path;

	/**
	 * Cache busting base URL.
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $busting_url;

	/**
	 * Main file URL (remote).
	 * %s is a locale like "en_US".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $main_file_url = 'https://connect.facebook.net/%s/fbevents.js';

	/**
	 * Main file name (local).
	 * %s is like "{{locale}}-{{version}}".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $main_file_name = 'fbpix-events-%s.js';

	/**
	 * Config file URL (remote).
	 * %d is an app ID (a number), %s is a version like "2.8.30".
	 * The "r" argument is the release segment: it is considered "stable".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $config_file_url = 'https://connect.facebook.net/signals/config/%s?v=%s&r=stable';

	/**
	 * Config file name (local).
	 * %s is like "{{app_id}}-{{version}}".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $config_file_name = 'fbpix-config-%s.js';

	/**
	 * Plugins file URL (remote).
	 * 1st %s is a plugin name like "identity", 2nd %s is a version like "2.8.30".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $plugins_file_url = 'https://connect.facebook.net/signals/plugins/%s?v=%s';

	/**
	 * Plugins file name (local).
	 * %s is like "{{plugin_name}}-{{version}}".
	 *
	 * @var    string
	 * @since  3.2
	 */
	private $plugins_file_name = 'fbpix-plugin-%s.js';

	/**
	 * Flag to track the replacement.
	 *
	 * @var    bool
	 * @since  3.2
	 */
	private $is_replaced = false;

	/**
	 * Filesystem object.
	 *
	 * @var    object
	 * @since  3.2.0
	 */
	protected $filesystem = false;

	/**
	 * Constructor.
	 *
	 * @param string $busting_path Path to the busting directory.
	 * @param string $busting_url URL of the busting directory.
	 * @since  3.2
	 *
	 */
	public function __construct($busting_path, $busting_url)
	{
		/** Warning: all file names and script URLs are dynamic, and must be run through sprintf(). */
		$this->busting_path = $busting_path . 'facebook-tracking/';
		$this->busting_url = $busting_url . 'facebook-tracking/';

		/*
			* Define the timeouts for the connections. Only available after the constructor is called
			* to allow for per-transport overriding of the default.
			*/
		if( !defined('FS_CONNECT_TIMEOUT') ) {
			define('FS_CONNECT_TIMEOUT', 30);
		}
		if( !defined('FS_TIMEOUT') ) {
			define('FS_TIMEOUT', 30);
		}

		// Set the permission constants if not already set.
		if( !defined('FS_CHMOD_DIR') ) {
			define('FS_CHMOD_DIR', (fileperms(ABSPATH) & 0777 | 0755));
		}
		if( !defined('FS_CHMOD_FILE') ) {
			define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php') & 0777 | 0644));
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$this->filesystem = new \WP_Filesystem_Direct(new \StdClass());
	}

	/**
	 * Perform the URL replacement process.
	 *
	 * @param string $html HTML contents.
	 * @return string       HTML contents.
	 * @since  3.2
	 *
	 */
	public function replace_url($html)
	{
		$this->is_replaced = false;

		$tags = $this->find_tags($html);

		if( !$tags ) {
			return $html;
		}

		\WGA_Plugin::app()->logger->info('FACEBOOK PIXEL CACHING PROCESS STARTED. Tag ' . $tags['tag_to_search']);

		$all_files = [];

		/**
		 * Fetch the main file: https://connect.facebook.net/{{locale}}/fbevents.js.
		 */
		$version = $this->get_most_recent_local_version();
		$locale = $this->get_locale_from_url($tags['tag_to_search']);
		$main_file_url = $this->get_main_file_url($locale);

		if( $version ) {
			// At least 1 main file exists locally (but maybe not in the right locale).
			$main_file_path = $this->get_busting_file_path($locale, $version);
			$main_file_contents = $this->get_file_contents($main_file_path, $main_file_url);
		} else {
			// No cached files yet.
			$main_file_contents = $this->get_remote_contents($main_file_url);
		}

		if( !$main_file_contents ) {
			return $html;
		}

		/**
		 * Grab some data from the main file and the inline tag: app_id and version.
		 */
		$variables = $this->get_variables($main_file_contents, $tags['tag_to_search']);

		if( !$variables ) {
			return $html;
		}

		if( !$version ) {
			// The local file doesn't exist yet, so we couldn't get its version (and so, can't know its path yet) until we fetch a fresh copy.
			$main_file_path = $this->get_busting_file_path($locale, $variables['version']);
		}

		$all_files[] = $main_file_path;
		unset($version);

		/**
		 * Fetch the config file: https://connect.facebook.net/signals/config/{{app_id}}?v={{version}}&r={{release_segment​}}.
		 */
		$config_file_path = $this->get_config_file_path($variables);

		if( !$config_file_path ) {
			return $html;
		}

		$all_files[] = $config_file_path;

		/**
		 * Fetch all plugin files: https://connect.facebook.net/signals/plugins/{{pluginName}}.js?v={{version}}​.
		 */
		$plugin_file_paths = $this->get_plugin_file_paths($variables);

		if( !$plugin_file_paths ) {
			return $html;
		}

		$all_files = array_merge($all_files, $plugin_file_paths);

		/**
		 * Modify the main file contents.
		 */
		$busting_file_url = $this->get_busting_file_url($locale, $variables['version']);
		$busting_dir_url = dirname($busting_file_url) . '/';
		$main_file_contents = $this->replace_main_file_contents($main_file_contents, $busting_dir_url);

		if( !$main_file_contents ) {
			return $html;
		}

		/**
		 * Save all the changes to the main file.
		 */
		$updated = $this->update_file_contents($main_file_path, $main_file_contents);

		if( !$updated ) {
			return $html;
		}

		/**
		 * Finally, replace the main file URL by the local one in the inline script tag.
		 */
		$replace_tag = preg_replace('@(?:https?:)?//connect\.facebook\.net/[a-zA-Z_-]+/fbevents\.js@i', $busting_file_url, $tags['tag_to_replace'], -1, $count);

		if( !$count || false === strpos($html, $tags['tag_to_replace']) ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: The local file URL could not be replaced in the page contents.');

			return $html;
		}

		$html = str_replace($tags['tag_to_replace'], $replace_tag, $html);

		$this->is_replaced = true;

		\WGA_Plugin::app()->logger->error('Fb pixel: Facebook pixel caching process succeeded. Files ' . $all_files);

		return $html;
	}

	/**
	 * Tell if the replacement was sucessful or not.
	 *
	 * @return bool
	 * @since  3.2
	 * @access public
	 *
	 */
	public function is_replaced()
	{
		return $this->is_replaced;
	}

	/**
	 * Search for elements in the DOM.
	 *
	 * @param string $html HTML contents.
	 * @return array|bool   {
	 *     An array on success, described as below. False if nothing is found.
	 *
	 * @type string $tag_to_replace The script tag that contains the facebook.net URL: this is the tag that will be replaced in the page HTML.
	 * @type string $tag_to_search It contains both app ID and facebook.net URL: this is what will be searched in for this data.
	 *
	 *     When the app ID and the URL are in the same tag, $tag_to_replace and $tag_to_search are the same.
	 * }
	 * @since  3.2
	 *
	 */
	private function find_tags($html)
	{
		preg_match_all('@<script[^>]*?>(.*)</script>@Umsi', $html, $matches, PREG_SET_ORDER);

		if( empty($matches) ) {
			return false;
		}

		$tags = [
			'app_id' => [],
			'url' => [],
			'both' => [],
		];

		foreach($matches as $match) {
			list($tag, $script) = $match;

			if( !trim($script) ) {
				continue;
			}

			$has_app_id = false;
			$has_url = false;

			if( preg_match('@fbq\s*\(\s*["\']init["\']\s*,\s*["\']' . self::APP_ID_CAPTURE . '["\']@', $script, $matches_init) ) {
				if( (int)$matches_init['app_id'] > 0 ) {
					$has_app_id = true;
				}
			}

			$has_url = (bool)$this->get_locale_from_url($script);

			if( $has_app_id && $has_url ) {
				// OK we have both.
				$tags['both'] = $tag;
				break;
			}

			if( $has_app_id ) {
				$tags['app_id'] = $tag;

				if( $tags['url'] ) {
					// OK we have both.
					break;
				}
			} elseif( $has_url ) {
				$tags['url'] = $tag;

				if( $tags['app_id'] ) {
					// OK we have both.
					break;
				}
			}
		}

		if( !empty($tags['both']) ) {
			return [
				'tag_to_replace' => $tags['both'],
				'tag_to_search' => $tags['both'],
			];
		}

		if( !empty($tags['app_id']) && !empty($tags['url']) ) {
			return [
				'tag_to_replace' => $tags['url'],
				'tag_to_search' => $tags['url'] . $tags['app_id'],
			];
		}

		return false;
	}

	/**
	 * Get some values from the main file and the inline script contents.
	 *
	 * @param string $main_file_contents Main file contents.
	 * @param string $tag_contents Inline script contents.
	 * @return array|bool {
	 *     An array of values. False on failure.
	 *
	 * @type string $app_id The app ID.
	 * @type string $version The file version.
	 * }
	 * @since  3.2
	 *
	 */
	private function get_variables($main_file_contents = null, $tag_contents = null)
	{
		$variables = [];

		if( isset($tag_contents) ) {
			// Retrieve the app ID from the tag contents.
			preg_match('@fbq\s*\(\s*["\']init["\']\s*,\s*["\']' . self::APP_ID_CAPTURE . '["\']@', $tag_contents, $matches);

			if( empty($matches['app_id']) ) {
				\WGA_Plugin::app()->logger->error('Fb pixel: The app ID could not be retrieved from the inline script contents.');

				return false;
			}

			$variables['app_id'] = $matches['app_id'];
		}

		if( isset($main_file_contents) ) {
			// Retrieve the version from the main file contents.
			preg_match('@fbq\.version\s*=\s*["\']' . self::VERSION_CAPTURE . '["\']\s*;@', $main_file_contents, $matches);

			if( empty($matches['version']) ) {
				\WGA_Plugin::app()->logger->error('Fb pixel: The version could not be retrieved from the main file contents.');

				return false;
			}

			$variables['version'] = $matches['version'];
		}

		return $variables;
	}

	/**
	 * Perform some replacements in the main file contents. Will be replaced:
	 * - the CDN_BASE_URL value,
	 * - the config file URL,
	 * - the plugins file URL.
	 *
	 * @param string $main_file_contents The file contents.
	 * @param string $busting_dir_url URL of the folder containing the files.
	 * @return string|bool                The new contents on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function replace_main_file_contents($main_file_contents, $busting_dir_url)
	{
		/**
		 * Replace the CDN_BASE_URL value.
		 * From: CDN_BASE_URL:"https://connect.facebook.net/"
		 * To:   CDN_BASE_URL:"https://example.com/wp-content/cache/busting/facebook-tracking/"
		 */
		$replacement = 'CDN_BASE_URL:"' . $busting_dir_url . '"';

		if( !strpos($main_file_contents, $replacement) ) {
			$main_file_contents = preg_replace('@CDN_BASE_URL\s*:\s*["\'][^"\']+["\']@', $replacement, $main_file_contents, -1, $count);

			if( !$count ) {
				\WGA_Plugin::app()->logger->error('Fb pixel: The CDN_BASE_URL could not be replaced in the main file contents.');

				return false;
			}
		}

		/**
		 * Replace the config file URL (https://connect.facebook.net/signals/config/{{app_id}}?v={{version}}&r={{release_segment​}}).
		 * From: CDN_BASE_URL+"signals/config/"+a+"?v="+b+"&r="+c
		 * To:   CDN_BASE_URL+"fbpix-config-"+a+"-"+b+".js" (the release segment is not taken into account, we consider it "stable")
		 */
		$replacement_pattern = $this->escape_file_name($this->config_file_name);
		$replacement_pattern = sprintf($replacement_pattern, '"\+[a-zA-Z._]+\+"\-"\+[a-zA-Z._]+\+"');
		$replacement_pattern = 'CDN_BASE_URL\+"' . $replacement_pattern . '"';

		if( !preg_match('/' . $replacement_pattern . '/', $main_file_contents) ) {
			$pattern = '@CDN_BASE_URL\s*\+\s*["\']signals/config/["\']\s*\+\s*([a-zA-Z._]+)\s*\+\s*["\']\?v=["\']\s*\+\s*([a-zA-Z._]+)\s*\+\s*["\']&r=["\']\s*\+\s*[a-zA-Z._]+@';
			$replacement = 'CDN_BASE_URL+"' . sprintf($this->config_file_name, '"+$1+"-"+$2+"') . '"';
			$main_file_contents = preg_replace($pattern, $replacement, $main_file_contents, -1, $count);

			if( !$count ) {
				\WGA_Plugin::app()->logger->error('Fb pixel: The config file URL could not be replaced in the main file contents.');

				return false;
			}
		}

		/**
		 * Replace the plugins file URL (https://connect.facebook.net/signals/plugins/{{plugin_name}}.js?v={{version}}​).
		 * From: CDN_BASE_URL+"signals/plugins/"+b+".js?v="+a.version
		 * To  : CDN_BASE_URL+"fbpix-plugin-"+b+"-"+a.version+".js"
		 */
		$replacement_pattern = $this->escape_file_name($this->plugins_file_name);
		$replacement_pattern = sprintf($replacement_pattern, '"\+[a-zA-Z._]+\+"-"\+[a-zA-Z._]+\+"');
		$replacement_pattern = 'CDN_BASE_URL\+"' . $replacement_pattern . '"';

		if( !preg_match('/' . $replacement_pattern . '/', $main_file_contents) ) {
			$pattern = '@CDN_BASE_URL\s*\+\s*["\']signals/plugins/["\']\s*\+\s*([a-zA-Z._]+)\s*\+\s*["\']\.js\?v=["\']\s*\+\s*([a-zA-Z._]+)@';
			$replacement = 'CDN_BASE_URL+"' . sprintf($this->plugins_file_name, '"+$1+"-"+$2+"') . '"';
			$main_file_contents = preg_replace($pattern, $replacement, $main_file_contents, -1, $count);

			if( !$count ) {
				\WGA_Plugin::app()->logger->error('Fb pixel: The plugins file URL could not be replaced in the main file contents.');

				return false;
			}
		}

		return $main_file_contents;
	}

	/**
	 * Save the contents of a URL into a local file if it doesn't exist yet.
	 *
	 * @param string $url URL to get the contents from.
	 * @param string $path Path to the file that will store the URL contents.
	 * @return bool         True on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function maybe_save($url, $path)
	{
		$filesystem = $this->filesystem;

		if( $filesystem->exists($path) ) {
			// If a previous version is present, keep it.
			return true;
		}

		return (bool)$this->save($url, $path);
	}

	/**
	 * Save the contents of a URL into a local file.
	 *
	 * @param string $url URL to get the contents from.
	 * @param string $path Path to the file that will store the URL contents.
	 * @return string|bool  The file contents on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function save($url, $path)
	{
		$contents = $this->get_remote_contents($url);

		if( !$contents ) {
			// Error, we couldn't fetch the file contents.
			return false;
		}

		return $this->update_file_contents($path, $contents);
	}

	/**
	 * Add new contents to a file. If the file doesn't exist, it is created.
	 *
	 * @param string $file_path Path to the file to update.
	 * @param string $file_contents New contents.
	 * @return string|bool           The file contents on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function update_file_contents($file_path, $file_contents)
	{
		if( !$this->filesystem->exists($this->busting_path) ) {
			wp_mkdir_p($this->busting_path);
		}

		if( !$this->filesystem->put_contents($file_path, $file_contents) ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: Contents could not be written into file. Paths ' . $file_path);

			return false;
		}

		return $file_contents;
	}

	/**
	 * Look for existing local files and update their contents if there's a new version available.
	 * Actually, if a more recent version exists on the FB side, it will delete all local files and hit the home page to recreate them.
	 *
	 * @return bool True on success. False on failure.
	 * @since  3.2
	 *
	 */
	public function refresh_all()
	{
		// Get all local main files.
		$main_files = $this->get_all_main_files();

		if( !$main_files ) {
			// No files (or there's an error).
			return false !== $main_files;
		}

		$updated = false;

		foreach($main_files as $local_main_file) {
			$remote_file_contents = $this->get_remote_contents($this->get_main_file_url($local_main_file['locale']));

			if( !$remote_file_contents ) {
				continue;
			}

			$variables = $this->get_variables($remote_file_contents);

			if( !$variables ) {
				unset($remote_file_contents, $variables);
				continue;
			}

			if( version_compare($local_main_file['version'], $variables['version']) >= 0 ) {
				unset($remote_file_contents, $variables);
				continue;
			}

			unset($remote_file_contents);
			$updated = true;
			break;
		}

		if( !$updated ) {
			return true;
		}

		// Delete all local files.
		$this->delete_all();

		return true;
	}

	/**
	 * Delete all Facebook Pixel busting files.
	 *
	 * @return bool True on success. False on failure.
	 * @since  3.2
	 * @access public
	 *
	 */
	public function delete_all()
	{
		$filesystem = $this->filesystem;
		$files = $this->get_all_files();

		if( !$files ) {
			// No files (or there's an error).
			return false !== $files;
		}

		$error_paths = [];

		foreach($files as $file_name) {
			if( !$filesystem->delete($this->busting_path . $file_name, false, 'f') ) {
				$error_paths[] = $this->busting_path . $file_name;
			}
		}

		if( $error_paths ) {
			\WGA_Plugin::app()->logger->error('Local file(s) could not be deleted. Paths ' . $error_paths);
		}

		return !$error_paths;
	}

	/**
	 * Get all cached files in the directory.
	 *
	 * @return array|bool A list of file names. False on failure.
	 * @since  3.2
	 *
	 */
	private function get_all_files()
	{
		$filesystem = $this->filesystem;
		$dir_path = rtrim($this->busting_path, '\\/');

		if( !$filesystem->exists($dir_path) ) {
			return [];
		}

		if( !$filesystem->is_writable($dir_path) ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: Directory is not writable. Path ' . $dir_path);

			return false;
		}

		$dir = $filesystem->dirlist($dir_path);

		if( false === $dir ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: could not get the directory contents. Path ' . $dir_path);

			return false;
		}

		if( !$dir ) {
			return [];
		}

		$list = [];

		foreach($dir as $entry) {
			if( 'f' !== $entry['type'] ) {
				continue;
			}
			if( preg_match('@^fbpix-(?:config|events|plugin)-.+\.js$@', $entry['name'], $matches) ) {
				$list[$entry['name']] = $entry['name'];
			}
		}

		return $list;
	}

	/**
	 * Get all main files in the directory.
	 *
	 * @return array|bool {
	 *     An array of file names (array keys) with following data as values. False on failure.
	 *
	 * @type string $locale The locale, like "en_US".
	 * @type string $version The file version.
	 * }
	 * @since  3.2
	 *
	 */
	private function get_all_main_files()
	{
		$filesystem = $this->filesystem;
		$dir_path = rtrim($this->busting_path, '\\/');

		if( !$filesystem->exists($dir_path) ) {
			return [];
		}

		if( !$filesystem->is_writable($dir_path) ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: Directory is not writable. Path ' . $dir_path);

			return false;
		}

		$dir = $filesystem->dirlist($dir_path);

		if( false === $dir ) {
			\WGA_Plugin::app()->logger->error('Fb pixel: could not get the directory contents. Path ' . $dir_path);

			return false;
		}

		if( !$dir ) {
			return [];
		}

		$list = [];
		$pattern = $this->escape_file_name($this->main_file_name);
		$pattern = sprintf($pattern, self::LOCALE_CAPTURE . '-' . self::VERSION_CAPTURE);

		foreach($dir as $entry) {
			if( 'f' !== $entry['type'] ) {
				continue;
			}
			if( preg_match('@^' . $pattern . '$@', $entry['name'], $matches) ) {
				unset($matches[0]);
				$list[$entry['name']] = $matches;
			}
		}

		return $list;
	}

	/**
	 * Get the most recent "version" of the main file cached locally.
	 *
	 * @return string|bool The version on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function get_most_recent_local_version()
	{
		$main_files = $this->get_all_main_files();

		if( !$main_files ) {
			return false;
		}

		$version = false;

		foreach($main_files as $file_name => $data) {
			if( !$version || version_compare($data['version'], $version) > 0 ) {
				$version = $data['version'];
			}
		}

		return $version;
	}

	/**
	 * Get the oldest "version" of the main file cached locally.
	 *
	 * @return string|bool The version on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function get_oldest_local_version()
	{
		$main_files = $this->get_all_main_files();

		if( !$main_files ) {
			return false;
		}

		$version = false;

		foreach($main_files as $file_name => $data) {
			if( !$version || version_compare($data['version'], $version) < 0 ) {
				$version = $data['version'];
			}
		}

		return $version;
	}

	/**
	 * Get the remote Facebook Pixel URL.
	 *
	 * @param string $locale A locale string, like 'en_US'.
	 * @return string
	 * @since  3.2
	 *
	 */
	public function get_main_file_url($locale)
	{
		return sprintf($this->main_file_url, $locale);
	}

	/**
	 * Extract the locale from a URL to bust.
	 *
	 * @param string $url Any string containing the URL to bust.
	 * @return string|bool The locale on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function get_locale_from_url($url)
	{
		$pattern = '@//connect\.facebook\.net/' . self::LOCALE_CAPTURE . '/fbevents\.js@i';

		if( !preg_match($pattern, $url, $matches) ) {
			return false;
		}

		return $matches['locale'];
	}

	/** ----------------------------------------------------------------------------------------- */
	/** BUSTING FILE (aka: cached copy of the main file) ======================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the local Facebook Pixel URL (the "main" file).
	 *
	 * @param string $locale A locale string, like 'en_US'.
	 * @param string $version The script version.
	 * @return string
	 * @since  3.2
	 *
	 */
	private function get_busting_file_url($locale, $version)
	{
		$filename = $this->get_busting_file_name($locale, $version);

		// This filter is documented in inc/functions/minify.php.
		return $this->busting_url . $filename;
	}

	/**
	 * Get the local Facebook Pixel file name.
	 *
	 * @param string $locale A locale string, like 'en_US'.
	 * @param string $version The script version.
	 * @return string
	 * @since  3.2
	 *
	 */
	private function get_busting_file_name($locale, $version)
	{
		return sprintf($this->main_file_name, $locale . '-' . $version);
	}

	/**
	 * Get the local Facebook Pixel file path.
	 *
	 * @param string $locale A locale string, like 'en_US'.
	 * @param string $version The script version.
	 * @return string
	 * @since  3.2
	 *
	 */
	private function get_busting_file_path($locale, $version)
	{
		return $this->busting_path . $this->get_busting_file_name($locale, $version);
	}

	/** ----------------------------------------------------------------------------------------- */
	/** CONFIG FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the path to the local "config" file. If the file doesn't exist, it is created by fetching its contents remotely, then saved locally.
	 *
	 * @param array $variables {
	 *     An array of variable values.
	 *
	 * @type int $app_id The app ID.
	 * @type string $version The file version.
	 * }
	 * @return string|bool The file path on success. False on failure.
	 * @since  3.2
	 * @access private
	 * @see    $this->get_variables()
	 *
	 */
	private function get_config_file_path($variables)
	{
		$config_file_url = sprintf($this->config_file_url, $variables['app_id'], $variables['version']);
		$config_file_name = sprintf($this->config_file_name, $variables['app_id'] . '-' . $variables['version']);
		$config_file_path = $this->busting_path . $config_file_name;

		if( !$this->maybe_save($config_file_url, $config_file_path) ) {
			return false;
		}

		return $config_file_path;
	}

	/**
	 * Get the paths to all local "plugin" files. If the files don't exist, they are created by fetching their contents remotely, then saved locally.
	 *
	 * @param array $variables {
	 *     An array of variable values.
	 *
	 * @type string $app_id The app ID.
	 * @type string $version The file version.
	 * }
	 * @return array|bool An array of file paths on success. False on failure.
	 * @since  3.2
	 * @see    $this->get_variables()
	 *
	 */
	private function get_plugin_file_paths($variables)
	{
		$paths = [];
		$plugin_names = [
			'identity',
			'microdata',
			'inferredEvents',
			'dwell',
			'sessions',
			'timespent',
			'ga2fbq',
		];

		foreach($plugin_names as $plugin_name) {
			$plugin_file_url = sprintf($this->plugins_file_url, $plugin_name, $variables['version']);
			$plugin_file_name = sprintf($this->plugins_file_name, $plugin_name . '-' . $variables['version']);
			$plugin_file_path = $this->busting_path . $plugin_file_name;

			if( !$this->maybe_save($plugin_file_url, $plugin_file_path) ) {
				return false;
			}

			$paths[] = $plugin_file_path;
		}

		return $paths;
	}

	/**
	 * Get a file contents. If the file doesn't exist or is not writtable, new contents are fetched remotely.
	 *
	 * @param string $file_path Path to the file.
	 * @param string $file_url URL to the remote file.
	 * @return string|bool       The contents on success, false on failure.
	 * @since  3.2
	 * @access private
	 *
	 */
	private function get_file_contents($file_path, $file_url = false)
	{
		$filesystem = $this->filesystem;

		if( $filesystem->is_writable($file_path) ) {
			// If a previous version is present, return its contents.
			$contents = $filesystem->get_contents($file_path);

			if( $contents ) {
				return $contents;
			}
			// In case the file is empty or we could not get its contents, try to get a fresh copy from remote location.
		}

		if( !$file_url ) {
			return false;
		}

		return $this->get_remote_contents($file_url);
	}

	/**
	 * Get the contents of a URL.
	 *
	 * @param string $url The URL to request.
	 * @return string|bool The contents on success. False on failure.
	 * @since  3.2
	 *
	 */
	private function get_remote_contents($url)
	{
		try {
			$response = wp_remote_get($url);
		} catch( \Exception $e ) {
			\WGA_Plugin::app()->logger->error('Remote file could not be fetched. Response ' . $e->getMessage());

			return false;
		}

		if( is_wp_error($response) ) {
			\WGA_Plugin::app()->logger->error('Remote file could not be fetched. Response ' . $response->get_error_message());

			return false;
		}

		$contents = wp_remote_retrieve_body($response);

		if( !$contents ) {
			\WGA_Plugin::app()->logger->error('Remote file could not be fetched. Response ' . $response->get_error_message());

			return false;
		}

		return $contents;
	}

	/**
	 * Escape a file name, to be used in a regex pattern (delimiter is `/`).
	 * `%s` conversion specifications are protected.
	 *
	 * @param string $file_name The file name.
	 * @return string
	 * @since  3.2
	 * @access private
	 * @author Grégory Viguier
	 *
	 */
	private function escape_file_name($file_name)
	{
		$file_name = explode('%s', $file_name);
		$file_name = array_map('preg_quote', $file_name);

		return implode('%s', $file_name);
	}
}
