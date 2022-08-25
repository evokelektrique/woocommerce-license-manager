<?php

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;

function fslm_metabox_licenses_list($product_id) {

    global $wpdb;

    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id='" . $product_id . "' AND license_status='available' ORDER BY license_id DESC LIMIT 10");

    $available_keys_count = $wpdb->get_var("SELECT COUNT(*)FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id='" . $product_id . "'  AND license_status='available'");


    $table = '<span>' . __('Total available keys for all variations:') . ' ' . $available_keys_count . '</span><table id="licenses" class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <th id="th_license_id" class="manage-column desc" data-sort="int"><span>' .  __('ID', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th id="th_license_id" class="manage-column desc" data-sort="int"><span>' .  __('Variation ID', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th id="th_license_key" class="manage-column"><span>' .  __('License Key', 'fslm') . '</span></span></th>
                <th id="th_creation_date" class="manage-column desc" data-sort="date"><span>' .  __('Creation Date', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th id="th_expiration_date" class="manage-column desc" data-sort="date"><span>' .  __('Expires on', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th scope="col" id="th_valid" class="manage-column sortable desc" data-sort="int"><span>' .  __('Validity', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th id="th_expiration_date" class="manage-column desc" data-sort="int"><span>' .  __('Instances', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th scope="col" id="th_deliver_x_times" class="manage-column"><span>' . __('Deliver times', 'fslm') . '</span></th>
                                   
            </tr>
        </thead>
        <tbody id="list">';

    if($query) {
        foreach ($query as $query) {

            $license_id = $query->license_id;
            $product_id = $query->product_id;

            if($query->variation_id!=0) {
                $single_variation = new WC_Product_Variation($query->variation_id);
                $variation_id = $single_variation->get_formatted_name();
            }else {
                $variation_id = 'Main Product';
            }

            $license_key = $query->license_key;
            $license_key = encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

            $license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
            $image_license_key = $query->image_license_key;
            $owner_name = $query->owner_first_name . '&nbsp;' . $query->owner_last_name;
            $owner_email_address = $query->owner_email_address ;
            $max_instance_number = $query->max_instance_number;
            $number_use_remaining = $query->number_use_remaining;

            $delivre_x_times = $query->delivre_x_times;
            $remaining_delivre_x_times = $query->remaining_delivre_x_times;

            $creation_date = $query->creation_date;
            $activation_date = $query->activation_date;
            $expiration_date = $query->expiration_date;
            $valid = $query->valid;
            $license_status = $query->license_status;

            if($image_license_key != '') {
                $upload_directory = wp_upload_dir();
                $image_license_key = '<img class="ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' .  $image_license_key . '">';
            }

            $table .= '<tr id="license-' .  $license_id . '"><td>'  . '<spen class="rhidden">' . __('ID', 'fslm') . ': </spen>' .  $license_id . '
                    <div class="row-actions fsactions">
                        <span class="inline"><a href="admin.php?page=license-manager&function=edit_license&license_id=' .  $license_id . '" class="editinline">' .  __('Edit', 'fslm') . '</a> | </span>
                        <span class="trash"><a href="admin.php?action=delete_license&license_id=' .  $license_id . '" class="fslm-mb-delete-btn" >' .  __('Delete', 'fslm') . '</a></span>
                    </div>                    
                </td>
                <td>' . '<spen class="rhidden">' . __('Variation', 'fslm') . ': </spen>' .  $variation_id . '</td>
                <td>' . '<spen class="rhidden">' . __('License Key', 'fslm') . ': </spen>' .  $image_license_key . $license_key . '</td>
                <td>' . '<spen class="rhidden">' . __('Created', 'fslm') . ': </spen>' .  fslm_format_date($creation_date) . '</td>
                <td>' . '<spen class="rhidden">' . __('Expires', 'fslm') . ': </spen>' .  fslm_format_date($expiration_date, true) . '</td>
                <td>' . '<spen class="rhidden">' . __('Validity', 'fslm') . ': </spen>' .  $valid . '</td>
                <td>' . '<spen class="rhidden">' . __('Activations', 'fslm') . ': </spen>' .  ($max_instance_number - $number_use_remaining). '/' . $max_instance_number . '</td>
                <td>' . '<spen class="rhidden">' . __('Delivery', 'fslm') . ': </spen>' .  ($delivre_x_times - $remaining_delivre_x_times). '/' . $delivre_x_times . '</td>
       
            </tr>';

        } 

    }else {

        $table .= '<tr>
            <td colspan="8" class="center">' .  __('There is no available license key in the database for this product', 'fslm') . '</td> 
        </tr>';


    }

    $table .= '</tbody></table>';
    
    return $table;

}

?>