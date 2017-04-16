<?php
/**
 * Plugin Name: User Switching in Admin Bar
 * Plugin URI: http://markwilkinson.me
 * Description: Building upon the <a href="http://wordpress.org/extend/plugins/user-switching/">User Switching plugin</a> by John Blackbourn this plugin adds a dropdown list of users in the WordPress admin bar with a link to switch to that user, then providing a switch back link in the admin bar too.
 * Author: Mark Wilkinson
 * Author URI: http://markwilkinson.me
 * Version: 1.1.2
*/

/**
 * Function abus_current_url
 * Determine the URL of the currently viewed page - will return array if $parse set to true
 * Taken from https://github.com/scottsweb/null/blob/master/functions.php
 */
function abus_get_redirect_url( $parse = false ) {

	return apply_filters( 'abus_switch_to_redirect', admin_url() );
}

/**
 * Function abus_usab_initialisation
 * Initialisation plugin to add error message to admin if user switching plugin not present
 */
function abus_usab_initialisation() {
	add_action( 'admin_notices', 'abus_error' );
}

/**
 * Function abus_usab_error
 * Deactivates the plugin and throws and error message when User Switching plugin not active
 */
function abus_error() {

	if( ! class_exists( 'user_switching' ) ) {
	
		deactivate_plugins( 'admin-bar-user-switching/admin-bar-user-switching.php', 'admin-bar-user-switching.php' );
	
		echo '<div class="error"><p>This plugin has <strong>been deactivated</strong>. The reason for this, is that it requires the User Switching plugin in order to work. Please install the User Switching plugin, then activate this plugin again. <strong>Please ignore the Plugin Activated message below</strong>.</p></div>';
	
	}
	
}

/* start this plugin once all other plugins have loaded */
add_action( 'plugins_loaded', 'abus_usab_initialisation' );

/**
 * function abus_adminbar_output()
 * output the admin bar markup for the user search box
 */
function abus_adminbar_output() {
	
	/* if user switching is not active - go no further! */
	if( ! class_exists( 'user_switching' ) ) {
		return;
	}
	
	/* check wether the admin bar is showing */
	if( is_admin_bar_showing() ) {
		
		global $user_switching;
		
		/* load the global admin bar variable */
		global $wp_admin_bar;
			
		/* check whether the current user can edit users - cap is filterable */
		if( current_user_can( apply_filters( 'abus_switch_to_capability', 'edit_users' ) ) ) {
		
			/* add admin bar menu for switching to a user */
			$wp_admin_bar->add_menu(
				array(
					'id'    => 'abus_switch_to_user',
					'title' => apply_filters( 'abus_switch_to_text', 'Switch to User' ),
					'href'  => '#',
				)
			);
			
			/* create a nonce */
			$nonce = wp_create_nonce( 'abus_user_search_nonce' );
			
			/* build the user search form markup */
			$form = '
				<div id="abus_wrapper">
					<form method="post" action="abus_user_search">
						<input id="abus_search_text" name="abus_search_text" autocomplete="off" type="text" placeholder="Enter a username" />
						<input id="abus_search_submit" name="abus_search_submit" type="submit" />
						<input name="abus_nonce" type="hidden" value="' . wp_create_nonce( 'abus_nonce' ) . '" />
					</form>
					<div id="abus_result"></div>
				</div>
			';
			
			/* add the admin bar sub menu item for the search form */
			$wp_admin_bar->add_menu(
				array(
					'id'		=> 'abus_user_search',
					'parent'	=> 'abus_switch_to_user',
					'title'		=> apply_filters( 'abus_form_output', $form ),
				)
			);
			
		} // end if super admin
		
		/* check if there is an old user stored i.e. this logged in user is through switching */
		if( $user_switching->get_old_user() ) {
			
			/* build the switch back url */
			$abus_switch_back_url = $user_switching->switch_back_url( $user_switching->get_old_user() );
			
			/* we are logged in throught swtiching so add admin bar menu to create the switch back link */
			$wp_admin_bar->add_menu( array(
				'id'    => 'switch_back',
				'title' => apply_filters( 'abus_switch_back_text', 'Switch Back' ),
				'href'   => esc_url( add_query_arg( array( 'redirect_to' => abus_get_redirect_url() ), $abus_switch_back_url ) )
			) );
			
		} // end if old user present
			
	} // end if admin bar showing
	
}

add_action( 'wp_before_admin_bar_render', 'abus_adminbar_output', 1 );

/**
 * function abus_user_search()
 * searches for the required user depending what was entered into the search box
 * in the admin bar
 */
function abus_user_search() {
		
	global $user_switching;
	
	/* get the posted query search, current url and nonce */
	$q = esc_attr( $_POST[ 'query' ] );
	$url = esc_url( $_POST[ 'currenturl' ] );
	$nonce = esc_attr( $_POST[ 'nonce' ] );
	
	/* check nonce passes for intent */
	if( ! wp_verify_nonce( $nonce, 'abus_nonce' ) )
		exit();
	
	$args = apply_filters(
		'abus_user_search_args',
		array(
			'search'	=> is_numeric( $q ) ? $q : '*' . $q . '*',
		)
	);
	
	/* query the users */
	$user_query = new WP_User_Query( $args );
	
	echo '<div class="abus_user_results">';
	
	/* check we have results returned */
	if ( ! empty( $user_query->results ) ) {
		
		/* loop through each returned user */
		foreach ( $user_query->results as $user ) {
			
			/* if this user is the current user - skip to next user */
			if( $user->ID == get_current_user_id() ) {
				continue;
			}
			
			$link = user_switching::maybe_switch_url( $user );
			if ( $link ) {
				$link = add_query_arg( 'redirect_to', apply_filters( 'abus_switch_to_url', $url ), $link );
				echo '<p class="result"><a href="' . esc_url( $link, $user ) . '">' . $user->display_name . '</a></p>';
			}
			
		}
	
	/* no users match search */
	} else {
		
		echo '<p class="result">No users found.</p>';
		
	}
	
	echo '</div>';
	
	die();
	
}

add_action( 'wp_ajax_abus_user_search', 'abus_user_search' );

/**
 * function abus_enqueue_scripts()
 * enqueues the necessary js and css for the plugin
 */
function abus_enqueue_scripts() {
   
	wp_register_script(
		'abus_script',
		plugins_url( '/assets/js/abus_script.js', __FILE__ ),
		array( 'jquery' )
	);

	$args = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'magicWord' => '',
	);

	$args = apply_filters( 'abus_ajax_args', $args );

	wp_localize_script(
		'abus_script',
		'abus_ajax',
		$args
	);        
	
	if( is_user_logged_in() ) {
		wp_enqueue_script( 'abus_script' );
	}

}

add_action( 'wp_enqueue_scripts', 'abus_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'abus_enqueue_scripts' );

/**
 * function abus_enqueue_styles()
 * enqueues the plugin stylsheet
 */
function abus_styles() {
	
	global $_wp_admin_css_colors;
	$hover_color = $_wp_admin_css_colors[get_user_option('admin_color')]->colors[2];

	$styles = '
		<style type="text/css">
			#wpadminbar .quicklinks #wp-admin-bar-abus_switch_to_user ul li .ab-item { height: auto; }
			#abus_user_results { background-color: #000000; }
			#abus_wrapper #abus_search_text {
				background-color: #fff;
				padding-left: 5px;
			}

			#abus_wrapper #abus_search_text:disabled { 
				background-image: url("/wp-content/plugins/woocommerce/assets/images/select2-spinner.gif");
				background-repeat: no-repeat;
				background-position: center right 5px;
				padding-left: 5px;
			}

			#abus_wrapper #abus_search_submit {
				display: none;
			}

			#abus_wrapper #abus_result p.result {
				background-color: #222;
				margin-top: 1px;
			}

			#abus_wrapper #abus_result p.result.active, #abus_wrapper #abus_result p.result:hover {
				background-color: ' . $hover_color. ';
				color: #fff;
				padding-left: 5px;
			}

			#abus_wrapper #abus_result p.result.active a, #abus_wrapper #abus_result p.result:hover a {
				color: #fff;
				padding-left: 5px;
			}

		</style>
	';
	
	echo apply_filters( 'abus_styles', $styles );
	
}

add_action( 'wp_head', 'abus_styles' );
add_action( 'admin_head', 'abus_styles' );