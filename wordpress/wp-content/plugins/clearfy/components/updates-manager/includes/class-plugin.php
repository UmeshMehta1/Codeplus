<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Основной класс плагина Updates manager
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 19.02.2018, Webcraftic
 */
class WUPM_Plugin extends Wbcr_Factory463_Plugin {

	/**
	 * @see self::app()
	 * @var Wbcr_Factory463_Plugin
	 */
	private static $app;

	/**
	 * @since  1.1.0
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
	 * @param array  $data
	 *
	 * @throws Exception
	 */
	public function __construct( $plugin_path, $data ) {
		parent::__construct( $plugin_path, $data );

		self::$app         = $this;
		$this->plugin_data = $data;

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
	 * @return \Wbcr_Factory463_Plugin|\WUPM_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * Выполняет конфигурацию плагина, после того, как все плагины будут загружены
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @throws \Exception
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			$this->register_pages();
		}

		require( WUPM_PLUGIN_DIR . '/includes/classes/class-configurate-updates.php' );
		new WUPM_ConfigUpdates( self::$app );
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
		$admin_path = WUPM_PLUGIN_DIR . '/admin/pages';

		self::app()->registerPage( 'WUPM_UpdatesPage', $admin_path . '/class-page-updates.php' );
		self::app()->registerPage( 'WUPM_PluginsPage', $admin_path . '/class-page-plugins.php' );
		self::app()->registerPage( 'WUPM_ThemesPage', $admin_path . '/class-page-themes.php' );
		self::app()->registerPage( 'WUPM_AdvancedPage', $admin_path . '/class-page-advanced.php' );
		self::app()->registerPage( 'WUPM_MoreFeaturesPage', $admin_path . '/class-page-more-features.php' );
	}

	/**
	 * Исполныет сценарии плагина только для бекенда
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function admin_scripts() {
		require_once( WUPM_PLUGIN_DIR . '/admin/activation.php' );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			require_once( WUPM_PLUGIN_DIR . '/admin/ajax/change-flag.php' );
		}

		require_once( WUPM_PLUGIN_DIR . '/admin/boot.php' );

		$this->init_activation();
	}

	/**
	 * Инициализирует класс активации/деактивации плагина
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	protected function init_activation() {
		include_once( WUPM_PLUGIN_DIR . '/admin/activation.php' );
		self::app()->registerActivation( 'WUPM_Activation' );
	}
}
