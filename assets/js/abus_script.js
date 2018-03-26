jQuery( function( $ ) {

	var entryButton = $( '#wp-admin-bar-abus_switch_to_user > a' ),
	    exitButton = $( '#wp-admin-bar-switch_back > a' ),
	    $wrapper    = $( '#wp-admin-bar-abus_switch_to_user' ),
	    $form       = $wrapper.find( 'form' ),
	    $input      = $form.find( 'input[name="abus_search_text"]' ),
	    currenturl  = $form.find( 'input[name="abus_current_url"]' ).val(),
	    nonce       = $form.find( 'input[name="abus_nonce"]' ).val(),
	    $form_li    = $('#wp-admin-bar-abus_user_search')
		;

	$( '#abus_search_text' ).on({
		focus: function() {
			$( '#wp-admin-bar-abus_switch_to_user #adminbarsearch' ).addClass( 'adminbar-focused' );
		}, blur: function() {
			$( '#wp-admin-bar-abus_switch_to_user #adminbarsearch' ).removeClass( 'adminbar-focused' );
		}
	} );

	// Clicking the admin-bar entry focuses the text box
	entryButton.on( 'click', function() {
		setTimeout(function(){
			$input.focus();
		});
	} );

	// Navigate through results using arrows
	$wrapper.on( 'keydown', '.switch-to-user', function( ev ) {
		var results = $wrapper.find( '#wp-admin-bar-abus_switch_to_user-default .switch-to-user' ),
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
				        $form_li.nextAll('li').remove();
			        },
			        success : function( response ) {
				        $input.prop( 'disabled', false );
				        $form_li.after( response );

				        // Focus the first result
				       $("#wp-admin-bar-abus_switch_to_user-default").find( '.switch-to-user:eq(0)' ).addClass( 'active' )
					        .find( 'a' ).focus();
			        }
		        } );

		return false;

	} );

	var magicWord         = ( 'undefined' !== typeof abus_ajax.magicWord ? abus_ajax.magicWord : '' ),
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
