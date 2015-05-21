<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
	
	add_action( 'init', 'comment_functions');


	if ( ! function_exists('comment_functions') ) :

		function comment_functions() {

			// Basics Comments Options/Settings
			$comments_value = get_option( 'joaogrilo_comments', 'Nothing Found' );

			if ( isset( $comments_value['comments-checkbox-1'] ) == 'on' ) {

				add_action('admin_init', 'joaogrilo_disable_comments_post_types_support');

			}

			if ( get_option( 'page_comments' ) || isset( $comments_value['comments-checkbox-2'] ) == 'on' ) {

				add_filter('comments_open', 'joaogrilo_disable_comments_status', 20, 2);
				add_filter('pings_open', 'joaogrilo_disable_comments_status', 20, 2);

			}

			if ( isset( $comments_value['comments-checkbox-3'] ) == 'on' ) {

				add_filter('comments_array', 'joaogrilo_hide_existing_comments', 10, 2);

			}

			if ( isset( $comments_value['comments-checkbox-4'] ) == 'on' ) {

				/**
				 * Remove comments menu item
				 * 
				 * @since JoaoGrilo (1.0)
				 */
				if ( function_exists('remove_menu') ) {
					remove_menu('edit-comments.php');
				}

			}

			if ( isset( $comments_value['comments-checkbox-5'] ) == 'on' ) {

				add_action('admin_init', 'joaogrilo_disable_comments_admin_menu_redirect');

			}

			if ( isset( $comments_value['comments-checkbox-6'] ) == 'on' ) {

				add_action('admin_init', 'joaogrilo_disable_comments_dashboard');

			}

			if ( isset( $comments_value['comments-checkbox-7'] ) == 'on' ) {

				/**
				 * Remove comments links from admin bar
				 * 
				 * @since JoaoGrilo (1.0)
				 */					
				if ( is_admin_bar_showing() ) {
					remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
				}
			}
		}
	endif;

	// *******************************************************************

	/**
	 * Disable support for comments and trackbacks in post types
	 *
	 * @since JoaoGrilo (1.0)
	 */
	function joaogrilo_disable_comments_post_types_support() {
		
		$post_types = get_post_types();

		foreach ($post_types as $post_type) {

			if ( post_type_supports( $post_type, 'comments') ) {

				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');

			}
		}
	}

	/**
	 * Close comments on the front-end
	 * 
	 * @since JoaoGrilo (1.0)
	 */
	function joaogrilo_disable_comments_status() {
		return false;
	}
	
	/**
	 * Hide existing comments from the Front-end
	 * 
	 * @since JoaoGrilo (1.0)
	 */
	function joaogrilo_hide_existing_comments( $comments ) {
		
		$comments = array();

		return $comments;
	}

	/**
	 * Redirect any user trying to access comments page on the admin area
	 * 
	 * @since JoaoGrilo (1.0)
	 */
	function joaogrilo_disable_comments_admin_menu_redirect() {
		
		global $pagenow;

		if ( $pagenow === 'edit-comments.php') {

			wp_redirect( esc_url( admin_url() ) );
			exit;

		}
	}

	/**
	 * Remove comments metabox from dashboard
	 * 
	 * @since JoaoGrilo (1.0)
	 */
	function joaogrilo_disable_comments_dashboard() {
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}