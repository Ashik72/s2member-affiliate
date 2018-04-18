<?php

if (!defined('ABSPATH'))
  exit;


add_action( 'tf_create_options', 'wp_expert_custom_options_s2member_affiliate', 150 );

function wp_expert_custom_options_s2member_affiliate() {


	$titan = TitanFramework::getInstance( 's2member_affiliate_opts' );
	$section = $titan->createAdminPanel( array(
		    'name' => __( 's2Member Affiliate Settings', 's2member_affiliate' ),
		    'icon'	=> 'dashicons-megaphone'
		) );

	$tab = $section->createTab( array(
    		'name' => 'General Options'
		) );




      $tab->createOption([
        'name' => 'Unique ID of Proform Referral Field',
        'id' => 'referral_unique_id_field',
        'type' => 'text',
        'desc' => 'General Options > Registration/Profile Fields & Options',
        'default' => 'refferal_id'
        ]);


		$section->createOption( array(
  			  'type' => 'save',
		) );


		/////////////New

/*		$embroidery_sub = $section->createAdminPanel(array('name' => 'Embroidering Pricing'));


		$embroidery_tab = $embroidery_sub->createTab( array(
    		'name' => 'Profiles'
		) );


		$wp_expert_custom_options['embroidery_tab'] = $embroidery_tab;

		return $wp_expert_custom_options;
*/
}


 ?>
