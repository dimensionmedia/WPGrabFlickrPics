<?php
/*
Plugin Name: Grab Flickr Pics
Plugin URI: http://www.davidbisset.com/wp-grab-flickr-pics
Description: This plugin will search through recent Flickr posts (containing a certain hashtag), and import those photos along with metadata into a custom post type.
Version: 0.3
Author: David Bisset
Author URI: http://www.davidbisset.com
Author Email: dbisset@dimensionmedia.com
License:

  Copyright 2013 David Bisset (dbisset@dimensionmedia.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/**
 * This is to be considered a "bare bones" plugin, to be incorporated into other larger plugins and themes.
 *
 * @version	1.0
 */
class WPGrabFlickrPics {

	/*--------------------------------------------*
	 * Attributes
	 *--------------------------------------------*/
	 
	/** Refers to a single instance of this class. */
	private static $instance = null;
	
	/** Refers to the slug of the plugin screen. */
	private $wpgfp_screen_slug = null;
	

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	 
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	WPGrabFlickrPics	A single instance of this class.
	 */
	public function get_instance() {
		return null == self::$instance ? new self : self::$instance;
	} // end get_instance;

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	private function __construct() {

	
		/**
		 * Load needed include files
		 */

		require_once( dirname( __FILE__ ) . '/includes/flickr.php' );

		/**
		 * Define globals
		 */
    		if ( ! defined('WP_GRAB_FLICKR_PICS_PERMISSIONS') ) define("WP_GRAB_FLICKR_PICS_PERMISSIONS", "manage_options");

		/**
		 * Load plugin text domain
		 */
		add_action( 'init', array( $this, 'wpgfp_textdomain' ) );

	    /*
	     * Add the options page and menu item.
	     */
	    add_action( 'admin_menu', array( $this, 'wpgfp_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'wpgfp_admin_init' ) );

	    /*
		 * Register site stylesheets and JavaScript
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'register_wpgfp_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_wpgfp_scripts' ) );

	    /*
		 * Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		 */
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

	    /*
	     * Here's where we define the custom functionality
	     */     
	     
		add_action( "admin_post_grab_flickrs", array ( $this, 'wpgfp_grab_flickr_posts' ) );	
		add_action( "admin_post_wpgfp_clear_settings", array ( $this, 'wpgfp_clear_settings' ) );	
        add_action( "admin_notices", array ( $this, 'render_msg' ) );
        add_action( "init", array ( $this, 'wpgfp_register_cpt' ) );
        add_action( "init", array ( $this, 'wpgfp_register_tax' ) );




	} // end constructor
	
	
	
	
     


	/**
	 * Fired when the plugin is activated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function activate( $network_wide ) {

	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function deactivate( $network_wide ) {

	} // end deactivate

	/**
	 * Loads the plugin text domain for translation
	 */
	public function wpgfp_textdomain() {

		$domain = 'wp-grab-flickr-pics-locale';
		$locale = apply_filters( 'wpgfp_locale', get_locale(), $domain );
		
        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	} // end wpgfp_textdomain

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {

		/*
		 * Check if the plugin has registered a settings page
		 * and if it has, make sure only to enqueue the scripts on the relevant screens
		 */
		
	    if ( isset( $this->wpgfp_screen_slug ) ){
	    	
	    	/*
			 * Check if current screen is the admin page for this plugin
			 * Don't enqueue stylesheet or JavaScript if it's not
			 */
	    
			 $screen = get_current_screen();
			 if ( $screen->id == $this->wpgfp_screen_slug ) {
			 	wp_enqueue_style( 'wpgfp-name-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );
			 } // end if
	    
	    } // end if
	    
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {

		/*
		 * Check if the plugin has registered a settings page
		 * and if it has, make sure only to enqueue the scripts on the relevant screens
		 */
		
	    if ( isset( $this->wpgfp_screen_slug ) ){
	    	
	    	/*
			 * Check if current screen is the admin page for this plugin
			 * Don't enqueue stylesheet or JavaScript if it's not
			 */
	    
			 $screen = get_current_screen();
			 if ( $screen->id == $this->wpgfp_screen_slug ) {
			 	wp_enqueue_script( 'wpgfp-name-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
			 } // end if
	    
	    } // end if

	} // end register_admin_scripts

	/**
	 * Registers and enqueues wpgfp-specific styles.
	 */
	public function register_wpgfp_styles() {
		// wp_enqueue_style( 'wpgfp-name-wpgfp-styles', plugins_url( 'css/display.css', __FILE__ ) );
	} // end register_wpgfp_styles

	/**
	 * Registers and enqueues wpgfp-specific scripts.
	 */
	public function register_wpgfp_scripts() {
		// wp_enqueue_script( 'wpgfp-name-wpgfp-script', plugins_url( 'js/display.js', __FILE__ ), array( 'jquery' ) );
	} // end register_wpgfp_scripts

	/**
	 * Registers the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function wpgfp_admin_menu() {
	    	
	    add_menu_page(
	        __("Grab Flickr Pics : Settings"),
	        __("Grab Flickr Pics"),
	        WP_GRAB_FLICKR_PICS_PERMISSIONS,
	        "wp-grab-flickr-pics",
	        array( $this, 'wpgfp_settings_page' )
	    );
	    add_submenu_page(
	        "wp-grab-flickr-pics",
	        __("Grab Flickr Pics : Settings"),
	        __("Settings"),
	        WP_GRAB_FLICKR_PICS_PERMISSIONS,
	        "wp-grab-flickr-pics",
	        array( $this, 'wpgfp_settings_page' )
	    );
	    add_submenu_page(
	        "wp-grab-flickr-pics",
	        __("Grab Flickr Pics : Grab Flickr Posts"),
	        __("Grab"),
	        WP_GRAB_FLICKR_PICS_PERMISSIONS,
	        "wp-grab-flickr-pics-items",
	        array( $this, 'wpgfp_grab_page' )
	    );
    	
	} // end wpgfp_admin_menu
	
	/**
	 * Renders the settings page for this plugin.
	 *
	 * @since 0.1
	 */
	public function wpgfp_settings_page() {
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
		$redirect = urlencode( $_SERVER['REQUEST_URI'] );
        
		$action_name = 'wpgfp_clear_settings';
		$nonce_name = 'wp-grab-flickr-pics';

		?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e( 'Grab Flickr Pics Settings' ); ?></h2>
			<?php settings_errors(); ?>
	        
	        <?php /* $max_id = esc_attr( get_option( 'wpgfp_flickr_gallery_max_id' ) ); ?>
	        <p>Currently, max_id of flickr is <?php echo $max_id; ?></p> */ ?>
	        
			<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
				<input type="hidden" name="action" value="<?php echo $action_name; ?>">
				<?php wp_nonce_field( $action_name, $nonce_name . '_nonce', FALSE ); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
				<?php do_settings_sections( 'wp-grab-flickr-pics-stats' ); ?>
				<?php submit_button( 'Clear All Stats' ); ?>
			</form>
	        

			<form action="options.php" method="POST">
				<?php settings_fields( 'wpgfp-options-group' ); ?>
				<?php do_settings_sections( 'wp-grab-flickr-pics-options' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Renders the grab page for this plugin.
	 *
	 * @since 0.1
	 */	 
	public function wpgfp_grab_page() {	
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
		$redirect = urlencode( $_SERVER['REQUEST_URI'] );
        
		$action_name = 'grab_flickrs';
		$nonce_name = 'wp-grab-flickr-pics';
	
		?>
		<div class="wrap">
			<?php screen_icon( 'edit-pages' ); ?>
			<h2><?php _e( 'Grap Flickr Posts' ); ?></h2>

			<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
				<input type="hidden" name="action" value="<?php echo $action_name; ?>">
				<?php wp_nonce_field( $action_name, $nonce_name . '_nonce', FALSE ); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
				<?php submit_button( 'Grab Posts' ); ?>
			</form>
		</div>
		<?php
	}


	/**
	 * This inits the sections and fields in the settings screens
	 */
	
	public function wpgfp_admin_init() {
	
	    register_setting( 'wpgfp-stats-group', 'wpgfp-stats' );
	    add_settings_section( 'section-stats', 'Stats', array( $this, 'options_stats_callback' ), 'wp-grab-flickr-pics-stats' );
	    add_settings_field( 'section-stats-last-grab', 'Last Grab', array( $this, 'options_lastgrab_field_callback' ), 'wp-grab-flickr-pics-stats', 'section-stats' );
	    	
	    register_setting( 'wpgfp-options-group', 'wpgfp-hashtag' );
	    register_setting( 'wpgfp-options-group', 'wpgfp-flickr-client-id' );
	    add_settings_section( 'section-options', 'Options', array( $this, 'options_section_callback' ), 'wp-grab-flickr-pics-options' );
	    add_settings_field( 'section-options-hashtag', 'Keyword', array( $this, 'options_hashtag_field_callback' ), 'wp-grab-flickr-pics-options', 'section-options' );
	    add_settings_field( 'section-options-flickr-client-id', 'Flickr Client API Key', array( $this, 'options_flickr_client_id_field_callback' ), 'wp-grab-flickr-pics-options', 'section-options' );

	    
	} // end wpgfp_admin_init

		function options_stats_callback() {
			// nothing to say here, but just in case
		}

		function options_maxid_field_callback() {
		    $setting = esc_attr( get_option( 'wpgfp_flickr_gallery_max_id' ) );
		    if (!$setting) {
				echo '<em>No max_id yet</em>';   
		    } else { 
			    echo $setting;
			}
		}
		
		function options_lastgrab_field_callback() {
		    $setting = esc_attr( get_option( 'wpgfp_flickr_last_grab' ) );
		    if (!$setting) {
				echo '<em>Nothing has been attempted.</em>'; 
		    } else { 
			    echo date('F jS, Y g:ia T', $setting);
			}
		}
			
		function options_section_callback() {
		    echo 'Keywords are usually hashtags. Include the "#". For example: "#wcmia"';
		}
		
		function options_hashtag_field_callback() {
		    $setting = esc_attr( get_option( 'wpgfp-hashtag' ) );
		    echo "<input type='text' name='wpgfp-hashtag' value='$setting' />";
		}
				
		function options_flickr_client_id_field_callback() {
		    $setting = esc_attr( get_option( 'wpgfp-flickr-client-id' ) );
		    echo "<input type='text' name='wpgfp-flickr-client-id' value='$setting' />";
		}
		
	
	
	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/


	 
	 
	/*
	 * Register CTP
	 */
	 
	public function wpgfp_register_cpt() {
		
	    $labels = array( 
	        'name' => _x( 'Flickr Posts', 'faq' ),
	        'singular_name' => _x( 'Flickr Post', 'faq' ),
	        'add_new' => _x( 'Add New', 'faq' ),
	        'add_new_item' => _x( 'Add New Flickr Post', 'faq' ),
	        'edit_item' => _x( 'Edit Flickr Post', 'faq' ),
	        'new_item' => _x( 'New Flickr Post', 'faq' ),
	        'view_item' => _x( 'View Flickr Post', 'faq' ),
	        'search_items' => _x( 'Search Flickr Posts', 'faq' ),
	        'not_found' => _x( 'No Tweets found', 'faq' ),
	        'not_found_in_trash' => _x( 'No Flickr Posts found in Trash', 'faq' ),
	        'parent_item_colon' => _x( 'Parent Flickr Post:', 'faq' ),
	        'menu_name' => _x( 'Flickr Posts', 'faq' ),
	    );
	    
	    //set up the rewrite rules
	    $rewrite = array(
	        'slug' => 'flickr-posts'
	    );
	
	    $args = array( 
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'Stored posts from Flickr.',
	        'supports' => array( 'title', 'page-attributes', 'editor', 'thumbnail' ),        
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'show_in_nav_menus' => false,
	        'publicly_queryable' => true,
	        'exclude_from_search' => true,
	        'has_archive' => false,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => $rewrite,
	        'capability_type' => 'post',
	        'register_meta_box_cb' => array ( $this, 'wpgfp_add_flickr_posts_metabox' )
	    );
	
	    register_post_type( 'wpgfp_flickrs', $args );
    
	}
	
	/*
	 * Add Meta Box For This Post Type
	 */
	
	public function wpgfp_add_flickr_posts_metabox() {
		
		add_meta_box('wpgfp_flickr_post_information', 'Flickr Post Information', array ( $this, 'wpgfp_flickr_posts_meta' ), 'wpgfp_flickrs', 'normal', 'default');
		
	}

	/*
	 * Add Fields For Meta Box
	 */
	
	public function wpgfp_flickr_posts_meta() {
		global $post;
		
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="flickrpostmeta_noncename" id="flickrpostmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		

		
		$wpgfp_fp_flickr_url = get_post_meta($post->ID, 'wpgfp_fp_flickr_url', true);		
		$wpgfp_fp_image_url = get_post_meta($post->ID, 'wpgfp_fp_image_url', true);
		$wpgfp_fp_image_date_taken = get_post_meta($post->ID, 'wpgfp_fp_image_date_taken', true);
		$wpgfp_fp_author_name = get_post_meta($post->ID, 'wpgfp_ip_author_name', true);

				
		// Echo out the fields
		echo '<label>Flickr URL:</label> <input type="text" name="wpgfp_fp_flickr_url" value="' . $wpgfp_fp_flickr_url  . '" class="widefat" />';
		echo '<label>Image URL:</label> <input type="text" name="wpgfp_fp_image_url" value="' . $wpgfp_fp_image_url  . '" class="widefat" />';		
		echo '<label>Date/Time Taken:</label> <input type="text" name="wpgfp_fp_image_date_taken" value="' . $wpgfp_fp_image_date_taken  . '" class="widefat" />';
		echo '<label>Author Name:</label> <input type="text" name="wpgfp_ip_author_name" value="' . $wpgfp_fp_author_name  . '" class="widefat" />';
	}


	
	/*
	 * Saving Metabox Data
	 */
	
	public function wpgfp_save_events_meta($post_id, $post) {
	
		if ( isset( $_POST['tweetmeta_noncename'] ) ) {
		
			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times
					
			if ( !wp_verify_nonce( $_POST['tweetmeta_noncename'], plugin_basename(__FILE__) )) {
				return $post->ID;
			}
		
			// Is the user allowed to edit the post or page?
			
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;
		
			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.
			
			$tweets_meta['wpgfp_ip_image_id'] = $_POST['wpgfp_ip_image_id'];
			$tweets_meta['wpgfp_ip_lat'] = $_POST['wpgfp_ip_lat'];
			$tweets_meta['wpgfp_ip_long'] = $_POST['wpgfp_ip_long'];
			$tweets_meta['wpgfp_ip_url'] = $_POST['wpgfp_ip_url'];
			$tweets_meta['wpgfp_ip_username'] = $_POST['wpgfp_ip_username'];
			$tweets_meta['wpgfp_ip_username_id'] = $_POST['wpgfp_ip_username_id'];
			$tweets_meta['wpgfp_ip_datetime'] = $_POST['wpgfp_ip_datetime'];
			
			// Add values of $events_meta as custom fields
			
			foreach ($tweets_meta as $key => $value) { // Cycle through the $tweets_meta array
			
				if( $post->post_type == 'revision' ) return; // Don't store custom data twice
				
				$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
				
				if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				} else { // If the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);
				}
				
				if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
			}
		
		}
	
	}
	
	
	/*
	 * Register Tax Term
	 *
	 * Note: media_categories places taxonomy for attachments - it was the idea i was running with
	 * but no longer being used primarily anymore
	 *
	 */
	 
	public function wpgfp_register_tax() {
	

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
		    'name' => _x( 'Flickr Types', 'taxonomy general name' ),
		    'singular_name' => _x( 'Flickr Types', 'taxonomy singular name' ),
		    'search_items' =>  __( 'Search Flickr Types' ),
		    'all_items' => __( 'All Flickr Types' ),
		    'parent_item' => __( 'Parent Flickr Type' ),
		    'parent_item_colon' => __( 'Parent Flickr Type:' ),
		    'edit_item' => __( 'Edit Flickr Type' ), 
		    'update_item' => __( 'Update Flickr Type' ),
		    'add_new_item' => __( 'Add New Flickr Type' ),
		    'new_item_name' => __( 'New Flickr Type Name' ),
		    'menu_name' => __( 'Flickr Types' ),
		); 	
		
		register_taxonomy('wpgfp_flickr_types',array('wpgfp_flickrs'), array(
		    'hierarchical' => true,
		    'labels' => $labels,
		    'show_ui' => true,
		    'query_var' => true
		));
		
		// add this tax if it doesn't exist
		
		if ( !taxonomy_exists('wpgfp_media_categories') ) {
	
			register_taxonomy('wpgfp_media_categories', 'attachment', array(
				// Hierarchical taxonomy (like categories)
				'hierarchical' => true,
				// This array of options controls the labels displayed in the WordPress Admin UI
				'labels' => array(
					'name' => _x( 'Media Category', 'taxonomy general name' ),
					'singular_name' => _x( 'Media Category', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Media Categories' ),
					'all_items' => __( 'All Media Categories' ),
					'parent_item' => __( 'Parent Media Category' ),
					'parent_item_colon' => __( 'Parent Media Category:' ),
					'edit_item' => __( 'Edit Media Category' ),
					'update_item' => __( 'Update Media Category' ),
					'add_new_item' => __( 'Add New Media Category' ),
					'new_item_name' => __( 'New Media Category Name' ),
					'menu_name' => __( 'Media Categories' ),
				),
				// Control the slugs used for this taxonomy
				'rewrite' => array(
					'slug' => 'media-categories', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
				),
			));
	
		}

		// add the media category option, if it exits	

		if ( taxonomy_exists('wpgfp_media_categories') ) {
	
			$term = term_exists('Flickr', 'wpgfp_media_categories');
			
			if ($term !== 0 && $term !== null) {
			
				// this exists, do nothing
				
			} else {

				$parent_term_id = 0; // there's no parent (yet)
				
				wp_insert_term(
				  'Flickr', // the term 
				  'wpgfp_media_categories', // the taxonomy
				  array(
				    'description'=> 'Posts from the Flickr social network.',
				    'slug' => 'flickr',
				    'parent'=> $parent_term_id
				  )
				);
				
			} // if term isn't null
			
		} // if tax exists
		
	} // wpgfp_register_tax
	
	
	/*
	 * This handles what happens when the 'clear all settings' button is pushed on the settings page.
	 * This attempts to remove and/or reset values.
	 */
	
	public function wpgfp_clear_settings() {

		// check nonce
        if ( ! wp_verify_nonce( $_POST[ 'wp-grab-flickr-pics' . '_nonce' ], 'wpgfp_clear_settings' ) )
            die( 'Invalid nonce.' . var_export( $_POST, true ) );
            
        // proceed with removing options and data

        	// remove the flickr max_id
        	
        	delete_option( 'wpgfp_flickr_gallery_max_id' );
        	
        	// clear the "last checked date"
        	
        	delete_option( 'wpgfp_flickr_last_grab' );        	
        
       // ok, let's get back to where we were, most likely the settings page
       
		$msg = "settings-reset";       
       
		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );
		
		wp_safe_redirect( $url );
		
		exit;


    } // end wpgfp_clear_settings
    

	
	
	/*
	 * wpgfp_grab_flickr_posts() wraps around wpgfp_do_grab_flickr_posts() and handles security when
	 * the grabbing is called manually via the WordPress backend on the grab page
	 */
	
	public function wpgfp_grab_flickr_posts() {

		// check nonce
        if ( ! wp_verify_nonce( $_POST[ 'wp-grab-flickr-pics' . '_nonce' ], 'grab_flickrs' ) )
            die( 'Invalid nonce.' . var_export( $_POST, true ) );
            
        // since nonce checks out, call the main function
        
       $msg = $this->wpgfp_do_grab_flickr_posts();
       
       $url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );

       wp_safe_redirect( $url );
       exit;

        
    }
    
    
	/*
	 * wpgfp_grab_flickr_posts() is the bulk of the plugin. It interacts with the flickr API to parse through posts via the
	 * hashtag, find images, and save those images (along with metadata) as a WordPress media item
	 *
	 * NOTICE: This is a work in progress with flickr's API. Trying to mediate how to do this better.
	 */   
    
	public function wpgfp_do_grab_flickr_posts() {
           
        // proceeding forward - woot!
        
        // let's grab the hashtag, henceforth known as the "tag"
	    $tag = esc_attr( get_option( 'wpgfp-hashtag' ) );
	    
	    // let's get the client id as well, assigned by flickr developer center
	    $api_key = get_option( 'wpgfp-flickr-client-id' );
	    
	    // setup a few variables and arrays
	    $msg = '';
	    $new_flickrs = array();
	    $image_counter = 0;
	    
	    if ( $tag && $api_key ) { // need a tag to search, and a client id to proceed
	    
		    /* $params = array(
		       'api_key' => $api_key,
		       'format' => Flickr::JSON,
		       'method' => 'flickr.groups.pools.getPhotos',
		       'group_id' => '2182578@N23'       
		    );
		    
		    $flickr_results = Flickr::makeCall($params);
		    
		    $response = json_decode($flickr_results); // we requested json, now decoding */
		    
   		    // let's go grab some flickr posts!
   		    $response = wp_remote_get( 'http://api.flickr.com/services/feeds/photos_public.gne?tags=' . urlencode($tag) . '&format=php_serial', array( 'sslverify' => false ) );

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
							
				$response = unserialize(($response['body']));
								
				// get the stored pub date, if it exists (if not, then it's the first time we've done this)
				
				$last_pub_date = get_option( 'wpgfp_flickr_gallery_max_id', 0 );
				
				//echo "<BR>".$last_pub_date; 
								
				if ( is_array($response) && $response['pub_date'] && $response['pub_date'] > $last_pub_date ) { // the response is good and has a pub_date, AND that date is greater than flickr's pub date
				
					// let's update the "last tried" field so someone knows when we last attempted to look
					update_option( 'wpgfp_flickr_last_grab', time() );

					// this store's flickr response's returned pub_date
					update_option( 'wpgfp_flickr_gallery_max_id', $response['pub_date'] );

					// check and see if there are 'items', and if so, go through them
					if ( !empty($response['items']) ) {
						
						foreach ( $response['items'] as $flickr_item ) {
						
					        $new_flickrs[] = array(
						        "title" => htmlspecialchars($flickr_item['title']),
						        "flickr_url" => htmlspecialchars($flickr_item['url']),
						        "description" => htmlspecialchars($flickr_item['description_raw']),
						        "image_url" => htmlspecialchars($flickr_item['l_url']),
						        "image_date_taken" => htmlspecialchars($flickr_item['date']),
						        "author_name" => htmlspecialchars($flickr_item['author_name'])
					        );
							
							
						} // foreach
						
					} // if not empty
					
					//
					// Ok, now loop through the $new_flickrs array and save them as WP posts
					//
						
					if ( !empty($new_flickrs) ) {
	
						foreach ($new_flickrs as $new_flickr) {
						
							$featured_image_done_yet = false;
						
							// Let's define the post title
							
							$post_title = wp_strip_all_tags($new_flickr['title']);
						
							// Create post object
							$flickr_post = array(
							  'post_title'    	=> $post_title,
							  'post_content'  	=> wp_strip_all_tags ( $new_flickr['description'] ),
							  'post_date'		=> date('Y-m-d H:i:s', $new_flickr['image_date_taken'] ),
							  'post_type'	  	=> 'wpgfp_flickrs',
							  'post_status'   	=> 'publish',
							  'ping_status'	  	=> 'closed'
							);
							
							// Insert the post into the database
							$post_id = wp_insert_post( $flickr_post );
							
							if ( $post_id ) {
							
								// grab image and attach it to the post
								
								$url = $new_flickr['image_url'];
								$tmp = download_url( $url );
								$file_array = array(
								    'name' => basename( $url ),
								    'tmp_name' => $tmp
								);
																		
								// Check for download errors
								if ( is_wp_error( $tmp ) ) {
								    @unlink( $file_array[ 'tmp_name' ] );
									print_r ("error: " . $tmp); die();
								}
								
								$attachment_id = $this->wpgfp_media_handle_sideload( $file_array, $post_id ); // the $post_id makes this attachment associated with the tweet post
								
								// Check for handle sideload errors.
								
								if ( is_wp_error( $attachment_id ) ) {
								
								    @unlink( $file_array['tmp_name'] );
									print_r ("error: " . $attachment_id); die();
								
								} else {
									
									// no errors? Woot.
									
									if ( !$featured_image_done_yet ) { // make the image the featured image, if there isn't one already
										
										set_post_thumbnail( $post_id, $attachment_id );
										$featured_image_done_yet = true;
										
									}
									
								}
								
								// add metadata
	
								if ( $new_flickr['flickr_url'] ) { add_post_meta($post_id, 'wpgfp_fp_flickr_url', $new_flickr['flickr_url'], true); }			
								if ( $new_flickr['image_url'] ) { add_post_meta($post_id, 'wpgfp_fp_image_url', $new_flickr['image_url'], true); }			
								if ( $new_flickr['image_date_taken'] ) { add_post_meta($post_id, 'wpgfp_fp_image_date_taken', $new_flickr['image_date_taken'], true); }
								if ( $new_flickr['author_name'] ) { add_post_meta($post_id, 'wpgfp_ip_author_name', $new_flickr['author_name'], true); }

								// ok, add one to the counter
								
								$image_counter++;
								
							
							} // if _post_id
							
						} // foreach
						
					} // if !empty

					$msg = "$image_counter images pulled from Flickr.";
				
				} // if everything checks out
				
			
			} // 200 == response
		    

		} else { // if we don't have a tag and client id
		
			if ( !$tag ) {
				$msg = "missing-tag";
			} else if ( !$client_id ) {
				$msg = "missing-client-id";
			}
					
		}
		
	return $msg;

	} // end wpgfp_grab_flickr_posts()
	
		
	/**
	 * Render Messages.
	 *
	 * @since 0.1
	 */
    public function render_msg() {
		$text = false;
    
		if ( ! isset( $_GET['msg'] ) )
			return;

		if ( 'settings-reset' === $_GET['msg'] )
			$text = __( 'Settings have been reset.' );

		if ( 'missing-tag' === $_GET['msg'] )
			$text = _( 'A tag/keyword to search for is required.' );

		if ( 'missing-client-id' === $_GET['msg'] )
			$text = __( 'You need a "client api key" provided by Flickr.' );;
                        
		if ( $text )        
			echo '<div class="updated"><p>' . $text . '</p></div>';
    }
	
	
	/*
	 * I had to create my own media handle sideload function because i got a 'white screen' with the official one
	 * with no visible errors that i could see, even in the logs
	 */
	
	public function wpgfp_media_handle_sideload($file_array, $post_id, $desc = null, $post_data = array()) {
	        $overrides = array('test_form'=>false);
	
	        $file = wp_handle_sideload($file_array, $overrides);
	        if ( isset($file['error']) )
	                return new WP_Error( 'upload_error', $file['error'] );
	
	        $url = $file['url'];
	        $type = $file['type'];
	        $file = $file['file'];
	        $title = preg_replace('/\.[^.]+$/', '', basename($file));
	        $content = '';
	        
	        /* 
	
	        // use image exif/iptc data for title and caption defaults if possible
	        if ( $image_meta = @wp_read_image_metadata($file) ) {
	                if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
	                        $title = $image_meta['title'];
	                if ( trim( $image_meta['caption'] ) )
	                        $content = $image_meta['caption'];
	        }
	        
	        */
	
	        if ( isset( $desc ) )
	                $title = $desc;
	
	        // Construct the attachment array
	        $attachment = array_merge( array(
	                'post_mime_type' => $type,
	                'guid' => $url,
	                'post_parent' => $post_id,
	                'post_title' => $title,
	                'post_content' => $content,
	        ), $post_data );
	
	        // This should never be set as it would then overwrite an existing attachment.
	        if ( isset( $attachment['ID'] ) )
	                unset( $attachment['ID'] );
	
	        // Save the attachment metadata
	        $id = wp_insert_attachment($attachment, $file, $post_id);
	        if ( !is_wp_error($id) )
	                wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	
	        return $id;
	}	
} // end class


WPGrabFlickrPics::get_instance();