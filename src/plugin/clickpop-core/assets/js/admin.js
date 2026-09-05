/**
 * ClickPop — پنل مدیریت.
 *
 * فقط دو کار: درج پاسخ آماده در فرم تیکت، و شمارندهٔ کاراکتر.
 * بدون jQuery، بدون وابستگی.
 */
( function () {
	'use strict';

	var reply = document.getElementById( 'cp-reply-body' );

	if ( ! reply ) {
		return;
	}

	document.querySelectorAll( '[data-cp-canned]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var text = button.getAttribute( 'data-cp-canned' ) || '';
			var current = reply.value.trim();

			reply.value = current ? current + '\n\n' + text : text;
			reply.focus();
			reply.setSelectionRange( reply.value.length, reply.value.length );
		} );
	} );
} )();
