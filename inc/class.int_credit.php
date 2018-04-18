<?php

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

/**
 * Coupon and Credit
 */
class s2_int_credit
{

  private static $instance;

  public static function get_instance() {
  	if ( ! isset( self::$instance ) ) {
  		self::$instance = new self();
  	}

  	return self::$instance;
  }


    function __construct()  {

    	//add_action( 'wp_footer', array($this, 'footer_data') );
      add_action( 'user_register', [$this, 'check_and_add_coupon'], 100, 1 );
      add_shortcode('coupon_user_manage', [$this, 'coupon_user_create_func']);
      add_action( 'wp_enqueue_scripts', array($this, 'coupon_scripts') );
      add_action( 'template_redirect', array($this, 'add_coupon_user') );
      add_action('manage_users_columns', [$this, 'modify_user_columns']);
      add_action('manage_users_custom_column', [$this, 'modify_user_columns_content'], 10, 3);
      add_action( 'template_redirect', array($this, 'update_user_eot_time') );
    	add_action( 'template_redirect', [$this, 'user_update_occured']);
			add_action( 'template_redirect', [$this, 'update_password_user']);
			add_action( 'wp_ajax_deliver_user_data', [$this, 'deliver_user_data'] );
	    add_action( 'wp_ajax_nopriv_deliver_user_data', [$this, 'deliver_user_data'] );

    }

		public function deliver_user_data() {


			$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);

			$s2member_credit_user_created = ( is_array($s2member_credit_user_created) ? $s2member_credit_user_created : [] );

			if (empty($s2member_credit_user_created)) {
				echo json_encode(self::emptyDataReturn());
				wp_die();
			}

			if (!empty($_POST['search']['value'])) {
				echo json_encode(self::search_user($_POST['search']['value']));;
				wp_die();
			}

			if (!isset($_POST['order'][0]) && empty($_POST['order'][0])) {
				echo json_encode(self::emptyDataReturn());
				wp_die();
			}

			$order_column = [
				'column_name' => $_POST['column_data'][$_POST['order'][0]['column']]['data'],
				'order' => $_POST['order'][0]['dir']
			];

			$start_val = $_POST['start'];
			$s2member_credit_user_created = self::sort_ids($s2member_credit_user_created, $order_column);
			if (empty($s2member_credit_user_created)) self::emptyReturnJSON();
			$data_array = [];
			$data_array['recordsTotal'] = count($s2member_credit_user_created);
			$data_array['recordsFiltered'] = $data_array['recordsTotal'];
			$request_length = $_POST['length'];

			$loop_count = $start_val + $request_length;
			$loop_init = $start_val;
			$user_ids = [];

			for ($i = $loop_init; $i < $loop_count; $i++)
				$user_ids[] = (int) $s2member_credit_user_created[$i];

				if (empty($user_ids)) {
					echo json_encode(self::emptyDataReturn());
					wp_die();
				}

			$data_array['data'] = self::getUsersData($user_ids);

			echo json_encode($data_array);
			wp_die();
		}

		public static function emptyReturnJSON() {
			echo json_encode(self::emptyDataReturn());
			wp_die();
		}

		public static function sort_ids($user_ids = [], $order_column = []) {

			if (empty($user_ids)) self::emptyReturnJSON();
			if (empty($order_column)) self::emptyReturnJSON();
			global $wpdb;

			$user_ids_to_sort = $user_ids;

			$order_meta_key = ($order_column['column_name'] == "s2member_auto_eot_time" ? $wpdb->prefix.$order_column['column_name'] : $order_column['column_name']);
			$user_ids = get_users([
				'include' => $user_ids,
				'fields' => 'ID',
				'order' => $order_column['order'],
				'meta_key' => $order_meta_key,
				'orderby'  => 'meta_value',
			]);


			if (empty($user_ids)) self::emptyReturnJSON();

			array_walk($user_ids, function(&$item, $key) {
				$item = (int) $item;
			});

			if (count($user_ids) < count($user_ids_to_sort)) {

				if ($order_column['column_name'] == "s2member_auto_eot_time") {
					$empty_eot_ids = array_diff($user_ids_to_sort, $user_ids);

					if ($order_column['order'] == 'asc')
						$user_ids = array_merge($empty_eot_ids, $user_ids);
					else
						$user_ids = array_merge($user_ids, $empty_eot_ids);

				}	else
						$user_ids = array_merge($user_ids, $user_ids_to_sort);


				$user_ids = array_unique($user_ids);
			}


			return $user_ids;
		}




		public function footer_data() {

			return;

			global $wpdb;

			$order_column = [
				'column_name' => 'wp_s2member_auto_eot_time',
				'order' => 'asc'
			];


			$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);

			d(self::sort_ids($s2member_credit_user_created, $order_column));
			//d(self::getUsersData());


			$order_column = [
				'column_name' => 'wp_s2member_auto_eot_time',
				'order' => 'desc'
			];


			$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);
d(self::getUsersData(self::sort_ids($s2member_credit_user_created, $order_column)));

    }

		public static function getUsersData($user_ids = []) {

			if (empty($user_ids)) return self::emptyDataReturn();

			$data_array = [];

			$allowed_to_increase_eot_by = get_user_meta(get_current_user_id(), 'allowed_to_increase_eot_by', true);

			foreach ($user_ids as $user_id_key => $user_id_value) {
				$single_uid = (int) $user_id_value;
				$user_data = get_user_meta($single_uid);
				$get_s2member_auto_eot_time = get_user_option('s2member_auto_eot_time', $single_uid);
				$get_s2member_auto_eot_time = ( !empty($get_s2member_auto_eot_time) ? date("F j, Y, g:i a", $get_s2member_auto_eot_time) : "" );

				$data_array[] = [
					'nickname' => $user_data['nickname'][0],
					'first_name' => $user_data['first_name'][0],
					'last_name' => $user_data['last_name'][0],
					's2member_auto_eot_time' => $get_s2member_auto_eot_time,
					'meta_email' => $user_data['meta_email'][0],
				];
			}

			if (empty($user_ids)) {
				echo json_encode(self::emptyDataReturn());
				wp_die();
			}

			return $data_array;

		}

		public static function search_user($query_value = "") {
			if (empty($query_value)) return;

			global $wpdb;
			$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);
							// The search term
				$search_term = $wpdb->_real_escape($query_value);

				$user_ids = $wpdb->get_results( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_value` LIKE '%{$search_term}%'", ARRAY_N );

				if (empty($user_ids)) return self::emptyDataReturn();

				array_walk($user_ids, function(&$item, $key) {
					$item = (int) $item[0];
				});

				$user_ids = array_intersect($user_ids, $s2member_credit_user_created);
				if (empty($user_ids)) return self::emptyDataReturn();

				$user_ids = array_unique($user_ids);

				array_walk($user_ids, function(&$item, $key) {
					$item = (int) $item;
				});

				$data_array = [];
				$data_array['recordsTotal'] = count($user_ids);
				$data_array['recordsFiltered'] = count($user_ids);
				$data_array['data'] = self::getUsersData($user_ids);


				return $data_array;
		}

		public static function emptyDataReturn() {

			return [
				'recordsTotal' => 0,
				'recordsFiltered' => 0,
				'data' => []
			];

		}


		function user_update_occured() {

			if (empty($_GET['s2_additional_credit']))
				return;

			if (empty($_GET['user_id']))
				return;

			$user_id = $_GET['user_id'];

			$capabilities = get_user_meta($user_id, 'wp_capabilities', true);
			$key_match = 0;
			$key_val = "";
			$output_array_cap = [];
			foreach ($capabilities as $key => $single_cap) {
				preg_match("/access_s2member_ccap_additional/", $key, $output_array_cap);

				if (!empty($output_array_cap)) {
					$key_val = $key;
					break;
				}

			}

			$key_val_temp = $key_val;

			if (empty($key_val))
				return;

			$key_val_split = preg_split("/access_s2member_ccap_additional_/", $key_val);
			if (count($key_val_split) <= 1)
				return;

			$key_val = (int) $key_val_split[1];

			if (empty($key_val))
				return;

			if (!array_key_exists($key_val_temp, $capabilities))
				return;

			unset($capabilities[$key_val_temp]);


			$prev_credit = get_user_meta($user_id, 's2member_unique_login_credit', true);

			$prev_credit = (int) $prev_credit;

			$key_val += $prev_credit;

			update_user_meta($user_id, 's2member_unique_login_credit', $key_val);

			update_user_meta($user_id, 'wp_capabilities', $capabilities);

				return;


		}



			public function update_password_user() {

				if (!isset($_POST['password_change_form_submit']))
					return;

				if (empty($_POST['user_id']))
					return;

				if (empty($_POST['user_password']))
					return;

				$user_id = $_POST['user_id'];

				$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);

				if (!in_array($user_id, $s2member_credit_user_created))
					return;

				$user_password = $_POST['user_password'];

				wp_set_password( $user_password, $user_id );


			}

    		public function update_user_eot_time() {
          if (!isset($_POST['eot_form_submit']))
            return;

    			if (empty($_POST['user_id']))
    				return;

					$user_id = $_POST['user_id'];

					$get_eot_safe_time = self::read_option('eot_safe_time');

					$get_eot_safe_time = (int) $get_eot_safe_time;

					if (!empty($get_eot_safe_time)) {

						$post_eot = get_user_meta(get_current_user_id(), "last_update_user_".$user_id, true);


						if (!empty($post_eot)) {

							if (time() >= $post_eot)
								delete_user_meta(get_current_user_id(), "last_update_user_".$user_id);
							else
								return;

						}

					}



					$s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);

					if (!in_array($user_id, $s2member_credit_user_created))
						return;

    			$user_meta = get_user_meta($user_id);

          $post_eot = get_user_meta(get_current_user_id(), 'allowed_to_increase_eot_by', true);

          $get_s2member_auto_eot_time = (int) get_user_option('s2member_auto_eot_time', $user_id);
          $get_s2member_auto_eot_time = $get_s2member_auto_eot_time - time();
          $s2member_user_level_need_update = false;

          $post_eot = (int) $post_eot;
          $post_eot = time() + $post_eot * 86400;
          if ($get_s2member_auto_eot_time < 0) {
          	$post_eot = $post_eot;
              $s2member_user_level_need_update = true;
          } else {
          	$post_eot += $get_s2member_auto_eot_time;
          }
          //$post_eot += $get_s2member_auto_eot_time;

					$get_coupon = (int) get_user_meta(get_current_user_id(), 's2member_unique_login_credit', true);

					if ($get_coupon <= 0)
						return;

          update_user_option( $user_id, 's2member_auto_eot_time', $post_eot);
          if ($s2member_user_level_need_update == 'true') {
          	$user = new WP_User($user_id);
          	if (current_user_can('access_s2member_ccap_upgraded_credits')) {
          		$user->set_role("s2member_level2");
          	} else {
			$user->set_role("s2member_level1");
          	}
          }
          if (current_user_can('access_s2member_ccap_upgraded_credits') && get_user_field('s2member_access_level', $user_id) !== '2') {
          	$user = new WP_User($user_id);
          	$user->set_role("s2member_level2");
          }

          $get_coupon = $get_coupon - 1;
          update_user_meta(get_current_user_id(), "s2member_unique_login_credit", $get_coupon);

					if (!empty($get_eot_safe_time))
						update_user_meta(get_current_user_id(), "last_update_user_".$user_id, (time()+($get_eot_safe_time*60)));


    				return;
    		}


    public function modify_user_columns_content($value, $column_name, $user_id) {
        $user = get_userdata( $user_id );
      if ( 'meta_email' == $column_name )
        return get_user_meta($user_id, 'meta_email', true);

        return $value;
    }

    public function modify_user_columns($column_headers) {
      $column_headers['meta_email'] = 'Meta Email';

      return $column_headers;
    }

    public function coupon_scripts() {

			wp_enqueue_style( 'coupon_scripts-datatable-style', 'https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css' );
			wp_register_script( 'coupon_scripts-datatable-script', 'https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'coupon_scripts-datatable-script' );

			wp_enqueue_style( 'coupon_scripts-select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
			wp_register_script( 'coupon_scripts-select2-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'coupon_scripts-select2-script' );


      wp_enqueue_style( 'coupon_scripts-script-style', s2member_unique_login_PLUGIN_URL.'css/style.css' );
			//wp_enqueue_style( 'coupon_scripts-materialize-style', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.1/css/materialize.min.css' );

			wp_register_script( 'coupon_scripts-js-script', s2member_unique_login_PLUGIN_URL.'js/jsscript.js', array( 'jquery' ), '', true );

			wp_localize_script( 'coupon_scripts-js-script', 'plugin_data', array( 'ajax_url' => admin_url('admin-ajax.php') ));

			wp_enqueue_script( 'coupon_scripts-js-script' );


    }

    public function check_and_add_coupon($user_id) {

      $get_coupon = explode("|", get_user_meta($user_id, 'wp_s2member_custom', true));
      $get_coupon = array_filter($get_coupon);

      if (empty($get_coupon))
        return;

      $get_coupon = (int) $get_coupon[1];

      if (empty($get_coupon))
        return;

        $prev_credit = get_user_meta($user_id, 's2member_unique_login_credit', true);

        $prev_credit = (int) $prev_credit;

        $get_coupon += $prev_credit;

        update_user_meta($user_id, 's2member_unique_login_credit', $get_coupon);


    }




    public function coupon_user_create_func($atts) {
				if (current_user_can('access_s2member_ccap_upgraded_credits')) {
					$atts = shortcode_atts( array(
						'level' => 'level2',
						'coupon_page' => "",
						'use_meta_email' => 0,
          					'eot' => 30
					), $atts, 'coupon_user_manage' );
				} else {
					$atts = shortcode_atts( array(
						'level' => 'level1',
						'coupon_page' => "",
						'use_meta_email' => 0,
          					'eot' => 30
					), $atts, 'coupon_user_manage' );
				}

				ob_start();

				// if (!empty($atts['coupon_page']))
				// 	$coupons_array = self::coupons_array($atts['coupon_page']);

					// if (!empty($atts['coupon_page']))
					// 	$coupons_array = self::coupons_array_from_all_pages();

          $get_coupon = get_user_meta(get_current_user_id(), 's2member_unique_login_credit', true);

          $get_coupon = (int) $get_coupon;

        update_user_meta(get_current_user_id(), 'allowed_to_increase_eot_by', $atts['eot']);

				if ($get_coupon > 0 || current_user_can('administrator'))
				  include s2member_unique_login_PLUGIN_DIR."template".DIRECTORY_SEPARATOR."add_user.php";

        include s2member_unique_login_PLUGIN_DIR."template".DIRECTORY_SEPARATOR."display_users.php";

				$output = ob_get_clean();
				return $output;
			}

      public static function Error() {

        if (empty($_POST['error']))
          return;

          $html = "<div class='error_form' style='color: red'>";

          $html .= $_POST['error'];

          $html .= "</div>";

          return $html;

      }


      public function add_coupon_user() {

  			if (!isset($_POST['submit']))
  				return;

					//d($_POST);

  				if ((empty($_POST['uname'])) || (empty($_POST['password'])) || (empty($_POST['email']))) {
  					$_POST['error'] = "*Please fill up all required fields.";
  					return;
  				}

  					$email = $_POST["email"];
  					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  					$_POST['error'] = "Invalid email format.";
  					return;
  				}


  					$user_id = username_exists( $_POST['uname'] );

  					if (!empty($user_id)) {
  						$_POST['error'] = "User exists!";
  						return;

  					}

            $level = $_POST['s2_level'];

  					try {

  						if (empty($_POST['use_meta_email']))
  							$user_id = wp_create_user( $_POST['uname'], $_POST['password'], $_POST['email'] );
  						else {
  							$user_id = wp_create_user( $_POST['uname'], $_POST['password'], "" );

  							update_user_meta($user_id, 'meta_email', $_POST['email']);

  						}


  					} catch (Exception $e) {
  						$_POST['error'] = "Error occured!";
  						return;

  					}

  					$user_id = wp_update_user( array( 'ID' => $user_id,
  						'display_name' => $_POST['fname']." ".$_POST['lname'],
  						'first_name' => $_POST['fname'],
  						'last_name' => $_POST['lname']
  					 ) );

            if (current_user_can('access_s2member_ccap_upgraded_credits')) {
  					 	$update_user = wp_update_user(wp_slash(array( 'ID' => $user_id,
   							'display_name' => $_POST['fname']." ".$_POST['lname'],
   							'first_name' => $_POST['fname'],
   							'last_name' => $_POST['lname'],
  							'role' => 's2member_'.(empty($_POST['s2_level']) ? "level2" : $_POST['s2_level'])
   					 	)));
                                    } else {
                                    	$update_user = wp_update_user(wp_slash(array( 'ID' => $user_id,
   							'display_name' => $_POST['fname']." ".$_POST['lname'],
   							'first_name' => $_POST['fname'],
   							'last_name' => $_POST['lname'],
  							'role' => 's2member_'.(empty($_POST['s2_level']) ? "level1" : $_POST['s2_level'])
   					 	)));
                                    }

  					 update_user_meta($user_id, 'created_by_user', get_current_user_id());

             $get_coupon = (int) get_user_meta(get_current_user_id(), 's2member_unique_login_credit', true);
             $get_coupon = $get_coupon - 1;
  					 update_user_meta(get_current_user_id(), "s2member_unique_login_credit", $get_coupon);

             $s2member_credit_user_created = get_user_meta(get_current_user_id(), 's2member_credit_user_created', true);
             $s2member_credit_user_created = ( is_array($s2member_credit_user_created) ? $s2member_credit_user_created : [] );
             array_push($s2member_credit_user_created, $user_id);
             update_user_meta(get_current_user_id(), "s2member_credit_user_created", $s2member_credit_user_created);

             $post_eot = get_user_meta(get_current_user_id(), 'allowed_to_increase_eot_by', true);
             $post_eot = (int) $post_eot;
             $post_eot = time() + $post_eot * 86400;
             update_user_option( $user_id, 's2member_auto_eot_time', $post_eot);

  					 return;
  		}

			public static function read_option($id){
		   $titan = TitanFramework::getInstance( 's2member_unique_login_opts' );
		   return $titan->getOption($id);
		  }

			public static function sort_ids_sql($user_ids = [], $order_column = []) {

				if (empty($user_ids)) self::emptyReturnJSON();
				if (empty($order_column)) self::emptyReturnJSON();
				global $wpdb;
				$user_ids_to_sort = $user_ids;
				$order_meta_key = ($order_column['column_name'] == "s2member_auto_eot_time" ? $wpdb->prefix.$order_column['column_name'] : $order_column['column_name']);

				$query = "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = '{$order_meta_key}' ORDER BY `{$wpdb->prefix}usermeta`.`meta_value` {$order_column['order']}";
				$user_ids = $wpdb->get_results( $query, ARRAY_N );

				array_walk($user_ids_to_sort, function(&$item, $key) {
					$item = (int) $item;
				});

				array_walk($user_ids, function(&$item, $key) {
					$item = (int) $item[0];
				});
				$users_array = [];

				for ($i=0; $i < count($user_ids); $i++)
					if (in_array($user_ids[$i], $user_ids_to_sort)) $users_array[] = $user_ids[$i];


				if (count($users_array) < count($user_ids_to_sort)) {

					if ($order_column['column_name'] == "s2member_auto_eot_time")
						$users_array = array_merge($user_ids_to_sort, $users_array);
					else
						$users_array = array_merge($users_array, $user_ids_to_sort);

					$users_array = array_unique($users_array);
				}


				if (empty($users_array)) self::emptyReturnJSON();

				return $users_array;
			}

}


?>
