<?php
// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Plugin class
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 19.02.2018, Webcraftic
 */
class WGA_Plugin extends Wbcr_Factory463_Plugin {

	/**
	 * @see self::app()
	 * @var Wbcr_Factory463_Plugin
	 */
	private static $app;

	/**
	 * @since  3.1.0
	 * @var array
	 */
	private $plugin_data;

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
	public function __construct($plugin_path, $data)
	{
		parent::__construct($plugin_path, $data);

		self::$app = $this;
		$this->plugin_data = $data;

		$this->global_scripts();

		if( is_admin() ) {
			$this->init_activation();
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
	 * @return \Wbcr_Factory463_Plugin|\WGA_Plugin
	 */
	public static function app()
	{
		return self::$app;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  3.0.0
	 */
	private function init_activation()
	{
		require_once(WGA_PLUGIN_DIR . '/admin/activation.php');
		self::app()->registerActivation('WGA_Activation');
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
	 * @since  3.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function register_pages()
	{
		if( $this->as_addon ) {
			return;
		}

		if( $this->isNetworkActive() and !is_network_admin() ) {
			return;
		}
		self::app()->registerPage('WGA_CachePage', WGA_PLUGIN_DIR . '/admin/pages/class-pages-general-settings.php');
		self::app()->registerPage('WGA_MoreFeaturesPage', WGA_PLUGIN_DIR . '/admin/pages/class-pages-more-features.php');
	}

	/**
	 * @throws \Exception
	 * @since  3.1.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function admin_scripts()
	{
		require(WGA_PLUGIN_DIR . '/admin/options.php');
		require(WGA_PLUGIN_DIR . '/admin/boot.php');

		$this->register_pages();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  3.0.0
	 */
	private function global_scripts()
	{
		require(WGA_PLUGIN_DIR . '/includes/classes/class-configurate-ga.php');
		new WGA_ConfigGACache(self::$app);

		require(WGA_PLUGIN_DIR . '/includes/classes/class-scheduler.php');
		new \WGA\Busting\Sheduller();
	}
}

