<?php #comp-page builds: premium

/**
 * Updates for altering the table used to store statistics data.
 * Adds new columns and renames existing ones in order to add support for the new social buttons.
 */
class WGZUpdate010108 extends Wbcr_Factory463_Update {

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	public function install() {

		$old_plugin_options = get_option( $this->plugin->getPrefix() . 'assets_manager_options', [] );
		$save_mode          = (int) get_option( $this->plugin->getPrefix() . 'for_admin_only', 0 );

		$settings = get_option( $this->plugin->getPrefix() . 'assets_states', [] );

		if ( ! empty( $old_plugin_options ) ) {
			if ( ! function_exists( 'wbcr_gnz_deploy_mu_plugin' ) ) {
				require_once WGZ_PLUGIN_DIR . '/includes/functions.php';
			}

			wbcr_gnz_deploy_mu_plugin();
		}

		if ( empty( $settings ) ) {
			$settings['save_mode'] = (bool) $save_mode;

			if ( ! empty( $old_plugin_options['disabled'] ) ) {
				foreach ( $old_plugin_options['disabled'] as $type => $assets ) {
					if ( ! empty( $assets ) ) {
						foreach ( $assets as $handle => $where ) {
							$group_settings = &$settings[ $type ][ $handle ];

							$exclude = $this->get_enabled_from_options( $old_plugin_options, $type, $handle );
							$this->where_to_condition( $where, $group_settings['visability'], $exclude );

							if ( 'plugins' === $type ) {
								$group_settings['load_mode'] = 'disable_assets';
							}

							$group_settings['visability'] = json_encode( $group_settings['visability'] );
						}
					}
				}

				$active_plugins = $this->get_active_plugins();

				if ( ! empty( $active_plugins ) ) {
					foreach ( (array) $active_plugins as $plugin_base ) {
						$plugin_name_parts = explode( '/', $plugin_base );
						if ( 2 === sizeof( $plugin_name_parts ) ) {
							$plugin_name = $plugin_name_parts[0];
							if ( empty( $settings['plugins'][ $plugin_name ]['load_mode'] ) ) {
								$settings['plugins'][ $plugin_name ]['load_mode'] = 'enable';
							}
							if ( empty( $settings['plugins'][ $plugin_name ]['visability'] ) ) {
								$settings['plugins'][ $plugin_name ]['visability'] = '';
							}

							if ( ! empty( $settings['js'] ) ) {
								$settings['plugins'][ $plugin_name ]['js'] = $settings['js'];
							}
							if ( ! empty( $settings['css'] ) ) {
								$settings['plugins'][ $plugin_name ]['css'] = $settings['css'];
							}
						}
					}
				}
				if ( ! empty( $settings['js'] ) ) {
					$settings['theme']['js'] = $settings['misc']['js'] = $settings['js'];
					unset( $settings['js'] );
				}
				if ( ! empty( $settings['css'] ) ) {
					$settings['theme']['css'] = $settings['misc']['css'] = $settings['css'];
					unset( $settings['css'] );
				}
			}
		}

		//update_option( $this->plugin->getPrefix() . 'backend_assets_states', $settings );
		update_option( $this->plugin->getPrefix() . 'assets_states', $settings );
	}

	/**
	 * Get a list of active plugins.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 * @return array
	 */
	private function get_active_plugins() {
		if ( is_multisite() ) {
			$active_network_plugins = (array) get_site_option( 'active_sitewide_plugins' );
			$active_network_plugins = array_keys( $active_network_plugins );
			$active_blog_plugins    = (array) get_option( 'active_plugins' );

			return array_unique( array_merge( $active_network_plugins, $active_blog_plugins ) );
		}

		return (array) get_option( 'active_plugins' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 *
	 * @param $where
	 * @param $settings
	 * @param $exclude
	 */
	private function where_to_condition( $where, &$settings, $exclude ) {
		if ( ! empty( $where['current'] ) ) {
			foreach ( (array) $where['current'] as $current_url ) {
				$settings[] = [
					'type'       => 'OR',
					'conditions' => [
						[
							'param'    => 'current-url',
							'operator' => 'equals',
							'type'     => 'default',
							'value'    => $current_url
						]
					]
				];
			}
		}
		if ( ! empty( $where['custom'] ) ) {
			foreach ( (array) $where['custom'] as $custom_url ) {
				$settings[] = [
					'type'       => 'OR',
					'conditions' => [
						[
							'param'    => 'location-page',
							'operator' => 'equals',
							'type'     => 'text',
							'value'    => $custom_url
						]
					]
				];
			}
		}
		if ( ! empty( $where['regex'] ) ) {
			$settings[] = [
				'type'       => 'OR',
				'conditions' => [
					[
						'param'    => 'regular-expression',
						'operator' => 'equals',
						'type'     => 'regexp',
						'value'    => $where['regex']
					]
				]
			];
		}
		if ( ! empty( $where['everywhere'] ) ) {
			$everywhere = [
				'type'       => 'OR',
				'conditions' => [
					[
						'param'    => 'location-some-page',
						'operator' => 'equals',
						'type'     => 'select',
						'value'    => 'base_web'
					]
				]
			];

			if ( ! empty( $exclude ) ) {
				foreach ( (array) $exclude as $group_name => $group ) {
					foreach ( (array) $group as $item_id ) {
						if ( ! in_array( $group_name, [ 'post_type', 'taxonomies', 'current' ] ) ) {
							continue;
						}

						switch ( $group_name ) {
							case 'post_type':
								$condition_param = 'location-post-type';
								$value           = $item_id;
								break;
							case 'taxonomies':
								$condition_param = 'location-taxonomy';
								$value           = $item_id;
								break;
							case 'current':
								$condition_param = 'current-url';
								$value           = $item_id;
								break;
							/*case 'categories':
								$condition_param = 'location-taxonomy';
								$value           = '';
								break;*/
						}

						$everywhere['conditions'][] = [
							'param'    => $condition_param,
							'operator' => 'notequal',
							'type'     => 'select',
							'value'    => $value
						];
					}
				}
			}

			$settings[] = $everywhere;
		}
	}

	/**
	 * Get enabled from options
	 *
	 * @param $type
	 * @param $handle
	 *
	 * @return null
	 */
	private function get_enabled_from_options( $options, $type, $handle ) {
		if ( isset( $options['enabled'] ) && isset( $options['enabled'][ $type ] ) && isset( $options['enabled'][ $type ][ $handle ] ) ) {
			return $options['enabled'][ $type ][ $handle ];
		}

		return null;
	}
}