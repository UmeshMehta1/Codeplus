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

class Google_Tag_Manager_Cache extends Abstract_Cache {

	/**
	 * Context used for the logger.
	 *
	 * @var    string
	 * @since  3.2.4
	 */
	const LOGGER_CONTEXT = 'gg tag manager';

	/**
	 * File name (local).
	 * %s is a "version": a md5 hash of the file contents.
	 *
	 * @var    string
	 * @since  3.2.4
	 * @access protected
	 */
	protected $filename_pattern = 'gtm-%s.js';

	/**
	 * Current file version (local): a md5 hash of the file contents.
	 *
	 * @var    string
	 * @since  3.2.4
	 * @access protected
	 */
	protected $file_version;

	/**
	 * Filesystem object.
	 *
	 * @var    object
	 * @since  3.2.4
	 * @access protected
	 */
	protected $filesystem = false;

	/**
	 * Google Analytics object.
	 *
	 * @var    object
	 * @since  3.2.4
	 * @access protected
	 */
	protected $ga_busting = false;

	/**
	 * Constructor.
	 *
	 * @param string $busting_path Path to the busting directory.
	 * @param string $busting_url URL of the busting directory.
	 * @param Google_Analytics_Cache $ga_busting A GoogleAnalytics instance.
	 * @since  3.1
	 * @access public
	 *
	 */
	public function __construct($busting_path, $busting_url, Google_Analytics_Cache $ga_busting)
	{
		parent::__construct($busting_path, $busting_url);
		$blog_id = get_current_blog_id();
		$this->busting_path = $busting_path . $blog_id . '/';
		$this->busting_url = $busting_url . $blog_id . '/';
		$this->ga_busting = $ga_busting;
	}

	/**
	 * Performs the replacement process.
	 *
	 * @param string $html HTML content.
	 * @return string
	 * @since  3.1
	 * @access public
	 *
	 */
	public function replace_url($html)
	{
		$script = $this->find('<script(\s+[^>]+)?\s+src\s*=\s*[\'"]\s*?((?:https?:)?\/\/www\.googletagmanager\.com(?:.+)?)\s*?[\'"]([^>]+)?\/?>', $html);

		if( !$script ) {
			return $html;
		}

		// replace relative protocol // with full https://.
		$gtm_url = preg_replace('/^\/\//', 'https://', $script[2]);

		\WGA_Plugin::app()->logger->info('GOOGLE TAG MANAGER CACHING PROCESS STARTED. Script ' . $script);

		if( !$this->save($gtm_url) ) {
			return $html;
		}

		$replace_script = str_replace($script[2], $this->get_busting_url(), $script[0]);
		$replace_script = str_replace('<script', '<script data-no-minify="1"', $replace_script);
		$html = str_replace($script[0], $replace_script, $html);

		\WGA_Plugin::app()->logger->info('Google Tag Manager caching process succeeded. Path  ' . $this->get_busting_path());

		return $html;
	}

	/**
	 * Searches for element(s) in the DOM.
	 *
	 * @param string $pattern Pattern to match.
	 * @param string $html HTML content.
	 * @return string
	 * @since  3.1
	 * @access public
	 *
	 */
	protected function find($pattern, $html)
	{
		preg_match_all('/' . $pattern . '/Umsi', $html, $matches, PREG_SET_ORDER);

		if( empty($matches) ) {
			return false;
		}

		return $matches[0];
	}

	/**
	 * Replaces the Google Analytics URL by the local copy inside the gtm-local.js file content
	 *
	 * @param string $content JavaScript content.
	 * @return string
	 * @since 3.1
	 *
	 */
	protected function replace_ga_url($content)
	{
		if( !$this->ga_busting->save($this->ga_busting->get_url()) ) {
			return $content;
		}

		return str_replace($this->ga_busting->get_url(), $this->ga_busting->get_busting_url(), $content);
	}
}