<?php
defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;

$upload_directory = wp_upload_dir();
$target_file = $upload_directory['basedir'] . '/fslm_files/encryption_key.php';

if(!@include_once($target_file)) {
    set_encryption_key('5RdRDCmG89DooltnMlUG', '2Ve2W2g9ANKpvQNXuP3w');
    @include_once($target_file);
}

global $wpdb;

$query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_id='" . (int) $_GET['license_id'] . "'");

if($query) {
    $query = $query[0];

    $license_id = $query->license_id;
    $product_id = $query->product_id;
    $variation_id = $query->variation_id;

    $license_key = $query->license_key;

    $license_key = encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

    $license_key = br2newLine($license_key);
    $license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
    $license_key = htmlspecialchars($license_key);

    $owner_first_name = $query->owner_first_name;
    $owner_last_name = $query->owner_last_name;
    $owner_email_address = $query->owner_email_address;
    $max_instance_number = $query->max_instance_number;
    $number_use_remaining = $query->number_use_remaining;
    $creation_date = $query->creation_date;
    $order_id = $query->order_id;

    $image_license_key_html = "";
    $image_license_key = $query->image_license_key;
    if($image_license_key != '') {
        $upload_directory = wp_upload_dir();
        $image_license_key_html = '<div class="input-box sub-input-box">
                                <div class="label">
                                    <span>' . __('Remove the old image', 'fslm') . '</span>
                                </div>
                                <div class="input">
                                    <input name="rmoi" id="rmoi" type="checkbox">
                                </div>
                            </div>';
    }

    $delivre_x_times = $query->delivre_x_times;
    $remaining_delivre_x_times = $query->remaining_delivre_x_times;





    $activation_day = ($query->activation_date=='' || $query->activation_date=='0000-00-00')?'':date('d', strtotime($query->activation_date));
    $activation_month =  ($query->activation_date=='' || $query->activation_date=='0000-00-00')?'':date('m', strtotime($query->activation_date));
    $activation_year =  ($query->activation_date=='' || $query->activation_date=='0000-00-00')?'':date('Y', strtotime($query->activation_date));

    $creation_day = date('d', strtotime($query->creation_date));
    $creation_month =  date('m', strtotime($query->creation_date));
    $creation_year =  date('Y', strtotime($query->creation_date));

    $expiration_day = ($query->expiration_date=='' || $query->expiration_date=='0000-00-00')?'':date('d', strtotime($query->expiration_date));
    $expiration_month = ($query->expiration_date=='' || $query->expiration_date=='0000-00-00')?'':date('m', strtotime($query->expiration_date));
    $expiration_year =  ($query->expiration_date=='' || $query->expiration_date=='0000-00-00')?'':date('Y', strtotime($query->expiration_date));

    $valid = $query->valid;

    $license_status = $query->license_status;

?>

<div class="wrap fslm">

    <h1><?php echo __('Edit License Key', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">

            <form action="<?php echo admin_url('admin.php?action=edit_license') ?>" method="post" enctype="multipart/form-data">

                <input name="license_id" type="hidden" value="<?php echo $license_id ?>">

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Product', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" id="product_id_select" name="product_id">

                            <?php

                            global $wpdb;

                            // A sql query to return all post titles
                            $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product"), ARRAY_A );


                            foreach ($results as $index => $post) {
	                            $selected = ($post['ID']==$product_id)?'selected':'';
	                            echo '<option ' . $selected . ' value="' . $post['ID'] . '">' . $post['ID'] . ' - ' . $post['post_title'] . '</option>';
                            }

                            ?>

                        </select>
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Variation', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" id="variation_id_select" name="variation_id">
                            <option  value="NONE">Main Product</option>
                            <?php
                            $variations = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product_variation"), ARRAY_A );

                            foreach ($variations as $index => $variation) {
	
	                            if($variation['post_title'] != "") {
		                            $selected = ($variation['ID']==$variation_id)?'selected':'';
		                            echo '<option ' . $selected . ' value="' . $variation['ID'] . '">' . $variation['ID'] . ' - ' . $variation['post_title'] . '</option>';
	                            }
                            }

                            ?>

                        </select>
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('License Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <textarea class="input-field" name="license_key" id="license_key" type="text"><?php echo $license_key ?></textarea>
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Image License Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div>
                            <?php echo $image_license_key_html ; ?>
                        </div>
                        <input class="input-field" name="image_license_key" id="image_license_key" type="file">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('You can use an image as a license key', 'fslm'); ?>
                            </div>
                        </div>

                        <div class="helper extra">
                            <span><?php echo __('See Old Image', 'fslm'); ?></span>
                            <div class="tip">
                                <?php echo __('If you upload a replacement this will be automatically removed', 'fslm'); ?>
                                <?php if($image_license_key != '') {
                                    echo '<br><br><img class="ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key . '">';
                                }else {
                                    echo '<br><br><b>' . __('No Image', 'fslm') . '</b>';
                                }?>
                            </div>
                        </div>
                    </div>

                </div>



                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Number Of Times To Deliver This Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="deliver_x_times" id="deliver_x_times" type="number" min="0" value="<?php echo $delivre_x_times ?>">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('The status will only change to sold after the key is sold the number of times in the input above', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Remaining delivery times', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="remaining_delivre_x_times" id="remaining_delivre_x_times" type="number" min="0" value="<?php echo $remaining_delivre_x_times ?>">
                    </div>

                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Maximum Instance Number', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="max_instance_number" id="max_instance_number" type="number" min="0" value="<?php echo $max_instance_number ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Remaining Instance Number', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="number_use_remaining" id="number_use_remaining" type="number" min="0" value="<?php echo $number_use_remaining ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Owner First Name', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="owner_first_name" id="owner_first_name" type="text" value="<?php echo $owner_first_name ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Owner Last Name', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="owner_last_name" id="owner_last_name" type="text" value="<?php echo $owner_last_name ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Owner Email Address', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="owner_email_address" id="owner_email_address" type="email" value="<?php echo $owner_email_address ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Creation Date', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div class="timestamp-wrap">
                            <select class="date" id="month" name="creation_month">
                                <?php
                                foreach($months as $month){
                                    $selected = ($month['number']==$creation_month)?'selected':'';
                                    
                                    echo '<option value="' . $month['number'] . '" data-text="' . __($month['text'], 'fslm') . '" ' . $selected . '>' . $month['number'] . '-' . __($month['text'], 'fslm') . '</option>';
                                }
                                ?>
                            </select>

                            <input class="date" id="day" name="creation_day" maxlength="2" type="number" placeholder="<?php _e('Day', 'fslm'); ?>" min="1" max="31" value="<?php echo $creation_day ?>">

                            <input class="date" id="year" name="creation_year" size="4" maxlength="4" type="text" placeholder="<?php _e('Year', 'fslm'); ?>" value="<?php echo $creation_year ?>">
                        </div>
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Activation Date', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div class="timestamp-wrap">
                            <select class="date" id="month" name="activation_month">
                                <option value=""></option>
                                <?php
                                foreach($months as $month){
                                    $selected = ($month['number']==$activation_month)?'selected':'';
                                    
                                    echo '<option value="' . $month['number'] . '" data-text="' . __($month['text'], 'fslm') . '" ' . $selected . '>' . $month['number'] . '-' . __($month['text'], 'fslm') . '</option>';
                                }
                                ?>
                            </select>

                            <input class="date" id="day" name="activation_day" maxlength="2" type="number" placeholder="<?php _e('Day', 'fslm'); ?>" min="1" max="31" value="<?php echo $activation_day ?>">

                            <input class="date" id="year" name="activation_year" size="4" maxlength="4" type="text" placeholder="<?php _e('Year', 'fslm'); ?>" value="<?php echo $activation_year ?>">
                        </div>
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Expiration Date', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div class="timestamp-wrap">
                            <select class="date" id="month" name="expiration_month">
                                <option value=""></option>
                                <?php
                                foreach($months as $month){
                                    $selected = ($month['number']==$expiration_month)?'selected':'';
                                    
                                    echo '<option value="' . $month['number'] . '" data-text="' . __($month['text'], 'fslm') . '" ' . $selected . '>' . $month['number'] . '-' . __($month['text'], 'fslm') . '</option>';
                                }
                                ?>
                            </select>

                            <input class="date" id="day" name="expiration_day" maxlength="2" type="number" placeholder="<?php _e('Day', 'fslm'); ?>" min="1" max="31" value="<?php echo $expiration_day ?>">

                            <input class="date" id="year" name="expiration_year" size="4" maxlength="4" type="text" placeholder="<?php _e('Year', 'fslm'); ?>" value="<?php echo $expiration_year ?>">
                        </div>
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Status', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" id="status" name="status">
                            <?php
                                foreach($status as $key => $statu){
                                    $selected = (strtolower($key)==strtolower($license_status))?'selected':'';
                                    
                                    echo '<option value="' . $key . '" ' . $selected . '>' . __($statu, 'fslm') . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Validity (Days)', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="valid" id="valid" type="number" min="0" value="<?php echo $valid ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Order ID', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="order_id" id="order_id" type="number" min="0" value="<?php echo $order_id ?>">
                    </div>
                </div>
                
                <?php $device_id_json = json_decode($query->device_id);
                
                if($query->device_id != '' && $query->device_id != '[]' && $query->device_id != null) {
	
	                if ($device_id_json != null && is_array($device_id_json) && json_last_error() === JSON_ERROR_NONE) { ?>

                        <div class="input-box">
                            <strong><?php echo __('Delete active domains/device IDs', 'fslm'); ?>:</strong>
                        </div>
                        
		                <?php foreach ($device_id_json as $device) { ?>
                            
                            <div class="input-box">
                                <div class="label">
                                    <span><?php echo sanitize_text_field($device) ?></span>
                                </div>
                                <div class="input">
                                    <input type="checkbox" name="fslm_device[]"
                                           value="<?php echo sanitize_text_field($device) ?>">
                                </div>
                            </div>
		
		                <?php } ?>
	
	                <?php } else { ?>

                        <div class="input-box">
                            <strong><?php echo __('Delete active domains/device IDs', 'fslm'); ?>:</strong>
                        </div>

                        <div class="input-box">
                            <div class="label">
                                <span><?php echo $query->device_id ?></span>
                            </div>
                            <div class="input">
                                <input type="checkbox" name="fslm_device[]"
                                       value="<?php echo $query->device_id ?>">
                            </div>
                        </div>
	
	                <?php }
	
                } ?>

                <p class="submit">
                    <input name="save" id="save-license-key" class="button button-primary" value="<?php echo __('Update License Key', 'fslm'); ?>" type="submit">
                    <br class="clear">
                </p>

            </form>
        </div>
    </div>
</div>

<?php
}
?>
