<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cyrlitera
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */
class WCTR_Plugin {

	/**
	 * @see self::app()
	 * @var WCL_Plugin
	 */
	private static $app;

	/**
	 * Конструктор
	 *
	 * Применяет конструктор родительского класса и записывает экземпляр текущего класса в свойство $app.
	 * Подробнее о свойстве $app см. self::app()
	 *
	 * @param string $plugin_path
	 * @param array  $data
	 *
	 * @throws Exception
	 */
	public function __construct() {
		if ( ! class_exists( 'WCL_Plugin' ) ) {
			throw new Exception( 'Plugin Clearfy is not installed!' );
		}

		self::$app = WCL_Plugin::app();

		$this->global_scripts();

		if ( is_admin() ) {
			$this->admin_scripts();
		}
	}

	/**
	 * Статический метод для быстрого доступа к интерфейсу плагина.
	 *
	 * Позволяет разработчику глобально получить доступ к экземпляру класса плагина в любом месте
	 * плагина, но при этом разработчик не может вносить изменения в основной класс плагина.
	 *
	 * Используется для получения настроек плагина, информации о плагине, для доступа к вспомогательным
	 * классам.
	 *
	 * @return WCL_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 * @throws \Exception
	 */
	private function register_pages() {
		self::app()->registerPage( 'WCTR_CyrliteraPage', WCTR_PLUGIN_DIR . '/admin/pages/class-page-cyrlitera.php' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @throws \Exception
	 */
	private function admin_scripts() {
		require_once( WCTR_PLUGIN_DIR . '/admin/boot.php' );

		$this->register_pages();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function global_scripts() {
		require_once( WCTR_PLUGIN_DIR . '/includes/classes/class-configurate-cyrlitera.php' );
		new WCTR_ConfigurateCyrlitera( self::$app );
	}
}