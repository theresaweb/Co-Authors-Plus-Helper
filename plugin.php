<?php
/*
Plugin Name: Co-Authors Plus Helper
Version: 1.0.0
Description: Co-Authors Plus Helper extends the co-authors plus plugin to handle special cases with author mapping. Read more in Issue at  <a href='https://github.com/Automattic/Co-Authors-Plus/issues/578' target='_blank'>#578</a>.
Author: Theresa Newman
Author URI: ''
Plugin URI: ''
Text Domain: co-authors-plus-helper
*/
/*
Co-Authors Plus Helper is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Co-Authors Plus Helper is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Co-Authors Plus Helper. If not, see {URI to Plugin License}.
*/

class Co_Authors_Plus_Helper extends WP_Base {
	const VERSION      = '1.0.0';

	public function __construct() {
		parent::__construct();
		add_filter( 'wp_insert_post_data', 'handle_user_mapping', 10, 2 );
		function handle_user_mapping($post_data , $original_args){
			//bail if not guest author
			if ( $post_data['post_type'] !== 'guest-author' ) {
				return $post_data;
			}
			// caps check
			if ( ! isset( $_POST['guest-author-nonce'] ) || ! wp_verify_nonce( $_POST['guest-author-nonce'], 'guest-author-nonce' ) ) {
				return $post_data;
			}
			$slug = sanitize_title( get_post_meta( $original_args['ID'],'cap-user_login',true) );
			$user_nicename = str_replace( 'cap-', '', $slug );
			$user = get_user_by( 'slug', $user_nicename );
			
			if ( $user
				&& is_user_member_of_blog( $user->ID, get_current_blog_id() )
				&& $user->user_login != get_post_meta( $original_args['ID'], 'cap-linked_account', true ) ) {
				// if user has selected to link account to matching user we don't have to bail
				if ( $_POST['cap-linked_account'] > 0 && ( (int)$_POST['cap-linked_account'] === (int)$user->ID ) ) {
					// wp_insert_post_data will also run in the plugin so need to update the meta  here so it knows not to wp_die
					update_post_meta( $original_args['ID'], 'cap-linked_account', $slug );
			 		return $post_data;
				}
				// if user has selected to link account NOT matching user, bail with custom message
				if ( $_POST['cap-linked_account'] > 0 && ( (int)$_POST['cap-linked_account'] !== (int)$user->ID ) ) {
					wp_die( esc_html__( 'Please map the guest author to the user with the same username', 'co-authors-plus' ) );
				}
				wp_die( esc_html__( 'There is a WordPress user with the same username as this guest author, please go back and link them in order to update.', 'co-authors-plus' ) );
			} else {
				return $post_data;
			}
		}
	}
}

$ch = Co_Authors_Plus_Helper::get_instance();

