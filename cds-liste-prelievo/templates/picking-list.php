<?php
/**
 * Vista di stampa liste di prelievo (A4 verticale).
 *
 * Variabili disponibili: $data (array da CDS_Picking_List::build_data).
 *
 * @package CDS_Liste_Prelievo
 */

defined( 'ABSPATH' ) || exit;

$cds_qty = static function ( $qty ) {
	return wc_format_localized_decimal( wc_stock_amount( $qty ) );
};

$generated = date_i18n( 'd/m/Y H:i' );
$site_name = get_bloginfo( 'name' );
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
<title><?php echo esc_html( sprintf( 'Liste di prelievo – %s', $generated ) ); ?></title>
<style>
	@page { size: A4 portrait; margin: 12mm 10mm 14mm; }
	* { box-sizing: border-box; }
	html, body { margin: 0; padding: 0; }
	body { font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 10.5pt; color: #000; background: #e5e5e5; }
	.sheet { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto 16px; padding: 12mm 10mm 14mm; }

	.toolbar { position: sticky; top: 0; z-index: 10; background: #1d2327; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px; }
	.toolbar button { background: #2271b1; color: #fff; border: 0; border-radius: 3px; padding: 8px 16px; font-size: 14px; cursor: pointer; }
	.toolbar button:hover { background: #135e96; }

	header.doc { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 4mm; margin-bottom: 5mm; }
	header.doc h1 { font-size: 16pt; margin: 0; }
	header.doc .meta { text-align: right; font-size: 9pt; line-height: 1.4; }
	h2 { font-size: 13pt; margin: 0 0 3mm; }
	.sub { font-size: 9pt; color: #444; margin: 0 0 4mm; }

	table { width: 100%; border-collapse: collapse; }
	th, td { border-bottom: 1px solid #999; padding: 1.8mm 2mm; text-align: left; vertical-align: top; }
	th { background: #eee; font-size: 9pt; text-transform: uppercase; letter-spacing: .02em; border-bottom: 1.5px solid #000; }
	thead { display: table-header-group; }
	tr { break-inside: avoid; page-break-inside: avoid; }
	.c-check { width: 8mm; text-align: center; }
	.c-qty { width: 14mm; text-align: right; font-weight: 700; font-size: 12pt; white-space: nowrap; }
	.c-sku { width: 30mm; font-family: Consolas, "Courier New", monospace; font-size: 9pt; word-break: break-all; }
	.c-orders { width: 45mm; font-size: 9pt; }
	.box { display: inline-block; width: 4mm; height: 4mm; border: 1.2px solid #000; }
	.name { font-weight: 600; }
	.itemmeta { font-size: 8.5pt; color: #333; margin-top: .6mm; }
	tfoot td { font-weight: 700; border-bottom: 0; border-top: 1.5px solid #000; }

	.section-recipients { break-before: page; page-break-before: always; }
	.recipient { border: 1.5px solid #000; margin-bottom: 5mm; break-inside: avoid; page-break-inside: avoid; }
	.recipient-head { display: flex; justify-content: space-between; gap: 4mm; padding: 2.5mm 3mm; background: #f2f2f2; border-bottom: 1px solid #000; }
	.recipient-head .who { font-size: 11.5pt; font-weight: 700; }
	.recipient-head .addr { font-size: 9.5pt; line-height: 1.35; }
	.recipient-head .orders { display: flex; flex-direction: column; gap: 2mm; font-size: 9.5pt; line-height: 1.35; white-space: nowrap; }
	.recipient-head .orders strong { font-size: 11.5pt; }
	.order-ref { display: flex; align-items: center; justify-content: flex-end; gap: 2.5mm; text-align: right; }
	.qr { width: 20mm; height: 20mm; flex: none; background: #fff; }
	.qr svg { display: block; width: 100%; height: 100%; }
	.recipient table th { background: none; }
	.recipient table tr:last-child td { border-bottom: 0; }
	.note { padding: 2mm 3mm; border-top: 1px dashed #000; font-size: 9pt; }

	@media print {
		body { background: #fff; }
		.toolbar { display: none; }
		.sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
		th, .recipient-head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
	}
</style>
</head>
<body>

<div class="toolbar">
	<span><?php echo esc_html( sprintf( '%d ordini · %s pezzi', $data['order_count'], $cds_qty( $data['total_pieces'] ) ) ); ?></span>
	<button type="button" onclick="window.print()">Stampa</button>
</div>

<div class="sheet">

	<!-- 1. LISTA PER PRODOTTO -->
	<section class="section-products">
		<header class="doc">
			<div>
				<h1>Lista di prelievo per prodotto</h1>
			</div>
			<div class="meta">
				<?php echo esc_html( $site_name ); ?><br>
				Generata il <?php echo esc_html( $generated ); ?><br>
				<?php echo esc_html( sprintf( '%d ordini · %d prodotti · %s pezzi', $data['order_count'], count( $data['products'] ), $cds_qty( $data['total_pieces'] ) ) ); ?>
			</div>
		</header>

		<table>
			<thead>
				<tr>
					<th class="c-check">✓</th>
					<th class="c-qty">Qtà</th>
					<th>Prodotto</th>
					<th class="c-sku">SKU</th>
					<th class="c-orders">Ordini</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $data['products'] as $p ) : ?>
				<tr>
					<td class="c-check"><span class="box"></span></td>
					<td class="c-qty"><?php echo esc_html( $cds_qty( $p['qty'] ) ); ?></td>
					<td>
						<div class="name"><?php echo esc_html( $p['name'] ); ?></div>
						<?php if ( $p['meta'] ) : ?>
							<div class="itemmeta"><?php echo esc_html( implode( ' · ', $p['meta'] ) ); ?></div>
						<?php endif; ?>
					</td>
					<td class="c-sku"><?php echo esc_html( $p['sku'] ); ?></td>
					<td class="c-orders">
						<?php
						$parts = array();
						foreach ( $p['orders'] as $num => $q ) {
							$parts[] = '#' . $num . ( $q != 1 ? ' (×' . $cds_qty( $q ) . ')' : '' ); // phpcs:ignore Universal.Operators.StrictComparisons
						}
						echo esc_html( implode( ', ', $parts ) );
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					<td class="c-qty"><?php echo esc_html( $cds_qty( $data['total_pieces'] ) ); ?></td>
					<td colspan="3">Totale pezzi</td>
				</tr>
			</tfoot>
		</table>
	</section>

	<!-- 2. LISTA PER DESTINATARIO -->
	<section class="section-recipients">
		<header class="doc">
			<div>
				<h1>Lista di prelievo per destinatario</h1>
			</div>
			<div class="meta">
				<?php echo esc_html( $site_name ); ?><br>
				Generata il <?php echo esc_html( $generated ); ?><br>
				<?php echo esc_html( sprintf( '%d destinatari · %d ordini', count( $data['recipients'] ), $data['order_count'] ) ); ?>
			</div>
		</header>

		<?php foreach ( $data['recipients'] as $r ) : ?>
			<div class="recipient">
				<div class="recipient-head">
					<div>
						<div class="who"><?php echo esc_html( $r['name'] ); ?><?php echo $r['company'] ? ' – ' . esc_html( $r['company'] ) : ''; ?></div>
						<div class="addr">
							<?php echo esc_html( implode( ' – ', $r['address'] ) ); ?>
							<?php if ( $r['phone'] ) : ?><br>Tel. <?php echo esc_html( $r['phone'] ); ?><?php endif; ?>
						</div>
					</div>
					<div class="orders">
						<?php foreach ( $r['orders'] as $o ) : ?>
							<div class="order-ref">
								<div class="order-info">
									<strong>#<?php echo esc_html( $o['number'] ); ?></strong><br>
									<?php echo esc_html( $o['date'] ); ?>
									<?php if ( $o['method'] ) : ?><br><small><?php echo esc_html( $o['method'] ); ?></small><?php endif; ?>
								</div>
								<div class="qr" data-url="<?php echo esc_url( $o['url'] ); ?>" title="<?php echo esc_attr( 'Apri ordine #' . $o['number'] ); ?>"></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<table>
					<thead>
						<tr>
							<th class="c-check">✓</th>
							<th class="c-qty">Qtà</th>
							<th>Prodotto</th>
							<th class="c-sku">SKU</th>
							<?php if ( count( $r['orders'] ) > 1 ) : ?><th class="c-orders">Ordine</th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $r['items'] as $it ) : ?>
						<tr>
							<td class="c-check"><span class="box"></span></td>
							<td class="c-qty"><?php echo esc_html( $cds_qty( $it['qty'] ) ); ?></td>
							<td>
								<div class="name"><?php echo esc_html( $it['name'] ); ?></div>
								<?php if ( $it['meta'] ) : ?>
									<div class="itemmeta"><?php echo esc_html( implode( ' · ', $it['meta'] ) ); ?></div>
								<?php endif; ?>
							</td>
							<td class="c-sku"><?php echo esc_html( $it['sku'] ); ?></td>
							<?php if ( count( $r['orders'] ) > 1 ) : ?>
								<td class="c-orders"><?php echo esc_html( '#' . implode( ', #', array_keys( $it['orders'] ) ) ); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $r['notes'] ) : ?>
					<div class="note"><strong>Note cliente:</strong> <?php echo nl2br( esc_html( implode( "\n", $r['notes'] ) ) ); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>

</div>

<script src="<?php echo esc_url( CDS_PL_URL . 'assets/vendor/qrcode.js?ver=' . CDS_PL_VERSION ); ?>"></script>
<script>
	(function () {
		// QR verso il dettaglio ordine in WooCommerce, generati in locale (nessun servizio esterno).
		if (typeof qrcode === 'function') {
			document.querySelectorAll('.qr[data-url]').forEach(function (el) {
				try {
					var qr = qrcode(0, 'M');
					qr.addData(el.getAttribute('data-url'));
					qr.make();
					el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
				} catch (e) {
					el.style.display = 'none';
				}
			});
		}
		window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
	})();
</script>
</body>
</html>
