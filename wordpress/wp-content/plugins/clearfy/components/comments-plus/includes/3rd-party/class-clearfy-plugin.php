<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable comments
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 *
 * @copyright (c) 2018 Webraftic Ltd
 */
class WCM_Plugin {

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

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
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
	 * @throws \Exception
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			$this->register_pages();
		}
	}

	/**
	 * Регистрирует классы страниц в плагине
	 *
	 * Мы указываем плагину, где найти файлы страниц и какое имя у их класса. Чтобы плагин
	 * выполнил подключение классов страниц. После регистрации, страницы будут доступные по url
	 * и в меню боковой панели администратора. Регистрируемые страницы будут связаны с текущим плагином
	 * все операции выполняемые внутри классов страниц, имеют отношение только текущему плагину.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @throws \Exception
	 */
	private function register_pages() {
		$admin_path = WCM_PLUGIN_DIR . '/admin/pages';

		self::app()->registerPage( 'WbcrCmp_CommentsPage', $admin_path . '/class-page-comments.php' );
		self::app()->registerPage( 'WbcrCmp_DeleteCommentsPage', $admin_path . '/class-page-delete-comments.php' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function admin_scripts() {
		require( WCM_PLUGIN_DIR . '/admin/boot.php' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function global_scripts() {
		require( WCM_PLUGIN_DIR . '/includes/boot.php' );
		require( WCM_PLUGIN_DIR . '/includes/classes/class-configurate-comments.php' );
		new WbcrCmp_ConfigComments( self::$app );
	}
}