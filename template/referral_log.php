<?php

$users = get_users([ 'fields' => [ 'ID' ] ]);

$id_distributors = [];

  for ($i=0; $i < count($users); $i++) {
      $user = $users[$i];

    $custom_field_data = get_user_meta($user->ID, $wpdb->prefix.'s2member_custom_fields', true);

    $s2member_ipn_signup_vars = get_user_meta($user->ID, $wpdb->prefix.'s2member_ipn_signup_vars', true);

    if (!empty($s2member_ipn_signup_vars) && isset($s2member_ipn_signup_vars['mc_gross'])) {
      $gross_amt = $s2member_ipn_signup_vars['mc_gross'];
      $gross_amt = floatval($gross_amt);
      $prev_amount = get_user_meta($user_id, 'total_spend_val', true);


      d([$user->ID, get_user_meta($user->ID)]);

    }


    if (isset($custom_field_data['refferal_id'])) {
      $tmp_id = intval($custom_field_data['refferal_id']);
      //if (in_array($tmp_id, $id_distributors)) continue;

      for ($j = 0; $j < count($id_distributors); $j++) {
        $id_distributor = $id_distributors[$j];

        if ( $tmp_id === $id_distributor['distributor_id'] ) {

            $id_distributors[$j]['referrals'][] = $user->ID;
            continue 2;

        }

      }


      $id_distributors[] = [
        'distributor_id' => $tmp_id,
        'referrals' => [$user->ID]
      ];
    }

  }

if (empty($id_distributors)) return;

$id_distributors_redefined = [];

for ($i=0; $i < count($id_distributors); $i++) {
  $id_distributor = $id_distributors[$i];
  //d($id_distributor['distributor_id']);
  //d($id_distributor);

}

d($id_distributors);


 ?>

<table id="reff_table" class="display">
    <thead>
        <tr>
            <th>Distributor</th>
            <th>Referrals</th>
            <th>Amount Paid</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Row 1 Data 1</td>
            <td>Row 1 Data 2</td>
            <td>Row 1 Data 2</td>

        </tr>
        <tr>
            <td>Row 2 Data 1</td>
            <td>Row 2 Data 2</td>
            <td>Row 2 Data 2</td>

        </tr>
    </tbody>
</table>
