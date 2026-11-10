<?php
/**
 * Plugin Name:       CDS Liste di Prelievo
 * Description:       Aggiunge alla pagina Ordini di WooCommerce un pulsante per generare le liste di prelievo (per prodotto e per destinatario) degli ordini selezionati, pronte per la stampa A4.
 * Version:           1.2.0
 * Author:            Vitamina Studio
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.1
 * Text Domain:       cds-liste-prelievo
 */

defined( 'ABSPATH' ) || exit;

define( 'CDS_PL_VERSION', '1.2.0' );
define( 'CDS_PL_FILE', __FILE__ );
define( 'CDS_PL_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDS_PL_URL', plugin_dir_url( __FILE__ ) );

// Compatibilità con HPOS (High-Performance Order Storage).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'CDS Liste di Prelievo richiede WooCommerce attivo.', 'cds-liste-prelievo' ) . '</p></div>';
				}
			);
			return;
		}

		require_once CDS_PL_DIR . 'includes/class-cds-picking-list.php';
		CDS_Picking_List::init();
	}
);
