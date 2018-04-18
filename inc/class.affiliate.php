<?php

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

/**
 * s2member_affiliate
 */
class s2member_affiliate
{

  private static $instance;

  public static function get_instance() {
  	if ( ! isset( self::$instance ) ) {
  		self::$instance = new self();
  	}

  	return self::$instance;
  }


    function __construct()  {

			add_action( 'wp_enqueue_scripts', array($this, 'coupon_scripts') );

			add_action( 'wp_ajax_load_users_id', [$this, 'load_users_id'] );
	    add_action( 'wp_ajax_nopriv_load_users_id', [$this, 'load_users_id'] );

			add_action( 'template_redirect', [$this, 'debug_user'] );

			add_action( 'user_register', [$this, 'check_and_update_eot'], 100, 1 );

			add_shortcode( 'referral_log', [$this, 'referral_log'] );


    }

		public function referral_log($atts) {

			if (!current_user_can('administrator')) return;

			$data = shortcode_atts( array(
				'foo' => 'something',
				'bar' => 'something else',
				), $atts );
				global $wpdb;

				ob_start();
				include s2member_affiliate_PLUGIN_DIR."template".DS."referral_log.php";
				$output = ob_get_clean();
				return $output;
		}

		public function check_and_update_eot($user_id) {

			global $wpdb;

			$get_eot_time = explode("|", get_user_meta($user_id, $wpdb->prefix.'s2member_custom', true));
      $get_eot_time = array_filter($get_eot_time);

      if (empty($get_eot_time))
        return;

      $get_eot_time = (int) $get_eot_time[1];

      if (empty($get_eot_time))
        return;

			$custom_field_data = get_user_meta($user_id, $wpdb->prefix.'s2member_custom_fields', true);
			$refferal_id = intval($custom_field_data['refferal_id']);

			//d([$refferal_id, $get_eot_time]);

			if(($eot_time = get_user_option('s2member_auto_eot_time', $refferal_id)))
					update_user_option($refferal_id, 's2member_auto_eot_time', $eot_time + ($get_eot_time * DAY_IN_SECONDS));


					return;
					$u_meta = get_user_meta(intval($user_id));

					$gross_amt = maybe_unserialize($u_meta['wp_s2member_ipn_signup_vars'][0])['mc_gross'];
					$gross_amt = floatval($gross_amt);
					$prev_amount = get_user_meta($user_id, 'total_spend_val', true);
					$prev_amount = floatval($prev_amount);
					$prev_amount += $gross_amt;
					update_user_meta($user_id, 'total_spend_val', maybe_unserialize($u_meta['wp_s2member_ipn_signup_vars'][0]));
			//file_put_contents(s2member_affiliate_PLUGIN_DIR."log".DS.time()."_udata.txt", json_encode([$get_eot_time, $custom_field_data]));
		}

		public function debug_user() {

			if (!current_user_can('administrator')) return;
			if (empty($_GET['debug'])) return;
			if (empty($_GET['user_id'])) return;

			$u_meta = get_user_meta(intval($_GET['user_id']));

			d($u_meta);
			d(maybe_unserialize($u_meta['wp_s2member_ipn_signup_vars'][0])['mc_gross']);
			d(maybe_unserialize($u_meta['wp_s2member_ipn_signup_vars'][0]));

			d(s2member_affiliate_PLUGIN_DIR);
			d($this->check_and_update_eot(3317));
			wp_die();
		}

		public function load_users_id() {
			$users = get_users([ 'fields' => [ 'ID', 'user_login' ] ]);

			if (empty($users)) wp_die();

			$opt_string = "";

			for ($i=0; $i < count($users); $i++) {
				$user = $users[$i];
				$opt_string .= '<option value="'.$user->ID.'">'.$user->user_login.'</option>';
			}

			echo json_encode($opt_string);
			wp_die();
		}

		public static function read_option($id){
		 $titan = TitanFramework::getInstance( 's2member_affiliate_opts' );
		 return $titan->getOption($id);
		}

		public function coupon_scripts() {

			wp_enqueue_style( 'coupon_scripts-datatable-style', 'https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css' );
			wp_register_script( 'coupon_scripts-datatable-script', 'https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'coupon_scripts-datatable-script' );

			wp_enqueue_style( 'coupon_scripts-select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
			wp_register_script( 'coupon_scripts-select2-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'coupon_scripts-select2-script' );


			wp_enqueue_style( 'coupon_scripts-script-style', s2member_affiliate_PLUGIN_URL.'css/style.css' );
			//wp_enqueue_style( 'coupon_scripts-materialize-style', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.1/css/materialize.min.css' );

			wp_register_script( 'coupon_scripts-js-script', s2member_affiliate_PLUGIN_URL.'js/aff.js', array( 'jquery' ), '', true );

			wp_localize_script( 'coupon_scripts-js-script', 'aff_data', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'unique_refferal_id' => self::read_option('referral_unique_id_field')
		 	));

			wp_enqueue_script( 'coupon_scripts-js-script' );


		}



}


?>
