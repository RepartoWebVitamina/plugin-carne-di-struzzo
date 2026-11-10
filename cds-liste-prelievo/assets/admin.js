/* global cdsPickingList */
(function () {
	'use strict';

	if (typeof cdsPickingList === 'undefined') {
		return;
	}

	var btn = null;

	function getSelectedIds() {
		// Solo i checkbox di riga spuntati nella tabella ordini (HPOS: id[], legacy: post[]).
		// Esclusi i checkbox "seleziona tutti" di intestazione/piè di tabella.
		var boxes = document.querySelectorAll(
			'.wp-list-table tbody th.check-column input[type="checkbox"]:checked'
		);
		var ids = [];
		Array.prototype.forEach.call(boxes, function (el) {
			if ((el.name === 'id[]' || el.name === 'post[]') && /^\d+$/.test(el.value) && ids.indexOf(el.value) === -1) {
				ids.push(el.value);
			}
		});
		return ids;
	}

	function updateState() {
		if (!btn) {
			return;
		}
		var count = getSelectedIds().length;
		btn.disabled = count === 0;
		btn.textContent = count ? cdsPickingList.label + ' (' + count + ')' : cdsPickingList.label;
		btn.title = count ? '' : cdsPickingList.noneAlert;
	}

	function onClick(e) {
		e.preventDefault();
		var ids = getSelectedIds();
		if (!ids.length) {
			updateState();
			return;
		}
		var url = cdsPickingList.url + '&ids=' + encodeURIComponent(ids.join(','));
		window.open(url, '_blank');
	}

	// Il "seleziona tutti" e lo shift+click di WordPress cambiano le spunte via JS
	// senza generare "change" sulle singole righe: ricalcola dopo ogni click/change.
	function scheduleUpdate() {
		window.setTimeout(updateState, 0);
	}

	function init() {
		var wrap = document.querySelector('#wpbody-content .wrap');
		if (!wrap || !document.querySelector('.wp-list-table')) {
			return;
		}

		btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button button-primary cds-pl-btn';
		btn.addEventListener('click', onClick);

		var headerEnd = wrap.querySelector('.wp-header-end');
		if (headerEnd) {
			wrap.insertBefore(btn, headerEnd);
		} else {
			wrap.insertBefore(btn, wrap.firstChild);
		}

		document.addEventListener('change', scheduleUpdate);
		document.addEventListener('click', scheduleUpdate);
		document.addEventListener('keyup', scheduleUpdate);

		updateState();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
