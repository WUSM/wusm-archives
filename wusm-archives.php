<?php
/*
Plugin Name: WUSM Custom Archives
Plugin URI: https://github.com/wusm/wusm-archives
Description: Custom Archives
Author: Aaron Graham
Version:14.04.30.0
Author URI: 
*/

add_action( 'init', 'github_plugin_updater_wusm_archives_init' );
function github_plugin_updater_wusm_archives_init() {

	if( ! class_exists( 'WP_GitHub_Updater' ) )
		include_once 'updater.php';

	if( ! defined( 'WP_GITHUB_FORCE_UPDATE' ) )
		define( 'WP_GITHUB_FORCE_UPDATE', true );

	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
		$config = array(
				'id' => 0,
				'slug' => plugin_basename( __FILE__ ),
				'plugin' => plugin_basename(__FILE__),
				'proper_folder_name' => 'wusm-archives',
				'api_url' => 'https://api.github.com/repos/wusm/wusm-archives',
				'raw_url' => 'https://raw.github.com/wusm/wusm-archives/master',
				'github_url' => 'https://github.com/wusm/wusm-archives',
				'zip_url' => 'https://github.com/wusm/wusm-archives/archive/master.zip',
				'sslverify' => true,
				'requires' => '3.0',
				'tested' => '3.9',
				'readme' => 'README.md',
				'access_token' => '',
		);

		new WP_GitHub_Updater( $config );
	}
}

class wusm_archives_plugin {
	/**
	 *
	 */
	public function __construct() {
		if( !is_admin() ) { add_action( 'wp_enqueue_scripts', array( $this, 'wusm_custom_archive_style' ) ); }
		/*add_action( 'init', array( $this, 'plugin_function' ) );*/
		add_shortcode( 'wusm_archive', array( $this, 'wusm_custom_archive' ) );
		add_action( 'MY_AJAX_HANDLER_wusm_archive_load_more', array( $this, 'wusm_archive_load_more_callback' ) );
		add_action( 'MY_AJAX_HANDLER_nopriv_wusm_archive_load_more', array( $this, 'wusm_archive_load_more_callback' ) );
	}

	public function wusm_custom_archive_style() {
		wp_register_style( 'plugin-styles', plugins_url('css/style.css', __FILE__) );
		wp_enqueue_style( 'plugin-styles' );
	}

	public function plugin_function( $atts, $content = null ) {
		return false;
	}

	function wusm_custom_archive( $atts ) {
		wp_enqueue_script( 'wusm-archive-scripts', plugins_url('js/script.js', __FILE__), array(), '1.0.0', true );
		extract( shortcode_atts( array(
			'type' => 'post'
		), $atts ) );

		$output = "<div class='$type-custom-archive custom-archive'>";
		$output .= $this->load_wusm_posts( $type,  1 );
		$output .= "</div>";
		$output .= "<p id='load-more' data-type='$type' data-page='2'>Load More<span class='spinner'></span></p>";
		$output .= "<p id='no-more'>No More Items Found</p>";

		return $output;
	}

	function wusm_archive_load_more_callback() {
		echo $this->load_wusm_posts( $_POST['post_type'], $_POST['page'] );
		die();
	}

	private function load_wusm_posts( $type, $page ) {
		$output = "";
		$num_to_fetch = apply_filters( "{$type}_num_per_page", 30);

		if( $page === 1 ) {
			// WP_Query arguments
			$args = array (
				'post_type' 	 => $type,
				'posts_per_page' => $num_to_fetch,
				'paged'     	 => $page,
				'orderby' 	     => 'date',
				'meta_key'       => 'sticky',
				'meta_value'     => 1,
			);

			// The Query
			$query = new WP_Query( $args );
			$ids = $query->posts;
			$num_to_fetch = $num_to_fetch - ( sizeof( $query->posts ) );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$post_id = get_the_ID();
					$output .= $this->print_the_wusm_post($type, $post_id);
				}
			}
			if($output !== "" ) {
				$output .= "<hr>";	
			}
		}

		// WP_Query arguments
		$args = array (
			'post_type' 	 => $type,
			'posts_per_page' => $num_to_fetch,
			'paged'     	 => $page,
			'orderby' 	     => 'date',
			'meta_query'     => array(
				array(
					'key'     => 'sticky',
					'value'   => 1,
					'compare' => '!=',
				),
				array(
					'key'     => 'sticky',
					'compare' => 'NOT EXISTS'
				),
				'relation' => 'OR'
			)
		);

		// The Query
		$query = new WP_Query( $args );
		
		// The Loop
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				$output .= $this->print_the_wusm_post($type, $post_id);
			}
		} else {
			$output = "false";
		}

		// Restore original Post Data
		wp_reset_postdata();
		
		return $output;
	}

	private function print_the_wusm_post($type, $post_id) {
		$output = '';
		add_filter( 'excerpt_more', function() { return ''; } );

		$title_text = apply_filters( "{$type}_title_text", '<h3>' . get_the_title() . '</h3>', $post_id );
		$date_text = apply_filters( "{$type}_date_text", get_the_date("m/d/y"), $post_id );
		$link_text = apply_filters( "{$type}_link_text", $title_text, $post_id );
		$excerpt_text = apply_filters( "{$type}_excerpt_text", get_the_excerpt(), $post_id );
		$thumbnail_size = apply_filters( "{$type}_thumbnail_size", 'post-thumbnail', $post_id );
		$link_field = apply_filters( "{$type}_link_field", '', $post_id );
		
		if( $link_field !== '' ) {
			$link = get_field( $link_field );
			if( is_array( $link ) ) {
				$url = $link['url'];
			} else {
				$url = $link;
			}
		} else {
			$url = get_permalink( $post_id );
		}
		if( $url === 'http://' )
			$url = 'javascript:return false;';
		
		//a hack for now
		if($type != 'in_focus') {
			$output .= "<div class='$type-custom-archive-entry custom-archive-entry clearfix'>";
			$output .= ( $date_text === "" ) ? "" : "<span class='$type-custom-archive-date custom-archive-date'>$date_text</span><br>";
			$output .= ( $link_text === "" ) ? "" : "<span class='$type-custom-archive-link custom-archive-link'><a href='$url'>$link_text</a></span>";
			$output .= ( $excerpt_text === "" ) ? "" : "<span class='$type-custom-archive-excerpt custom-archive-excerpt'>$excerpt_text</span><br>";
			$output .= "<a href='$url'>";
			$output .= get_the_post_thumbnail($post_id, $thumbnail_size, array( 'class'	=> "$type-custom-archive-thumb custom-archive-thumb" ) );
			$output .= "</a>";
			$output .= "</div>";
		} else {
			$output .= "<div class='$type-custom-archive-entry custom-archive-entry clearfix'>";
			$output .= "<div style='float: right; width: 330px; padding: 20px;'>";
			$output .= ( $title_text === "" ) ? "" : "<span class='$type-custom-archive-title custom-archive-title'>$title_text</span>";
			$output .= "<hr>";
			$output .= ( $date_text === "" ) ? "" : "<span class='$type-custom-archive-date custom-archive-date'>$date_text</span><br>";
			$output .= ( $excerpt_text === "" ) ? "" : "<span class='$type-custom-archive-excerpt custom-archive-excerpt'>$excerpt_text</span>";
			$output .= ( $link_text === "" ) ? "" : "<span class='$type-custom-archive-link custom-archive-link'><a href='$url'>$link_text</a></span>";
			$output .= "</div>";
			$output .= "<a href='$url'>";
			$output .= get_the_post_thumbnail($post_id, $thumbnail_size, array( 'class'	=> "$type-custom-archive-thumb custom-archive-thumb" ) );
			$output .= "</a>";
			$output .= "</div>";
		}
		return $output;
	}
}
new wusm_archives_plugin();
