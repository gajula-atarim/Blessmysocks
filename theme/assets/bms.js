/**
 * Bless My Socks front-end behaviour.
 * - Cart drawer backed by the WooCommerce Store API (add, +/-, totals).
 * - "Add +" buttons (links containing ?add-to-cart=ID) add without a page load.
 * - Collection strip arrows and the delivery-region tabs.
 */
(function () {
	'use strict';

	var cfg = window.BMS || null;
	var drawer = document.getElementById('bms-drawer');

	function money(minor, cur) {
		var unit = Math.pow(10, cur.currency_minor_unit);
		return (cur.currency_prefix || '') + (Number(minor) / unit).toFixed(cur.currency_minor_unit) + (cur.currency_suffix || '');
	}

	function api(path, method, body) {
		return fetch(cfg.store + path, {
			method: method || 'GET',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'Nonce': cfg.nonce },
			body: body ? JSON.stringify(body) : undefined
		}).then(function (r) {
			var n = r.headers.get('Nonce');
			if (n) { cfg.nonce = n; }
			return r.json().then(function (j) {
				if (!r.ok) { throw j; }
				return j;
			});
		});
	}

	function setCount(count) {
		document.querySelectorAll('[data-bms-count]').forEach(function (el) { el.textContent = count; });
		document.querySelectorAll('.bms-cart .elementor-button-icon[data-counter]').forEach(function (el) {
			el.setAttribute('data-counter', count);
		});
	}

	function render(cart) {
		var cur = cart.totals;
		var count = cart.items_count || 0;
		setCount(count);
		if (!drawer) { return; }
		var lines = drawer.querySelector('[data-bms-lines]');
		var empty = lines.querySelector('.bms-drawer__empty');
		lines.querySelectorAll('.bms-line-item').forEach(function (el) { el.remove(); });
		empty.hidden = count > 0;
		cart.items.forEach(function (item) {
			var row = document.createElement('div');
			row.className = 'bms-line-item';
			var img = item.images && item.images[0] ? item.images[0].thumbnail : '';
			row.innerHTML =
				'<div class="bms-line-item__img">' + (img ? '<img src="' + img + '" alt="">' : '') + '</div>' +
				'<div class="bms-line-item__info"><span class="bms-line-item__name"></span><span class="bms-line-item__price"></span></div>' +
				'<div class="bms-qty"><button type="button" aria-label="Decrease">−</button><span></span><button type="button" aria-label="Increase">+</button></div>';
			row.querySelector('.bms-line-item__name').textContent = item.name;
			row.querySelector('.bms-line-item__price').textContent = money(item.totals.line_subtotal, item.totals);
			row.querySelector('.bms-qty span').textContent = item.quantity;
			var btns = row.querySelectorAll('.bms-qty button');
			btns[0].addEventListener('click', function () { changeQty(item, item.quantity - 1); });
			btns[1].addEventListener('click', function () { changeQty(item, item.quantity + 1); });
			lines.appendChild(row);
		});
		drawer.querySelector('[data-bms-subtotal]').textContent = money(cur.total_items, cur);
		var saving = drawer.querySelector('[data-bms-saving]');
		var fees = Number(cur.total_fees || 0);
		saving.hidden = fees >= 0;
		drawer.querySelector('[data-bms-saving-amount]').textContent = fees < 0 ? '−' + money(-fees, cur) : '';
		var hint = drawer.querySelector('[data-bms-hint]');
		if (count > 0 && count < 3) {
			hint.textContent = 'Add ' + (3 - count) + ' more to save 20%.';
		} else if (count >= 3 && count < 5) {
			hint.textContent = '20% bundle saving applied · add ' + (5 - count) + ' more for free shipping.';
		} else if (count >= 5) {
			hint.textContent = '20% bundle saving and free shipping unlocked.';
		} else {
			hint.textContent = '';
		}
	}

	function refresh() {
		return api('cart').then(render).catch(function () {});
	}

	function changeQty(item, qty) {
		var req = qty > 0
			? api('cart/update-item', 'POST', { key: item.key, quantity: qty })
			: api('cart/remove-item', 'POST', { key: item.key });
		req.then(render).catch(refresh);
	}

	function openDrawer() {
		if (!drawer) { return; }
		drawer.classList.add('is-open');
		drawer.setAttribute('aria-hidden', 'false');
		document.documentElement.classList.add('bms-lock');
	}

	function closeDrawer() {
		if (!drawer) { return; }
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		document.documentElement.classList.remove('bms-lock');
	}

	if (cfg && drawer) {
		drawer.addEventListener('click', function (e) {
			if (e.target.closest('[data-bms-close]')) { closeDrawer(); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { closeDrawer(); }
		});

		document.addEventListener('click', function (e) {
			// Header cart (UAE Cart widget) opens the drawer instead of leaving the page.
			var cartLink = e.target.closest('.bms-cart a');
			if (cartLink) {
				e.preventDefault();
				openDrawer();
				refresh();
				return;
			}
			// "Add +" buttons: any link carrying ?add-to-cart=ID inside .bms-add.
			var add = e.target.closest('.bms-add a[href*="add-to-cart="]');
			if (add) {
				e.preventDefault();
				var id = parseInt(new URL(add.href, location.href).searchParams.get('add-to-cart'), 10);
				if (!id) { return; }
				add.classList.add('is-loading');
				api('cart/add-item', 'POST', { id: id, quantity: 1 })
					.then(function (cart) { render(cart); openDrawer(); })
					.catch(function () { location.href = add.href; })
					.then(function () { add.classList.remove('is-loading'); });
			}
		});

		refresh();
	}

	// Collection strips: arrow buttons scroll the strip that sits beside them.
	document.addEventListener('click', function (e) {
		var arrow = e.target.closest('.bms-arrow');
		if (!arrow) { return; }
		e.preventDefault();
		var wrap = arrow.closest('.bms-strip-wrap');
		var strip = wrap && wrap.querySelector('.bms-strip');
		if (!strip) { return; }
		var dir = arrow.classList.contains('bms-prev') ? -1 : 1;
		strip.scrollBy({ left: dir * strip.clientWidth * 0.6, behavior: 'smooth' });
	});

	// Delivery estimate tabs: buttons link to #au / #intl, panels carry .bms-region-au / .bms-region-intl.
	function pickRegion(region) {
		document.querySelectorAll('.bms-tab').forEach(function (tab) {
			var a = tab.querySelector('a');
			var on = a && a.getAttribute('href') === '#' + region;
			tab.classList.toggle('is-active', on);
			if (a) { a.setAttribute('aria-pressed', on ? 'true' : 'false'); }
		});
		document.querySelectorAll('.bms-region-au, .bms-region-intl').forEach(function (el) {
			el.classList.toggle('is-hidden', !el.classList.contains('bms-region-' + region));
		});
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest('.bms-tab a');
		if (!a) { return; }
		e.preventDefault();
		pickRegion(a.getAttribute('href').slice(1));
	});
	if (document.querySelector('.bms-tab')) { pickRegion('au'); }
})();
