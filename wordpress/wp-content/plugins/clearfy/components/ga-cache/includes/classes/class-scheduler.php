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

class Sheduller {

	protected $ga_processor;
	protected $gtm_processor;
	protected $fbpix_processor;
	protected $fbsdk_processor;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-abstract-cache.php';
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-google-analytics-cache.php';
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-yandex-metrika-cache.php';
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-google-tag-manager-cache.php';
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-facebook-sdk.php';
		require_once WGA_PLUGIN_DIR . '/includes/classes/class-facebook-cache.php';

		$uploads_dir = wp_get_upload_dir();

		if( !$uploads_dir ) {
			return false;
		}

		$busting_path = trailingslashit($uploads_dir['basedir']) . WGA_PLUGIN_CACHE_FOLDER . '/';
		$busting_url = trailingslashit($uploads_dir['baseurl']) . WGA_PLUGIN_CACHE_FOLDER . '/';

		if( \WGA_Plugin::app()->getPopulateOption('yandex_metrika_cache') ) {
			$this->ym_processor = new Yandex_Metrika_Cache($busting_path, $busting_url);
		}

		if( \WGA_Plugin::app()->getPopulateOption('google_analytics_cache') ) {
			$this->ga_processor = new Google_Analytics_Cache($busting_path, $busting_url);
			$this->gtm_processor = new Google_Tag_Manager_Cache($busting_path, $busting_url, $this->ga_processor);
		}
		if( \WGA_Plugin::app()->getPopulateOption('facebook_cache') ) {
			$this->fbpix_processor = new Facebook_Pixel_Cache($busting_path, $busting_url);
			$this->fbsdk_processor = new Facebook_SDK($busting_path, $busting_url);
		}

		add_action('cron_schedules', [$this, 'add_schedule']);
		add_action('wclearfy/google_tracking_cache_update', [$this, 'update_tracking_cache']);
		add_action('init', [$this, 'schedule_tracking_cache_update']);

		add_action('template_redirect', function () {
			ob_start([$this, 'cache_busting_google_tracking']);
		}, 1);

		add_action('wclearfy_delete_cache', [$this, 'delete_tracking_cache']);
	}

	/**
	 * Processes the cache busting on the HTML content
	 *
	 * Google Analytics replacement is performed first, and if no replacement occured, Google Tag Manager replacement is performed.
	 *
	 * @param string $html HTML content.
	 * @return string
	 * @since 3.1
	 *
	 */
	public function cache_busting_google_tracking($html)
	{
		if( !$this->is_busting_active() ) {
			return $html;
		}

		if( \WGA_Plugin::app()->getPopulateOption('yandex_metrika_cache') ) {
			$html = $this->ym_processor->replace_url($html);
		}

		if( \WGA_Plugin::app()->getPopulateOption('google_analytics_cache') ) {
			$html = $this->ga_processor->replace_url($html);
			$html = $this->gtm_processor->replace_url($html);
		}
		if( \WGA_Plugin::app()->getPopulateOption('facebook_cache') ) {
			$html = $this->fbpix_processor->replace_url($html);
			$html = $this->fbsdk_processor->replace_url($html);
		}

		return $html;
	}


	/**
	 * Adds weekly interval to cron schedules
	 *
	 * @param $schedules array An array of intervals used by cron jobs.
	 * @return []
	 * @since 3.2.0
	 *
	 */
	public function add_schedule($schedules)
	{
		if( !$this->is_busting_active() ) {
			return $schedules;
		}

		$schedules['weekly'] = [
			'interval' => 604800,
			'display' => __('weekly', 'clearfy'),
		];

		return $schedules;
	}

	/**
	 * Schedules the auto-update of Google Analytics cache busting file
	 *
	 * @return void
	 * @since 3.2.0
	 */
	public function schedule_tracking_cache_update()
	{
		if( !$this->is_busting_active() ) {
			return;
		}

		if( !wp_next_scheduled('wclearfy/google_tracking_cache_update') ) {
			wp_schedule_event(time(), 'weekly', 'wclearfy/google_tracking_cache_update');
		}
	}

	/**
	 * Updates Google Analytics cache busting file
	 *
	 * @return bool
	 * @since 3.2.0
	 */
	public function update_tracking_cache()
	{
		if( !$this->is_busting_active() ) {
			return false;
		}

		if( \WGA_Plugin::app()->getPopulateOption('yandex_metrika_cache') ) {
			$this->ym_processor->refresh_save($this->ym_processor->get_url());
		}

		if( \WGA_Plugin::app()->getPopulateOption('google_analytics_cache') ) {
			$this->ga_processor->refresh_save($this->ga_processor->get_url());
		}

		if( \WGA_Plugin::app()->getPopulateOption('facebook_cache') ) {
			$this->fbsdk_processor->refresh();
			$this->fbpix_processor->refresh_all();
		}

		return true;
	}

	/**
	 * Deletes the GA busting file.
	 *
	 * @return bool
	 * @since 3.2.0
	 */
	public function delete_tracking_cache()
	{
		if( !$this->is_busting_active() ) {
			return false;
		}

		$result = false;

		if( \WGA_Plugin::app()->getPopulateOption('facebook_cache') ) {
			$result = $this->fbsdk_processor->delete() && $this->fbpix_processor->delete_all();
		}

		if( \WGA_Plugin::app()->getPopulateOption('google_analytics_cache') ) {
			$result = $result && $this->ga_processor->delete() && $this->gtm_processor->delete();
		}

		if( \WGA_Plugin::app()->getPopulateOption('yandex_metrika_cache') ) {
			$result = $result && $this->ym_processor->delete();
		}

		return $result;
	}

	/**
	 * Tell if the cache busting option is active.
	 *
	 * @return bool
	 * @since 3.2.0
	 *
	 */
	private function is_busting_active()
	{
		return (\WGA_Plugin::app()->getPopulateOption('yandex_metrika_cache') || \WGA_Plugin::app()->getPopulateOption('google_analytics_cache') || \WGA_Plugin::app()->getPopulateOption('facebook_cache'));
	}
}
