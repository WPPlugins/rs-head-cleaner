<?php
/**
 * RS Head Cleaner Plus/Lite - uninstall.php
 * File Version: 1.4.3
 *
 * This script uninstalls RS Head Cleaner Plus/Lite and removes all cache files, options, data, and traces of its existence.
 */

if ( !defined( 'ABSPATH' ) || !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	if( !headers_sent() ) { header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden'); }
	die( 'ERROR: This plugin requires WordPress and will not function if called directly.' );
}

if( !defined( 'RSHCP_PLUGIN_BASENAME' ) ) 		{ define( 'RSHCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); }
if( !defined( 'RSHCP_PLUGIN_NAME' ) ) 			{ define( 'RSHCP_PLUGIN_NAME', trim( dirname( RSHCP_PLUGIN_BASENAME ), '/' ) ); }
if( !defined( 'RSHCP_LITE' ) )					{ define( 'RSHCP_LITE', FALSE !== strpos( RSHCP_PLUGIN_NAME, '-lite' ) ? TRUE : FALSE ); }
if( !defined( 'RSHCP_CACHE_DIR_NAME' ) ) 		{ define( 'RSHCP_CACHE_DIR_NAME', TRUE === RSHCP_LITE ? 'rshcl' : 'rshcp' ); }
if( !defined( 'RSHCP_CACHE_PATH' ) ) 			{ define( 'RSHCP_CACHE_PATH', WP_CONTENT_DIR.'/cache/'.RSHCP_CACHE_DIR_NAME.'/' ); }
if( !defined( 'RSHCP_JS_PATH' ) ) 				{ define( 'RSHCP_JS_PATH', RSHCP_CACHE_PATH.'/js/' ); }
if( !defined( 'RSHCP_CSS_PATH' ) ) 				{ define( 'RSHCP_CSS_PATH', RSHCP_CACHE_PATH.'/css/' ); }

if( !class_exists( 'RS_Head_Cleaner_Uninstall' ) ) {

	class RS_Head_Cleaner_Uninstall {

		/**
		 * RS Head Cleaner Uninstall Class
		 */

		function __construct() {
			$this->uninstall_plugin();
		}

		function uninstall_plugin() {
			/* Options to Delete */
			$rshcp_options = array( 'rshcp_options', 'rshcp_admin_notices', 'rs_head_cleaner_version' );
			$rshcl_options = array( 'rshcp_options', 'rshcp_admin_notices', 'rs_head_cleaner_lite_version', 'rshcl_admin_notices' );
			if( TRUE === RSHCP_LITE ) { $rshcp_options = $rshcl_options; }
			foreach( $rshcp_options as $i => $rshcp_option ) { delete_option( $rshcp_option ); }
			$rshcp_cache_path_old	= str_replace( '/cache/'.RSHCP_CACHE_DIR_NAME.'/', '/'.RSHCP_CACHE_DIR_NAME.'-cache/', RSHCP_CACHE_PATH );
			$rshcp_css_path_old		= str_replace( '/cache/'.RSHCP_CACHE_DIR_NAME.'/', '/'.RSHCP_CACHE_DIR_NAME.'-cache/', RSHCP_CSS_PATH );
			$rshcp_js_path_old		= str_replace( '/cache/'.RSHCP_CACHE_DIR_NAME.'/', '/'.RSHCP_CACHE_DIR_NAME.'-cache/', RSHCP_JS_PATH );
			$rshcp_dirs_all = array(
				array( 'css' => RSHCP_CSS_PATH, 'js' => RSHCP_JS_PATH, 'cache' => RSHCP_CACHE_PATH ), 
				array( 'css' => $rshcp_css_path_old, 'js' => $rshcp_js_path_old, 'cache' => $rshcp_cache_path_old ), 
				);
			foreach( $rshcp_dirs_all as $i => $rshcp_dirs ) {
				foreach( $rshcp_dirs as $d => $dir ) {
					if ( is_dir( $rshcp_dirs[$d] ) ) {
						$filelist = $this->scandir( $rshcp_dirs[$d] );
						foreach( $filelist as $f => $filename ) {
							$file = $rshcp_dirs[$d].$filename;
							if ( is_file( $file ) ){
								@chmod( $file, 0775 );
								@unlink( $file );
								if ( file_exists( $file ) ) { @chmod( $file, 0644 ); }
							}
						}
						@chmod( $rshcp_dirs[$d], 0775 );
						@rmdir( $rshcp_dirs[$d] );
						if ( file_exists( $rshcp_dirs[$d] ) ) { @chmod( $rshcp_dirs[$d], 0755 ); }
					}
				}
			}
		}

		function scandir( $dir ) {
			clearstatcache();
			$dot_files = array( '..', '.' );
			$dir_contents_raw = scandir( $dir );
			$dir_contents = array_values( array_diff( $dir_contents_raw, $dot_files ) );
			return $dir_contents;
		}

	}

}

if( class_exists('RS_Head_Cleaner_Uninstall') ) {
	$RSHC_Uninstall = new RS_Head_Cleaner_Uninstall();
}

?>