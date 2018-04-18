<?php

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

	if (file_exists(__DIR__.'/vendor/autoload.php'))
		require __DIR__.'/vendor/autoload.php';

  require_once( 'titan-framework-checker.php' );
  require_once( 'titan-framework-options.php' );

// require_once( plugin_dir_path( __FILE__ ) . '/inc/class.s2Member_check_login.php' );
// require_once( plugin_dir_path( __FILE__ ) . '/inc/class.coupon_and_credit.php' );
//require_once( plugin_dir_path( __FILE__ ) . '/inc/class.int_credit.php' );
require_once( plugin_dir_path( __FILE__ ) . '/inc/class.affiliate.php' );


  add_action( 'plugins_loaded', function () {
  	//s2Member_check_login::get_instance();
		//s2UL_Coupon_Credit::get_instance();
		s2member_affiliate::get_instance();
  } );

 ?>
