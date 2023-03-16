<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transliteration core class
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 19.02.2018, Webcraftic
 */
class WCTR_Plugin extends Wbcr_Factory463_Plugin {

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
	 * @return \Wbcr_Factory463_Plugin|\WCTR_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	protected function init_activation() {
		include_once( WCTR_PLUGIN_DIR . '/admin/activation.php' );
		self::app()->registerActivation( 'WCTR_Activation' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 * @throws \Exception
	 */
	private function register_pages() {
		self::app()->registerPage( 'WCTR_CyrliteraPage', WCTR_PLUGIN_DIR . '/admin/pages/class-page-cyrlitera.php' );
		self::app()->registerPage( 'WCTR_MoreFeaturesPage', WCTR_PLUGIN_DIR . '/admin/pages/class-page-more-features.php' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @throws \Exception
	 */
	private function admin_scripts() {
		require_once( WCTR_PLUGIN_DIR . '/admin/boot.php' );

		$this->init_activation();
		$this->register_pages();
	}

	private function global_scripts() {
		require_once( WCTR_PLUGIN_DIR . '/includes/classes/class-configurate-cyrlitera.php' );
		new WCTR_ConfigurateCyrlitera( self::$app );
	}
}

