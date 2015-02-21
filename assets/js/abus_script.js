jQuery(document).on( 'submit', '#abus_wrapper form', function() {
	
	/* get the form */
	var $form = jQuery(this);
	
	/* get the form input for searching */
    var $input = $form.find('input[name="abus_search_text"]');
    
    /* get the value of our search input */
    var query = $input.val();
    
    /* get the form input for current url */
    var $currenturl = $form.find('input[name="abus_current_url"]');
    
    /* get the value of the current url */
    var currenturl = $currenturl.val();
    
    /* get the nonce */
    var $nonce = $form.find('input[name="abus_nonce"]');
    
    /* get the nonce value */
    var nonce = $nonce.val();
    
    /* get the results div */
    var $content = jQuery('#abus_result');
    
	jQuery.ajax({
		type : 'post',
		url : abus_ajax.ajaxurl,
		data : {
			action : 'abus_user_search',
			query : query,
			currenturl : currenturl,
			nonce : nonce
		},
		beforeSend: function() {
			$input.prop('disabled', true);
			$content.addClass('loading');
		},
		success : function( response ) {
			$input.prop('disabled', false);
			$content.removeClass('loading');
			$content.html( response );
		}
	});
	
	return false;
    
})