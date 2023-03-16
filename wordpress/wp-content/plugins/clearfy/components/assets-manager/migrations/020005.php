<?php #comp-page builds: premium

/**
 * Updates for altering the table used to store statistics data.
 * Adds new columns and renames existing ones in order to add support for the new social buttons.
 */
class WGZUpdate020005 extends Wbcr_Factory463_Update {

	public function install() {
		$settings = get_option( $this->plugin->getPrefix() . 'assets_states', [] );

		if ( ! empty( $settings ) ) {
			if ( ! function_exists( 'wbcr_gnz_deploy_mu_plugin' ) ) {
				require_once WGZ_PLUGIN_DIR . '/includes/functions.php';
			}

			wbcr_gnz_deploy_mu_plugin();
		}
	}
}