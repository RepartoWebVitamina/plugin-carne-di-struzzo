=== CDS Liste di Prelievo ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.2.0

Liste di prelievo stampabili (A4 verticale) dagli ordini WooCommerce selezionati.

== Uso ==

1. WooCommerce > Ordini: spunta gli ordini da preparare.
2. Clicca "Crea liste di prelievo" (in alto a destra).
3. Si apre una nuova scheda con la vista di stampa e la finestra di stampa del browser
   (salva come PDF o stampa direttamente).

Il documento contiene:

* Pagina 1+: tutti i prodotti degli ordini selezionati raggruppati per prodotto/variante
  (quantità totale, SKU, ordini di provenienza) con casella da spuntare.
* Dalla pagina successiva: prodotti raggruppati per destinatario (indirizzo di spedizione,
  o di fatturazione se assente), con numero d'ordine, telefono, metodo di spedizione e note cliente.
  Più ordini dello stesso destinatario vengono uniti in un unico blocco.

Le quantità rimborsate vengono sottratte. Compatibile con HPOS e con l'archiviazione ordini legacy.

== Filtri ==

* cds_pl_capability (default: edit_shop_orders)
* cds_pl_data — modifica i dati prima del rendering.

== Librerie incluse ==

* assets/vendor/qrcode.js — QR Code Generator for JavaScript 1.4.4, (c) Kazuhiko Arase, licenza MIT.

== Changelog ==

= 1.2.0 =
* Lista per destinatario: QR code per ogni ordine che apre il dettaglio ordine in WooCommerce
  (generato in locale, nessun servizio esterno).

= 1.1.0 =
* Pulsante disattivato finché non è selezionato almeno un ordine; mostra il numero di ordini selezionati.

= 1.0.1 =
* Considerati solo i checkbox di riga spuntati (esclusi i "seleziona tutti").

= 1.0.0 =
* Prima versione.
