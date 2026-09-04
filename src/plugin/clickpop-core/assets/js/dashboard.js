/**
 * ClickPop — داشبورد کاربر
 *
 * قواعد:
 *  - هر درخواست نویسنده‌دار، هدر X-WP-Nonce دارد.
 *  - مبلغ نمایشی فقط برای UX است؛ مبلغ نهایی را سرور تعیین می‌کند.
 *  - هر ثبت سفارش یک idempotency_key دارد تا دابل‌کلیک سفارش دوم نسازد.
 */
( function () {
	'use strict';

	var cfg = window.clickpopData;
	if ( ! cfg || ! cfg.root ) {
		return;
	}

	var app = document.getElementById( 'cp-app' );
	if ( ! app ) {
		return;
	}

	var i18n = cfg.i18n || {};

	/* ── ابزارها ───────────────────────────────────────────── */

	function api( path, options ) {
		options = options || {};

		var init = {
			method: options.method || 'GET',
			headers: {
				'X-WP-Nonce': cfg.nonce,
				'Accept': 'application/json'
			},
			credentials: 'same-origin'
		};

		if ( options.body ) {
			init.headers['Content-Type'] = 'application/json';
			init.body = JSON.stringify( options.body );
		}

		return fetch( cfg.root + path, init ).then( function ( response ) {
			return response.json().then( function ( data ) {
				if ( ! response.ok ) {
					var error = new Error( ( data && data.message ) || i18n.error );
					error.data = data;
					throw error;
				}
				return data;
			} );
		} );
	}

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( undefined !== text && null !== text ) {
			node.textContent = String( text );
		}
		return node;
	}

	function uuid() {
		if ( window.crypto && window.crypto.randomUUID ) {
			return window.crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c ) {
			var r = ( Math.random() * 16 ) | 0;
			var v = 'x' === c ? r : ( r & 0x3 ) | 0x8;
			return v.toString( 16 );
		} );
	}

	function debounce( fn, wait ) {
		var timer;
		return function () {
			var args = arguments;
			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( null, args );
			}, wait );
		};
	}

	function setMessage( node, text, tone ) {
		if ( ! node ) {
			return;
		}
		node.textContent = text || '';
		node.className = 'cp-formmsg' + ( tone ? ' is-' + tone : '' );
	}

	/* ── فرم سفارش ─────────────────────────────────────────── */

	var form = app.querySelector( '[data-cp-order]' );
	var tree = [];

	function initOrderForm() {
		if ( ! form ) {
			return;
		}

		var brandSel = form.querySelector( '[data-cp-brand]' );
		var catSel = form.querySelector( '[data-cp-category]' );
		var svcSel = form.querySelector( '[data-cp-service]' );
		var qty = form.querySelector( '#cp-qty' );
		var link = form.querySelector( '#cp-link' );
		var range = form.querySelector( '[data-cp-range]' );
		var hint = form.querySelector( '[data-cp-service-hint]' );
		var total = form.querySelector( '[data-cp-total]' );
		var msg = form.querySelector( '[data-cp-order-msg]' );
		var submit = form.querySelector( '[data-cp-submit]' );

		function currentBrand() {
			return tree[ brandSel.value ] || null;
		}

		function currentCategory() {
			var brand = currentBrand();
			return brand ? brand.categories[ catSel.value ] : null;
		}

		function currentService() {
			var category = currentCategory();
			if ( ! category ) {
				return null;
			}
			for ( var i = 0; i < category.services.length; i++ ) {
				if ( String( category.services[ i ].id ) === svcSel.value ) {
					return category.services[ i ];
				}
			}
			return null;
		}

		function fillBrands() {
			brandSel.innerHTML = '';
			tree.forEach( function ( brand, index ) {
				brandSel.appendChild( new Option( brand.label, String( index ) ) );
			} );
			fillCategories();
		}

		function fillCategories() {
			var brand = currentBrand();
			catSel.innerHTML = '';
			catSel.disabled = ! brand;
			if ( ! brand ) {
				return;
			}
			brand.categories.forEach( function ( category, index ) {
				catSel.appendChild( new Option( category.label, String( index ) ) );
			} );
			fillServices();
		}

		function fillServices() {
			var category = currentCategory();
			svcSel.innerHTML = '';
			svcSel.disabled = ! category;
			if ( ! category ) {
				return;
			}
			category.services.forEach( function ( service ) {
				svcSel.appendChild( new Option( service.name, String( service.id ) ) );
			} );
			onServiceChange();
		}

		function onServiceChange() {
			var service = currentService();
			if ( ! service ) {
				range.textContent = '';
				hint.textContent = '';
				total.textContent = '—';
				return;
			}

			qty.min = service.min;
			qty.max = service.max;
			if ( ! qty.value || Number( qty.value ) < service.min ) {
				qty.value = service.min;
			}

			range.textContent = ( i18n.quantityFmt || '%1$s — %2$s' )
				.replace( '%1$s', service.min.toLocaleString( 'fa-IR' ) )
				.replace( '%2$s', service.max.toLocaleString( 'fa-IR' ) );

			var tags = [];
			if ( service.refill ) {
				tags.push( 'جبران ریزش' );
			}
			if ( service.dripfeed ) {
				tags.push( 'Drip-feed' );
			}
			if ( service.cancel ) {
				tags.push( 'قابل لغو' );
			}
			hint.textContent = [ service.description, tags.join( ' · ' ) ].filter( Boolean ).join( ' — ' );

			if ( service.template_link ) {
				link.placeholder = service.template_link;
			}

			requestQuote();
		}

		var requestQuote = debounce( function () {
			var service = currentService();
			var amount = Number( qty.value );

			if ( ! service || ! amount || amount < service.min || amount > service.max ) {
				total.textContent = '—';
				return;
			}

			api( 'services/quote', {
				method: 'POST',
				body: { service_id: service.id, quantity: amount }
			} ).then( function ( data ) {
				total.textContent = data.charge.display;
			} ).catch( function () {
				total.textContent = '—';
			} );
		}, 350 );

		brandSel.addEventListener( 'change', fillCategories );
		catSel.addEventListener( 'change', fillServices );
		svcSel.addEventListener( 'change', onServiceChange );
		qty.addEventListener( 'input', requestQuote );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var service = currentService();
			if ( ! service ) {
				setMessage( msg, i18n.selectSvc, 'bad' );
				return;
			}

			submit.disabled = true;
			var original = submit.textContent;
			submit.textContent = i18n.submitting || '…';
			setMessage( msg, '' );

			api( 'orders', {
				method: 'POST',
				body: {
					service_id: service.id,
					quantity: Number( qty.value ),
					link: link.value.trim(),
					idempotency_key: uuid()
				}
			} ).then( function ( order ) {
				setMessage( msg, 'سفارش ' + order.id + ' ثبت شد — ' + order.status_label, 'ok' );
				link.value = '';
				loadOrders();
				loadWallet();
			} ).catch( function ( error ) {
				setMessage( msg, error.message || i18n.error, 'bad' );
			} ).finally( function () {
				submit.disabled = false;
				submit.textContent = original;
			} );
		} );

		api( 'services/tree' ).then( function ( data ) {
			tree = Array.isArray( data ) ? data : [];
			if ( ! tree.length ) {
				brandSel.innerHTML = '';
				brandSel.appendChild( new Option( i18n.empty, '' ) );
				return;
			}
			fillBrands();
		} ).catch( function () {
			brandSel.innerHTML = '';
			brandSel.appendChild( new Option( i18n.error, '' ) );
		} );
	}

	/* ── سفارش‌ها ──────────────────────────────────────────── */

	var ordersBody = app.querySelector( '[data-cp-orders]' );

	function loadOrders() {
		if ( ! ordersBody ) {
			return;
		}

		api( 'orders' ).then( function ( data ) {
			ordersBody.innerHTML = '';

			if ( ! data.items.length ) {
				var empty = el( 'tr' );
				var cell = el( 'td', 'cp-empty', i18n.empty );
				cell.colSpan = 6;
				empty.appendChild( cell );
				ordersBody.appendChild( empty );
				updateStats( [] );
				return;
			}

			data.items.forEach( function ( order ) {
				var row = el( 'tr' );

				row.appendChild( el( 'td', 'cp-oid', '#' + order.id ) );
				row.appendChild( el( 'td', 'cp-svcname', order.service_name ) );
				row.appendChild( el( 'td', '', order.quantity.toLocaleString( 'fa-IR' ) ) );
				row.appendChild( el( 'td', '', order.charge.display ) );

				var progressCell = el( 'td' );
				var wrap = el( 'span', 'cp-prog' );
				var bar = el( 'span', 'cp-bar' );
				var fill = el( 'i' );
				fill.style.width = ( null === order.progress ? 0 : order.progress ) + '%';
				bar.appendChild( fill );
				wrap.appendChild( bar );
				wrap.appendChild( el( 'span', 'cp-prog__n', null === order.progress ? '—' : order.progress + '٪' ) );
				progressCell.appendChild( wrap );
				row.appendChild( progressCell );

				var statusCell = el( 'td' );
				statusCell.appendChild( el( 'span', 'cp-pill cp-pill--' + order.status_tone, order.status_label ) );
				row.appendChild( statusCell );

				ordersBody.appendChild( row );
			} );

			updateStats( data.items );
		} ).catch( function () {
			ordersBody.innerHTML = '';
			var row = el( 'tr' );
			var cell = el( 'td', 'cp-empty', i18n.error );
			cell.colSpan = 6;
			row.appendChild( cell );
			ordersBody.appendChild( row );
		} );
	}

	function updateStats( items ) {
		var running = 0;
		var completed = 0;

		items.forEach( function ( order ) {
			if ( 'processing' === order.status || 'in_progress' === order.status ) {
				running++;
			}
			if ( 'completed' === order.status ) {
				completed++;
			}
		} );

		setStat( 'running', running );
		setStat( 'completed', completed );
		setStat( 'total', items.length );
	}

	function setStat( key, value ) {
		var node = app.querySelector( '[data-cp-stat="' + key + '"]' );
		if ( node ) {
			node.textContent = Number( value ).toLocaleString( 'fa-IR' );
		}
	}

	/* ── کیف پول ───────────────────────────────────────────── */

	var ledger = app.querySelector( '[data-cp-ledger]' );
	var topup = app.querySelector( '[data-cp-topup]' );

	function loadWallet() {
		var balanceNode = app.querySelector( '[data-cp-balance]' );

		api( 'wallet' ).then( function ( data ) {
			if ( balanceNode ) {
				balanceNode.textContent = data.balance.display;
			}
		} ).catch( function () {} );

		if ( ! ledger ) {
			return;
		}

		api( 'wallet/transactions' ).then( function ( data ) {
			ledger.innerHTML = '';

			if ( ! data.items.length ) {
				ledger.appendChild( el( 'p', 'cp-empty', i18n.empty ) );
				return;
			}

			data.items.forEach( function ( item ) {
				var row = el( 'div', 'cp-lrow' );
				var isCredit = 'credit' === item.direction;

				row.appendChild( el( 'span', 'cp-lico cp-lico--' + ( isCredit ? 'in' : 'out' ), isCredit ? '+' : '−' ) );

				var meta = el( 'span', 'cp-lmeta' );
				meta.appendChild( el( 'span', 'cp-lmeta__t', labelForType( item ) ) );
				meta.appendChild( el( 'span', 'cp-lmeta__d', item.created_fa + ( item.ref ? ' · ' + item.ref : '' ) ) );
				row.appendChild( meta );

				row.appendChild(
					el( 'strong', 'cp-lamt cp-lamt--' + ( isCredit ? 'in' : 'out' ), ( isCredit ? '+' : '−' ) + ' ' + item.amount.display )
				);

				ledger.appendChild( row );
			} );
		} ).catch( function () {
			ledger.innerHTML = '';
			ledger.appendChild( el( 'p', 'cp-empty', i18n.error ) );
		} );
	}

	function labelForType( item ) {
		switch ( item.type ) {
			case 'deposit':
				return 'شارژ کیف پول' + ( item.gateway ? ' — ' + item.gateway : '' );
			case 'order':
				return 'پرداخت سفارش';
			case 'refund':
				return 'بازگشت وجه';
			case 'adjust':
				return 'تعدیل توسط پشتیبانی' + ( item.reason ? ' — ' + item.reason : '' );
			default:
				return item.type;
		}
	}

	if ( topup ) {
		topup.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var msg = topup.querySelector( '[data-cp-topup-msg]' );
			var button = topup.querySelector( 'button[type="submit"]' );

			button.disabled = true;
			setMessage( msg, '' );

			api( 'wallet/topup', {
				method: 'POST',
				body: {
					amount_tomans: Number( topup.querySelector( '[name="amount"]' ).value ),
					gateway: topup.querySelector( '[name="gateway"]' ).value
				}
			} ).then( function ( data ) {
				window.location.href = data.redirect;
			} ).catch( function ( error ) {
				setMessage( msg, error.message || i18n.error, 'bad' );
				button.disabled = false;
			} );
		} );
	}

	/* ── تیکت‌ها ───────────────────────────────────────────── */

	var ticketsBox = app.querySelector( '[data-cp-tickets]' );
	var ticketForm = app.querySelector( '[data-cp-ticket]' );

	function loadTickets() {
		if ( ! ticketsBox ) {
			return;
		}

		api( 'tickets' ).then( function ( data ) {
			ticketsBox.innerHTML = '';

			if ( ! data.items.length ) {
				ticketsBox.appendChild( el( 'p', 'cp-empty', i18n.empty ) );
				return;
			}

			data.items.forEach( function ( ticket ) {
				var row = el( 'div', 'cp-lrow' );
				var meta = el( 'span', 'cp-lmeta' );
				meta.appendChild( el( 'span', 'cp-lmeta__t', ticket.subject ) );
				meta.appendChild( el( 'span', 'cp-lmeta__d', ticket.updated_fa ) );
				row.appendChild( meta );
				row.appendChild( el( 'span', 'cp-pill cp-pill--' + ( 'answered' === ticket.status ? 'ok' : 'run' ), ticket.status ) );
				ticketsBox.appendChild( row );
			} );
		} ).catch( function () {
			ticketsBox.innerHTML = '';
			ticketsBox.appendChild( el( 'p', 'cp-empty', i18n.error ) );
		} );
	}

	if ( ticketForm ) {
		ticketForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var msg = ticketForm.querySelector( '[data-cp-ticket-msg]' );
			var button = ticketForm.querySelector( 'button[type="submit"]' );

			button.disabled = true;
			setMessage( msg, '' );

			api( 'tickets', {
				method: 'POST',
				body: {
					department: ticketForm.querySelector( '[name="department"]' ).value,
					subject: ticketForm.querySelector( '[name="subject"]' ).value,
					body: ticketForm.querySelector( '[name="body"]' ).value
				}
			} ).then( function () {
				setMessage( msg, 'تیکت ثبت شد. پاسخ در همین بخش نمایش داده می‌شود.', 'ok' );
				ticketForm.reset();
				loadTickets();
			} ).catch( function ( error ) {
				setMessage( msg, error.message || i18n.error, 'bad' );
			} ).finally( function () {
				button.disabled = false;
			} );
		} );
	}

	/* ── راه‌اندازی ────────────────────────────────────────── */

	app.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-cp-refresh]' );
		if ( ! button ) {
			return;
		}
		if ( 'orders' === button.getAttribute( 'data-cp-refresh' ) ) {
			loadOrders();
		}
	} );

	initOrderForm();
	loadOrders();
	loadWallet();
	loadTickets();
} )();
