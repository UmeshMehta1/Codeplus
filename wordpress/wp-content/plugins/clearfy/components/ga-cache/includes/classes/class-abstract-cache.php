<?php

namespace WGA\Busting;

use FilesystemIterator;
use IteratorIterator;
use RegexIterator;

/**
 * This class configures the google analytics cache
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2020 CreativeMotion Ltd
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

abstract class Abstract_Cache {

	/**
	 * Cache busting files base path
	 *
	 * @var string
	 */
	protected $busting_path;

	/**
	 * Cache busting base URL
	 *
	 * @var string
	 */
	protected $busting_url;

	/**
	 * Filename for the cache busting file.
	 *
	 * @var string
	 */
	protected $filename;

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
	 * @since 3.2.0
	 */
	public function __construct($busting_path, $busting_url)
	{
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
	 * Tell if the cache busting should happen.
	 *
	 * @return bool
	 * @since 3.2.0
	 *
	 */
	private function is_allowed()
	{
		//if( defined('DONOTROCKETOPTIMIZE') && DONOTROCKETOPTIMIZE ) {
		//return false;
		//}

		return true;
	}

	/**
	 * Saves the content of the URL to bust to the busting file.
	 *
	 * @param string $url URL to get the content from.
	 * @return bool
	 * @since  3.2.0
	 */
	public function refresh_save($url)
	{
		// Before doing anything, make sure the busting file can be created.
		if( !$this->is_busting_dir_writable() ) {
			return false;
		}

		// Get remote content.
		$content = $this->get_remote_contents($url);

		if( !$content ) {
			// Could not get the remote contents.
			return false;
		}

		$version = md5($content);
		$path = $this->get_busting_file_path($version);

		return $this->update_file_contents($path, $content);
	}


	/**
	 * Performs the replacement process.
	 *
	 * @param string $html HTML content.
	 * @return string
	 * @since 3.2.0
	 */
	abstract public function replace_url($html);

	/**
	 * Searches for element(s) in the DOM
	 *
	 * @param string $pattern Pattern to match.
	 * @param string $html HTML content.
	 * @return string
	 * @since 3.2.0
	 */
	abstract protected function find($pattern, $html);

	/**
	 * Saves the content of the URL to bust to the busting file if it doesn't exist yet.
	 *
	 * @param string $url URL to get the content from.
	 * @return bool
	 * @since 3.2.0
	 */
	public function save($url)
	{
		if( $this->get_busting_version() ) {
			// We have a local copy.
			\WGA_Plugin::app()->logger->info('Found local file. Busting path ' . $this->get_busting_path());

			return true;
		}

		if( $this->refresh_save($url) ) {
			// We downloaded a fresh copy.
			\WGA_Plugin::app()->logger->info('New copy downloaded. Busting path ' . $this->get_busting_path());

			return true;
		}

		return false;
	}

	/**
	 * Deletes the busting file.
	 *
	 * @return bool True on success. False on failure.
	 * @since 3.2.0
	 */
	public function delete()
	{
		$files = $this->get_all_files();

		if( false === $files ) {
			// Error.
			return false;
		}

		$this->file_version = null;

		if( !$files ) {
			// No local files yet.
			return true;
		}

		return $this->delete_files(array_keys($files));
	}

	/**
	 * Get the version of the current busting file.
	 *
	 * @return string|bool Version of the file. False if the file does not exist.
	 *
	 *
	 * @since 3.2.0
	 */
	protected function get_busting_version()
	{
		if( !empty($this->file_version) ) {
			return $this->file_version;
		}

		$files = $this->get_all_files();

		if( !$files ) {
			return false;
		}

		$this->file_version = reset($files);

		return $this->file_version;
	}

	/**
	 * Get all cached files in the directory.
	 * In a perfect world, there should be only one.
	 *
	 * @return bool|array A list of file names (as array keys) and versions (as array values). False on failure.
	 * @since 3.2.0
	 * @access private
	 *
	 */
	private function get_all_files()
	{
		$dir_path = rtrim($this->busting_path, '\\/');

		if( !$this->filesystem->exists($dir_path) ) {
			return [];
		}

		if( !$this->filesystem->is_readable($dir_path) ) {
			\WGA_Plugin::app()->logger->error('Directory is not readable. Path ' . $dir_path);

			return false;
		}

		$pattern = '/' . sprintf($this->escape_file_name($this->filename_pattern), '([a-f0-9]{32}|local)') . '/';

		$entries = $this->get_dir_files_by_regex($dir_path, $pattern);

		$list = [];
		foreach($entries as $entry) {
			$filename = $entry->getFilename();

			preg_match($pattern, $filename, $file_details_match);
			if( !empty($file_details_match[1]) ) {
				$list[$filename] = $file_details_match[1];
			}
		}

		return $list;
	}

	/**
	 * Get the final URL for the current cache busting file.
	 *
	 * @return string|bool URL of the file. False if the file does not exist.
	 * @since 3.2.0
	 * @access protected
	 *
	 */
	public function get_busting_url()
	{
		return $this->get_busting_file_url($this->get_busting_version());
	}

	/**
	 * Get the path to the current cache busting file.
	 *
	 * @return string|bool URL of the file. False if the file does not exist.
	 *
	 *
	 * @since 3.2.0
	 * @access protected
	 */
	protected function get_busting_path()
	{
		return $this->get_busting_file_path($this->get_busting_version());
	}

	/**
	 * Get the final URL for a cache busting file.
	 *
	 * @param string $version The file version.
	 * @return string|bool     URL of the file with this version. False if no versions are provided.
	 * @since 3.2.0
	 * @access private
	 *
	 *
	 */
	private function get_busting_file_url($version)
	{
		if( !$version ) {
			return false;
		}

		$filename = $this->get_busting_file_name($version);

		return $this->busting_url . $filename;
	}

	/**
	 * Get the local file name.
	 *
	 * @param string $version The file version.
	 * @return string|bool     The name of the file with this version. False if no versions are provided.
	 * @since 3.2.0
	 * @access private
	 */
	private function get_busting_file_name($version)
	{
		if( !$version ) {
			return false;
		}

		return sprintf($this->filename_pattern, $version);
	}

	/**
	 * Get the local file path.
	 *
	 * @param string $version The file version.
	 * @return string|bool     Path to the file with this version. False if no versions are provided.
	 * @since 3.2.0
	 * @access private
	 */
	private function get_busting_file_path($version)
	{
		if( !$version ) {
			return false;
		}

		return $this->busting_path . $this->get_busting_file_name($version);
	}

	/**
	 * Escape a file name, to be used in a regex pattern (delimiter is `/`).
	 * `%s` conversion specifications are protected.
	 *
	 * @param string $filename_pattern The file name.
	 * @return string
	 * @since 3.2.0
	 * @access private
	 *
	 */
	private function escape_file_name($filename_pattern)
	{
		return preg_quote($filename_pattern, '/');
	}

	/**
	 * Delete busting files.
	 *
	 * @param array $files A list of file names.
	 * @return bool         True if files have been deleted (or no files have been provided). False on failure.
	 * @since 3.2.0
	 */
	private function delete_files($files)
	{
		if( !$files ) {
			return true;
		}

		$has_deleted = false;
		$error_paths = [];

		foreach($files as $file_name) {
			if( !$this->filesystem->delete($this->busting_path . $file_name, false, 'f') ) {
				$error_paths[] = $this->busting_path . $file_name;
			} else {
				$has_deleted = true;
			}
		}

		if( $error_paths ) {
			\WGA_Plugin::app()->logger->error('Local file(s) could not be deleted. Path ' . $error_paths);
		}

		return $has_deleted;
	}

	/**
	 * Add new contents to a file. If the file doesn't exist, it is created.
	 *
	 * @param string $file_path Path to the file to update.
	 * @param string $file_contents New contents.
	 * @return string|bool           The file contents on success. False on failure.
	 *
	 *
	 * @since 3.2.0
	 * @access private
	 */
	private function update_file_contents($file_path, $file_contents)
	{
		if( !$this->is_busting_dir_writable() ) {
			return false;
		}

		if( !$this->filesystem->put_contents($file_path, $file_contents) ) {
			\WGA_Plugin::app()->logger->error('Contents could not be written into file. Path ' . $file_path);

			return false;
		}

		return $file_contents;
	}

	/**
	 * Tell if the directory containing the busting file is writable.
	 *
	 * @return bool
	 *
	 *
	 * @since  3.2
	 * @access private
	 */
	private function is_busting_dir_writable()
	{
		if( !$this->filesystem->exists($this->busting_path) ) {
			wp_mkdir_p($this->busting_path);
		}

		if( !$this->filesystem->is_writable($this->busting_path) ) {
			\WGA_Plugin::app()->logger->error('Directory is not writable. Paths ' . $this->busting_path);

			return false;
		}

		return true;
	}

	/** ----------------------------------------------------------------------------------------- */
	/** GET LOCAL/REMOTE CONTENTS =============================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get a file contents. If the file doesn't exist, new contents are fetched remotely.
	 *
	 * @param string $file_path Path to the file.
	 * @param string $file_url URL to the remote file.
	 * @return string|bool       The contents on success, false on failure.
	 *
	 * @since 3.2.0
	 */
	private function get_file_or_remote_contents($file_path, $file_url)
	{
		$content = $this->get_file_contents($file_path);

		if( $content ) {
			// We have a local file.
			return $content;
		}

		return $this->get_remote_contents($file_url);
	}

	/**
	 * Get a file contents.
	 *
	 * @param string $file_path Path to the file.
	 * @return string|bool       The contents on success, false on failure.
	 * @since 3.2.0
	 * @access private
	 *
	 *
	 */
	private function get_file_contents($file_path)
	{
		if( !$this->filesystem->exists($file_path) ) {
			\WGA_Plugin::app()->logger->error('Local file does not exist. Path ' . $file_path);

			return false;
		}

		if( !$this->filesystem->is_readable($file_path) ) {
			\WGA_Plugin::app()->logger->error('Local file is not readable. Path' . $file_path);

			return false;
		}

		$content = $this->filesystem->get_contents($file_path);

		if( !$content ) {
			\WGA_Plugin::app()->logger->error('Local file is empty. Path' . $file_path);

			return false;
		}

		return $content;
	}

	/**
	 * Get the contents of a URL.
	 *
	 * @param string $url The URL to request.
	 * @return string|bool The contents on success. False on failure.
	 * @since 3.2.0
	 * @access private
	 *
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
	 * Gets Directory files matches regex.
	 *
	 * @param string $dir Directory to search for files inside it.
	 * @param string $regex Regular expression for files need to be searched for.
	 *
	 * @return array|RegexIterator List of files matches this regular expression.
	 * @since 3.6.3
	 * @access private
	 *
	 */
	function get_dir_files_by_regex($dir, $regex)
	{ // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		try {
			$iterator = new IteratorIterator(new FilesystemIterator($dir));

			return new RegexIterator($iterator, $regex);
		} catch( \Exception $e ) {
			return [];
		}
	}
}
