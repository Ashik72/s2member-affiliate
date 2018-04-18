<?php

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

/**
 * Coupon and Credit
 */
class s2UL_Coupon_Credit
{

  private static $instance;

  public static function get_instance() {
  	if ( ! isset( self::$instance ) ) {
  		self::$instance = new self();
  	}

  	return self::$instance;
  }


    function __construct()  {

      add_action( 'init', array($this, 'add_user_coupon') );
      add_action( 'wp_footer', array($this, 'footer_data') );
      add_filter('c_ws_plugin__s2member_pro_sc_gift_codes_content', [$this, 'mod_c_ws_plugin__s2member_pro_sc_gift_codes_content'], 10, 2);

			add_action( 'template_redirect', array($this, 'update_user_eot_time') );

			add_filter('ws_plugin__s2member_pro_sc_stripe_form', [$this, 'ws_plugin__s2member_pro_sc_stripe_form_filter'], 10, 2);

			add_shortcode('coupon_user_create', [$this, 'coupon_user_create_func']);

			add_action( 'wp_enqueue_scripts', array($this, 'coupon_scripts') );
			add_action( 'template_redirect', array($this, 'add_coupon_user') );
			add_action('manage_users_columns', [$this, 'modify_user_columns']);
			add_action('manage_users_custom_column', [$this, 'modify_user_columns_content'], 10, 3);

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

		public static function Error() {

			if (empty($_POST['error']))
				return;

				$html = "<div class='error_form' style='color: red'>";

				$html .= $_POST['error'];

				$html .= "</div>";

				return $html;

		}

		public function coupon_scripts() {

			wp_enqueue_style( 'coupon_scripts-script-style', s2member_unique_login_PLUGIN_URL.'css/style.css' );

		}

		public function add_coupon_user() {

			if (!isset($_POST['submit']))
				return;

				if ((empty($_POST['uname'])) || (empty($_POST['password'])) || (empty($_POST['email'])) || (empty($_POST['coupon']))) {
					$_POST['error'] = "*Please fill up all required fields.";
					return;
				}

					$email = $_POST["email"];
					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$_POST['error'] = "Invalid email format.";
					return;
				}

				$coupon_class = new c_ws_plugin__s2member_pro_coupons();

				$isValid = $coupon_class->valid_coupon($_POST['coupon'], []);

					if (empty($isValid)) {
						$_POST['error'] = "Invalid coupon.";
						return;
					}

					d($_POST);

					$user_id = username_exists( $_POST['uname'] );

					if (!empty($user_id)) {
						$_POST['error'] = "User exists!";
						return;

					}

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

					 $update_user = wp_update_user(wp_slash(['ID' => $user_id, 'role' => 's2member_'.$level]));

					 $update_user = wp_update_user(wp_slash(array( 'ID' => $user_id,
 						'display_name' => $_POST['fname']." ".$_POST['lname'],
 						'first_name' => $_POST['fname'],
 						'last_name' => $_POST['lname'],
						'role' => 's2member_'.(empty($_POST['s2_level']) ? "level1" : $_POST['s2_level'])
 					 )));

					 update_user_meta($user_id, 'created_by_user', get_current_user_id());

					 update_user_meta($user_id, "coupon_used", $_POST['coupon']);

					 $coupon_class->update_uses($_POST['coupon'], $user_id);

					 d(get_user_meta($user_id));


					 return;
		}


		public function coupon_user_create_func($atts) {

				$atts = shortcode_atts( array(
					'level' => 'level1',
					'coupon_page' => "",
					'use_meta_email' => 0
				), $atts, 'coupon_user_create' );

				ob_start();

				// if (!empty($atts['coupon_page']))
				// 	$coupons_array = self::coupons_array($atts['coupon_page']);

					if (!empty($atts['coupon_page']))
						$coupons_array = self::coupons_array_from_all_pages();


				include s2member_unique_login_PLUGIN_DIR."template".DIRECTORY_SEPARATOR."add_user.php";

				$output = ob_get_clean();
				return $output;
			}

		public function ws_plugin__s2member_pro_sc_stripe_form_filter($content, $arg) {
			//d($arg);
			return $content;
		}

    public function mod_c_ws_plugin__s2member_pro_sc_gift_codes_content($content, $vars) {
      $get_users = get_users([ 'fields' => 'ID' ]);
      $refined_uid_coupons = [];
      $search = 0;

      ob_start();


      foreach ($get_users as $key => $uid) {

        $uid = (int) $uid;

        $get_meta = get_user_meta($uid, 'coupon_used', true);

        if (empty($get_meta))
          continue;

          $search = preg_grep("/".$get_meta."/", explode("\n", $content));

          if (!empty($search)) {

            $refined_uid_coupons[$key]['user_id'] = $uid;
            $refined_uid_coupons[$key]['coupon_used'] = $get_meta;

          }

      }


      ?>


      <table class="ws-plugin--s2member-gift-codes table table-condensed table-striped table-hover">
    <thead>
    <tr><th class="-code">User</th><th class="-status">Coupon Used</th>
				<th class="-status">EOT</th><th class="-status">Add Extra Time</th>
		</tr>
    </thead>
    <tbody>

      <?php
      foreach ($refined_uid_coupons as $key => $single_refined_uid_coupons) {


        $user_info = get_userdata($single_refined_uid_coupons['user_id']);



      ?>

    <tr class="-status-unused"><td class="-code"><?php

    _e($user_info->user_login ." | "."(User ID: ".$single_refined_uid_coupons['user_id'].")" );

    ?></td>

      <td class="-status"><?php _e($single_refined_uid_coupons['coupon_used']); ?></td>

<td class="-status"><?php

$get_s2member_auto_eot_time = get_user_option('s2member_auto_eot_time', $single_refined_uid_coupons['user_id']);

_e( !empty($get_s2member_auto_eot_time) ? date("F j, Y, g:i a", $get_s2member_auto_eot_time) : "" );
?></td>
<td class="-status">


	<form method="post" class='eot_form'>

		<?php

		if (s2Member_check_login::read_option('user_define_eot'))
			echo 'Extra Time (in days): <input type="text" name="eot_time_days">';

		 ?>


	<input type="hidden" name="user_id" value="<?php _e($single_refined_uid_coupons['user_id']); ?>"><br>

	<span>Use Coupon : </span><select name="use_coupon">

	<?php

	$coupons_arr = self::coupons_array();

	if (empty($coupons_arr))
		return;

	if (!is_array($coupons_arr))
		return;

		foreach ($coupons_arr as $key => $coupon_single) {
			if (!isset($coupon_single['code']))
				continue;

			if (empty($coupon_single['code']))
				continue;

				_e('<option value="'.$coupon_single['code'].'">'.$coupon_single['code'].'</option>');

		}

	 ?>
	</select><br>
	<input type="submit" name="add_eot" value="Add">

	<style type="text/css" scoped>
			.eot_form input {
			margin-bottom: 0.5rem;
			}
	</style>

	</form>



</td>
		</tr>
      <?php
      }
      ?>
      <tbody>
      </table>


      <?php

			//d(class_exists('c_ws_plugin__s2member_pro_coupons'));

			$c_ws_plugin__s2member_pro_coupons_ob = new c_ws_plugin__s2member_pro_coupons();
			$gift_opt_key = $c_ws_plugin__s2member_pro_coupons_ob->gift_option_key('GC00K24JF7OLUGIR43425E');
			// d(get_option($gift_opt_key));
			//
			// $user_meta = get_user_meta(23);
			// d($user_meta);
			// d(get_user_option('s2member_auto_eot_time', 23));
			// d($c_ws_plugin__s2member_pro_coupons_ob->valid_coupon("GC0K38KEQFOLUGIR4XHWYA", ""));
			// d(get_the_ID());
			// $user_meta = get_user_meta(get_current_user_id(), 'wp_s2m_gcs_40_0cb6d3d3cfdb4a6dad9257463ef57db1');
			// d($user_meta);

      $content_extra = ob_get_clean();

      return $content.$content_extra;
    }

		public function update_user_eot_time() {


			if (empty($_POST['user_id']))
				return;

			if (empty($_POST['use_coupon']))
				return;

			$use_coupon = $_POST['use_coupon'];

			$user_id = $_POST['user_id'];

			$user_meta = get_user_meta($user_id);

			$one_time_increase = s2Member_check_login::read_option('one_time_increase');

			if ($one_time_increase) {

				$one_time_increase_stat = get_user_meta($user_id, 'one_time_increase_stat', true);

				if (!empty($one_time_increase_stat))
					return;
			}


			if (empty($user_meta['coupon_used']))
				return;

			if (empty($user_meta['coupon_used'][0]))
				return;

			$coupon_used = $user_meta['coupon_used'][0];

			$current_user_meta = get_user_meta(get_current_user_id());
			$current_page_id = get_the_ID();
			$the_coupons_arr = [];
			$string_to_search = "wp_s2m_gcs_".$current_page_id;
			$output_array = [];

			foreach ($current_user_meta as $key => $current_user_meta_single) {
				preg_match("/{$string_to_search}/", $key, $output_array);

				if (empty($output_array))
					continue;

				$the_coupons_arr = $current_user_meta_single;
				break;

			}

			$the_coupons_arr = ( (count($the_coupons_arr) == 1) ? $the_coupons_arr[0] : $the_coupons_arr );

			$the_coupons_arr = maybe_unserialize($the_coupons_arr);

			if (!array_key_exists($coupon_used, $the_coupons_arr))
				return;

				global $wpdb;

				// d($user_meta);
				// d($wpdb->prefix);

				if (s2Member_check_login::read_option('user_define_eot')) {

					$max_eot = s2Member_check_login::read_option('max_eot_time_define');
					$max_eot = time() + $max_eot * 86400;

					$post_eot = (int) $_POST['eot_time_days'];
					$post_eot = time() + $post_eot * 86400;

					if ($post_eot > $max_eot)
						$post_eot = $post_eot - ($post_eot-$max_eot);

					update_user_option( $user_id, 's2member_auto_eot_time', $post_eot);

					$one_time_increase = s2Member_check_login::read_option('one_time_increase');
					$one_time_increase_stat = get_user_meta($user_id, 'one_time_increase_stat', true);

					if ($one_time_increase)
						update_user_meta($user_id, 'one_time_increase_stat', 1);

						self::s2_user_apply_coupon($user_id, $use_coupon);

					return;
				}

				$max_eot = s2Member_check_login::read_option('max_eot_time_define');
				$max_eot_time = $max_eot * 86400;

				$max_eot = time() + $max_eot * 86400;

				$s2member_last_payment_time = $user_meta[$wpdb->prefix.'s2member_last_payment_time'][0];
				$s2member_eot_time = get_user_option('s2member_auto_eot_time', $user_id);

				if (empty($s2member_eot_time))
					$new_time = $max_eot;
				else
					$new_time = $s2member_eot_time + $max_eot_time;

				update_user_option( $user_id, 's2member_auto_eot_time', $new_time);

				if ($one_time_increase)
					update_user_meta($user_id, 'one_time_increase_stat', 1);

				self::s2_user_apply_coupon($user_id, $use_coupon);

				return;
		}

		public static function s2_user_apply_coupon($user_id = "", $coupon = "", $level = "") {

			if (empty($user_id))
				return;

			if (empty($coupon))
				return;

			if(!class_exists('c_ws_plugin__s2member_pro_coupons'))
				return;

				$coupon_class = new c_ws_plugin__s2member_pro_coupons();

				$isValid = $coupon_class->valid_coupon($coupon, []);


				if (empty($isValid))
					return;

				$coupon_class->update_uses($coupon, $user_id);
				update_user_meta($user_id, "coupon_used", $coupon);
					//wp_s2member_paid_registration_times
					if (!empty($level)) {
						$update_user = wp_update_user(wp_slash(['ID' => $user_id, 'role' => 's2member_'.$level]));
						return;
					}

					$wp_s2member_paid_registration_times = get_user_meta($user_id, 'wp_s2member_paid_registration_times', true);

					if (empty($wp_s2member_paid_registration_times) || !is_array($wp_s2member_paid_registration_times))
						return;

						$level = "";

						foreach ($wp_s2member_paid_registration_times as $level_key => $value) {
							$level = $level_key;
						}

						if (empty($level))
							return;

						$isLevel = "";

						preg_match("/level/", $level, $isLevel);

						if (empty($isLevel))
							return;

							$update_user = wp_update_user(wp_slash(['ID' => $user_id, 'role' => 's2member_'.$level]));

							return;
		}


    public function footer_data() {

			//self::s2_user_apply_coupon(9, "GC0K5U3VQ1OLUGIR48K4VV", "level3");
			//self::s2_user_apply_coupon(20, "GC0K5U3VQ1OLUGIR48K4VV");
			//d(get_user_meta(20));

			//self::s2_user_apply_coupon(9, "GC0K1CT226OLUGIR3WZFA2");
			// d(get_user_meta(20));
			// update_user_meta(9, "coupon_used", "GC0KAFIL3LOLUGIR46H6HJ");

			//d(get_user_meta(get_current_user_id()));

			//d(self::coupons_array_from_all_pages());

			return;

			d(get_user_meta(24));

			//wp_update_user(wp_slash(['ID' => 9, 'role' => 's2member_level2'])); // OK. Now send this array for an update.

			d(get_user_meta(9));

			d(get_user_meta(10));

			d(get_user_meta(8));


			$ccap_times = get_user_meta(8, 'wp_s2member_paid_registration_times', true);

			d($ccap_times);

      //d(has_filter('c_ws_plugin__s2member_pro_sc_gift_codes_content'));

      if (empty($_GET['data_show']))
        return;

        $_GET['data_show'] = 'a:13:{i:0;s:1:"1";i:1;s:1:"8";i:2;s:2:"10";i:3;s:1:"9";i:4;s:2:"11";i:5;s:2:"13";i:6;s:2:"12";i:7;s:1:"6";i:8;s:1:"2";i:9;s:1:"3";i:10;s:1:"4";i:11;s:1:"7";i:12;s:1:"5";}';
      d($_GET['data_show']);
      d(maybe_unserialize($_GET['data_show']));

    }

    public function add_user_coupon() {

      if (empty($_GET['coupon_notifier']))
        return;

        $get_users = get_users([ 'fields' => 'ID' ]);
        $found_uid = 0;

        foreach ($get_users as $key => $uid) {

          $uid = (int) $uid;

          $get_meta = get_user_meta($uid, 'wp_s2member_subscr_id', true);

          if ($get_meta == $_GET['sid']) {

              update_user_meta( $uid, 'coupon_used', $_GET['coupon'] );
              $found_uid = $uid;
              break;
          }

        }


    }


		private static function coupons_array($page_id = NULL) {

			$current_user_meta = get_user_meta(get_current_user_id());
			$current_page_id = (empty($page_id) ? get_the_ID() : $page_id) ;
			$the_coupons_arr = [];
			$string_to_search = "wp_s2m_gcs_".$current_page_id;
			$output_array = [];

			foreach ($current_user_meta as $key => $current_user_meta_single) {
				preg_match("/{$string_to_search}/", $key, $output_array);

				if (empty($output_array))
					continue;

				$the_coupons_arr = $current_user_meta_single;
				break;

			}

			$the_coupons_arr = ( (count($the_coupons_arr) == 1) ? $the_coupons_arr[0] : $the_coupons_arr );

			$the_coupons_arr = maybe_unserialize($the_coupons_arr);

			if(!class_exists('c_ws_plugin__s2member_pro_coupons'))
				return;

				$coupon_class = new c_ws_plugin__s2member_pro_coupons();

				$filtered_coupons = [];
				$isValid = [];
				foreach ($the_coupons_arr as $key => $coupon_data) {

					$isValid = $coupon_class->valid_coupon($coupon_data['code'], []);

					if (empty($isValid))
						continue;

					$filtered_coupons[] = $coupon_data;
				}

			return $filtered_coupons;

		}

		private static function coupons_array_from_all_pages() {

			$current_user_meta = get_user_meta(get_current_user_id());
			$current_page_id = (empty($page_id) ? get_the_ID() : $page_id) ;
			$the_coupons_arr = [];
			$string_to_search = "wp_s2m_gcs_";
			$output_array = [];

			foreach ($current_user_meta as $key => $current_user_meta_single) {
				preg_match("/{$string_to_search}/", $key, $output_array);
				if (empty($output_array))
					continue;

				$the_coupons_arr[] = $current_user_meta_single;


			}

			$the_coupons_arr_uns = [];
			$the_coupons_arr_uns_combined = [];

			foreach ($the_coupons_arr as $key => $the_coupons_arr_single) {
				$the_coupons_arr_uns = maybe_unserialize($the_coupons_arr_single[0]);

				$the_coupons_arr_uns_combined = array_merge($the_coupons_arr_uns_combined, $the_coupons_arr_uns);

			}

			$the_coupons_arr = $the_coupons_arr_uns_combined;

			if(!class_exists('c_ws_plugin__s2member_pro_coupons'))
				return;

				$coupon_class = new c_ws_plugin__s2member_pro_coupons();

				$filtered_coupons = [];
				$isValid = [];
				foreach ($the_coupons_arr as $key => $coupon_data) {

					$isValid = $coupon_class->valid_coupon($coupon_data['code'], []);

					if (empty($isValid))
						continue;

					$filtered_coupons[] = $coupon_data;
				}

			return $filtered_coupons;

		}

}


?>
