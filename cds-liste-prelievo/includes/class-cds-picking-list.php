<?php
/**
 * Logica principale: pulsante nella lista ordini + generazione vista di stampa.
 *
 * @package CDS_Liste_Prelievo
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Picking_List {

	const ACTION     = 'cds_picking_list';
	const NONCE      = 'cds_picking_list';
	const MAX_ORDERS = 500;

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'render_print_view' ) );
	}

	/**
	 * Capability richiesta per generare le liste.
	 */
	private static function capability() {
		return apply_filters( 'cds_pl_capability', 'edit_shop_orders' );
	}

	/**
	 * True se siamo sulla lista ordini (HPOS o legacy), non sul dettaglio ordine.
	 */
	private static function is_orders_list_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return false;
		}

		// Legacy (ordini come post).
		if ( 'edit-shop_order' === $screen->id ) {
			return true;
		}

		// HPOS: stessa schermata per lista e dettaglio, il dettaglio ha ?action=edit|new.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'woocommerce_page_wc-orders' === $screen->id && empty( $_GET['action'] ) ) {
			return true;
		}

		return false;
	}

	public static function enqueue_assets() {
		if ( ! self::is_orders_list_screen() || ! current_user_can( self::capability() ) ) {
			return;
		}

		wp_enqueue_style( 'cds-pl-admin', CDS_PL_URL . 'assets/admin.css', array(), CDS_PL_VERSION );
		wp_enqueue_script( 'cds-pl-admin', CDS_PL_URL . 'assets/admin.js', array(), CDS_PL_VERSION, true );
		wp_localize_script(
			'cds-pl-admin',
			'cdsPickingList',
			array(
				'url'       => add_query_arg(
					array(
						'action'   => self::ACTION,
						'_wpnonce' => wp_create_nonce( self::NONCE ),
					),
					admin_url( 'admin-post.php' )
				),
				'label'     => __( 'Crea liste di prelievo', 'cds-liste-prelievo' ),
				'noneAlert' => __( 'Seleziona almeno un ordine dalla lista.', 'cds-liste-prelievo' ),
			)
		);
	}

	/**
	 * Endpoint admin-post: stampa la pagina HTML delle liste.
	 */
	public static function render_print_view() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'cds-liste-prelievo' ), 403 );
		}
		check_admin_referer( self::NONCE );

		$raw_ids = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
		$ids     = array_slice( array_unique( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) ), 0, self::MAX_ORDERS );

		$orders = array();
		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );
			if ( $order instanceof WC_Order && 'trash' !== $order->get_status() ) {
				$orders[] = $order;
			}
		}

		if ( empty( $orders ) ) {
			wp_die( esc_html__( 'Nessun ordine valido selezionato.', 'cds-liste-prelievo' ) );
		}

		// Ordina per numero/ID ordine crescente.
		usort(
			$orders,
			static function ( $a, $b ) {
				return $a->get_id() <=> $b->get_id();
			}
		);

		$data = self::build_data( $orders );

		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		include CDS_PL_DIR . 'templates/picking-list.php';
		exit;
	}

	/**
	 * Costruisce le due aggregazioni: per prodotto e per destinatario.
	 *
	 * @param WC_Order[] $orders
	 * @return array
	 */
	private static function build_data( array $orders ) {
		$products     = array();
		$recipients   = array();
		$total_pieces = 0;

		foreach ( $orders as $order ) {
			$order_number = $order->get_order_number();
			$recipient    = self::get_recipient( $order );
			$rkey         = $recipient['key'];

			if ( ! isset( $recipients[ $rkey ] ) ) {
				$recipient['orders'] = array();
				$recipient['notes']  = array();
				$recipient['items']  = array();
				$recipients[ $rkey ] = $recipient;
			}

			$recipients[ $rkey ]['orders'][] = array(
				'number' => $order_number,
				'date'   => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd/m/Y' ) : '',
				'status' => wc_get_order_status_name( $order->get_status() ),
				'method' => $order->get_shipping_method(),
				// Link al dettaglio ordine in admin (gestisce sia HPOS sia legacy post.php?post=ID&action=edit).
				'url'    => $order->get_edit_order_url(),
			);

			$note = trim( (string) $order->get_customer_note() );
			if ( '' !== $note ) {
				$recipients[ $rkey ]['notes'][] = '#' . $order_number . ': ' . $note;
			}

			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
				/** @var WC_Order_Item_Product $item */
				$qty = (float) $item->get_quantity() + (float) $order->get_qty_refunded_for_item( $item_id );
				if ( $qty <= 0 ) {
					continue;
				}

				$product = $item->get_product();
				$meta    = self::get_item_meta_lines( $item );
				$pid     = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
				$pkey    = ( $pid ? $pid : 'name:' . $item->get_name() ) . '|' . md5( implode( "\n", $meta ) );

				$line = array(
					'name' => $item->get_name(),
					'sku'  => $product ? $product->get_sku() : '',
					'meta' => $meta,
					'qty'  => $qty,
				);

				// Per prodotto.
				if ( ! isset( $products[ $pkey ] ) ) {
					$products[ $pkey ]           = $line;
					$products[ $pkey ]['qty']    = 0;
					$products[ $pkey ]['orders'] = array();
				}
				$products[ $pkey ]['qty'] += $qty;
				$products[ $pkey ]['orders'][ $order_number ] = ( isset( $products[ $pkey ]['orders'][ $order_number ] ) ? $products[ $pkey ]['orders'][ $order_number ] : 0 ) + $qty;

				// Per destinatario (stesso prodotto in più ordini dello stesso destinatario viene sommato).
				if ( ! isset( $recipients[ $rkey ]['items'][ $pkey ] ) ) {
					$recipients[ $rkey ]['items'][ $pkey ]           = $line;
					$recipients[ $rkey ]['items'][ $pkey ]['qty']    = 0;
					$recipients[ $rkey ]['items'][ $pkey ]['orders'] = array();
				}
				$recipients[ $rkey ]['items'][ $pkey ]['qty']                    += $qty;
				$recipients[ $rkey ]['items'][ $pkey ]['orders'][ $order_number ] = true;

				$total_pieces += $qty;
			}
		}

		// Prodotti in ordine alfabetico, poi SKU.
		uasort(
			$products,
			static function ( $a, $b ) {
				$cmp = strnatcasecmp( $a['name'], $b['name'] );
				return 0 !== $cmp ? $cmp : strnatcasecmp( $a['sku'], $b['sku'] );
			}
		);

		foreach ( $recipients as &$r ) {
			uasort(
				$r['items'],
				static function ( $a, $b ) {
					return strnatcasecmp( $a['name'], $b['name'] );
				}
			);
		}
		unset( $r );

		return apply_filters(
			'cds_pl_data',
			array(
				'products'     => $products,
				'recipients'   => $recipients,
				'order_count'  => count( $orders ),
				'total_pieces' => $total_pieces,
			),
			$orders
		);
	}

	/**
	 * Destinatario = indirizzo di spedizione, oppure fatturazione se assente.
	 */
	private static function get_recipient( WC_Order $order ) {
		$use_shipping = $order->has_shipping_address();
		$prefix       = $use_shipping ? 'shipping' : 'billing';

		$get = static function ( $field ) use ( $order, $prefix ) {
			$method = "get_{$prefix}_{$field}";
			return method_exists( $order, $method ) ? trim( (string) $order->$method() ) : '';
		};

		$name    = trim( $get( 'first_name' ) . ' ' . $get( 'last_name' ) );
		$company = $get( 'company' );
		$phone   = $get( 'phone' );
		if ( '' === $phone ) {
			$phone = trim( (string) $order->get_billing_phone() );
		}

		$address_lines = array_filter(
			array(
				trim( $get( 'address_1' ) . ' ' . $get( 'address_2' ) ),
				trim( $get( 'postcode' ) . ' ' . $get( 'city' ) . ( $get( 'state' ) ? ' (' . $get( 'state' ) . ')' : '' ) ),
				( $get( 'country' ) && WC()->countries->get_base_country() !== $get( 'country' ) ) ? $get( 'country' ) : '',
			)
		);

		$key_source = strtolower( implode( '|', array( $name, $company, $get( 'address_1' ), $get( 'postcode' ), $get( 'city' ) ) ) );
		$key        = md5( preg_replace( '/\s+/', ' ', $key_source ) );

		return array(
			'key'     => $key,
			'name'    => '' !== $name ? $name : __( '(senza nome)', 'cds-liste-prelievo' ),
			'company' => $company,
			'address' => array_values( $address_lines ),
			'phone'   => $phone,
			'email'   => $order->get_billing_email(),
		);
	}

	/**
	 * Meta visibili della riga (opzioni/addon), esclusi gli attributi già nel nome variazione.
	 *
	 * @return string[]
	 */
	private static function get_item_meta_lines( WC_Order_Item_Product $item ) {
		$lines = array();
		foreach ( $item->get_formatted_meta_data() as $meta ) {
			$value = trim( wp_strip_all_tags( $meta->display_value ) );
			if ( '' === $value ) {
				continue;
			}
			$lines[] = wp_strip_all_tags( $meta->display_key ) . ': ' . $value;
		}
		return $lines;
	}
}
