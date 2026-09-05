/**
 * ClickPop — پنل مدیریت.
 *
 * بدون jQuery. سه کار: پاسخ آمادهٔ تیکت، ریپیتر پویا، انتخابگر رسانه.
 */
( function () {
	'use strict';

	/* ── پاسخ آمادهٔ تیکت ─────────────────────────────────── */
	var reply = document.getElementById( 'cp-reply-body' );

	if ( reply ) {
		document.querySelectorAll( '[data-cp-canned]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var text = button.getAttribute( 'data-cp-canned' ) || '';
				var current = reply.value.trim();

				reply.value = current ? current + '\n\n' + text : text;
				reply.focus();
				reply.setSelectionRange( reply.value.length, reply.value.length );
			} );
		} );
	}

	/* ── ریپیتر ────────────────────────────────────────────── */
	document.querySelectorAll( '[data-cp-rep]' ).forEach( function ( rep ) {
		var rows = rep.querySelector( '[data-cp-rep-rows]' );
		var tpl = rep.querySelector( '[data-cp-rep-tpl]' );
		var add = rep.querySelector( '[data-cp-rep-add]' );

		if ( ! rows || ! tpl || ! add ) {
			return;
		}

		function reindex() {
			var key = rep.getAttribute( 'data-cp-rep-key' );

			rows.querySelectorAll( '[data-cp-rep-row]' ).forEach( function ( row, index ) {
				row.querySelectorAll( '[name]' ).forEach( function ( input ) {
					input.name = input.name.replace(
						new RegExp( 'cp_rep\\\\[' + key + '\\\\]\\\\[[^\\\\]]*\\\\]' ),
						'cp_rep[' + key + '][' + index + ']'
					);
				} );
			} );
		}

		add.addEventListener( 'click', function () {
			var html = tpl.innerHTML.replace( /__INDEX__/g, String( Date.now() ) );
			var wrap = document.createElement( 'div' );

			wrap.innerHTML = html;

			var node = wrap.firstElementChild;

			if ( node ) {
				rows.appendChild( node );
				bindMedia( node );
				reindex();

				var first = node.querySelector( 'input, textarea, select' );
				if ( first ) {
					first.focus();
				}
			}
		} );

		rows.addEventListener( 'click', function ( event ) {
			var del = event.target.closest( '[data-cp-rep-del]' );

			if ( ! del ) {
				return;
			}

			var row = del.closest( '[data-cp-rep-row]' );

			if ( row ) {
				row.remove();
				reindex();
			}
		} );
	} );

	/* ── انتخابگر رسانه ────────────────────────────────────── */
	function bindMedia( scope ) {
		( scope || document ).querySelectorAll( '[data-cp-media]' ).forEach( function ( box ) {
			if ( box.dataset.cpMediaBound ) {
				return;
			}
			box.dataset.cpMediaBound = '1';

			var input = box.querySelector( '[data-cp-media-input]' );
			var preview = box.querySelector( '[data-cp-media-preview]' );
			var pick = box.querySelector( '[data-cp-media-pick]' );
			var clear = box.querySelector( '[data-cp-media-clear]' );

			if ( pick ) {
				pick.addEventListener( 'click', function () {
					if ( ! window.wp || ! window.wp.media ) {
						return;
					}

					var frame = window.wp.media( {
						title: 'انتخاب تصویر',
						button: { text: 'استفاده از این تصویر' },
						library: { type: 'image' },
						multiple: false
					} );

					frame.on( 'select', function () {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						var url = attachment.sizes && attachment.sizes.thumbnail
							? attachment.sizes.thumbnail.url
							: attachment.url;

						input.value = attachment.id;
						preview.textContent = '';

						var img = document.createElement( 'img' );
						img.src = url;
						img.alt = '';
						preview.appendChild( img );
					} );

					frame.open();
				} );
			}

			if ( clear ) {
				clear.addEventListener( 'click', function () {
					input.value = '0';
					preview.textContent = '';
				} );
			}
		} );
	}

	bindMedia( document );

	/* ── نمایش کد رنگ کنار انتخابگر ───────────────────────── */
	document.querySelectorAll( '.cp-colorwrap input[type="color"]' ).forEach( function ( picker ) {
		var hex = picker.parentNode.querySelector( '.cp-colorhex' );

		if ( ! hex ) {
			return;
		}

		picker.addEventListener( 'input', function () {
			hex.value = picker.value.toUpperCase();
		} );
	} );

	/* ── انتخاب همه در جدول سفارش‌ها ──────────────────────── */
	var checkAll = document.querySelector( '[data-cp-checkall]' );

	if ( checkAll ) {
		checkAll.addEventListener( 'change', function () {
			document.querySelectorAll( '[data-cp-rowcheck]' ).forEach( function ( box ) {
				box.checked = checkAll.checked;
			} );
		} );
	}

	var bulkForm = document.querySelector( '[data-cp-bulkform]' );

	if ( bulkForm ) {
		bulkForm.addEventListener( 'submit', function ( event ) {
			var action = bulkForm.querySelector( '[name="bulk"]' ).value;
			var checked = bulkForm.querySelectorAll( '[data-cp-rowcheck]:checked' ).length;

			if ( ! action ) {
				event.preventDefault();
				return;
			}

			if ( ! checked ) {
				event.preventDefault();
				window.alert( 'هیچ سفارشی انتخاب نشده است.' );
				return;
			}

			if ( 'cancel_refund' === action ) {
				var ok = window.confirm(
					'مبلغ ' + checked + ' سفارش به کیف پول کاربران برمی‌گردد. این عمل برگشت‌پذیر نیست. ادامه می‌دهید؟'
				);

				if ( ! ok ) {
					event.preventDefault();
				}
			}
		} );
	}
} )();
