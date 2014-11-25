<?php
/**
Plugin Name: User Switching in Admin Bar
Plugin URI: http://markwilkinson.me
Description: Building upon the <a href="http://wordpress.org/extend/plugins/user-switching/">User Switching plugin</a> by John Blackbourn this plugin adds a dropdown list of users in the WordPress admin bar with a link to switch to that user, then providing a switch back link in the admin bar too.
Author: Mark Wilkinson
Author URI: http://markwilkinson.me
Version: 0.1
*/

/******************************************************************************************
* Function abus_current_url
* Determine the URL of the currently viewed page - will return array if $parse set to true
* Taken from https://github.com/scottsweb/null/blob/master/functions.php
******************************************************************************************/
function abus_current_url( $parse = false ) {

	$s = empty( $_SERVER[ 'HTTPS' ] ) ? '' : ( $_SERVER[ 'HTTPS' ] == 'on' ) ? 's' : '';
	$protocol = substr( strtolower( $_SERVER[ 'SERVER_PROTOCOL' ] ), 0, strpos( strtolower( $_SERVER[ 'SERVER_PROTOCOL' ] ), '/' ) ) . $s;
	$port = ( $_SERVER[ 'SERVER_PORT' ] == '80') ? '' : ( ":".$_SERVER[ 'SERVER_PORT' ] );
	
	if ( $parse ) {
		return parse_url( $protocol . "://" . $_SERVER[ 'HTTP_HOST' ] . $port . $_SERVER[ 'REQUEST_URI' ] );
	} else { 
		return $protocol . "://" . $_SERVER[ 'HTTP_HOST' ] . $port . $_SERVER[ 'REQUEST_URI' ];
	}
	
}

/******************************************************************************************
* Function abus_usab_initialisation
* Initialisation plugin to add error message to admin if user switching plugin not present
******************************************************************************************/
function abus_usab_initialisation() {
	add_action( 'admin_notices', 'abus_usab_error' );
}


/******************************************************************************************
* Function abus_usab_error
* Deactivates the plugin and throws and error message when User Switching plugin not active
******************************************************************************************/
function abus_usab_error() {

	if( !class_exists( 'user_switching' ) ) {
	
		deactivate_plugins( 'admin-bar-user-switching/admin-bar-user-switching.php', 'admin-bar-user-switching.php' );
	
		echo '<div class="error"><p>This plugin has <strong>been deactivated</strong>. The reason for this, is that it requires the User Switching plugin in order to work. Please install the User Switching plugin, then activate this plugin again. <strong>Please ignore the Plugin Activated message below</strong>.</p></div>';
	
	}
	
}

/* start this plugin once all other plugins have loaded */
add_action( 'plugins_loaded', 'abus_usab_initialisation' );

/******************************************************************************************
* Function abus_user_switching_adminbar
* Adds the Switcht to User menu items in the WordPress admin bar as well as a Switch Back
* link when users have switched.
******************************************************************************************/
function abus_user_switching_adminbar() {
	
	/* include plugin file to make this work on the front end */
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	/* check whether the user switching plugin is active */
	if( is_plugin_active( 'user-switching/user-switching.php' ) ) {
	
		/* check wether the admin bar is showing */
		if( is_admin_bar_showing() ) {
			
			/* load the user switching plugin global variable */
			global $user_switching;
			
			/* load the global admin bar variable */
			global $wp_admin_bar;
			
			/* check whether the current user is super admin */
			if( is_super_admin() ) {
			
				/* add admin bar menu for switching to a user */
				$wp_admin_bar->add_menu( array(
					'id'    => 'abus_switch_to_user',
					'title' => 'Switch to User',
					'href'  => '#',
				) );
				
				/* set some arguments for our user query */
				$abus_user_query_args = array(
					'role' => '',
					'orderby' => 'display_name'
				);
							
				/* create a new user query */
				$abus_user_query = new WP_User_Query( $abus_user_query_args );
				
				/* store results from user query */
				$abus_users = $abus_user_query->get_results();
				
				$abus_current_user = get_current_user_id();
				
				/* check we have users */
				if( !empty( $abus_users ) ) {
					
					/* loop through each user */
					foreach( $abus_users as $abus_user ) {
					
						/* check whether this user is the current user */
						if( $abus_current_user == $abus_user->ID )
							continue;
						
						/* get all of this users data */
						$abus_user_info = get_userdata( $abus_user->ID );
											
						/* build menu url */
						$abus_full_menu_url = $user_switching->switch_to_url( $abus_user ).'&redirect_to='.abus_current_url();
						
						/* build menu id for each user */
						$abus_menu_id = sanitize_key( $abus_user_info->first_name . '-' . $abus_user_info->last_name );
						
						/* add admin bar menu to create each users switch to link */
						$wp_admin_bar->add_menu( array(
							'id'    => $abus_menu_id,
							'parent' => 'abus_switch_to_user',
							'title' => $abus_user_info->display_name,
							'href'  => $abus_full_menu_url,
						) );
						
					} // 
					
				} // check we have users
				
			} // check we are super admin
			
			
			/* check if there is an old user stored i.e. this logged in user is through switching */
			if( $user_switching->get_old_user() ) {
				
				/* build the switch back url */
				$abus_switch_back_url = $user_switching->switch_back_url( $user_switching->get_old_user() );
				
				/* we are logged in throught swtiching so add admin bar menu to create the switch back link */
				$wp_admin_bar->add_menu( array(
					'id'    => 'switch_back',
					'title' => 'Switch Back',
					'href'   => add_query_arg( array( 'redirect_to' => esc_url( abus_current_url() ) ), $abus_switch_back_url )
				) );
				
			} // end if old user present
			
		} // end if admin bar showing
		
	} // check user switching plugin is active

}

/* hook our customisations of the admin bar function into wordpress */
add_action('wp_before_admin_bar_render', 'abus_user_switching_adminbar', 0);