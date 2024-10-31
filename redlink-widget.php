<?php
/*
Plugin Name: REDlink Widget
Description: Widżet REDlink umożliwia szybkie zapisywanie użytkowników na listę adresową REDlink. 
Author: Vercom Sp. z o.o.
Version: 1.0
Author URI: http://www.vercom.pl/
Plugin URI: http://www.redlink.pl/
*/

/**
 * Set up the autoloader.
 */

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/lib/'));

spl_autoload_extensions('.class.php');

if (! function_exists('buffered_autoloader')) {
	
	function buffered_autoloader ($c) {

		try {
		
			spl_autoload($c);
			
		} catch (Exception $e) {
			
			$message = $e->getMessage();
			
			return $message;
		}
	}
}

spl_autoload_register('buffered_autoloader');

/**
 * Get the plugin object. All the bookkeeping and other setup stuff happens here.
 */

$ns_redlink_plugin = NS_REDLINK_Plugin::get_instance();

register_deactivation_hook(__FILE__, array(&$ns_redlink_plugin, 'remove_options'));
