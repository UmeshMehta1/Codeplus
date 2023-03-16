<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Disable comments
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 *
 * @copyright (c) 2018 Webraftic Ltd
 */
class WMAC_Plugin {

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
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		if( !class_exists('WCL_Plugin') ) {
			throw new Exception('Plugin Clearfy is not installed!');
		}

		self::$app = WCL_Plugin::app();

		$this->global_scripts();

		if( is_admin() ) {
			$this->admin_scripts();
		}

		add_action('plugins_loaded', [$this, 'plugins_loaded']);
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
	public static function app()
	{
		return self::$app;
	}


	/**
	 * Выполнение действий после загрузки плагина
	 * Подключаем все классы оптимизации и запускаем процесс
	 */
	public function plugins_loaded()
	{
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-base.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-cache.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-cache-checker.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-scripts.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-css-min.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-styles.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-criticalcss.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-main.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/class-helper.php');
		
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/ext/php/jsmin.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/ext/php/yui-php-cssmin-bundled/Colors.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/ext/php/yui-php-cssmin-bundled/Minifier.php');
		require_once(WMAC_PLUGIN_DIR . '/includes/classes/ext/php/yui-php-cssmin-bundled/Utils.php');

		$plugin = new WMAC_PluginMain();
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
	private function register_pages()
	{
		$admin_path = WMAC_PLUGIN_DIR . '/admin/pages';

		self::app()->registerPage('WMAC_MinifyAndCombineSettingsPage', $admin_path . '/class-pages-settings.php');
	}

	/**
	 * Подключаем функции бекенда
	 *
	 * @throws \Exception
	 */
	private function admin_scripts()
	{
		require_once(WMAC_PLUGIN_DIR . '/admin/boot.php');
		$this->register_pages();
	}

	/**
	 * Подключаем глобальные функции
	 */
	private function global_scripts()
	{
		require_once(WMAC_PLUGIN_DIR . '/includes/boot.php');
	}
}