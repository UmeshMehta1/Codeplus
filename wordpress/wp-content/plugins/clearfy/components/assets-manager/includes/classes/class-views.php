<?php
/**
 * Class that handles templates.
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 05.04.2019, Webcraftic
 * @version       1.0
 */

class WGZ_Views {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    array
	 */
	protected static $_instance = [];

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * WRIO_Views constructor.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param string $plugin_dir
	 */
	public function __construct( $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6 - add instace id
	 * @since  1.3.0
	 *
	 * @param string $plugin_dir
	 *
	 * @return object|\WGZ_Views object Main instance.
	 */
	public static function get_instance( $plugin_dir ) {
		$instance_id = md5( $plugin_dir );

		if ( ! isset( self::$_instance[ $instance_id ] ) ) {
			self::$_instance[ $instance_id ] = new self( $plugin_dir );
		}

		return self::$_instance[ $instance_id ];
	}

	/**
	 * Get a template contents.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @param string                          $template   The template name.
	 * @param mixed                           $data       Some data to pass to the template.
	 * @param WBCR\Factory_Templates_113\Pages\PageBase $page
	 *
	 * @return bool|string       The page contents. False if the template doesn't exist.
	 */
	public function get_template( $template, $data = [], WBCR\Factory_Templates_113\Pages\PageBase $page = null ) {
		$template = str_replace( '_', '-', $template );
		$path     = $this->plugin_dir . '/views/' . $template . '.php';

		if ( ! file_exists( $path ) ) {
			return false;
		}

		ob_start();
		include $path;
		$contents = ob_get_clean();

		return trim( (string) $contents );
	}

	/**
	 * Print a template.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @access public
	 *
	 * @since  1.3.0
	 *
	 * @param string                          $template   The template name.
	 * @param mixed                           $data       Some data to pass to the template.
	 * @param WBCR\Factory_Templates_113\Pages\PageBase $page
	 */
	public function print_template( $template, $data = [], WBCR\Factory_Templates_113\Pages\PageBase $page = null ) {
		echo $this->get_template( $template, $data, $page );
	}
}