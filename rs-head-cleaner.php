<?php
/*
Plugin Name: RS Head Cleaner Plus
Plugin URI: https://www.redsandmarketing.com/plugins/rs-head-cleaner/
Description: This plugin combines/minifies/caches CSS & JavaScript files, removes junk code from the document HEAD & HTTP headers, moves JavaScript from header to footer, hides the Generator/WordPress Version number, removes version numbers from CSS and JS links, minifies HTML, removes Emoji Detection code bloat, and fixes the "Read more" link so it displays the entire post. The plugin cleans up a number of issues, replacing multiple plugins, improving speed, efficiency, security, SEO, and user experience.
Author: Scott Allen
Version: 1.4.3.8
Author URI: https://www.redsandmarketing.com/
Text Domain: rs-head-cleaner
License: GPLv2
*/

/*  Copyright 2014-2017 Scott Allen ( https://www.redsandmarketing.com/contact/ )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 *	Adds features, cleans up WP code, and eliminates need for multiple plugins:
 *	- Hide WP Generator 				- Security
 *	- Removes CSS/JS Versions 			- Security, Speed, Code Validation - Speed: Allows browser to cache JS and CSS files when they don't have arguments appended to URL
 *	- Adds Defer & Async to JS			- OFF BY DEFAULT - ENABLE USING wp-config.php constants - (Optional) For Speed in page loading - Adds 'defer' and 'async' attributes to all JS except jQuery, Theme, and AMP JS to speed up page loading
 *	- Fixes "More" link					- Fixes "More" link so you see the whole post when you click, not just the part after the "more"
 *	- Removes Open Sans					- OFF BY DEFAULT - ENABLE USING wp-config.php constants - (Optional) Removes the Open Sans from WordPress to speed up your site by removing the call to Google Fonts Library
 *	- Remove CF7 JS/CSS					- Removes Contact Form 7 JS/CSS on pages/post where shortcode isn't used (it only needs to be on pages that actually use it)
 *	- Disable Emoji Detection			- Disables the emoji detection for older browsers and removes significant code bloat from the head and other places. Based on code from "Disable Emojis" plugin. ( https://wordpress.org/plugins/disable-emojis/ )
 *	- Combine, Minify & Cache JS/CSS	- Combine all properly registered/queued JS & CSS into one file, minify, and cache these new single files. Fixes CSS image URL locations too. CSS stays in Header, JS will be moved to footer.
 *	- Minify HTML						- Removes comments from HTML and minifies HTML code
 *	- JavaScript to Footer 				- For Speed in page loading - Part of the Combine, Minify, and Cache
 *	- Head Cleaner						- Removes the following from the head section for SEO, security, and speed: RSD Link, Windows Live Writer Manifest Link, WordPress Shortlinks, and Adjacent Posts links (Prev/Next)
 */


/* PLUGIN - BEGIN */

/* Make sure plugin remains secure if called directly */
if( !defined( 'ABSPATH' ) ) {
	if( !headers_sent() ) { header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden'); }
	die( 'ERROR: This plugin requires WordPress and will not function if called directly.' );
}

define( 'RSHCP_VERSION',				'1.4.3.8'	);
define( 'RSHCP_REQUIRED_WP_VERSION',	'4.0'		);
define( 'RSHCP_REQUIRED_PHP_VERSION',	'5.3'		);
define( 'RSHCP_PHP_TEST_MAX',			'7.1'		); /* MAX PHP VERSION TESTED */

if( !defined( 'RSHCP_DEBUG' ) )					{ define( 'RSHCP_DEBUG', FALSE ); }	/* Do not change value unless developer asks you to - for debugging only. Change in wp-config.php. */

if( !defined( 'RSHCP_PLUGIN_BASENAME' ) ) 		{ define( 'RSHCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); }
if( !defined( 'RSHCP_PLUGIN_FILE_BASENAME' ) )	{ define( 'RSHCP_PLUGIN_FILE_BASENAME', trim( basename( __FILE__ ), '/' ) ); }
if( !defined( 'RSHCP_PLUGIN_NAME' ) ) 			{ define( 'RSHCP_PLUGIN_NAME', trim( dirname( RSHCP_PLUGIN_BASENAME ), '/' ) ); }
if( !defined( 'RSHCP_PLUGIN_PATH' ) ) 			{ define( 'RSHCP_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/' ); }
if( !defined( 'RSHCP_LITE' ) )					{ define( 'RSHCP_LITE', ( FALSE !== strpos( RSHCP_PLUGIN_NAME, '-lite' ) ) ? TRUE : FALSE ); }
if( !defined( 'RSHCP_CLASS' ) )					{ define( 'RSHCP_CLASS', ( TRUE === RSHCP_LITE ) ? 'RS_Head_Cleaner_Lite' : 'RS_Head_Cleaner' ); global $rshcp_class; $rshcp_class = RSHCP_CLASS; }
$rshcp_conflict_check = $rshcp_class::conflict_check();	/* Make sure user hasn't activated both versions of plugin. */
if( TRUE === $rshcp_conflict_check ) { return; }

/* BENCHMARK - BEGIN */
/* $start_time = $rshcp_class::microtime(); */

if( !defined( 'RSHCP_PHP_VERSION' ) ) 			{ define( 'RSHCP_PHP_VERSION', PHP_VERSION ); }
if( !defined( 'RSHCP_WP_VERSION' ) ) 			{ define( 'RSHCP_WP_VERSION', $GLOBALS['wp_version'] ); }
if( !defined( 'RSHCP_SITE_URL' ) ) 				{ define( 'RSHCP_SITE_URL', untrailingslashit( home_url() ) ); }
if( !defined( 'RSHCP_SITE_PATH' ) ) 			{ define( 'RSHCP_SITE_PATH', untrailingslashit( ABSPATH ) ); }
if( !defined( 'RSHCP_SITE_DOMAIN' ) ) 			{ define( 'RSHCP_SITE_DOMAIN', $rshcp_class::get_domain( RSHCP_SITE_URL ) ); }
if( !defined( 'RSHCP_EOL' ) ) 					{ $rshcp_eol	= ( defined( 'PHP_EOL' ) && ( "\n" === PHP_EOL || "\r\n" === PHP_EOL ) ) ? PHP_EOL : $rshcp_class::eol(); define( 'RSHCP_EOL', $rshcp_eol ); }
if( !defined( 'RSHCP_DS' ) ) 					{ $rshcp_ds		= ( defined( 'DIRECTORY_SEPARATOR' ) && ( '/' === DIRECTORY_SEPARATOR || '\\' === DIRECTORY_SEPARATOR ) ) ? DIRECTORY_SEPARATOR : $rshcp_class::ds(); define( 'RSHCP_DS', $rshcp_ds ); }
if( !defined( 'RSHCP_SITE_WP_URL' ) ) 			{ define( 'RSHCP_SITE_WP_URL', untrailingslashit( strtolower( site_url() ) ) ); }
if( !defined( 'RSHCP_SITE_WP_JS_URL' ) ) 		{ define( 'RSHCP_SITE_WP_JS_URL', $rshcp_class::get_schemeless_url( RSHCP_SITE_WP_URL.'/wp-includes/js/' ) ); }
if( !defined( 'RSHCP_SITE_AMP_URL' ) ) 			{ define( 'RSHCP_SITE_AMP_URL', RSHCP_SITE_URL.'/amp/' ); }
if( !defined( 'RSHCP_CACHE_DIR_NAME' ) ) 		{ define( 'RSHCP_CACHE_DIR_NAME', TRUE === RSHCP_LITE ? 'rshcl' : 'rshcp' ); }
if( !defined( 'RSHCP_CACHE_PATH' ) ) 			{ define( 'RSHCP_CACHE_PATH', WP_CONTENT_DIR.'/cache/'.RSHCP_CACHE_DIR_NAME.'/' ); }
if( !defined( 'RSHCP_JS_PATH' ) ) 				{ define( 'RSHCP_JS_PATH', RSHCP_CACHE_PATH.'js/' ); }
if( !defined( 'RSHCP_CSS_PATH' ) ) 				{ define( 'RSHCP_CSS_PATH', RSHCP_CACHE_PATH.'css/' ); }
if( !defined( 'RSHCP_CACHE_URL' ) ) 			{ define( 'RSHCP_CACHE_URL', WP_CONTENT_URL.'/cache/'.RSHCP_CACHE_DIR_NAME.'/' ); }
if( !defined( 'RSHCP_JS_URL' ) ) 				{ define( 'RSHCP_JS_URL', RSHCP_CACHE_URL.'js/' ); }
if( !defined( 'RSHCP_CSS_URL' ) ) 				{ define( 'RSHCP_CSS_URL', RSHCP_CACHE_URL.'css/' ); }
if( !defined( 'RSHCP_CONTENT_DIR_URL' ) ) 		{ define( 'RSHCP_CONTENT_DIR_URL', WP_CONTENT_URL ); }
if( !defined( 'RSHCP_CONTENT_DIR_PATH' ) ) 		{ define( 'RSHCP_CONTENT_DIR_PATH', WP_CONTENT_DIR ); }
if( !defined( 'RSHCP_PLUGINS_DIR_URL' ) ) 		{ define( 'RSHCP_PLUGINS_DIR_URL', WP_PLUGIN_URL ); }
if( !defined( 'RSHCP_PLUGINS_DIR_PATH' ) )		{ define( 'RSHCP_PLUGINS_DIR_PATH', WP_PLUGIN_DIR ); }
if( !defined( 'RSHCP_INCL_DIR_URL' ) ) 			{ define( 'RSHCP_INCL_DIR_URL', RSHCP_SITE_URL.'/'.WPINC.'/' ); }
if( !defined( 'RSHCP_INCL_DIR_PATH' ) )			{ define( 'RSHCP_INCL_DIR_PATH', RSHCP_SITE_PATH.'/'.WPINC.'/' ); }
if( !defined( 'RSHCP_SERVER_ADDR' ) ) 			{ define( 'RSHCP_SERVER_ADDR', $rshcp_class::get_server_addr() ); }
if( !defined( 'RSHCP_SERVER_NAME' ) ) 			{ define( 'RSHCP_SERVER_NAME', $rshcp_class::get_server_name() ); }
if( !defined( 'RSHCP_SERVER_NAME_REV' ) ) 		{ define( 'RSHCP_SERVER_NAME_REV', strrev( RSHCP_SERVER_NAME ) ); }
if( !defined( 'RSHCP_DEBUG_SERVER_NAME' ) ) 	{ define( 'RSHCP_DEBUG_SERVER_NAME', '.redsandmarketing.com' ); }
if( !defined( 'RSHCP_DEBUG_SERVER_NAME_REV' ) )	{ define( 'RSHCP_DEBUG_SERVER_NAME_REV', strrev( RSHCP_DEBUG_SERVER_NAME ) ); }
if( !defined( 'RSHCP_THIS_URL' ) ) 				{ define( 'RSHCP_THIS_URL', $rshcp_class::get_url() ); }
if( !defined( 'RSHCP_THIS_SLUG' ) ) 			{ define( 'RSHCP_THIS_SLUG', $rshcp_class::get_slug() ); }
if( !defined( 'RSHCP_AMP_URL' ) ) 				{ define( 'RSHCP_AMP_URL', $rshcp_class::is_amp_url() ); }
if( !defined( 'RSHCP_RSM_URL' ) ) 				{ define( 'RSHCP_RSM_URL', 'https://www.redsandmarketing.com/' ); }
if( !defined( 'RSHCP_HOME_URL' ) ) 				{ define( 'RSHCP_HOME_URL', RSHCP_RSM_URL.'plugins/'.RSHCP_PLUGIN_NAME.'/' ); }
if( !defined( 'RSHCP_SUPPORT_URL' ) ) 			{ define( 'RSHCP_SUPPORT_URL', RSHCP_RSM_URL.'plugins/wordpress-plugin-support/?plugin='.RSHCP_PLUGIN_NAME ); }
if( !defined( 'RSHCP_WP_URL' ) ) 				{ define( 'RSHCP_WP_URL', 'https://wordpress.org/extend/plugins/'.RSHCP_PLUGIN_NAME.'/' ); }
if( !defined( 'RSHCP_WP_RATING_URL' ) ) 		{ define( 'RSHCP_WP_RATING_URL', 'https://wordpress.org/support/plugin/'.RSHCP_PLUGIN_NAME.'/reviews/#new-post' ); }
if( !defined( 'RSHCP_DONATE_URL' ) ) 			{ define( 'RSHCP_DONATE_URL', RSHCP_RSM_URL.'go/donate/'.RSHCP_PLUGIN_NAME.'/' ); }

if( TRUE !== RSHCP_DEBUG && TRUE !== WP_DEBUG )	{ @ini_set('display_errors',0); @error_reporting(0); } /* Prevents error display, but will display errors if WP_DEBUG turned on. */

unset( $rshcp_conflict_check, $rshcp_eol, $rshcp_ds );

/* SET ADVANCED OPTIONS - Change be overridden in wp-config.php. Advanced users only. */
if( !defined( 'RSHCP_REMOVE_OPEN_SANS' ) ) 		{ define( 'RSHCP_REMOVE_OPEN_SANS', FALSE ); }	/* By default this feature is off, but if you don't need Open Sans and you want a faster Admin, add this line in your wp-config.php: "define( 'RSHCP_REMOVE_OPEN_SANS', TRUE );" */
if( !defined( 'RSHCP_CKF_IMG_DOM' ) ) 			{ define( 'RSHCP_CKF_IMG_DOM',		FALSE ); }	/* CDN/Cookie-free image domain. */
if( !defined( 'RSHCP_NO_MINIFY_ALL' ) ) 		{ define( 'RSHCP_NO_MINIFY_ALL',	FALSE ); }
if( !defined( 'RSHCP_NO_MINIFY_CSS' ) ) 		{ define( 'RSHCP_NO_MINIFY_CSS',	FALSE ); }
if( !defined( 'RSHCP_NO_MINIFY_JS' ) ) 			{ define( 'RSHCP_NO_MINIFY_JS',		FALSE ); }
if( !defined( 'RSHCP_NO_MINIFY_HTML' ) ) 		{ define( 'RSHCP_NO_MINIFY_HTML',	TRUE === RSHCP_LITE ? TRUE	: FALSE	); }	/* PLUS = FALSE	/ LITE = TRUE */
if( !defined( 'RSHCP_NO_MINIFY_HTM2' ) ) 		{ define( 'RSHCP_NO_MINIFY_HTM2',	TRUE === RSHCP_LITE ? TRUE	: FALSE	); }	/* PLUS = FALSE	/ LITE = TRUE */
if( !defined( 'RSHCP_NO_MINLINE_CSS' ) ) 		{ define( 'RSHCP_NO_MINLINE_CSS',	TRUE === RSHCP_LITE ? TRUE	: FALSE	); }	/* PLUS = FALSE	/ LITE = TRUE */
if( !defined( 'RSHCP_NO_MINLINE_JS' ) ) 		{ define( 'RSHCP_NO_MINLINE_JS',	TRUE === RSHCP_LITE ? TRUE	: FALSE	); }	/* PLUS = FALSE	/ LITE = TRUE */
if( !defined( 'RSHCP_JS_DEFER' ) ) 				{ define( 'RSHCP_JS_DEFER',			TRUE === RSHCP_LITE ? FALSE	: FALSE	); }	/* PLUS = FALSE	/ LITE = FALSE */
if( !defined( 'RSHCP_JS_TO_FTR' ) ) 			{ define( 'RSHCP_JS_TO_FTR',		TRUE === RSHCP_LITE ? FALSE	: TRUE	); }	/* PLUS = TRUE	/ LITE = FALSE */
if( !defined( 'RSHCP_DISABLE_EMOJIS' ) ) 		{ define( 'RSHCP_DISABLE_EMOJIS',	TRUE === RSHCP_LITE ? FALSE	: TRUE	); }	/* PLUS = TRUE	/ LITE = FALSE */

add_action( 'plugins_loaded', $rshcp_class.'::plugins_loaded_50', 50 );
add_action( 'init', $rshcp_class.'::setup_tasks', 1 );

/* Admin */
register_activation_hook( __FILE__, $rshcp_class.'::activation' );
add_action( 'admin_init', $rshcp_class.'::hide_cpn_notices', -10 );
add_action( 'admin_init', $rshcp_class.'::check_version', 10 );
add_filter( 'all_plugins', $rshcp_class.'::network_admin_remove_plugin', 10 );
add_filter( 'plugin_row_meta', $rshcp_class.'::meta_links', 10, 2 );
if( class_exists( 'WPEngine_PHPCompat' ) ) {
	add_filter( 'phpcompat_whitelist', $rshcp_class.'::php_compat', 10 );
}
register_deactivation_hook( __FILE__, $rshcp_class.'::deactivation' );


class RS_Head_Cleaner {

	/**
	 *	RS Head Cleaner Class
	 */

	function __construct() {
		/**
		 *	Do nothing...for now
		 */
	}

	static public function setup_tasks() {

		/* Add better login page detection */
		add_action( 'login_init', __CLASS__.'::login_init', 10 );

		/**
		 *	CLEANUP HEADER & HTML CODE
		 *	- Remove RSD Link - If you edit blog through browser, then it is not needed anyway.
		 *	- Remove Windows Live Writer Manifest Link...similar deal
		 *	- Remove WordPress Shortlinks from WP HEAD - WP implements it incorrectly, Bad for SEO, and it adds ugly code
		 *	- Remove WordPress Shortlinks from HTTP Headers
		 *	- Remove REL = PREV/NEXT
		 *		- WP implements this awkwardly - supposed to fix pagination issues but it doesn't implement it quite right
		 *		- Use All in One SEO Pack instead - it handles proper implementation of this well, on paginated pages/posts
		 *	- Remove WP Generator/Version - for security reasons (Data leakage)
		 *	USABILITY
		 *	- Change the "Read more" link so it displays the entire post, not just the part after the "#more"
		 *	SPEED
		 *	- Remove version numbers from CSS and JS in HEAD (Also security - Data leakage)
		 *	- Remove HTML Comments and Minify HTML
		 *	- Where appropriate, add Defer & Async to Scripts
		 *	- Combine all JS and CSS, Minify, Cache and Serve one file. CSS stays in Header, JS will be moved to footer.
		 *	- Remove Open Sans font in admin to Speed Page Loading - Only for Admin area, must enable using wp-config.php setting
		 *	- Remove Contact Form 7 JS/CSS on pages/posts where shortcode isn't used
		 *	MORE INFO
		 *	- https://www.redsandmarketing.com/plugins/rs-head-cleaner/
		 */
		
		add_action( 'init', __CLASS__.'::cache_combine_js_css', 10 );
		add_action( 'init', __CLASS__.'::disable_emojis', 10 );
		add_action( 'admin_init', __CLASS__.'::remove_opensans', 9999 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		remove_action( 'wp_head', 'wp_generator' );
		if( !has_action( 'login_enqueue_scripts', 'wp_print_styles' ) ) {
			add_action( 'login_enqueue_scripts', 'wp_print_styles', 11 );
		}
		add_action( 'get_header', __CLASS__.'::buffer_start', 9999 );
		add_action( 'wp_footer', __CLASS__.'::buffer_end', 9999 );

		if( is_admin() || is_preview() || self::is_customize_preview() ) { return; } /* Only continue if not in Admin area, and not preview or customizer preview */

		add_filter( 'status_header', __CLASS__.'::handle_404', 9999, 4 );
		add_filter( 'style_loader_src', __CLASS__.'::remove_wp_ver_css_js', 9999 );
		add_filter( 'script_loader_src', __CLASS__.'::remove_wp_ver_css_js', 9999 );
		add_filter( 'clean_url', __CLASS__.'::defer_async_js', 9999, 1 );
		add_filter( 'the_content', __CLASS__.'::remove_more', 10 );
		add_filter( 'the_content', __CLASS__.'::html_remove_eol', 9999 );

		/**
		 *	Only continue if not in Admin area (previous), not a preview, not a 404, not a feed, and not a search page
		 *	NOTE: Checks have to be run within following callback functions though, as checks may return FALSE prematurely if run at this point.
		 */

		if( defined( 'WPCF7_VERSION' ) ) {
			add_action( 'wp', __CLASS__.'::remove_cf7_css_js', 10 );
		}
	}

	static public function remove_wp_ver_css_js( $src ) {
		return ( FALSE !== strpos( $src, 'ver=' ) ) ? remove_query_arg( 'ver', $src ) : $src;
	}

	static public function remove_html_comments( $buffer ) {
		$rgx	= '~<!--(.|s)*?-->~';
		$func	= __CLASS__.'::strip_comments';
		$buffer = preg_replace_callback( $rgx, $func, $buffer );
		return self::simple_minifier_html( $buffer );
	}

	static public function strip_comments( $s ) {
		list( $l ) = $s;
		return ( preg_match( "~\!?\[(end)?if~iu", $l ) ) ? $l : '';
	}

	static public function buffer_start() {
		ob_start( __CLASS__.'::remove_html_comments' );
	}

	static public function buffer_end() {
		ob_end_flush();
	}

	static public function simple_minifier_html( $html_to_minify, $filter = TRUE ) {
		global $rshcp_slider_active;
		if( is_array( $html_to_minify ) ) { list( $html_to_minify ) = $html_to_minify; }
		if( empty( $filter ) ) { return $html_to_minify; }
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_HTML ) { return $html_to_minify; }
		$html_buffer = $html_to_minify;
		$cdn = RSHCP_CKF_IMG_DOM;
		/* Images - Set CDN or cookie-free domain */
		if( !empty( $cdn ) && !empty( $html_buffer ) && !is_admin() && !is_preview() || !self::is_customize_preview() ) {
			$rgx_i	= "~['\"]https?\://[a-z0-9\-_\/\.\?\&\=\~\@\%\+\#\:]+\.(gif|jpe?g|png)['\"]~i";
			$func_i	= __CLASS__.'::replace_ckf_img_dom_urls';
			$html_buffer = preg_replace_callback( $rgx_i, $func_i, $html_buffer );
		}
		$html_buffer = preg_replace( "~(\r\n|\f|\v)~u", RSHCP_EOL, $html_buffer );
		$html_buffer = preg_replace( "~\>(".RSHCP_EOL.")+\<~", '><', $html_buffer );
		/* Inline CSS and JS */
		$html_buffer = preg_replace( "~//\ +\<\!\[CDATA\[~", "/* <![CDATA[ */", $html_buffer );
		$html_buffer = preg_replace( "~// +\]\]\>~", "/* ]]> */", $html_buffer );
		$html_buffer = preg_replace( "~[\ \t]+~", ' ', $html_buffer );
		/* Minify Inline CSS */
		if( FALSE === RSHCP_NO_MINLINE_CSS && empty( $rshcp_slider_active ) ) {
			$rgx_c	= "~\<style(?:\s+type\=(?:'|\")text/css(?:'|\")\s*)?\>([^>]+)\</style\>~";
			$func_c	= __CLASS__.'::html_css_min';
			$html_buffer = preg_replace_callback( $rgx_c, $func_c, $html_buffer );
		}
		/* Minify Inline JS */
		if( FALSE === RSHCP_NO_MINLINE_JS && empty( $rshcp_slider_active ) ) {
			$rgx_j	= "~\<script(?:\s+type=(?:'|\")[a-z]+/[a-z]+(?:'|\")\s*)?\>\s*(?:/\*\s+\<\!\[CDATA\[\s\*/|//\s+\<\!\[CDATA\[)?([^>]+)(?:/\*\s+\]\]\>\s+\*/|//\s+\]\]\>)?\s*\</script\>~";
			$func_j	= __CLASS__.'::html_js_min';
			$html_buffer = preg_replace_callback( $rgx_j, $func_j, $html_buffer );
		}
		/* Minify HTML */
		$html_buffer = str_replace( array( '/* <![CDATA[*/', '/*]]> */' ), array( '/* <![CDATA[ */ ', ' /* ]]> */' ), $html_buffer );
		$html_buffer = preg_replace( "~".RSHCP_EOL."\s+".RSHCP_EOL."~", RSHCP_EOL, $html_buffer );
		$html_buffer = preg_replace( "~".RSHCP_EOL."(\t|\ )+~", RSHCP_EOL, $html_buffer );
		$html_buffer = preg_replace( "~(\t|\ )+".RSHCP_EOL."~", RSHCP_EOL, $html_buffer );
		$html_buffer = preg_replace( "~\t+~", "", $html_buffer );
		$html_buffer = preg_replace( "~\ +~", " ", $html_buffer );
		$html_buffer = preg_replace( "~".RSHCP_EOL."{2,}~", RSHCP_EOL, $html_buffer );
		$html_buffer = preg_replace( "~".RSHCP_EOL."+~", "", $html_buffer );
		$html_buffer = str_replace( array( '>'.RSHCP_EOL.'<', '>'."\r\n".'<', '>'."\n".'<', ), '><', $html_buffer );
		/* Replace Remaining JS with CDN JS */

		$orig_js = array( 
			RSHCP_SITE_WP_JS_URL.'jquery/jquery.js', 
			RSHCP_SITE_WP_JS_URL.'jquery/jquery.min.js', 
			RSHCP_SITE_WP_JS_URL.'jquery/jquery-migrate.min.js', 
			RSHCP_SITE_WP_JS_URL.'masonry.min.js', 
			'http://cdnjs.cloudflare.com/', 

			/* RSHCP_SITE_WP_JS_URL.'imagesloaded.min.js', */
		);
		$repl_js = array( 
			'//cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js', 					/* ALT: 'https://code.jquery.com/jquery-1.12.4.min.js', */
			'//cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js', 					/* ALT: 'https://code.jquery.com/jquery-1.12.4.min.js', */
			'//cdnjs.cloudflare.com/ajax/libs/jquery-migrate/1.4.1/jquery-migrate.min.js', 		/* ALT: 'https://code.jquery.com/jquery-migrate-1.4.1.min.js', */
			'//cdnjs.cloudflare.com/ajax/libs/masonry/3.3.2/masonry.pkgd.min.js', 
			'https://cdnjs.cloudflare.com/', 
		);
		$html_buffer = str_replace( $orig_js, $repl_js, $html_buffer );
		/* Add more rules - BEGIN */


		/* Add more rules - END */
		return trim( $html_buffer );
	}

	static public function html_remove_eol( $html_to_filter, $filter = TRUE ) {
		if( is_array( $html_to_filter ) ) { list( $html_to_filter ) = $html_to_filter; }
		if( empty( $filter ) ) { return $html_to_filter; }
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_HTML || TRUE === RSHCP_NO_MINIFY_HTM2 ) { return $html_to_filter; }
		$html_buffer = $html_to_filter;
		$html_buffer = str_replace( array( '>'.RSHCP_EOL.'<', '>'."\r\n".'<', '>'."\n".'<', '>'."\f".'<', '>'."\v".'<', ), '><', $html_buffer );
		if( FALSE !== strpos( $html_to_filter, "\r\n" ) || FALSE !== strpos( $html_to_filter, "\n" ) || FALSE !== strpos( $html_to_filter, "\f" ) || FALSE !== strpos( $html_to_filter, "\v" ) ) {
			$html_buffer = preg_replace( "~\>(\r\n|\n|\f|\v)+\<~u", '><', $html_buffer );
		}
		/* Add more rules - BEGIN */


		/* Add more rules - END */
		return trim( $html_buffer );
	}
	/* CLEANUP HEADER & HTML CODE - END */



	/* IMPROVE USER EXPERIENCE - BEGIN */

	static public function remove_more( $content ) {
		global $id;
		return str_replace( '#more-'.$id.'"', '"', $content );
	}
	/* IMPROVE USER EXPERIENCE - END */



	/* SPEED UP WORDPRESS - BEGIN */

	/**
	 *  As of 1.4.3.6, disabled by default
	 */
	static public function defer_async_js( $url, $context = NULL ) {
		if( FALSE === RSHCP_JS_DEFER || TRUE === RSHCP_AMP_URL || defined( 'AMP__VERSION' ) || defined( 'AMPFORWP_VERSION' ) ) { return $url; }
		if( is_admin() || is_user_logged_in() || is_preview() || self::is_customize_preview() || self::is_404() ) { return $url; }
		$slug			= RSHCP_THIS_SLUG;
		$min_slug		= 'rsm-min-js-'.$slug;
		$min_file_slug	= $min_slug.'.js';
		$js_url			= RSHCP_JS_URL.$min_file_slug;
		$script_src_rev	= self::fix_url( $url, TRUE, TRUE, TRUE );
		if( strpos( $script_src_rev, 'sj.' ) !== 0 ) { return $url; } /* Not JS */
		if( FALSE !== strpos( $url, RSHCP_JS_URL ) ) { return $url; }
		if( FALSE !== strpos( $url, 'jquery.js' ) || FALSE !== strpos( $url, '/jquery' ) || FALSE !== strpos( $url, '/masonry' ) || FALSE !== strpos( $url, '/themes/' ) ) { return $url; } /* Skip jquery and theme related JS */
		if( FALSE !== strpos( $url, 'ampproject.org' ) || FALSE !== strpos( $url, '//cdn.' ) ) { return $url; } /* AMP JS or CDN JS */
		if( FALSE !== strpos( $url, 'slider' ) ) { global $rshcp_slider_active; $rshcp_slider_active = TRUE; return $url; } /* Skip sliders */
		if( FALSE !== strpos( $url, 'bootstrap' ) || FALSE !== strpos( $url, 'modernizr' ) || FALSE !== strpos( $url, 'slides' ) ) { return $url; }
		return $url.'\' async defer ';
	}

	static public function remove_opensans() {
		if( TRUE === RSHCP_REMOVE_OPEN_SANS && is_admin() && wp_style_is( 'open-sans', 'enqueued' ) ) {
			wp_deregister_style( 'open-sans' );
			wp_register_style( 'open-sans', FALSE );
			wp_enqueue_style( 'open-sans', '' );
		}
	}

	static public function remove_cf7_css_js() {
		global $wp_query, $post; 
		if( ( !empty( $wp_query ) && ( self::is_404() || is_feed() || is_search() ) ) || is_preview() || self::is_customize_preview() ) { return; }
		if( is_object( $post ) ) {
			if( !has_shortcode( $post->post_content, 'contact-form-7' ) ) {
				remove_action( 'wp_enqueue_scripts', 'wpcf7_enqueue_styles' );
				remove_action( 'wp_enqueue_scripts', 'wpcf7_enqueue_scripts' );
			}
		}
	}

	static public function disable_emojis() {
		if( TRUE === RSHCP_DISABLE_EMOJIS ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}
	}

	static public function remove_tinymce_emojis( $plugins ) {
		if ( is_array( $plugins ) ) { return array_diff( $plugins, array( 'wpemoji' ) ); } else { return array(); }
	}

	static public function html_css_min( $css_str ) {
		/* For inline CSS */
		if( is_array( $css_str ) ) { list( $css_str ) = $css_str; }
		return self::simple_minifier_css( $css_str, TRUE, TRUE, TRUE );
	}

	static public function html_js_min( $js_str ) {
		/* For inline JS */
		if( is_array( $js_str ) ) { list( $js_str ) = $js_str; }
		return self::simple_minifier_js( $js_str, TRUE, TRUE, TRUE );
	}

	static public function simple_minifier_css( $css_to_minify, $filter = TRUE, $remcom = TRUE, $inline = FALSE ) {
		if( empty( $filter ) ) { return $css_to_minify; }
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_CSS || is_admin() || is_preview() || self::is_customize_preview() ) { return $css_to_minify; }
		$css_buffer 	= $css_to_minify;
		/* Replace all newlines with \n ( RSHCP_EOL ) */
		$css_buffer 	= str_replace( array( "\r\n","\r","\n","\f","\v" ), array( RSHCP_EOL,RSHCP_EOL,RSHCP_EOL,RSHCP_EOL,RSHCP_EOL ), $css_buffer );
		/* Remove comments */
		$css_buffer 	= preg_replace( "~(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)~", '', $css_buffer );
		$css_buffer 	= preg_replace( "~(?:(?![\/a-zA-Z0-9]+)[".RSHCP_EOL."\t\ ]*\/\/.*".RSHCP_EOL.")~", RSHCP_EOL, $css_buffer );
		$css_buffer 	= preg_replace( "~(?:(?![\/a-zA-Z0-9]+)([;\{\}]*)\/\/.*".RSHCP_EOL.")~", "$1", $css_buffer );
		/* Trim lines */
		$css_buffer 	= preg_replace( "~(?:\ *".RSHCP_EOL."\ *)~", RSHCP_EOL, $css_buffer );
		/* Remove tabs, spaces, etc. */
		$css_buffer 	= str_replace( array( "\t",'  ','   ','    ','     ' ), '', $css_buffer );
		/* Remove spaces after {},;: */
		$css_buffer 	= str_replace( array( '{ ',' }',' {','} ',', ','; ',' : ',': ' ), array( '{','}','{','}',',',';',':',':' ), $css_buffer );
		/* Remove tabs, spaces, newlines, etc. */
		$css_buffer 	= preg_replace( "~".RSHCP_EOL."{2,}~", RSHCP_EOL, $css_buffer );
		$css_buffer 	= str_replace( array( "{".RSHCP_EOL,RSHCP_EOL."}",RSHCP_EOL."{","}".RSHCP_EOL,",".RSHCP_EOL,";".RSHCP_EOL ), array( '{','}','{','}',',',';' ), $css_buffer );
		/* Add more rules - BEGIN */
		$css_buffer 	= preg_replace( "~\s+,~", ",", $css_buffer );

		/* Add more rules - END */
		return trim( $css_buffer );
	}

	static public function simple_minifier_js( $js_to_minify, $filter = TRUE, $remcom = TRUE, $inline = FALSE ) {
		/* $remcom is Remove Comments */
		if( empty( $filter ) ) { return $js_to_minify; }
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_JS ) { return $js_to_minify; }
		$js_buffer		= $js_to_minify;
		/**
		 *	These aren't all done at once because order of steps is important
		 *	Replace all newlines with \n ( RSHCP_EOL )
		 */
		$js_buffer 		= str_replace( array( "\r\n","\r","\n","\f","\v" ), array( RSHCP_EOL,RSHCP_EOL,RSHCP_EOL,RSHCP_EOL,RSHCP_EOL ), $js_buffer );
		if( TRUE === $remcom ) {
			/* Remove comments */
			$js_buffer 	= preg_replace( "~(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)~", '', $js_buffer );
			$js_buffer 	= preg_replace( "~(?:(?![\/a-zA-Z0-9]+)[".RSHCP_EOL."\t\ ]*\/\/.*".RSHCP_EOL.")~", RSHCP_EOL, $js_buffer );
			$js_buffer 	= preg_replace( "~(?:(?![\/a-zA-Z0-9]+)([;\{\}]*)\/\/.*".RSHCP_EOL.")~", "$1", $js_buffer );
		}
		/* Trim lines */
		$js_buffer 		= preg_replace( "~(?:\ *".RSHCP_EOL."\ *)~", RSHCP_EOL, $js_buffer );
		$js_buffer 		= str_replace( array( " \\\n", " \\ \n" ), array( '', '' ), $js_buffer );
		/* Remove spaces around JS operators: - + * ? % || && = == != < > <= >= */
		$js_buffer 		= str_replace( array(' - ',' + ',' * ',' / ',' ? ',' % ',' || ',' && ',' = ',' != ',' == ',' === ',' < ',' > ',' <= ',' >= ' ), array( '-','+','*','/','?','%','||','&&','=','!=','==','===','<','>','<=','>=' ), $js_buffer );
		/* Remove tabs, spaces, etc. */
		$js_buffer 		= str_replace( array( "\t",'  ','   ','    ','     ' ), '', $js_buffer );
		/* Remove spaces after {}[](),;: */
		$js_buffer 		= str_replace( array( '{ ',' }',' {','} ','[ ',' ]','( ',' )',' (',') ',', ','; ',' : ',': ' ), array( '{','}','{','}','[',']','(',')','(',')',',',';',':',':' ), $js_buffer );
		/* Remove tabs, spaces, newlines, etc. */
		$js_buffer 		= str_replace( array( " \\\n", " \\ \n" ), array( '', '' ), $js_buffer );
		$js_buffer 		= preg_replace( "~".RSHCP_EOL."{2,}~", RSHCP_EOL, $js_buffer );
		$js_buffer 		= str_replace( array( "{".RSHCP_EOL,RSHCP_EOL."}","[".RSHCP_EOL,RSHCP_EOL."]" ), array( '{','}','[',']' ), $js_buffer );
		$js_buffer 		= str_replace( array( ",".RSHCP_EOL,":".RSHCP_EOL,";".RSHCP_EOL,"&".RSHCP_EOL,"=".RSHCP_EOL,"+".RSHCP_EOL,"-".RSHCP_EOL,"?".RSHCP_EOL,"}\\".RSHCP_EOL," } \\ ".RSHCP_EOL ), array( ',',':',';','&','=','+','-','?','}','}' ), $js_buffer );
		/* Add more rules - BEGIN */


		/* Add more rules - END */
		return trim( $js_buffer );
		}

	static public function get_slug() {
		/**
		 *	@Used by: constant 'RSHCP_THIS_SLUG'
		 */
		return self::md5( RSHCP_THIS_URL );
	}

	static public function cache_combine_js_css() {
		if( !self::is_404() && !is_admin() && !is_user_logged_in() ) {
			foreach ( array( 'wp_enqueue_scripts', 'login_enqueue_scripts' ) as $a ) { foreach ( array( -999 => 'styles', 9999 => 'scripts' ) as $p => $b ) { add_action( $a, __CLASS__.'::enqueue_'.$b, $p ); } }
			add_action( 'wp_print_styles', __CLASS__.'::inspect_styles', 9999 );
			foreach ( array( 'wp_print_scripts', 'wp_print_head_scripts' ) as $a ) { add_action( $a, __CLASS__.'::inspect_scripts', 9999 ); }
			foreach ( array( 'wp_head', 'login_head' ) as $a ) { add_action( $a, __CLASS__.'::insert_head_js', 10 ); }
			if( TRUE === RSHCP_JS_TO_FTR ) {
				foreach ( array( 'wp_footer', 'login_footer' ) as $a ) { add_action( $a, __CLASS__.'::insert_footer_js', 100 ); }
			}
		}
	}

	static public function enqueue_styles() {
		global $rshcp_css_null,$wp_styles;
		if( !empty( $rshcp_css_null ) ) { return; }
		$slug			= RSHCP_THIS_SLUG;
		$min_slug		= 'rsm-min-css-'.$slug;
		$min_file_slug	= $min_slug.'.css';
		$css_url		= RSHCP_CSS_URL.$min_file_slug;
		$deps			= array();
		if( is_object( $wp_styles ) ) {
			foreach( $wp_styles->queue as $handle ) {
				$style_deps = (array)$wp_styles->registered[$handle]->deps;
				/* Keep an eye out for potential issues */
				if( !empty( $style_deps ) ) { $deps = array_merge( $deps, $style_deps ); }
			}
			$deps = self::sort_unique( $deps );
			foreach( $wp_styles->queue as $handle ) {
				if( self::in_array( $handle, $deps ) ) {
					$key = array_search( $handle, $deps );
					unset( $deps[$key] );
				}
			}
		}
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_CSS ) { $css_url = str_replace( '-min-', '-raw-', $css_url ); }
		wp_register_style( $min_slug, $css_url, $deps, RSHCP_VERSION );
		wp_enqueue_style( $min_slug );
	}

	static public function enqueue_scripts() {
		global $rshcp_js_null,$wp_scripts;
		if( TRUE === $rshcp_js_null ) { return; }
		$slug			= RSHCP_THIS_SLUG;
		$min_slug		= 'rsm-min-js-'.$slug;
		$min_file_slug	= $min_slug.'.js';
		$js_url			= RSHCP_JS_URL.$min_file_slug;
		$deps			= array();
		if( is_object( $wp_scripts ) ) {
			foreach( $wp_scripts->queue as $handle ) {
				$script_deps = (array)$wp_scripts->registered[$handle]->deps;
				/* Keep an eye out for potential issues */
				if( !empty( $script_deps ) ) { $deps = array_merge( $deps, $script_deps ); }
			}
			$deps = self::sort_unique( $deps );
			foreach( $wp_scripts->queue as $handle ) {
				if( self::in_array( $handle, $deps ) ) {
					$key = array_search( $handle, $deps );
					unset( $deps[$key] );
				}
			}
		}
		if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_JS ) { $js_url = str_replace( '-min-', '-raw-', $js_url ); }
		$js_url = NULL; /* Since v 1.4.2.2 */
		wp_register_script( $min_slug, $js_url, $deps, RSHCP_VERSION, RSHCP_JS_TO_FTR );
		wp_enqueue_script( $min_slug );
	}

	static public function insert_head_js() {
		global $rshcp_js_h_null;
		if( TRUE === $rshcp_js_h_null && TRUE === RSHCP_JS_TO_FTR ) { return; }
		if( !self::is_404() && !is_admin() && !is_user_logged_in() ) {
			$slug			= RSHCP_THIS_SLUG;
			$h				= TRUE === RSHCP_JS_TO_FTR ? 'h' : '';
			$min_slug 		= 'rsm-min-js-'.$h.$slug;
			$min_file_slug	= $min_slug.'.js';
			$js_h_url		= RSHCP_JS_URL.$min_file_slug;
			if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_JS ) { $js_h_url = str_replace( '-min-', '-raw-', $js_h_url ); }
			echo RSHCP_EOL."<script type='text/javascript' src='".$js_h_url."'></script>".RSHCP_EOL;
		}
	}

	static public function insert_footer_js() {
		global $rshcp_js_null;
		if( TRUE === $rshcp_js_null ) { return; }
		if( !self::is_404() && !is_admin() && !is_user_logged_in() ) {
			$slug			= RSHCP_THIS_SLUG;
			$min_slug		= 'rsm-min-js-'.$slug;
			$min_file_slug	= $min_slug.'.js';
			$js_f_url		= RSHCP_JS_URL.$min_file_slug;
			if( TRUE === RSHCP_NO_MINIFY_ALL || TRUE === RSHCP_NO_MINIFY_JS ) { $js_f_url = str_replace( '-min-', '-raw-', $js_f_url ); }
			echo RSHCP_EOL."<script type='text/javascript' src='".$js_f_url."'></script>".RSHCP_EOL;
		}
	}

	static public function inspect_scripts( $head_scripts = NULL ) {
		global $rshcp_js_run,$wp_scripts,$rshcp_js_h_null,$rshcp_js_null,$rshcp_vfile,$vfile_set;
		if( !empty( $rshcp_js_run ) ) { return; }
		$slug	= RSHCP_THIS_SLUG;
		$url 	= RSHCP_THIS_URL;
		$domain	= self::get_domain( $url );
		$h		= !empty( $head_scripts ) ? 'h' : '';
		$raw_slug = 'rsm-raw-js-'.$h.$slug;
		$min_slug = 'rsm-min-js-'.$h.$slug;
		$raw_file_slug = $raw_slug.'.js';
		$min_file_slug = $min_slug.'.js';
		$raw_js_file = RSHCP_JS_PATH.$raw_file_slug;
		$min_js_file = RSHCP_JS_PATH.$min_file_slug;
		$script_handles = array();
		$script_srcs 	= array();
		/* $deps 		= array(); // TO DO */
		$combined_js 	= array();
		$http_proto		= self::is_https() ? 'https://' : 'http://';
		if( empty( $head_scripts ) && defined( 'WPCF7_VERSION' ) && wp_script_is( 'jquery-form', 'enqueued' ) ) {
			$handle				= 'jquery-form';
			$script_src			= $script_src_path = WPCF7_PLUGIN_URL.'/includes/js/jquery.form.min.js';
			$script_domain		= $domain;
			$script_src_rev		= self::fix_url( $script_src, TRUE, TRUE, TRUE );
			if( strpos( $script_src_path, '//' ) === 0 ) { $script_src_path = str_replace( '//', $http_proto, $script_src_path ); }
			$script_src_path	= str_replace( array( RSHCP_CONTENT_DIR_URL, RSHCP_INCL_DIR_URL, RSHCP_SITE_URL ), array( RSHCP_CONTENT_DIR_PATH, RSHCP_INCL_DIR_PATH, RSHCP_SITE_PATH ), $script_src_path );
			$js_buffer			= file_get_contents( $script_src_path );
			if( !empty( $js_buffer ) && function_exists( 'wpcf7_plugin_url' ) ) {
				$c = '';
				$_wpcf7 = array( 'loaderUrl' => wpcf7_plugin_url( 'images/ajax-loader.gif' ), 'sending' => __( 'Sending ...', 'contact-form-7' ) ); 
				if( defined( 'WP_CACHE' ) && WP_CACHE ) { $_wpcf7['cached'] = 1; $c = ',"cached":"1"'; }
				if( function_exists( 'wpcf7_support_html5_fallback' ) && wpcf7_support_html5_fallback() ) { $_wpcf7['jqueryUi'] = 1; }
				$js_buffer			.= RSHCP_EOL.'var _wpcf7 = {"loaderUrl":"'.str_replace( '/', '\/', WPCF7_PLUGIN_URL ).'\/images\/ajax-loader.gif","sending":"'.__( 'Sending ...', 'contact-form-7' ).'"'.$c.'};'.RSHCP_EOL;
				$script_handles[]	 = $handle;
				$script_srcs[]		 = $script_src;
				$combined_js[] 		 = $js_buffer;
			}
			unset ( $js_buffer );
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
			wp_register_script( $handle, NULL, FALSE, NULL, TRUE);
			wp_enqueue_script( $handle );
		}
		if( !empty( $head_scripts ) ) {
			foreach( $head_scripts as $handle => $a ) {
				$script_src			= $script_src_path = $a['src'];
				$script_domain		= self::get_domain( $script_src );
				if( empty( $script_src ) || $handle == $min_slug || $script_domain != $domain ) { continue; }
				$script_src_rev		= self::fix_url( $script_src, TRUE, TRUE, TRUE );
				if( strpos( $script_src_rev, 'sj.' ) !== 0 ) { continue; } /* Not JS */
				if( strpos( $script_src_path, '//' ) === 0 ) { $script_src_path = str_replace( '//', $http_proto, $script_src_path ); }
				$script_src_path	= str_replace( array( RSHCP_CONTENT_DIR_URL, RSHCP_INCL_DIR_URL, RSHCP_SITE_URL ), array( RSHCP_CONTENT_DIR_PATH, RSHCP_INCL_DIR_PATH, RSHCP_SITE_PATH ), $script_src_path );
				$script_src_path_ex	= str_replace( RSHCP_CONTENT_DIR_PATH, '', $script_src_path );
				if( FALSE !== strpos( $script_src_path_ex, 'slider' ) ) { global $rshcp_slider_active; $rshcp_slider_active = TRUE; continue; } /* Skip sliders */
				if( FALSE !== strpos( $script_src_path_ex, 'bootstrap' ) || FALSE !== strpos( $script_src_path_ex, 'modernizr' ) || FALSE !== strpos( $script_src_path_ex, 'slides' ) ) { continue; }
				$js_buffer			= file_get_contents( $script_src_path );
				if( empty( $js_buffer ) ) { continue; }
				$script_handles[] 	= $handle;
				$script_srcs[] 		= $script_src;
				$combined_js[] 		= $js_buffer;
				unset ( $js_buffer );
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
			if( !empty( $combined_js ) ) {
				$rshcp_js_h_null = FALSE;
				unset( $head_scripts );
				remove_action( 'rshcp_inspect_scripts_head', __CLASS__.'::inspect_scripts' );
			}
		} elseif( !empty( $wp_scripts ) && is_object( $wp_scripts ) ) {
			$rshcp_js_h_null = TRUE;
			$head_scripts = array();
			foreach( $wp_scripts->queue as $handle ) {
				$script_src			= $script_src_path = $wp_scripts->registered[$handle]->src;
				/* $script_deps 	= (array)$wp_scripts->registered[$handle]->deps; // TO DO */
				$script_domain		= self::get_domain( $script_src );
				if( empty( $script_src ) || $handle == $min_slug || $script_domain != $domain ) { continue; }
				$script_src_rev		= self::fix_url( $script_src, TRUE, TRUE, TRUE );
				if( strpos( $script_src_rev, 'sj.' ) !== 0 ) { continue; } /* Not JS */
				if( strpos( $script_src_path, '//' ) === 0 ) { $script_src_path = str_replace( '//', $http_proto, $script_src_path ); }
				$script_src_path	= str_replace( array( RSHCP_CONTENT_DIR_URL, RSHCP_INCL_DIR_URL, RSHCP_SITE_URL ), array( RSHCP_CONTENT_DIR_PATH, RSHCP_INCL_DIR_PATH, RSHCP_SITE_PATH ), $script_src_path );
				$script_src_path_ex	= str_replace( RSHCP_CONTENT_DIR_PATH, '', $script_src_path );
				if( FALSE !== strpos( $script_src_path_ex, 'slider' ) ) { global $rshcp_slider_active; $rshcp_slider_active = TRUE; continue; } /* Skip sliders */
				if( FALSE !== strpos( $script_src_path_ex, 'bootstrap' ) || FALSE !== strpos( $script_src_path_ex, 'modernizr' ) || FALSE !== strpos( $script_src_path_ex, 'slides' ) ) { continue; }
				$js_buffer			= file_get_contents( $script_src_path );
				if( empty( $js_buffer ) ) { continue; }
				/**
				 *	Helps with compatibility when async and defer are being used
				 *	$js_buffer			= str_replace( array( '(function($)', '( function( $ )', 'jQuery(document).readyjQuery(document).ready(function($)' ), 'jQuery(document).ready(function($)', $js_buffer );
				 *	$js_buffer			= str_replace( array( '(jQuery)', '( jQuery )', '(jquery)', '( jquery )' ), '', $js_buffer );
				 */
				$script_handles[] 	= $handle;
				$script_srcs[] 		= $script_src;
				$combined_js[] 		= $js_buffer;
				unset ( $js_buffer );
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
			if( !self::is_login_page() && wp_script_is( 'jquery', 'enqueued' ) ) {
				$hs_rev = array_reverse( $head_scripts );
				$hs_rev['jquery-migrate'] = array( 'handle' => 'jquery-migrate', 'src' => RSHCP_SITE_URL.'/wp-includes/js/jquery/jquery-migrate.min.js' );
				$hs_rev['jquery-core'] = array( 'handle' => 'jquery-core', 'src' => RSHCP_SITE_URL.'/wp-includes/js/jquery/jquery.js' );
				$head_scripts = array_reverse( $hs_rev );
				wp_dequeue_script( 'jquery' );
				wp_deregister_script( 'jquery' );
				wp_register_script( 'jquery', NULL, FALSE, NULL, TRUE);
				wp_enqueue_script( 'jquery' );
			}
			if( !empty( $head_scripts ) ) { add_action( 'rshcp_inspect_scripts_head', __CLASS__.'::inspect_scripts', 1 ); }
		} else { $rshcp_js_null = TRUE; return; }
		$combined_js_contents_raw	= implode( RSHCP_EOL, $combined_js );
		if( FALSE === RSHCP_JS_TO_FTR && !empty( $rshcp_vfile ) ) {
			$combined_js_contents_raw = $combined_js_contents_raw.RSHCP_EOL.$rshcp_vfile;
			unset( $rshcp_vfile ); $vfile_set = TRUE;
			if( 47 == self::strlen( basename( $raw_js_file ) ) ) {
				$raw_js_file = str_replace( '/rsm-raw-js-h', '/rsm-raw-js-', $raw_js_file );
				$min_js_file = str_replace( '/rsm-min-js-h', '/rsm-min-js-', $min_js_file );
			}
		}
		$combined_js_contents_len	= self::strlen( $combined_js_contents_raw );
		$combined_js_contents		= self::simple_minifier_js( $combined_js_contents_raw );
		$plugin_file_mod_time		= filemtime( __FILE__ );
		if( file_exists( $raw_js_file ) ) {
			$raw_js_file_mod_time	= filemtime( $raw_js_file );
			$raw_js_file_filesize	= filesize( $raw_js_file );
		} else {
			$raw_js_file_mod_time	= $raw_js_file_filesize = FALSE;
		}
		$js_cache_time = time() - 86400; /* 60 * 60 * 1 - Sec * Min * Hour; 3600 = 1 Hour; 86400 = 24 Hours; */
		if( !empty( $combined_js_contents_len ) ) {
			if( $raw_js_file_filesize != $combined_js_contents_len || $raw_js_file_mod_time < $plugin_file_mod_time || $raw_js_file_mod_time < $js_cache_time || FALSE === $raw_js_file_mod_time ) {
				self::write_cache_file( $raw_js_file, $combined_js_contents_raw );
				self::write_cache_file( $min_js_file, $combined_js_contents );
			}
		} else { $rshcp_js_null = TRUE; }
		if( !empty( $head_scripts ) ) { do_action( 'rshcp_inspect_scripts_head', $head_scripts ); }
		$rshcp_js_run = TRUE;
	}

	static public function inspect_styles() {
		global $rshcp_css_run,$domain,$wp_styles,$new_url_base,$url_buffer,$rshcp_css_null;
		if( !empty( $rshcp_css_run ) ) { return; }
		$slug			= RSHCP_THIS_SLUG;
		$url			= RSHCP_THIS_URL;
		$domain			= self::get_domain( $url );
		$raw_slug		= 'rsm-raw-css-'.$slug;
		$min_slug		= 'rsm-min-css-'.$slug;
		$raw_file_slug	= $raw_slug.'.css';
		$min_file_slug	= $min_slug.'.css';
		$raw_css_file	= RSHCP_CSS_PATH.$raw_file_slug;
		$min_css_file	= RSHCP_CSS_PATH.$min_file_slug;
		$style_handles 	= $style_srcs = $combined_css = array(); /* $deps = array(); // TO DO */
		$http_proto		= self::is_https() ? 'https://' : 'http://';
		if( is_object( $wp_styles ) ) {
			foreach( $wp_styles->queue as $handle ) {
				$style_src			= $style_src_path = $wp_styles->registered[$handle]->src;
				/* $style_deps 		= (array)$wp_styles->registered[$handle]->deps; // TO DO */
				$style_domain		= self::get_domain( $style_src );
				if( empty( $style_src ) || $handle == $min_slug || $style_domain != $domain ) { continue; }
				$style_src_rev		= self::fix_url( $style_src, TRUE, TRUE, TRUE );
				if( strpos( $style_src_rev, 'ssc.' ) !== 0 ) { continue; } /* Not CSS */
				$handle_rgx			= preg_quote( $handle );
				if( strpos( $style_src_path, '//' ) === 0 ) { $style_src_path = str_replace( '//', $http_proto, $style_src_path ); }
				$style_src_path		= str_replace( array( RSHCP_CONTENT_DIR_URL, RSHCP_INCL_DIR_URL, RSHCP_SITE_URL ), array( RSHCP_CONTENT_DIR_PATH, RSHCP_INCL_DIR_PATH, RSHCP_SITE_PATH ), $style_src_path );
				$css_buffer 		= file_get_contents( $style_src_path );
				if( empty( $css_buffer ) ) { continue; }
				$style_handles[] 	= $handle;
				$style_srcs[] 		= $style_src;
				$style_src_no_http	= str_replace( array( 'https://', 'http://' ), '', $style_src );
				$url_buffer 		= explode( '/', $style_src_no_http );
				$url_elements		= count( $url_buffer ) - 1;
				unset( $url_buffer[$url_elements] );
				--$url_elements;
				if( preg_match_all( "~(url\(['\"]?(?:\.?/)?([a-z0-9/\-_]+\.[a-z]{2,4}([#\?&][a-z0-9#\=\-_\.]+)*?)['\"]?\))~i", $css_buffer, $matches ) ) {
					$new_url_base	= implode( '/', $url_buffer );
					$css_buffer		= preg_replace_callback( "~url\(['\"]?\.?/?([a-z0-9/\-_]+\.[a-z]{2,4}([#\?&][a-z0-9#\=\-_\.]+)*?)['\"]?\)~i", __CLASS__.'::replace_css_urls', $css_buffer );
				}
				if( preg_match_all( "~(url\(['\"]?(?:\.\./)+(?:[a-z0-9/\-_]+\.[a-z]{2,4}([#\?&][a-z0-9#\=\-_\.]+)*?)['\"]?\))~i", $css_buffer, $matches ) ) {
					foreach( $matches[1] as $m => $match ) {
						$url_buffer_m		= $url_buffer;
						/* Number of directories down */
						$num_dirs_down		= substr_count( $match, '../' );
						/* URL Elements Reduced */
						$url_elements_red	= $url_elements - $num_dirs_down;
						/* Removing last element(s) of array is how we go down one or more directories */
						$i = $url_elements;
						while( $i > $url_elements_red ) { unset( $url_buffer_m[$i] ); $i--; }
						$new_url_base		= implode( '/', $url_buffer_m );
						$cdn = RSHCP_CKF_IMG_DOM;
						if( !empty( $cdn ) && !empty( $new_url_base ) && !is_admin() && !is_preview() && !self::is_customize_preview() && preg_match( "~\.(gif|jpe?g|png)([#\?&][a-z0-9#\=\-_\.]+)*?~", $match ) ) {
							$new_url_base	= str_replace( $domain, RSHCP_CKF_IMG_DOM, $new_url_base );
						}
						$m_buffer 		= $match;
						$m_buffer 		= preg_replace( "~url\(['\"]?(?:\.\./)+([a-z0-9/\-_]+\.[a-z]{2,4}([#\?&][a-z0-9#\=\-_\.]+)*?)['\"]?\)~i", "url('".'//'.$new_url_base."/$1')", $m_buffer );
						$match_rgx 		= preg_quote( $match );
						$css_buffer 	= preg_replace( "~$match_rgx~i", $m_buffer, $css_buffer, -1, $count );
					}
				}

				/* Fixes - BEGIN */

				$css_buffer = str_replace( 'dashicons/css/fonts/dashicons.', 'dashicons/fonts/dashicons.', $css_buffer ); /* Dashicons / Minamaze Theme */
			
				/* Fixes - END */

				$combined_css[] = $css_buffer;
				unset ( $css_buffer );
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		} else { return; }
		$combined_css_contents_raw	= implode( RSHCP_EOL, $combined_css );
		$combined_css_contents_len	= self::strlen( $combined_css_contents_raw );
		$combined_css_contents		= self::simple_minifier_css( $combined_css_contents_raw );
		$plugin_file_mod_time		= filemtime( __FILE__ );
		if( file_exists( $raw_css_file ) ) {
			$raw_css_file_mod_time	= filemtime( $raw_css_file );
			$raw_css_file_filesize	= filesize( $raw_css_file );
		} else {
			$raw_css_file_mod_time	= FALSE;
			$raw_css_file_filesize	= FALSE;
		}
		$css_cache_time = time() - 86400; /* 60 * 60 * 1 - Sec * Min * Hour; 3600 = 1 Hour; 86400 = 24 Hours; */
		if( !empty( $combined_css_contents_len ) ) {
			if( $raw_css_file_filesize != $combined_css_contents_len || $raw_css_file_mod_time < $plugin_file_mod_time || $raw_css_file_mod_time < $css_cache_time || FALSE === $raw_css_file_mod_time ) {
				self::write_cache_file( $raw_css_file, $combined_css_contents_raw );
				self::write_cache_file( $min_css_file, $combined_css_contents );
			}
		} else { $rshcp_css_null = TRUE; }
		$rshcp_css_run = TRUE;
	}

	static public function replace_css_urls( $s ) {
		global $domain,$new_url_base;
		$mod_url_base	= $new_url_base;
		list( $m )		= $s;
		$cdn			= RSHCP_CKF_IMG_DOM;
		if( empty( $mod_url_base ) ) { return $m; }
		if( !empty( $domain ) && !empty( $cdn ) && !is_admin() && !is_preview() && !self::is_customize_preview() && preg_match( "~\.(gif|jpe?g|png)([#\?&][a-z0-9#\=\-_\.]+)*?~", $m ) ) {
			$mod_url_base = str_replace( $domain, $cdn, $new_url_base );
		}
		return preg_replace( "~url\(['\"]?\.?/?([a-z0-9/\-_]+\.[a-z]{2,4}([#\?&][a-z0-9#\=\-_\.]+)*?)['\"]?\)~i", "url('".'//'.$mod_url_base."/$1')", $m );
	}

	static public function replace_ckf_img_dom_urls( $s ) {
		/**
		 *	Replace domain with cookie-free domain to improve speed
		 *	This can be a CDN or another domain (or subdomain) you own where there is no cookie traffic.
		 *	This speeds up the transfer of images to the client (browser).
		 */
		$url		= RSHCP_THIS_URL;
		$domain		= self::get_domain( $url );
		list( $m )	= $s;
		$cdn		= RSHCP_CKF_IMG_DOM;
		if( empty( $cdn ) || empty( $domain ) ) { return $m; }
		return str_replace( array( $domain, 'http://'.$cdn, ), array( $cdn, 'https://'.$cdn, ), $m );
	}

	/**
	 *	Handle 404 errors properly
	 *	If visitor is major Search Engine bot and 404 is for an RSHCP .css file, then serve theme's CSS file, to prevent problems and make sure bot gets a CSS file.
	 */
	static public function handle_404( $status_header, $code, $description, $protocol ) {
		remove_filter('status_header',__CLASS__.'::handle_404',9999);
		global $rshcp_handle_404_run; if( !empty( $rshcp_handle_404_run ) ) { return $status_header; }
		$rshcp_handle_404_run = TRUE; $code = $new_code = intval( $code );
		if( $code != 404 || is_admin() ) { return $status_header; }
		if( FALSE !== strpos( $_SERVER['REQUEST_URI'], '.css' ) ) {
			$ua = self::get_user_agent( FALSE, TRUE );
			$theme_css_url = get_stylesheet_uri();
			if( ( FALSE !== strpos( $ua, 'googlebot' ) || FALSE !== strpos( $ua, 'bingbot' ) || FALSE !== strpos( $ua, 'yahoo! slurp' ) ) && FALSE === strpos( $_SERVER['REQUEST_URI'], $theme_css_url && preg_match( "~/css/rsm\-(min|raw)\-css\-[a-z0-9]{32,}\.css$~i", $_SERVER['REQUEST_URI'] ) ) ) {
				header( 'Location: '.$theme_css_url, TRUE, 301 ); exit();
			}
		}
		return $status_header;
	}
	/* SPEED UP WORDPRESS - END */



	/* Standard/Common Functions - BEGIN */

	/**
	 *  Detect and set correct End of Line character
	 *  For cross-platform compatibility, especially with certain server setups where PHP_EOL constant is not always set.
	 *	@dependencies	none
	 *	@used by		...
	 *	@notes			Function must be defined in main class, not includes or child classes
	 *	@since			1.0.0
	 */
	static public function eol() {
		return !empty( $GLOBALS['is_IIS'] ) ? "\r\n" : "\n";
	}

	/**
	 *  Detect and set correct Directory Separator character
	 *  For cross-platform compatibility, especially with certain server setups where DIRECTORY_SEPARATOR constant is not always set.
	 *  Even if OS will interpret either correctly when receiving input from script,
	 *  	it is still best practice for correct processing of strings returned by OS.
	 *	@dependencies	none
	 *	@used by		...
	 *	@notes			Function must be defined in main class, not includes or child classes
	 *	@since			1.0.0
	 */
	static public function ds() {
		return !empty( $GLOBALS['is_IIS'] ) ? '\\' : '/';
	}

	/**
	 *	Sanitize a string. Much faster than sanitize_text_field()
	 *	@dependencies	self::filter_null()
	 *	@since			1.4.3.2
	 */
	static public function sanitize_string( $str ) {
		return self::filter_null( trim( addslashes( htmlentities( stripslashes( strip_tags( $str ) ) ) ) ) );
	}

	/**
	 *	Remove null bytes from a string
	 *	Similar to wp_kses_no_null()
	 *	@dependencies	none
	 *	@since			1.4.3.8
	 */
	static public function rs_wpss_filter_null( $str ) {
		$str = preg_replace( '~[\x00-\x08\x0B\x0C\x0E-\x1F]~', '', $str );
		$str = preg_replace( '~\\\\+0+~', '', $str );
		return str_replace( chr(0), '', $str );
	}

	static public function strlen( $str ) {
		/**
		 *	Use this function instead of mb_strlen because some servers (often IIS) have mb_ functions disabled by default
		 *	BUT mb_strlen is superior to strlen, so use it whenever possible
		 */
		return function_exists( 'mb_strlen' ) ? mb_strlen( $str, 'UTF-8' ) : strlen( $str );
	}

	static public function casetrans( $type, $string ) {
		/**
		 *	Convert case using multibyte version if available, if not, use defaults
		 *	@since 1.4.3.2
		 */
		switch($type) {
			case 'upper':
				return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string, 'UTF-8' ) : strtoupper( $string );
			case 'lower':
				return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string, 'UTF-8' ) : strtolower( $string );
			case 'ucfirst':
				if( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
					$strtmp = mb_strtoupper( mb_substr( $string, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $string, 1, NULL, 'UTF-8' );
					return self::strlen( $string ) === self::strlen( $strtmp ) ? $strtmp : ucfirst( $string );
				}
				else { return ucfirst( $string ); }
			case 'ucwords':
				return function_exists( 'mb_convert_case' ) ? mb_convert_case( $string, MB_CASE_TITLE, 'UTF-8' ) : ucwords( $string );
				/**
				 *	Note differences in results between ucwords() and this. 
				 *	ucwords() will capitalize first characters without altering other characters, whereas this will lowercase everything, but capitalize the first character of each word.
				 *	This works better for our purposes, but be aware of differences.
				 */
			default:
				return $string;
		}
	}

	static public function get_domain( $url ) {
		/* Get domain from URL */
		/* Filter URLs with nothing after http */
		if( empty( $url ) || preg_match( "~^https?\:*/*$~i", $url ) ) { return ''; }
		/* Fix poorly formed URLs so as not to throw errors when parsing */
		$url = self::fix_url( $url );
		/* NOW start parsing */
		$parsed = self::parse_url($url);
		/* Filter URLs with no domain */
		if( empty( $parsed['host'] ) ) { return ''; }
		return self::casetrans( 'lower', $parsed['host'] );
	}

	static public function get_url( $safe = FALSE ) {
		/**
		 *	Get the URL of current page/post/etc
		 *	@Used By: get_slug(), inspect_scripts(), inspect_styles(), replace_ckf_img_dom_urls(), append_log_data(), admin_cpn_notices(), hide_cpn_notices()
		 *	@Used By: constant 'RSHCP_THIS_URL'
		 */
		$url  = self::is_https() ? 'https://' : 'http://';
		$req  = self::casetrans( 'lower', $_SERVER['REQUEST_URI'] ); /* $req_uri_lc */
		$url .= RSHCP_SERVER_NAME.$req;
		if( TRUE === $safe ) { $url = esc_url( $url ); }
		return $url;
	}

	static public function get_schemeless_url( $url ) {
		/**
		 *	Make URL schemeless / protocol-relative
		 *	Use judiciously to prevent SEO + proxy issues
		 *	@since 1.4.3.2
		 *	@Used By: ...
		 */
		return str_replace( array( 'https://', 'http://' ), '//', $url);
	}

	static public function fix_url( $url, $rem_frag = FALSE, $rem_query = FALSE, $rev = FALSE ) {
		/* Fix poorly formed URLs so as not to throw errors or cause problems */
		/* Too many forward slashes or colons after http */
		$url = preg_replace( "~^(https?)\:+/+~i", "$1://", $url );
		/* Too many dots */
		$url = preg_replace( "~\.+~i", ".", $url );
		/* Too many slashes after the domain */
		$url = preg_replace( "~([a-z0-9]+)/+([a-z0-9]+)~i", "$1/$2", $url );
		/* Remove fragments */
		if( !empty( $rem_frag ) && FALSE !== strpos( $url, '#' ) ) { $url_arr = explode( '#', $url ); $url = $url_arr[0]; }
		/* Remove query string completely */
		if( !empty( $rem_query ) && FALSE !== strpos( $url, '?' ) ) { $url_arr = explode( '?', $url ); $url = $url_arr[0]; }
		/* Reverse */
		if( !empty( $rev ) ) { $url = strrev($url); }
		return $url;
	}

	static public function get_query_string( $url ) {
		/**
		 *	Get query string from URL
		 *	Filter URLs with nothing after http
		 */
		if( empty( $url ) || preg_match( "~^https?\:*/*$~i", $url ) ) { return ''; }
		/* Fix poorly formed URLs so as not to throw errors when parsing */
		$url = self::fix_url( $url );
		/* NOW start parsing */
		$parsed = self::parse_url($url);
		/* Filter URLs with no query string */
		if( empty( $parsed['query'] ) ) { return ''; }
		return $parsed['query'];
	}

	static public function get_query_args( $url ) {
		/**
		 *	Get query string array from URL
		 */
		if( empty( $url ) ) { return array(); }
		$query_str = self::get_query_string( $url );
		parse_str( $query_str, $args );
		return $args;
	}

	/**
	 *  Detect https/http
	 *  Use instead of WP function is_ssl(), as this is more accurate
	 *  @dependencies	none
	 *  @used by		self::inspect_scripts(), self::inspect_styles(), self::get_url()
	 *  @since			1.0.0 as is_ssl()
	 */
	static public function is_https() {
		if( !empty( $_SERVER['HTTPS'] )						&& 'off'	!==	strtolower( $_SERVER['HTTPS'] )	)					{ return TRUE; }
		if( !empty( $_SERVER['SERVER_PORT'] )				&& '443'	 ==	$_SERVER['SERVER_PORT'] )							{ return TRUE; }
		if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] )	&& 'https'	===	strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )	{ return TRUE; }
		if( !empty( $_SERVER['HTTP_X_FORWARDED_SSL'] )		&& 'off'	!==	strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) )	{ return TRUE; }
		return FALSE;
	}

	static public function get_server_addr() {
		$server_addr = !empty( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : $_ENV['SERVER_ADDR'];
		return !empty( $server_addr ) ? $server_addr : '';
	}

	static public function get_server_name() {
		$server_name = '';
		if(		!empty( $_SERVER['HTTP_HOST'] ) )	{ $server_name = $_SERVER['HTTP_HOST']; }
		elseif(	!empty( $_ENV['HTTP_HOST'] ) )		{ $server_name = $_ENV['HTTP_HOST']; }
		elseif(	!empty( $_SERVER['SERVER_NAME'] ) )	{ $server_name = $_SERVER['SERVER_NAME']; }
		elseif(	!empty( $_ENV['SERVER_NAME'] ) )	{ $server_name = $_ENV['SERVER_NAME']; }
		return self::casetrans( 'lower', $server_name );
	}

	static public function get_ip_addr() {
		/** 
		 *	Detect accurate IP Address
		 *	TO DO: Add detection code. For now, just return sanitized $_SERVER['REMOTE_ADDR']
		 *	@since 1.4.3.2
		 */
		$ip = self::sanitize_ip( $_SERVER['REMOTE_ADDR'] );
		return $ip;
	}

	static public function sanitize_ip( $str ) {
		/**
		 *	Sanitize IP address input from $_SERVER[] vars
		 *	Can extract IP address from a list of IP's, from forwarded data, remove port #, etc.
		 *	It is possible for $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], etc., to return a list instead of a single IP
		 *	Run IP data through this function to sanitize before using in code
		 *	@since 1.4.3.2
		 */
		if( empty( $str ) ) { return ''; }
		$forwarded = array( 'for', '=', '"', );
		$str_tmp = str_replace( $forwarded, '', $str );
		$str_tmp = strtok( $str_tmp, ', ;' ); strtok('', '');
		$str_tmp = strtok( $str_tmp, ':' ); strtok('', '');
		return trim( $str_tmp );
	}

	static public function get_user_agent( $raw = FALSE, $lowercase = FALSE ) {
		/**
		 *	Gives User-Agent with filters
		 *	If blank, gives an initialized var to eliminate need for testing if isset() everywhere
		 *	Default is sanitized - use raw for testing, and sanitized for output
		 *	@since 1.4.3.2
		 *	@Used by: handle_404()
		 */
		if( !empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = !empty ( $raw ) ? trim( $_SERVER['HTTP_USER_AGENT'] ) : self::sanitize_string( $_SERVER['HTTP_USER_AGENT'] );
			if( !empty ( $lowercase ) )	{ $user_agent = self::casetrans( 'lower', $user_agent ); }
		} else { $user_agent = ''; }
		return $user_agent;
	}

	static public function doc_txt() {
		return __( 'Documentation', RSHCP_PLUGIN_NAME );
	}

	static public function scandir( $dir ) {
		clearstatcache();
		$dot_files = array( '..', '.' );
		if( !file_exists( $dir ) || !is_dir( $dir ) ) { return array(); }
		$dir_contents_raw = scandir( $dir );
		$dir_contents = array_values( array_diff( $dir_contents_raw, $dot_files ) );
		return $dir_contents;
	}

	static public function append_log_data( $str = NULL, $rsds_only = FALSE, $spec_func = NULL ) {
		/**
		 *	Adds data to the log for debugging - only use when Debugging - Use with WP_DEBUG & RSHCP_DEBUG
		 *	Example:
		 *	self::append_log_data( RSHCP_EOL.'$rshcp_example_variable: "'.$rshcp_example_variable.'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | MEM USED: ' . $rshcp_class::wp_memory_used() . ' | VER: ' . RSHCP_VERSION, TRUE );
		 *	self::append_log_data( RSHCP_EOL.'[A]$rshcp_example_array_var: "'.$rshcp_class::json_encode($rshcp_example_array_var).'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | MEM USED: ' . $rshcp_class::wp_memory_used() . ' | VER: ' . RSHCP_VERSION, TRUE );
		 *	self::append_log_data( RSHCP_EOL.'$rshcp_example_variable: "'.$rshcp_example_variable.'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | URL: '.RSHCP_THIS_URL.' | MEM USED: ' . $rshcp_class::wp_memory_used() . ' | VER: ' . RSHCP_VERSION, TRUE );
		 *	self::append_log_data( RSHCP_EOL.'[A]$rshcp_example_array_var: "'.$rshcp_class::json_encode($rshcp_example_array_var).'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | URL: '.RSHCP_THIS_URL.' | MEM USED: ' . $rshcp_class::wp_memory_used() . ' | VER: ' . RSHCP_VERSION, TRUE );
		 *	@ $spec_func Run only during specific function
		 *	@1.4.3.2 Added 'spec_func' parameter
		 */
		if( TRUE === WP_DEBUG && TRUE === RSHCP_DEBUG ) {
			if( !empty( $rsds_only ) && strpos( RSHCP_SERVER_NAME_REV, RSHCP_DEBUG_SERVER_NAME_REV ) !== 0 ) { return; }
			if( !empty( $spec_func ) && FALSE === strpos( ' Func: '.(string)$spec_func ) ) { return; }
			$rshcp_log_str = 'RSHCP DEBUG: ['.self::get_ip_addr().'] '.str_replace(RSHCP_EOL, "", $str);
			error_log( $rshcp_log_str, 0 ); /* Logs to debug.log */
		}
	}

	static public function microtime() {
		return microtime( TRUE );
	}

	static public function timer( $start = NULL, $end = NULL, $show_seconds = FALSE, $precision = 8, $no_format = FALSE, $raw = FALSE ) {
		/**
		 *	$precision will default to 8 but can be set to anything - 1,2,3,4,5,6,etc.
		 *	Use $no_format when clean numbers are needed for calculations. International formatting throws a wrench into things.
		 */
		if( empty( $start ) || empty( $end ) ) { $start = $end = 0; }
		$total_time = $end - $start;
		if( empty( $no_format ) ) {
			$total_time_for = self::number_format( $total_time, $precision );
			if( !empty( $show_seconds ) ) { $total_time_for .= ' seconds'; }
		} elseif( empty( $raw ) ) {
			$total_time_for = number_format( $total_time, $precision );
		} else { $total_time_for = $total_time; }
		return $total_time_for;
	}

	static public function number_format( $number, $precision = NULL ) {
		/* $precision will default to NULL but can be set to anything - 1,2,3,4,5,6,etc. */
		if( function_exists( 'number_format_i18n' ) ) { $number_for = number_format_i18n( $number, $precision ); }
		else { $number_for = number_format( $number, $precision ); }
		return $number_for;
	}

	static public function format_bytes( $size, $precision = 2 ) {
		if( !is_numeric($size) ) { return $size; }
		$base = log($size) / log(1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');
		$formatted_num = round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
		return $formatted_num;
	}

	static public function wp_memory_used() {
		return function_exists( 'memory_get_usage' ) ? self::format_bytes( memory_get_usage() ) : 0;
	}

	static public function date_diff( $start, $end ) {
		$start_ts = strtotime($start);
		$end_ts = strtotime($end);
		$diff = ($end_ts-$start_ts);
		$start_array = explode('-', $start);
		$start_year = $start_array[0];
		$end_array = explode('-', $end);
		$end_year = $end_array[0];
		$years = $end_year-$start_year;
		if(($years%4) == 0) { $extra_days = ((($end_year-$start_year)/4)-1); } else { $extra_days = ((($end_year-$start_year)/4)); }
		$extra_days = round($extra_days);
		return round($diff/86400)+$extra_days;
	}

	static public function sort_unique($arr) {
		$arr_tmp = array_unique( $arr ); sort( $arr_tmp, SORT_REGULAR ); $new_arr = array_values( $arr_tmp );
		return $new_arr;
	}

	static public function md5( $string ) {
		/**
		 *	Use this function instead of hash for compatibility
		 *	BUT hash is faster than md5, so use it whenever possible
		 */
		return function_exists( 'hash' ) ? hash( 'md5', $string ) : md5( $string );
	}

	static public function parse_url( $url ) {
		/**
		 *	Use this function instead of parse_url() for compatibility with PHP < 5.4.7. wp_parse_url() was added in WP ver 4.4
		 *	@since 1.4.3.2
		 */
		return ( function_exists( 'wp_parse_url' ) && self::is_wp_ver('4.4') ) ? wp_parse_url( $url ) : @parse_url( $url );
	}

	static public function json_encode( $data, $options = 0, $depth = 512 ) {
		/**
		 *	Use this function instead of json_encode() for compatibility, esp with non-UTF-8 data. wp_json_encode() was added in WP ver 4.1
		 *	@since 1.4.3.2
		 */
		return ( function_exists( 'wp_json_encode' ) && self::is_wp_ver('4.1') ) ? wp_json_encode( $data, $options, $depth ) : json_encode( $data, $options );
	}

	static public function is_wp_ver( $ver ) {
		/**
		 *	Check if current install is a specific WordPress version or later, for compatibility checks
		 *	@since 1.4.3.2
		 */
		return version_compare( RSHCP_WP_VERSION, $ver, '>=' );
	}

	static public function login_init() {
		global $rshcp_is_login_page; $rshcp_is_login_page = TRUE;
	}

	static public function is_login_page() {
		global $pagenow, $rshcp_is_login_page;
		if( $pagenow === 'wp-login.php' || $pagenow === 'wp-register.php' || !empty( $rshcp_is_login_page ) ) { return TRUE; }
		if( FALSE !== strpos( $_SERVER['PHP_SELF'], '/wp-login.php' ) || FALSE !== strpos( $_SERVER['PHP_SELF'], '/wp-register.php' ) ) { return TRUE; }
		return FALSE;
	}

	static public function is_user_admin() {
		global $rshcp_user_can_manage_options;
		if( empty( $rshcp_user_can_manage_options ) ) { $rshcp_user_can_manage_options = current_user_can( 'manage_options' ) ? 'YES' : 'NO'; }
		if( $rshcp_user_can_manage_options === 'YES' ) { return TRUE; }
		return FALSE;
	}

	static public function is_plugin_active( $plug_bn, $check_network = TRUE ) {
		/**
		 *	Using this because is_plugin_active() only works in Admin area
		 *	ex. $plug_bn = 'folder/filename.php'; // Plugin Basename
		 */
		if( empty( $plug_bn ) ){ return FALSE; }
		global $rshcp_conf_active_plugins;
		/* Quick Check */
		if( !empty( $rshcp_conf_active_plugins[$plug_bn] ) ) { return TRUE; }
		if( TRUE === $check_network && is_multisite() ) { if( !empty( $rshcp_conf_active_network_plugins[$plug_bn] ) ) { return TRUE; } }
		$rshcp_conf_active_plugins = array();
		$rshcp_conf_active_network_plugins = array();
		/* Check known plugin constants and classes */
		$plug_cncl = array(
			/* Compatibility Fixes */
			'autoptimize/autoptimize.php' => array( 'cn' => 'AUTOPTIMIZE_WP_CONTENT_NAME', 'cl' => 'autoptimizeConfig' ), 'jetpack/jetpack.php' => array( 'cn' => 'JETPACK__VERSION', 'cl' => 'Jetpack' ), 'wordpress-seo/wp-seo.php' => array( 'cn' => 'WPSEO_VERSION', 'cl' => '' ), 
			/* Forms, Membership & Registration */
			'bbpress/bbpress.php' => array( 'cn' => '', 'cl' => 'bbPress' ), 'buddypress/bp-loader.php' => array( 'cn' => 'BP_PLUGIN_DIR', 'cl' => 'BuddyPress' ), 'contact-form-7/wp-contact-form-7.php' => array( 'cn' => 'WPCF7_VERSION', 'cl' => '' ), 'gravityforms/gravityforms.php' => array( 'cn' => 'GF_MIN_WP_VERSION', 'cl' => 'GFForms' ), 'mailchimp-for-wp/mailchimp-for-wp.php' => array( 'cn' => 'MC4WP_LITE_VERSION', 'cl' => 'MC4WP_Lite' ), 'ninja-forms/ninja-forms.php' => array( 'cn' => 'NF_PLUGIN_VERSION', 'cl' => 'Ninja_Forms' ), 
			/* Ecommerce Plugins */
			'download-manager/download-manager.php' => array( 'cn' => 'WPDM_Version', 'cl' => '' ), 'easy-digital-downloads/easy-digital-downloads.php' => array( 'cn' => 'EDD_VERSION', 'cl' => '' ), 'ecwid-shopping-cart/ecwid-shopping-cart.php' => array( 'cn' => 'ECWID_DEMO_STORE_ID', 'cl' => '' ), 'eshop/eshop.php' => array( 'cn' => 'ESHOP_VERSION', 'cl' => '' ), 'gravityformspaypal/paypal.php' => array( 'cn' => 'GF_PAYPAL_VERSION', 'cl' => 'GF_PayPal_Bootstrap' ), 'ithemes-exchange/init.php' => array( 'cn' => '', 'cl' => 'IT_Exchange' ), 'jigoshop/jigoshop.php' => array( 'cn' => 'JIGOSHOP_VERSION', 'cl' => '' ), 'shopp/Shopp.php' => array( 'cn' => '', 'cl' => 'ShoppLoader' ), 'usc-e-shop/usc-e-shop.php' => array( 'cn' => 'USCES_VERSION', 'cl' => '' ), 'woocommerce/woocommerce.php' => array( 'cn' => 'WOOCOMMERCE_VERSION', 'cl' => 'WooCommerce' ), 'wordpress-ecommerce/marketpress.php' => array( 'cn' => 'MP_LITE', 'cl' => 'MarketPress' ), 'wordpress-simple-paypal-shopping-cart/wp_shopping_cart.php' => array( 'cn' => 'WP_CART_VERSION', 'cl' => '' ), 'wp-e-commerce/wp-shopping-cart.php' => array( 'cn' => 'WPSC_FILE_PATH', 'cl' => '' ), 
			/* Page Builder Plugins */
			'beaver-builder-lite-version/fl-builder.php' => array( 'cn' => 'FL_BUILDER_VERSION', 'cl' => 'FLBuilder' ), 'bb-plugin/fl-builder.php' => array( 'cn' => 'FL_BUILDER_VERSION', 'cl' => 'FLBuilder' ), 
			/* All others */
			'wordfence/wordfence.php' => array( 'cn' => 'WORDFENCE_VERSION', 'cl' => 'wordfence' ), 
			);
		if( ( !empty( $plug_cncl[$plug_bn]['cn'] ) && defined( $plug_cncl[$plug_bn]['cn'] ) ) || ( !empty( $plug_cncl[$plug_bn]['cl'] ) && class_exists( $plug_cncl[$plug_bn]['cl'] ) ) ) { $rshcp_conf_active_plugins[$plug_bn] = TRUE; return TRUE; }
		/* No match yet, so now do standard check */
		global $rshcp_active_plugins; if( empty( $rshcp_active_plugins ) ) { $rshcp_active_plugins = self::get_active_plugins(); }
		if( self::in_array( $plug_bn, $rshcp_active_plugins ) ) { $rshcp_conf_active_plugins[$plug_bn] = TRUE; return TRUE; }
		if( TRUE === $check_network && is_multisite() ) {
			if( self::is_plugin_active_network( $plug_bn ) ) { return TRUE; }
		}
		return FALSE;
	}

	static public function get_active_plugins( $sort = TRUE ) {
		global $rshcp_active_plugins;
		if( empty( $rshcp_active_plugins ) ) { $rshcp_active_plugins = get_option( 'active_plugins' ); }
		if( TRUE === $sort ) { $rshcp_active_plugins = self::sort_unique( $rshcp_active_plugins ); }
		return $rshcp_active_plugins;
	}

	static public function is_plugin_active_network( $plug_bn ) {
		if ( !is_multisite() ) { return FALSE; }
		global $rshcp_active_network_plugins; if( empty( $rshcp_active_network_plugins ) ) { $rshcp_active_network_plugins = self::get_active_network_plugins(); }
		if( self::in_array( $plug_bn, $rshcp_active_network_plugins ) ) { $rshcp_conf_active_network_plugins[$plug_bn] = TRUE; return TRUE; }
		return FALSE;
	}

	static public function get_active_network_plugins() {
		global $rshcp_active_network_plugins;
		if( empty( $rshcp_active_network_plugins ) ) { 
			$rshcp_active_network_plugins = get_site_option( 'active_sitewide_plugins' );
			if( !empty( $rshcp_active_network_plugins ) && is_array( $rshcp_active_network_plugins ) ) {
				$rshcp_active_network_plugins = self::sort_unique( array_flip( $rshcp_active_network_plugins ) );
			}
		}
		return $rshcp_active_network_plugins;
	}

	static public function is_404() {
		return ( !empty( $GLOBALS['wp_query'] ) && is_404() );
	}

	static public function is_customize_preview() {
		return ( self::is_wp_ver( '4.0' ) && is_customize_preview() );
	}

	static public function is_amp_url() {
		return ( 0 === strpos( RSHCP_THIS_URL, RSHCP_SITE_AMP_URL ) );
	}

	/**
	 *  Use this function instead of in_array() as it's *much* faster.
	 *  Equivalent of 'in_array( $needle, $haystack, TRUE )' ($strict = TRUE)
	 *  @since			1.4.3.7
	 *  @reference		http://php.net/manual/en/function.in-array.php
	 *  @param			string	$needle
	 *  @param			array	$haystack
	 */
	static public function in_array( $needle, $haystack ) {
		$haystack_flip = array_flip( $haystack );
		return ( isset( $haystack_flip[$needle] ) );
	}

	/* Standard/Common Functions - END */


	/* Admin Functions - BEGIN */

	static public function activation() {
		global $rshcp_options;
		$rshcp_options = get_option( 'rshcp_options' );
		$installed_ver = !empty( $rshcp_options['version'] ) ? $rshcp_options['version'] : '';
		self::upgrade_check( $installed_ver, TRUE );
		self::mk_cache_dir();
	}

	static public function conflict_check() {
		/* Prevent conflicts - make sure other version of plugin is not running */
		$v_cl = 'RS_Head_Cleaner'; $v_bn = 'rs-head-cleaner';
		if( FALSE === strpos( __CLASS__, '_Lite' ) ) { $v_cl .= '_Lite'; $v_bn .= '-lite'; }
		if( class_exists( $v_cl ) || ( defined( 'RSHCL_PLUGIN_NAME' ) && $v_bn === RSHCL_PLUGIN_NAME ) || ( defined( 'RSHCP_PLUGIN_NAME' ) && $v_bn === RSHCP_PLUGIN_NAME ) ) {
			self::deactivate_plugins( RSHCP_PLUGIN_BASENAME );
			$notice_text = '<p>' . __('Plugin <strong>deactivated</strong>.') . ' ' . __( 'Please do not try to run both versions of the plugin at the same time. Please choose <em>either</em> <strong>RS Head Cleaner Plus</strong>, <em>or</em> <strong>RS Head Cleaner Lite</strong>.', RSHCP_PLUGIN_NAME ) . '</p>';
			$new_admin_notice = array( 'style' => 'error notice is-dismissible', 'notice' => $notice_text );
			update_option( 'rshcp_admin_notices', $new_admin_notice );
			add_action( 'admin_notices', __CLASS__.'::admin_notices' );
			return TRUE;
		}
		return FALSE;
	}

	static public function upgrade_check( $installed_ver = NULL ) {
		global $rshcp_options;
		$rshcp_options = !empty( $rshcp_options ) ? $rshcp_options : get_option( 'rshcp_options' );
		$installed_ver = !empty( $rshcp_options['version'] ) ? $rshcp_options['version'] : '';
		if( $installed_ver !== RSHCP_VERSION ) {
			if( empty( $rshcp_options ) || !is_array( $rshcp_options ) ) { $rshcp_options = array(); }
			$rshcp_options['version'] = RSHCP_VERSION;
			if( empty( $rshcp_options['install_date'] ) ) { $rshcp_options['install_date'] = date('Y-m-d'); }
			update_option('rshcp_options', $rshcp_options);
			self::mk_cache_dir();
		}
	}

	static public function mk_cache_dir( $i = 1 ) {
		$k						= $i-1;
		$d_perm					= array( 0755, 0775, 0775, 0775, );
		$f_perm					= array( 0644, 0664, 0664, 0664, );
		$rshcp_js_dir			= RSHCP_JS_PATH;
		$rshcp_css_dir			= RSHCP_CSS_PATH;
		$rshcp_index_file		= RSHCP_PLUGIN_PATH.'index.php';
		$rshcp_htaccess_file	= RSHCP_PLUGIN_PATH.'lib/.htaccess';
		if( !file_exists( $rshcp_js_dir ) ) {
			wp_mkdir_p( $rshcp_js_dir );
			@chmod( $rshcp_js_dir, $d_perm[$k] );
			@copy ( $rshcp_index_file, $rshcp_js_dir.'index.php' );
		}
		if( !file_exists( $rshcp_css_dir ) ) {
			wp_mkdir_p( $rshcp_css_dir );
			@chmod( $rshcp_css_dir, $d_perm[$k] );
			@copy ( $rshcp_index_file, $rshcp_css_dir.'index.php' );
		}
		@copy ( $rshcp_index_file, RSHCP_CACHE_PATH.'index.php' );
		@copy ( $rshcp_htaccess_file, RSHCP_CACHE_PATH.'.htaccess' );
	}

	static public function write_cache_file( $file, $contents, $i = 0 ) {
		/**
		 *	This function ensures that the cache file is written.
		 *	file_put_contents() just throws a PHP Warning if a folder is missing, but this will attempt to create the folder and write the file up to 3 times.
		 */
		global $vfile_set;
		$filename = basename( $file );
		if( empty( $vfile_set ) && FALSE === RSHCP_JS_TO_FTR && 46 == self::strlen( $filename ) ) {
			if( 0 === strpos( $filename, 'rsm-raw-js-' ) ) {
				self::vfile_put_contents( $file, $contents );
				return;
			} elseif( 0 === strpos( $filename, 'rsm-min-js-' ) ) { return; }
		}
		$i++; $m = 4;
		if( $i === $m ) {
			clearstatcache();
			if( file_exists( $file ) ) {
				@chmod( $file, 0775 ); @unlink( $file );
			}
		} else {
			$direxists	= file_exists( dirname( $file ) );
			if( empty( $direxists ) ) { self::mk_cache_dir($i); }
			if( FALSE === file_put_contents( $file, $contents ) ) {
				self::mk_cache_dir($i);
				self::write_cache_file( $file, $contents, $i );
			}
		}
		@chmod( $file, 0644 );
	}

	static public function vfile_put_contents( $file, $contents ) {
		/* Virtual File */
		global $rshcp_vfile;
		$rshcp_vfile = $contents;
	}

	static public function check_version() {
		if( current_user_can( 'manage_network' ) ) {
			/* Check for pending admin notices */
			$admin_notices = get_option('rshcp_admin_notices');
			if( !empty( $admin_notices ) ) { add_action( 'network_admin_notices', __CLASS__.'::admin_notices' ); }
			/* Make sure not network activated */
			if( self::is_plugin_active_network( RSHCP_PLUGIN_BASENAME ) ) {
				self::deactivate_plugins( RSHCP_PLUGIN_BASENAME, TRUE, TRUE );
				$notice_text = '<p>' . __('Plugin <strong>deactivated</strong>.') . ' ' . __( 'RS Head Cleaner is not available for network activation.', RSHCP_PLUGIN_NAME ) . '</p>';
				$new_admin_notice = array( 'style' => 'error notice is-dismissible', 'notice' => $notice_text );
				update_option( 'rshcp_admin_notices', $new_admin_notice );
				add_action( 'network_admin_notices', __CLASS__.'::admin_notices' );
				return FALSE;
			}
		}
		if( current_user_can( 'manage_options' ) ) {
			/* Check if plugin has been upgraded */
			self::upgrade_check();
			/* Check for pending admin notices */
			$admin_notices = get_option('rshcp_admin_notices');
			if( !empty( $admin_notices ) ) { add_action( 'admin_notices', __CLASS__.'::admin_notices' ); }
			/* Make sure user has minimum required WordPress version, in order to prevent issues */
			if( !self::is_wp_ver( RSHCP_REQUIRED_WP_VERSION ) ) {
				self::deactivate_plugins( RSHCP_PLUGIN_BASENAME );
				$notice_text = '<p>' . __('Plugin <strong>deactivated</strong>.') . ' ' . sprintf( __( 'WordPress Version %s required. Please upgrade WordPress to the latest version.', RSHCP_PLUGIN_NAME ), RSHCP_REQUIRED_WP_VERSION ) . '</p>';
				$new_admin_notice = array( 'style' => 'error notice is-dismissible', 'notice' => $notice_text );
				update_option( 'rshcp_admin_notices', $new_admin_notice );
				add_action( 'admin_notices', __CLASS__.'::admin_notices' );
				return FALSE;
			}
			/* Make sure user has minimum required PHP version, in order to prevent issues */
			$rshcp_php_version = RSHCP_PHP_VERSION;
			if( !empty( $rshcp_php_version ) && version_compare( $rshcp_php_version, RSHCP_REQUIRED_PHP_VERSION, '<' ) ) {
				self::deactivate_plugins( RSHCP_PLUGIN_BASENAME );
				$notice_text = '<p>' . __('Plugin <strong>deactivated</strong>.') . ' ' . str_replace( 'WordPress', 'RS Head Cleaner', sprintf( __('Your server is running PHP version %1$s but WordPress %2$s requires at least %3$s.'), RSHCP_PHP_VERSION, RSHCP_VERSION, RSHCP_REQUIRED_PHP_VERSION ) ) . ' ' . sprintf( __( 'We are no longer supporting PHP 5.2, as it has not been supported by the PHP team <a href=%2$s>since 2011</a>, and there are known security, performance, and compatibility issues.</p><p>The version of PHP running on your server is <em>extremely out of date</em>. You should upgrade your PHP version as soon as possible.</p><p>If you need help with this, please contact your web hosting company and ask them to switch your PHP version to 5.5, 5.6, or higher. Please see the <a href=%4$s>plugin documentation</a> if you have further questions.</p>', RSHCP_PLUGIN_NAME ), RSHCP_REQUIRED_PHP_VERSION, '"http://php.net/archive/2011.php#id2011-08-23-1" target="_blank" rel="external" ', $rshcp_php_version, '"'.RSHCP_HOME_URL.'?src='.RSHCP_VERSION.'-php-notice#rshc_requirements" target="_blank" rel="external" ' ); /* TO DO: NEEDS TRANSLATION - Added 1.4.0 */
				$new_admin_notice = array( 'style' => 'error notice is-dismissible', 'notice' => $notice_text );
				update_option( 'rshcp_admin_notices', $new_admin_notice );
				add_action( 'admin_notices', __CLASS__.'::admin_notices' );
				return FALSE;
			}
			/* Make sure user's site has .htaccess capability & is not on standalone Nginx, in order to prevent issues */
			global $is_apache, $is_IIS, $is_iis7, $is_nginx;
			/* Nginx */
			if( !empty( $is_nginx ) && empty( $is_apache ) ) {
				self::deactivate_plugins( RSHCP_PLUGIN_BASENAME );
				$notice_text = '<p>' . __('Plugin <strong>deactivated</strong>.') . ' ' . __( 'Your server is running standalone Nginx but RS Head Cleaner requires either an Apache or Apache/Nginx hybrid setup.', RSHCP_PLUGIN_NAME ) . ' ' . sprintf( __( 'Your site does not meet the plugin\'s <a href=%1$s>minimum requirements</a>. Please see the <a href=%1$s>plugin documentation</a> if you have further questions.</p>', RSHCP_PLUGIN_NAME ), '"'.RSHCP_HOME_URL.'?src=nginx-notice#rshc_requirements" target="_blank" rel="external" ' ); /* TO DO: NEEDS TRANSLATION - Added 1.4.3.2 */
				$new_admin_notice = array( 'style' => 'error notice is-dismissible', 'notice' => $notice_text );
				update_option( 'rshcp_admin_notices', $new_admin_notice );
				add_action( 'admin_notices', __CLASS__.'::admin_notices' );
				return FALSE;
			}
			/* TO DO: Add check for .htaccess capabilities: AllowOverride All */
			self::conflict_check();
			self::check_cpn_notices();
		}
	}

	static public function admin_notices() {
		$admin_notices = get_option( 'rshcp_admin_notices' );
		if( !empty( $admin_notices ) ) {
			$style 	= $admin_notices['style']; /* 'error' or 'updated' */
			$notice	= $admin_notices['notice'];
			echo '<div class="'.$style.'"><p>'.$notice.'</p></div>';
		}
		delete_option( 'rshcp_admin_notices' );
	}

	static public function admin_cpn_notices() {
		global $current_user; $current_user = wp_get_current_user();
		$nag_notices		= get_user_meta( $current_user->ID, 'rshcp_nag_notices', TRUE );
		if( !empty( $nag_notices ) ) {
			$nid			= $nag_notices['nid'];
			$style			= $nag_notices['style']; /* 'error' or 'updated' */
			$timenow		= time();
			$url			= RSHCP_THIS_URL;
			$query_args		= self::get_query_args( $url );
			$query_str		= '?' . http_build_query( array_merge( $query_args, array( 'rshcp_hide_nag' => '1', 'nid' => $nid ) ) );
			$query_str_con	= 'QUERYSTRING';
			$notice			= str_replace( array( $query_str_con ), array( $query_str ), $nag_notices['notice'] );
			echo '<div class="'.$style.'"><p>'.$notice.'</p></div>';
		}
	}

	static public function check_cpn_notices() {
		global $current_user; $current_user = wp_get_current_user();
		$status			= get_user_meta( $current_user->ID, 'rshcp_nag_status', TRUE );
		if( !empty( $status['currentnag'] ) ) { add_action( 'admin_notices', __CLASS__.'::admin_cpn_notices' ); return; }
		if( !is_array( $status ) ) { $status = array(); update_user_meta( $current_user->ID, 'rshcp_nag_status', $status ); }
		$timenow		= time();
		$num_days_inst	= self::num_days_inst();
		$query_str_con	= 'QUERYSTRING';
		/* Notices (Positive Nags) */
		if( empty( $status['currentnag'] ) && ( empty( $status['lastnag'] ) || $status['lastnag'] <= $timenow - 1209600 ) ) {
			if( empty( $status['vote'] ) && $num_days_inst >= 14 ) { /* TO DO: TRANSLATE */
				$nid = 'n01'; $style = 'updated';
				$notice_text = __( 'It looks like you\'ve been using RS Head Cleaner for a while now. That\'s great! :)', RSHCP_PLUGIN_NAME ) .'</p><p>'. __( 'If you find this plugin useful, would you take a moment to give it a rating on WordPress.org?', RSHCP_PLUGIN_NAME ) .'</p><p>'. sprintf( __( '<strong><a href=%1$s>%2$s</a></strong>', RSHCP_PLUGIN_NAME ), '"'.RSHCP_WP_RATING_URL.'" target="_blank" rel="external" ', __( 'Yes, I\'d like to rate it!', RSHCP_PLUGIN_NAME ) ) .' &mdash; '.  sprintf( __( '<strong><a href=%1$s>%2$s</a></strong>', RSHCP_PLUGIN_NAME ), '"'.$query_str_con.'" ', __( 'I already did!', RSHCP_PLUGIN_NAME ) );
				$status['currentnag'] = TRUE; $status['vote'] = FALSE;
			} elseif( empty( $status['donate'] ) && $num_days_inst >= 90 ) { /* TO DO: TRANSLATE */
				$nid = 'n02'; $style = 'updated';
				$notice_text = __( 'You\'ve been using RS Head Cleaner for several months now. We hope that means you like it and are finding it helpful. :)', RSHCP_PLUGIN_NAME ) .'</p><p>'. __( 'RS Head Cleaner is provided for free.', RSHCP_PLUGIN_NAME ) . ' ' . __( 'If you like the plugin, consider a donation to help further its development.', RSHCP_PLUGIN_NAME ) .'</p><p>'. sprintf( __( '<strong><a href=%1$s>%2$s</a></strong>', RSHCP_PLUGIN_NAME ), '"'.RSHCP_DONATE_URL.'" target="_blank" rel="external" ', __( 'Yes, I\'d like to donate!', RSHCP_PLUGIN_NAME ) ) .' &mdash; '. sprintf( __( '<strong><a href=%1$s>%2$s</a></strong>', RSHCP_PLUGIN_NAME ), '"'.$query_str_con.'" ', __( 'I already did!', RSHCP_PLUGIN_NAME ) );
				$status['currentnag'] = TRUE; $status['donate'] = FALSE;
			}
		}
		/* Warnings (Negative Nags) */
		/* TO DO: Add Negative Nags - warnings about plugin conflicts and missing PHP functions */
		if( !empty( $status['currentnag'] ) ) {
			add_action( 'admin_notices', __CLASS__.'::admin_cpn_notices' );
			$new_nag_notice = array( 'nid' => $nid, 'style' => $style, 'notice' => $notice_text );
			update_user_meta( $current_user->ID, 'rshcp_nag_notices', $new_nag_notice );
			update_user_meta( $current_user->ID, 'rshcp_nag_status', $status );
		}
	}

	static public function hide_cpn_notices() {
		if( !self::is_user_admin() ) { return; }
		$ns_codes		= array( 'n01' => 'vote', 'n02' => 'donate', ); /* Nag Status Codes */
		if( !isset( $_GET['rshcp_hide_nag'], $_GET['nid'], $ns_codes[$_GET['nid']] ) || $_GET['rshcp_hide_nag'] != '1' ) { return; }
		global $current_user; $current_user = wp_get_current_user();
		$status			= get_user_meta( $current_user->ID, 'rshcp_nag_status', TRUE );
		$timenow		= time();
		$url			= RSHCP_THIS_URL;
		$query_args		= self::get_query_args( $url ); unset( $query_args['rshcp_hide_nag'],$query_args['nid'] );
		$query_str		= http_build_query( $query_args ); if( $query_str != '' ) { $query_str = '?'.$query_str; }
		$redirect_url	= self::fix_url( $url, TRUE, TRUE ) . $query_str;
		$status['currentnag'] = FALSE; $status['lastnag'] = $timenow; $status[$ns_codes[$_GET['nid']]] = TRUE;
		update_user_meta( $current_user->ID, 'rshcp_nag_status', $status );
		update_user_meta( $current_user->ID, 'rshcp_nag_notices', array() );
		wp_redirect( $redirect_url );
		exit;
	}

	static public function network_admin_remove_plugin( $all ) {
		/**
		 *	Remove plugin from Network Admin Plugins list to prevent Network Activation
		 *	@since 1.4.3
		 */
		global $current_screen;
		if( $current_screen->is_network ) {
			unset( $all[RSHCP_PLUGIN_BASENAME] );
		}
		return $all;
	}

	static public function meta_links( $links, $file ) {
		/**
		 *	Add Links on Dashboard Plugins page, in plugin meta
		 *	After other links
		 */
		if( $file == RSHCP_PLUGIN_BASENAME ) {
			$links[] = '<a href="'.RSHCP_HOME_URL.'" target="_blank" rel="external" >' . self::doc_txt() . '</a>';
			$links[] = '<a href="'.RSHCP_SUPPORT_URL.'" target="_blank" rel="external" >' . __( 'Support', RSHCP_PLUGIN_NAME ) . '</a>';
			$links[] = '<a href="'.RSHCP_WP_RATING_URL.'" title="' . __( 'Let others know by giving it a good rating on WordPress.org!', RSHCP_PLUGIN_NAME ) . '" target="_blank" rel="external" >' . __( 'Rate the Plugin', RSHCP_PLUGIN_NAME ) . '</a>';
			$links[] = '<a href="'.RSHCP_DONATE_URL.'" target="_blank" rel="external" >' . __( 'Donate', RSHCP_PLUGIN_NAME ) . '</a>';
		}
		return $links;
	}

	static public function num_days_inst() {
		global $rshcp_options;
		$current_date	= date('Y-m-d');
		$install_date	= empty( $rshcp_options['install_date'] ) ? $current_date : $rshcp_options['install_date'];
		$num_days_inst	= self::date_diff($install_date, $current_date); if( $num_days_inst < 1 ) { $num_days_inst = 1; }
		return $num_days_inst;
	}

	static public function deactivation() {
		$rshcp_css_path_old	= str_replace( '/cache/'.RSHCP_CACHE_DIR_NAME.'/', '/'.RSHCP_CACHE_DIR_NAME.'-cache/', RSHCP_CSS_PATH );
		$rshcp_js_path_old	= str_replace( '/cache/'.RSHCP_CACHE_DIR_NAME.'/', '/'.RSHCP_CACHE_DIR_NAME.'-cache/', RSHCP_JS_PATH );
		$rshcp_dirs_all		=
			array(
				array( 'css' => RSHCP_CSS_PATH, 'js' => RSHCP_JS_PATH ),
				array( 'css' => $rshcp_css_path_old, 'js' => $rshcp_js_path_old ),
			);
		foreach( $rshcp_dirs_all as $i => $rshcp_dirs ) {
			foreach( $rshcp_dirs as $d => $dir ) {
				if( is_dir( $rshcp_dirs[$d] ) ) {
					$filelist = self::scandir( $rshcp_dirs[$d] );
					foreach( $filelist as $f => $filename ) {
						$file = $rshcp_dirs[$d].$filename; $filerev = strrev($file); $drev = strrev($d);
						if( is_file( $file ) ){
							if( strpos( $filerev, $drev.'.' ) !== 0 ) { continue; }
							@chmod( $file, 0775 );
							@unlink( $file );
							if( file_exists( $file ) ) { @chmod( $file, 0644 ); }
						}
					}
				}
			}
		}
	}

	static public function deactivate_plugins( $plugins, $silent = FALSE, $network_wide = null ) {
		if( is_multisite() ) {
			$network_current = get_site_option( 'active_sitewide_plugins', array() );
		}
		$current = get_option( 'active_plugins', array() );
		$do_blog = $do_network = FALSE;
		foreach( (array) $plugins as $plugin ) {
			$plugin = plugin_basename( trim( $plugin ) );
			if( !self::is_plugin_active($plugin) ) { continue; }
			$network_deactivating = FALSE !== $network_wide && self::is_plugin_active_network( $plugin );
			if( ! $silent ) {
				do_action( 'deactivate_plugin', $plugin, $network_deactivating );
			}
			if( FALSE !== $network_wide ) {
				if( self::is_plugin_active_network( $plugin ) ) {
					$do_network = TRUE;
					unset( $network_current[ $plugin ] );
				} elseif( $network_wide ) {
					continue;
				}
			}
			if( TRUE !== $network_wide ) {
				$key = array_search( $plugin, $current );
				if( FALSE !== $key ) {
					$do_blog = TRUE;
					unset( $current[ $key ] );
				}
			}
			if( !$silent ) {
				do_action( 'deactivate_' . $plugin, $network_deactivating );
				do_action( 'deactivated_plugin', $plugin, $network_deactivating );
			}
		}
		if( $do_blog ) {
			update_option('active_plugins', $current);
		}
		if( $do_network ) {
			update_site_option( 'active_sitewide_plugins', $network_current );
		}
	}

	/**
	 *  Plugins Loaded 50
	 *  Run deconfliction and security protocols
	 *  @dependencies	...
	 *  @used by		...
	 *  @action			'plugins_loaded' / priority 50
	 *  @since			1.4.3.7
	 */
	static public function plugins_loaded_50() {

		if( is_admin() ) {
			/**
			 *	Add directives that should run in admin
			 */

			/**
			 *	Yoast SEO plugin adds a deceptive notice claiming "Cloaking", which is patently FALSE.
			 *	Minification and caching plugins often have to detect search engine bots. This does not serve different content.
			 *	Team Yoast added this deceptive notice as retaliation for calling them out publicly for attacking other plugin developers.
			 *	This next line removes the deceptive warning, since Team Yoast is unwilling to demonstrate ethics, professionalism and civility.
			 *	Ironically, Yoast SEO may be damaging both your site speed, and your SEO.
			 */
			remove_action( 'admin_init', array( 'WPSEO_Plugin_Conflict', 'hook_check_for_plugin_conflicts' ), 10 );

			/**
			 *	Add more admin directives...
			 */

		}

		/**
		 *	Add more directives...
		 */

	}

	/**
	 *	Add plugins to PHP Compatibility Checker Plugin whitelist to prevent false positives
	 *	All our plugins are fully PHP 7+ compatible
	 *	@dependencies	...
	 *	@since			1.4.3.6
	 */
	static public function php_compat( $ignored = array() ) {
		if( !is_array( $ignored ) ) { return $ignored; }
		$rsmg_plugins = array( 'rs-feedburner', 'rs-head-cleaner', 'rs-head-cleaner-lite', 'rs-nofollow-blogroll', 'rs-system-diagnostic', 'scrapebreaker', 'wp-spamshield' );
		foreach( $rsmg_plugins as $i => $p ) {
			$plugin = '*/'.$p.'/*';
			if( !self::in_array( $plugin, $ignored ) ) {
				$ignored[] = $plugin;
			}
		}
		return $ignored;
	}

	/* Admin Functions - END */


	/* Deprecata */

	static public function is_ssl() {
		_deprecated_function( __METHOD__, '1.4.3.6', __CLASS__.'::is_https()' );
		return self::is_https();
	}

}

/* BENCHMARK - END */
/*
$end_time = $rshcp_class::microtime();
$total_time = $rshcp_class::timer( $start_time, $end_time, FALSE, 6, TRUE );
$rshcp_class::append_log_data( RSHCP_EOL.'$start_time: "'.$start_time.'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | '.RSHCP_THIS_URL.' | MEM USED: ' . $rshcp_class::wp_memory_used(), TRUE );
$rshcp_class::append_log_data( RSHCP_EOL.'$end_time: "'.$end_time.'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | '.RSHCP_THIS_URL.' | MEM USED: ' . $rshcp_class::wp_memory_used(), TRUE );
$rshcp_class::append_log_data( RSHCP_EOL.'$total_time: "'.$total_time.'" Line: '.__LINE__.' | Func: '.__FUNCTION__.' | '.RSHCP_THIS_URL.' | MEM USED: ' . $rshcp_class::wp_memory_used(), TRUE );
*/

/* PLUGIN - END */
