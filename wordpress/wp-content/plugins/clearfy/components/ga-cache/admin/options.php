<?php
/**
 * Options for additionally form
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 21.01.2018, Webcraftic
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * @return array
 */
function wbcr_ga_get_plugin_options()
{
	$options = [];

	$options[] = [
		'type' => 'html',
		'html' => '<div class="wbcr-factory-page-group-header">' . __('<strong>Improve browser caching for 3rd-party services</strong>.', 'simple-google-analytics') . '<p>' . __('To improve Google Page Speed indicators Analytics caching is needed. However, it can also slightly increase your website loading speed, because Analytics js files will load locally. The second case that you might need these settings is the usual Google Analytics connection to your website. You do not need to do this with other plugins or insert the tracking code into your theme.', 'simple-google-analytics') . '</p></div>'
	];

	$options[] = [
		'type' => 'checkbox',
		'way' => 'buttons',
		'name' => 'facebook_cache',
		'title' => __('Improve browser caching for Facebook Pixel', 'simple-google-analytics'),
		'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
		'hint' => __('Clearfy will host these Facebook Pixels locally on your server to help satisfy the PageSpeed recommendation for Leverage browser caching.', 'simple-google-analytics') . '<br>--<br><span class="wbcr-factory-light-orange-color">' . __('ATTENTION! Before using this option, remove the previously installed Google Analytics code inside your theme or plugins associated with this feature!', 'simple-google-analytics') . '</span>',
		'default' => false
	];

	$options[] = [
		'type' => 'checkbox',
		'way' => 'buttons',
		'name' => 'google_analytics_cache',
		'title' => __('Improve browser caching for Google Analytics', 'simple-google-analytics'),
		'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
		'hint' => __('Clearfy will host these Google scripts locally on your server to help satisfy the PageSpeed recommendation for Leverage browser caching.', 'simple-google-analytics') . (WGA_Plugin::app()->getPopulateOption('ga_cache') ? '<br>--<br><span class="wbcr-factory-light-orange-color">' . __('ATTENTION! Before using this option, please disable the option "Add Google Analytics code and Cache"!', 'simple-google-analytics') . '</span>' : ''),
		'cssClass' => WGA_Plugin::app()->getPopulateOption('ga_cache') ? ['factory-checkbox-disabled'] : [],
		'default' => false
	];

	$options[] = [
		'type' => 'checkbox',
		'way' => 'buttons',
		'name' => 'yandex_metrika_cache',
		'title' => __('Improve browser caching for Yandex metrika', 'simple-google-analytics'),
		'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
		'hint' => __('Clearfy will host these Yandex metrika scripts locally on your server to help satisfy the PageSpeed recommendation for Leverage browser caching..', 'simple-google-analytics') . (WGA_Plugin::app()->getPopulateOption('ga_cache') ? '<br>--<br><span class="wbcr-factory-light-orange-color">' . __('ATTENTION! Before using this option, please disable the option "Add Google Analytics code and Cache"!', 'simple-google-analytics') . '</span>' : ''),
		'default' => false
	];

	$options[] = [
		'type' => 'checkbox',
		'way' => 'buttons',
		'name' => 'ga_cache',
		'title' => __('Add Google Analytics code and Cache', 'simple-google-analytics'),
		//'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
		'hint' => __('If you enable this option, the plugin will begin to save a local copy of Google Analytics to speed up the loading of your website and improve Google Page Speed.', 'simple-google-analytics') . '<br>--<br><span class="wbcr-factory-red-color">' . __('ATTENTION! This option is deprecated and cannot be used since plugin version 1.9.0! Use the new option "Improve browser caching for Google Analytics", you will have to insert the google analytics code yourself without the  Clearfy plugin.', 'simple-google-analytics') . '</span>',
		'default' => false,
		'cssClass' => !WGA_Plugin::app()->getPopulateOption('ga_cache') ? ['factory-checkbox-disabled'] : [],
		'eventsOn' => [
			'show' => '#wbcr-clearfy-performance-ga-block'
		],
		'eventsOff' => [
			'hide' => '#wbcr-clearfy-performance-ga-block'
		]

	];

	$options[] = [
		'type' => 'div',
		'id' => 'wbcr-clearfy-performance-ga-block',
		'items' => [
			[
				'type' => 'textbox',
				'way' => 'buttons',
				'name' => 'ga_tracking_id',
				'title' => __('Google analytic Code', 'simple-google-analytics'),
				'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
				'hint' => __('Set the Google Analytics tracking code.', 'simple-google-analytics'),
				'placeholder' => 'UA-XXXXX-Y'
			],
			[
				'type' => 'dropdown',
				'way' => 'buttons',
				'name' => 'ga_script_position',
				'data' => [
					['header', 'Header'],
					['footer', 'Footer'],
				],
				'title' => __('Save GA in', 'simple-google-analytics'),
				'hint' => __('Select location for the Google Analytics code.', 'simple-google-analytics'),
				'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
				'default' => 'footer'
			],
			[
				'type' => 'integer',
				'name' => 'ga_adjusted_bounce_rate',
				'title' => __('Use adjusted bounce rate?', 'simple-google-analytics'),
				'default' => 0,
				'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
				'hint' => __('Essentially, you set up an event which is triggered after a user spends a certain amount of time on the landing page, telling Google Analytics not to count these users as bounces. A user may come to your website, find all of the information they need (a phone number, for example) and then leave the site without visiting another page. Without adjusted bounce rate, such a user would be considered a bounce, even though they had a successful experience. By defining a time limit after which you can consider a user to be "engaged," that user would no longer count as a bounce, and you\'d get a more accurate idea of whether they found what they were looking for.', 'simple-google-analytics')
			],
			[
				'type' => 'integer',
				'way' => 'buttons',
				'name' => 'ga_enqueue_order',
				'title' => __('Change enqueue order?', 'simple-google-analytics'),
				'default' => 0,
				'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
				'hint' => __('By default, Google Analytics code is loaded before other scripts and javasscript code, but if you set the value to 100, the GA code will be loaded after all other scripts. By changing the priority, you can set code position on the page.', 'simple-google-analytics')
			],
			[
				'type' => 'checkbox',
				'way' => 'buttons',
				'name' => 'ga_disable_display_features',
				'title' => __('Disable all display features functionality?', 'simple-google-analytics'),
				//'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'grey'),
				'hint' => sprintf(__('Disable all <a href="%s">display features functionality?</a>', 'simple-google-analytics'), 'https://developers.google.com/analytics/devguides/collection/analyticsjs/display-features'),
				'default' => false
			],
			[
				'type' => 'checkbox',
				'way' => 'buttons',
				'name' => 'ga_anonymize_ip',
				'title' => __('Use Anonymize IP? (Required by law for some countries)', 'simple-google-analytics'),
				//'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'grey'),
				'hint' => sprintf(__('Use <a href="%s">Anonymize IP?</a> (Required by law for some countries)', 'simple-google-analytics'), 'https://support.google.com/analytics/answer/2763052'),
				'default' => false
			],
			[
				'type' => 'checkbox',
				'way' => 'buttons',
				'name' => 'ga_track_admin',
				'title' => __('Track logged in Administrators?', 'simple-google-analytics'),
				'layout' => ['hint-type' => 'icon', 'hint-icon-color' => 'grey'],
				'hint' => __('Track logged in Administrators?', 'simple-google-analytics'),
				'default' => false
			]
		]
	];

	return $options;
}

/**
 * @param $form
 * @param $page Wbcr_FactoryPages463_ImpressiveThemplate
 *
 * @return mixed
 */
function wbcr_ga_additionally_form_options($form, $page)
{
	if( empty($form) ) {
		return $form;
	}

	$options = wbcr_ga_get_plugin_options();

	foreach($options as $option) {
		$form[0]['items'][] = $option;
	}

	return $form;
}

add_filter('wbcr_clr_code_clean_form_options', 'wbcr_ga_additionally_form_options', 10, 2);

