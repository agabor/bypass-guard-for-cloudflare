document.addEventListener( 'DOMContentLoaded', function () {
	var copyButtons = document.querySelectorAll( '.bypass-guard-copy-btn' );

	copyButtons.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var value = button.getAttribute( 'data-copy-value' );

			copyToClipboard( value ).then( function () {
				showCopiedFeedback( button );
			} );
		} );
	} );

	function copyToClipboard( value ) {
		return navigator.clipboard.writeText( value );
	}

	function showCopiedFeedback( button ) {
		var originalText = button.textContent;

		button.textContent = 'Copied!';
		button.classList.add( 'copied' );

		setTimeout( function () {
			button.textContent = originalText;
			button.classList.remove( 'copied' );
		}, 1500 );
	}
} );