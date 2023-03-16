<?php
/**
 * Основной класс плагина
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 19.02.2018, Webcraftic
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WHTM_Plugin extends Wbcr_Factory463_Plugin {

	/**
	 * @see self::app()
	 * @var Wbcr_Factory463_Plugin
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
	public function __construct( $plugin_path, $data ) {
		parent::__construct( $plugin_path, $data );

		self::$app = $this;

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
	 * @return \Wbcr_Factory463_Plugin|\WCM_Plugin
	 */
	public static function app() {
		return self::$app;
	}


	/**
	 * Выполнение действий после загрузки плагина
	 * Подключаем все классы оптимизации и запускаем процесс
	 *
	 * @throws \Exception
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			$this->register_pages();
		}

		require_once( WHTM_PLUGIN_DIR . '/includes/classes/class-base.php' );
		require_once( WHTM_PLUGIN_DIR . '/includes/classes/class-html.php' );
		require_once( WHTM_PLUGIN_DIR . '/includes/classes/class-main.php' );

		require_once( WHTM_PLUGIN_DIR . '/includes/classes/ext/php/class-minify-html.php' );

		$plugin = new WHTM_PluginMain();
		$plugin->start();
	}

	/**
	 * Регистрирует классы страниц в плагине
	 *
	 * Мы указываем плагину, где найти файлы страниц и какое имя у их класса. Чтобы плагин
	 * выполнил подключение классов страниц. После регистрации, страницы будут доступные по url
	 * и в меню боковой панели администратора. Регистрируемые страницы будут связаны с текущим плагином
	 * все операции выполняемые внутри классов страниц, имеют отношение только текущему плагину.
	 *
	 * @throws \Exception
	 */
	private function register_pages() {
		if ( defined( 'WMAC_PLUGIN_ACTIVE' ) ) {
			return;
		}

		$admin_path = WHTM_PLUGIN_DIR . '/admin/pages';

		// Пример основной страницы настроек
		self::app()->registerPage( 'WHTM_SettingsPage', $admin_path . '/class-pages-settings.php' );
	}

	/**
	 * Подключаем функции бекенда
	 */
	private function admin_scripts() {
		require( WHTM_PLUGIN_DIR . '/admin/boot.php' );
	}
}
