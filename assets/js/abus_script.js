jQuery( function( $ ) {

	var entryButton = $( '#wp-admin-bar-abus_switch_to_user > a' ),
	    exitButton = $( '#wp-admin-bar-switch_back > a' ),
	    $wrapper    = $( '#abus_wrapper' ),
	    $form       = $wrapper.find( 'form' ),
	    $input      = $form.find( 'input[name="abus_search_text"]' ),
	    currenturl  = $form.find( 'input[name="abus_current_url"]' ).val(),
	    nonce       = $form.find( 'input[name="abus_nonce"]' ).val(),
	    $content    = $wrapper.find( '#abus_result' )
		;


	// Clicking the admin-bar entry focuses the text box
	entryButton.on( 'click', function() {
		$input.focus();

		return false;
	} );

	// Navigate through results using arrows
	$wrapper.on( 'keydown', '.result', function( ev ) {
		var results = $wrapper.find( '.abus_user_results .result' ),
		    active  = results.filter( '.active' ),
		    idx     = 0;

		if ( results.length < 2 ) {
			return;
		}

		if ( 0 == active.length ) {
			active = results.eq( 0 ).addClass( 'active' );
		}

		// Down
		if ( 40 == ev.which ) {
			idx = results.index( active );
			if ( results.length - idx > 1 ) {
				active.removeClass( 'active' );
				results.eq( idx + 1 ).addClass( 'active' ).find( 'a' ).focus();

				return false;
			}
		}
		// Up
		else if ( 38 == ev.which ) {
			idx = results.index( active );
			if ( idx > 0 ) {
				active.removeClass( 'active' );
				results.eq( idx - 1 ).addClass( 'active' ).find( 'a' ).focus();

				return false;
			}
		}
	} );

	// Form submission / user search
	$form.submit( function() {

		var query = $input.val();

		$.ajax( {
			        type : 'post',
			        url : abus_ajax.ajaxurl,
			        data : {
				        action : 'abus_user_search',
				        query : query,
				        currenturl : currenturl,
				        nonce : nonce
			        },
			        beforeSend : function() {
				        $input.prop( 'disabled', true );
				        $content.addClass( 'loading' );
			        },
			        success : function( response ) {
				        $input.prop( 'disabled', false );
				        $content.removeClass( 'loading' );
				        $content.html( response );

				        // Focus the first result
				        $content.find( '.result:eq(0)' ).addClass( 'active' )
					        .find( 'a' ).focus();
			        }
		        } );

		return false;

	} );

	var magicWord         = ( 'undefined' !== typeof abus_ajax.magicword ? abus_ajax.magicword : '' ),
	    magicWordProgress = '';

	/**
	 * Activate search box after typing 'switch' ( or configured word ) in the open ( while not an input is focused )
	 */
	if ( magicWord ) {
		$( document ).on( 'keypress', function( ev ) {
			var el = $( ev.target );

			if ( el.filter( ':input' ).size() ) {
				magicWordProgress = '';
				return;
			}

			if ( -1 == magicWord.indexOf( magicWordProgress ) ) {
				magicWordProgress = '';
			}
			magicWordProgress += String.fromCharCode( ev.which );

			if ( magicWord === magicWordProgress ) {

				if ( entryButton.length ) {
					magicWordProgress = '';
					entryButton.parent().addClass('hover');
					$input.focus();

					return false;
				} else if ( exitButton.length ) {
					magicWordProgress = '';
					exitButton.focus();

					return false;
				}

			}
		} );
	}

} );
