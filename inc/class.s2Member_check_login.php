<?php

if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\Device;

/**
 * s2Member_check_login
 */
class s2Member_check_login
{

private static $instance;

public static function get_instance() {
	if ( ! isset( self::$instance ) ) {
		self::$instance = new self();
	}

	return self::$instance;
}


  function __construct()  {

    //add_action( 'wp_footer', array($this, 'wp_footer_test') );
    add_action( 'template_redirect', array($this, 'user_browser_and_os_check'));
    add_action('clear_auth_cookie', array($this, 'clear_user_browser_and_os'));


  }

  public function clear_user_browser_and_os() {

    //delete_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72');

    $get_device_data = get_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', true);

		if (empty($get_device_data))
			return;

		if (!is_array($get_device_data))
			return;

    $bos = self::get_browser_and_os();
    $clear_stat = 0;
    foreach ($get_device_data as $key => $single_device_data) {

      if ( ($single_device_data['user_agent'] == $bos['user_agent']) && ($single_device_data['os'] == $bos['os'])) {

        unset($get_device_data[$key]);

        $clear_stat = 1;
      }

    }

    if (empty($clear_stat)) {

      $key = (count($get_device_data) - 1);

      //unset($get_device_data[$key]);

    }

    $update = update_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', $get_device_data);


    //file_put_contents("testLogger", "test on clear_user_browser_and_os ".$update." ".date('l jS \of F Y h:i:s A')."\n", FILE_APPEND);

    file_put_contents("testLogger", maybe_serialize($get_device_data)."\n\n", FILE_APPEND);

    return;

  }

  public function wp_footer_test() {

    $browser = new Browser();
    $os = new Os();

    //d($browser->getUserAgent()->getUserAgentString());
    //d($os->getName());
    $get_device_data = get_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', true);
    d($get_device_data);
    //file_put_contents("testLogger", "test on ".date('l jS \of F Y h:i:s A')."\n", FILE_APPEND);

  }

  public function user_browser_and_os_check() {


    if(!is_user_logged_in())
      return;

    if(!S2MEMBER_CURRENT_USER_IS_LOGGED_IN_AS_MEMBER)
      return;

    $is_this_level_on_opts = self::levels_and_membership(S2MEMBER_CURRENT_USER_ACCESS_LEVEL, TRUE);

    if (empty($is_this_level_on_opts))
      return;

    $get_the_list = self::levels_and_membership();

    $get_the_list = ( (is_array($get_the_list)) ? $get_the_list : [] );

    $allowed_logins = NULL;

    foreach ($get_the_list as $key => $single_row) {

      if (S2MEMBER_CURRENT_USER_ACCESS_LEVEL === $single_row['level']) {

        $allowed_logins = $single_row['allowed_logins'];

      }

    }

    $get_device_data = get_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', true);
    if (empty($get_device_data)) {

      $bos = self::get_browser_and_os();
      $bos_data = [];
      $bos_data[0] = $bos;

      update_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', $bos_data);

      return;

    }

    $bos = self::get_browser_and_os();

    foreach ($get_device_data as $key => $single_device_data) {

      if ( ($single_device_data['user_agent'] == $bos['user_agent']) && ($single_device_data['os'] == $bos['os']))
        return;

    }

    // d($get_device_data);

    $get_device_data = ( (is_array($get_device_data)) ? $get_device_data : [] );

    array_push($get_device_data, $bos);

    // d($get_device_data);
    // d($allowed_logins);

    if (count($get_device_data) <= $allowed_logins) {
      update_user_meta(get_current_user_id(), 'user_logged_in_browsers_a72', $get_device_data);
      return;
    } else {

      $limit_exceeded_link = self::read_option('limit_exceeded_link');

      $limit_exceeded_link = ( empty($limit_exceeded_link) ? "." : $limit_exceeded_link );
      wp_logout();

      ?>

      <script type="text/javascript">
        window.location.href = "<?php _e($limit_exceeded_link); ?>";
      </script>

      <?php
      wp_die("MAXIMUM LOGIN LIMIT REACHED");

      return;

    }
    // if (count($get_device_data) )
    // d($allowed_logins);
    // d($get_device_data);


      //d(S2MEMBER_CURRENT_USER_IS_LOGGED_IN_AS_MEMBER);
      //d(S2MEMBER_CURRENT_USER_ACCESS_LEVEL);

    //file_put_contents("testLogger", "test on after_user_login ".date('l jS \of F Y h:i:s A')."\n", FILE_APPEND);


  }


  private static function get_browser_and_os() {

    $browser = new Browser();
    $os = new Os();
    $getUserAgentString = $browser->getUserAgent()->getUserAgentString();
    $getName = $os->getName();

    if (empty($getUserAgentString) || empty($getName))
      return 0;

    return [ 'user_agent' => $browser->getUserAgent()->getUserAgentString(), 'os' => $os->getName() ];
  }

  private static function levels_and_membership($level = NULL, $is_this_level_on_opts = NULL) {

    $get_titan_data = self::read_option('membership_level_allowed_login');

    if (empty($get_titan_data))
      return 0;

    if (!empty($is_this_level_on_opts)) {
      if (empty($level))
        return 0;
    }


    $get_titan_data = explode(PHP_EOL, $get_titan_data);

    $get_titan_data = ( (is_array($get_titan_data)) ? $get_titan_data : [] );

    $membership_data = [];

    foreach ($get_titan_data as $key => $single_titan_data) {

      $membership_and_login = explode("|", $single_titan_data);

      array_walk($membership_and_login, function(&$item) {

        $item = preg_replace('/\s+/', '', $item);
        $item = (int) $item;

      });

      if (!empty($level) && !empty($is_this_level_on_opts)) {

        $level = (int) $level;

        if ($membership_and_login[0] === $level)
          return 1;

      }


      $membership_data[] = [ 'level' => $membership_and_login[0], 'allowed_logins' => $membership_and_login[1] ];

    }

    if (!empty($level) && !empty($is_this_level_on_opts))
      return 0;

    return $membership_data;

  }



  public static function read_option($id){
   $titan = TitanFramework::getInstance( 's2member_unique_login_opts' );
   return $titan->getOption($id);
  }

}


 ?>
