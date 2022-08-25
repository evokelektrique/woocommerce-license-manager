<?php

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

function fslm_format_date($date, $expiration_date = false){
    
    if($date == '0000-00-00' && $expiration_date) {
        return __('Doesn\'t Expire', 'fslm');
    }
    
    if($date == '0000-00-00') {
        return __('None', 'fslm');
    }
    
    if($date != '') {
        $date = strtotime($date);
        return __(date( 'M', $date), 'fslm') . ' ' . date( 'd, Y', $date);
    }
    
    return __('None', 'fslm');
}

function newLine2br($str) {
    $newLineArray = array("\r\n","\n\r","\n","\r");
    $output = str_replace($newLineArray,"<br>",$str);

    return $output;
}

function br2newLine($str) {
    $newLineArray = array("<br>","<br />","<br/>");
    $output = str_replace($newLineArray,"\n",$str);

    return $output;
}

function set_encryption_key($key, $vi, $action = 'set') {
    $upload_directory = wp_upload_dir();
    $target_dir = $upload_directory['basedir'] . '/fslm_files/';

    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);

        $fp = fopen($target_dir . '.htaccess', 'w');
        fwrite($fp, 'deny from all');
        fclose($fp);

        $fp = fopen($target_dir . 'encryption_key.php', 'w');
        fwrite($fp, "<?php define(\"ENCRYPTION_KEY\", \"" . $key . "\");\ndefine(\"ENCRYPTION_VI\", \"" . $vi . "\");");
        fclose($fp);

        $fp = fopen($target_dir . 'index.php', 'w');
        fwrite($fp, '<?php');
        fclose($fp);
    }else if ($action = 'update'){
        $fp = fopen($target_dir . 'encryption_key.php', 'w');
        fwrite($fp, "<?php define(\"ENCRYPTION_KEY\", \"" . $key . "\");\ndefine(\"ENCRYPTION_VI\", \"" . $vi . "\");");
        fclose($fp);
    }
}

function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {
    $output = false;

    if ($secret_key == "" && $secret_iv == "") {
        return $string;
    }

    if (!extension_loaded('openssl')) {
        return $string;
    }

    $encrypt_method = "AES-256-CBC";

    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if($action == 'decrypt'){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

$months = array(
    array('number' => '01', 'text' => __('Jan', 'fslm')),
    array('number' => '02', 'text' => __('Feb', 'fslm')),
    array('number' => '03', 'text' => __('Mar', 'fslm')),
    array('number' => '04', 'text' => __('Apr', 'fslm')),
    array('number' => '05', 'text' => __('May', 'fslm')),
    array('number' => '06', 'text' => __('Jun', 'fslm')),
    array('number' => '07', 'text' => __('Jul', 'fslm')),
    array('number' => '08', 'text' => __('Aug', 'fslm')),
    array('number' => '09', 'text' => __('Sep', 'fslm')),
    array('number' => '10', 'text' => __('Oct', 'fslm')),
    array('number' => '11', 'text' => __('Nov', 'fslm')),
    array('number' => '12', 'text' => __('Dec', 'fslm'))
);


$status = array(
    'Available' => __('Available', 'fslm'),
    'Active'    => __('Active', 'fslm'),
    'Expired'   => __('Expired', 'fslm'),
    'Inactive'  => __('Inactive', 'fslm'),
    'Returned'  => __('Returned', 'fslm'),
    'Sold'      => __('Sold', 'fslm'),
    'Redeemed'  => __('Redeemed', 'fslm')
);

include(FSLM_PLUGIN_BASE . "/includes/fslm-debug-logger.php");

//Initialize debug logger
global $fslm_debug_logger;
$fslm_debug_logger = new FSLM_Debug_Logger();

function fslm_verify_database() {
    global $wpdb;

    $current_database_columns = array();
    $last_version_columns = array(
        "license_id",
        "product_id",
        "variation_id",
        "license_key",
        "image_license_key",
        "license_status",
        "owner_first_name",
        "owner_last_name",
        "owner_email_address",
        "delivre_x_times",
        "remaining_delivre_x_times",
        "max_instance_number",
        "number_use_remaining",
        "activation_date",
        "creation_date",
        "sold_date",
        "expiration_date",
        "valid",
        "order_id",
        "device_id"
    );

    $table_name_1 = $wpdb->prefix . 'wc_fs_product_licenses_keys';
    $table_name_2 = $wpdb->prefix . 'wc_fs_product_licenses_keys_generator_rules';
    $table_name_3 = $wpdb->prefix . 'wc_fs_licensed_products';

    if(
        ($table_name_1 === $wpdb->get_var( "SHOW TABLES LIKE '$table_name_1'" )) &&
        ($table_name_2 === $wpdb->get_var( "SHOW TABLES LIKE '$table_name_2'" )) &&
        ($table_name_3 === $wpdb->get_var( "SHOW TABLES LIKE '$table_name_3'" ))
    ) {

        $query = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name_1}`");

        foreach($query as $q) {
            $current_database_columns[] = $q->Field;
        }

        if(array_diff($last_version_columns, $current_database_columns)) {
            echo '<div class="error">' . '<h4>' . __("Your database is not up to date.", "fslm") . '</h4><form method="post" action="options.php"><input type="hidden" name="fslm_db_version" value="0">';

            settings_fields('fslm_update_option_group');
            do_settings_sections('fslm_update_option_group');

            submit_button(__('Update Now', 'fslm'));

            echo '</form></p></div>';
        }
    } else {

        echo '<div class="error">' . '<h4>' . __("Database tables missing.", "fslm") . '</h4><form method="post" action="options.php"><input type="hidden" name="fslm_db_version" value="0">';

        settings_fields('fslm_update_option_group');
        do_settings_sections('fslm_update_option_group');

        submit_button(__('Create required database tables', 'fslm'));

        echo '</form></p></div>';

    }

}