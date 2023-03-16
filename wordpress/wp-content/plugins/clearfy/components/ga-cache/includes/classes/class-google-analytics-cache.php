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

class Google_Analytics_Cache extends Abstract_Cache {

	/**
	 * Google Analytics URL.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	protected $url = 'https://www.google-analytics.com/analytics.js';

	/**
	 * File name (local).
	 * %s is a "version": a md5 hash of the file contents.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	protected $filename_pattern = 'ga-%s.js';

	/**
	 * Current file version (local): a md5 hash of the file contents.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	protected $file_version;

	/**
	 * Flag to track the replacement.
	 *
	 * @var    bool
	 * @since  3.2.0
	 */
	protected $is_replaced = false;

	/**
	 * Constructor.
	 *
	 * @param string $busting_path Path to the busting directory.
	 * @param string $busting_url URL of the busting directory.
	 * @since  3.1
	 * @access public
	 *
	 */
	public function __construct($busting_path, $busting_url)
	{
		parent::__construct($busting_path, $busting_url);

		$this->busting_path = $busting_path . 'google-tracking/';
		$this->busting_url = $busting_url . 'google-tracking/';
	}

	/**
	 * Performs the replacement process.
	 *
	 * @param string $html HTML content.
	 * @return string
	 * @since  3.2.0
	 *
	 */
	public function replace_url($html)
	{
		$this->is_replaced = false;

		$tag = $this->find('<script\s*(?<attr>[^>]*)?>(?<content>[^<]+)?<\/script>', $html);

		if( !$tag ) {
			return $html;
		}

		\WGA_Plugin::app()->logger->info('GOOGLE ANALYTICS CACHING PROCESS STARTED. TAG #' . $tag);

		if( !$this->save($this->url) ) {
			return $html;
		}

		$replace_tag = preg_replace('/(?:https?:)?\/\/www\.google-analytics\.com\/analytics\.js/i', $this->get_busting_url(), $tag);
		$html = str_replace($tag, $replace_tag, $html);

		$this->is_replaced = true;

		\WGA_Plugin::app()->logger->info('Google Analytics caching process succeeded. Busting path ' . $this->get_busting_path());

		return $html;
	}

	/**
	 * Tell if the replacement was sucessful or not.
	 *
	 * @return bool
	 *
	 * @since  3.2.0
	 */
	public function is_replaced()
	{
		return $this->is_replaced;
	}

	/**
	 * Get the Google Analytics URL.
	 *
	 * @return string
	 * @author Remy Perona
	 *
	 * @since  3.1
	 * @access public
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 * Searches for element(s) in the DOM.
	 *
	 * @param string $pattern Pattern to match.
	 * @param string $html HTML content.
	 * @return string
	 * @author Remy Perona
	 *
	 * @since  3.1
	 * @access public
	 */
	protected function find($pattern, $html)
	{
		preg_match_all('/' . $pattern . '/is', $html, $all_matches, PREG_SET_ORDER);

		$matches = array_map(function ($match) {

			if( empty($match['content']) || (!preg_match('/src\s*=\s*[\'"]\s*(?:https?:)?\/\/www\.google-analytics\.com\/analytics\.js\s*[\'"]/i', $match['attr'] . $match['content']) && false === strpos($match['content'], 'GoogleAnalyticsObject')) ) {
				return;
			}

			return $match[0];
		}, $all_matches);

		$matches = array_values(array_filter($matches));

		if( !$matches ) {
			return false;
		}

		return $matches[0];
	}
}