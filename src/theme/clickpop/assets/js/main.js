/**
 * ClickPop Theme — main.js
 *
 * بدون وابستگی. هر تعامل با کیبورد هم کار می‌کند.
 */
( function () {
	'use strict';

	var root = document.documentElement;

	/* ── تعویض تم: روشن / تیره ─────────────────────────────── */
	document.querySelectorAll( '[data-cp-theme-toggle]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var next = 'dark' === root.getAttribute( 'data-theme' ) ? 'light' : 'dark';

			root.setAttribute( 'data-theme', next );

			try {
				localStorage.setItem( 'cp-theme', next );
			} catch ( e ) {}
		} );
	} );

	// اگر کاربر انتخاب صریح نکرده، تغییر تم سیستم باید بلافاصله اثر کند.
	if ( window.matchMedia ) {
		window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', function ( event ) {
			var stored = null;

			try {
				stored = localStorage.getItem( 'cp-theme' );
			} catch ( e ) {}

			if ( 'light' !== stored && 'dark' !== stored ) {
				root.setAttribute( 'data-theme', event.matches ? 'dark' : 'light' );
			}
		} );
	}

	/* ── منوی موبایل ───────────────────────────────────────── */
	var burger = document.querySelector( '[data-cp-burger]' );
	var mobileNav = document.getElementById( 'cp-mobile-nav' );

	if ( burger && mobileNav ) {
		burger.addEventListener( 'click', function () {
			var open = 'true' === burger.getAttribute( 'aria-expanded' );

			burger.setAttribute( 'aria-expanded', String( ! open ) );
			mobileNav.hidden = open;
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && 'true' === burger.getAttribute( 'aria-expanded' ) ) {
				burger.setAttribute( 'aria-expanded', 'false' );
				mobileNav.hidden = true;
				burger.focus();
			}
		} );
	}

	/* ── آکاردئون پرسش‌ها ──────────────────────────────────── */
	document.querySelectorAll( '.cp-qa__q' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var open = 'true' === button.getAttribute( 'aria-expanded' );
			var panel = document.getElementById( button.getAttribute( 'aria-controls' ) );

			button.setAttribute( 'aria-expanded', String( ! open ) );

			if ( panel ) {
				panel.hidden = open;
			}
		} );
	} );

	/* ── شمارنده ───────────────────────────────────────────── */
	var counters = document.querySelectorAll( '[data-cp-count]' );

	if ( counters.length && window.IntersectionObserver ) {
		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( ! reduced ) {
			var observer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					animate( entry.target );
					observer.unobserve( entry.target );
				} );
			}, { threshold: 0.4 } );

			counters.forEach( function ( node ) {
				observer.observe( node );
			} );
		}
	}

	function animate( node ) {
		var target = parseInt( node.getAttribute( 'data-cp-count' ), 10 ) || 0;
		var suffix = node.querySelector( 'em' );
		var started = null;
		var duration = 1100;

		// عمداً بدون innerHTML: فقط گرهٔ متنی عدد به‌روز می‌شود و <em> پسوند دست‌نخورده می‌ماند.
		var number = document.createTextNode( '' );
		node.textContent = '';
		node.appendChild( number );

		if ( suffix ) {
			node.appendChild( suffix );
		}

		function step( timestamp ) {
			if ( null === started ) {
				started = timestamp;
			}

			var progress = Math.min( ( timestamp - started ) / duration, 1 );
			var eased = 1 - Math.pow( 1 - progress, 3 );

			number.nodeValue = Math.round( target * eased ).toLocaleString( 'fa-IR' );

			if ( progress < 1 ) {
				window.requestAnimationFrame( step );
			}
		}

		window.requestAnimationFrame( step );
	}
} )();
