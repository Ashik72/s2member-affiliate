<?php
/*
Plugin Name: s2Member Affiliate
Plugin URI: https://www.upwork.com/companies/~01caf98798b24dd9af
Description: s2Member Affiliate Plugin
Version: 0.0.1
Author: Ashik72
Author URI: https://www.upwork.com/companies/~01caf98798b24dd9af
License: GPLv2 or later
Text Domain: s2member_affiliate
*/

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

if (!class_exists('Kint') && file_exists(dirname( __FILE__ ) . '/kint.php'))
	include_once ( dirname( __FILE__ ) . '/kint.php' );


if (!function_exists('d')) {

	function d($data) {

		ob_start();
		var_dump($data);
		$output = ob_get_clean();
		//echo $output;
		echo "";

	}
}

define( 's2member_affiliate_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 's2member_affiliate_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'DS' ) ) define( 'DS', DIRECTORY_SEPARATOR );


if ( in_array( 's2member/s2member.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	// Brace Yourself
	require_once( plugin_dir_path( __FILE__ ) . 'plugin_loader.php' );

	// Start the Engine
	//add_action( 'plugins_loaded', array( 'MYPLUG', 'get_instance' ) );

}
