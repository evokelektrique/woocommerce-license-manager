<?php

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;

if((isset($_GET['function']) && $_GET['function']=='edit_rule') && isset($_GET['rule_id'])) {
    require_once('edit_generator_rule.php');
  
    die();
}else if((isset($_GET['function']) && $_GET['function']=='edit_license') && isset($_GET['license_id'])) {    
    require_once('edit_license_key.php');
  
    die();
}

$upload_directory = wp_upload_dir();
$target_file = $upload_directory['basedir'] . '/fslm_files/encryption_key.php';

if(!@include_once($target_file)) {
    set_encryption_key('5RdRDCmG89DooltnMlUG', '2Ve2W2g9ANKpvQNXuP3w');
    @include_once($target_file);
}

?>


<div class="wrap fslm">


    <?php fslm_verify_database(); ?>


    <h1><?php echo __('License Manager', 'fslm'); ?></h1>
	
	<?php
	if(isset($_GET['result'])) {
		
		$result =  json_decode( base64_decode( urldecode( $_GET['result'] ) ) );
		
		?>
        <h3><?php echo __('Import result:', 'fslm'); ?></h3>

        <table class="wp-list-table widefat fixed striped posts" style="width: 300px">
            <tr>
                <td><strong><?php echo __('Total', 'fslm'); ?></strong></td>
                <td><?php echo $result->total ?></td>
            </tr>

            <tr>
                <td><strong><?php echo __('Imported', 'fslm'); ?></strong></td>
                <td><?php echo $result->imported ?></td>
            </tr>

            <tr>
                <td><strong><?php echo __('Duplicate', 'fslm'); ?></strong></td>
                <td><?php echo $result->duplicate ?></td>
            </tr>

        </table>
	<?php }
	?>

    <form action="<?php echo admin_url('admin.php?action=licenses_bulk_action') ?>" method="post">
        <div class="inside">
                
            <h3 id="hlk"><?php echo __('License Keys', 'fslm'); ?> <a href="<?php echo admin_url('admin.php') ?>?page=license-manager-add-license-key" class="page-title-action"><?php echo __('Add New', 'fslm'); ?></a></h3>
            


                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php echo __('Select bulk action', 'fslm'); ?></label>
                        <select name="baction" id="bulk-action-selector-top">
                            <option value="-1"><?php echo __('Bulk Actions', 'fslm'); ?></option>
                            <option value="available"><?php echo __('Change status to: Available', 'fslm'); ?></option>
                            <option value="expired"><?php echo __('Change status to: Expired', 'fslm'); ?></option>
                            <option value="active"><?php echo __('Change status to: Active', 'fslm'); ?></option>
                            <option value="inactive"><?php echo __('Change status to: Inactive', 'fslm'); ?></option>
                            <option value="sold"><?php echo __('Change status to: Sold', 'fslm'); ?></option>
                            <option value="returned"><?php echo __('Change status to: Returned', 'fslm'); ?></option>
                            <option value="trash"><?php echo __('Delete', 'fslm'); ?></option>
                        </select>
                        <input class="button action" value="<?php echo __('Apply', 'fslm'); ?>" type="submit">

                    </div>
                    
                    


                <?php

                    global $wpdb;

                    $lklc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_fs_product_licenses_keys");
                    $lklp = 0;
                    $lklnb = esc_attr( get_option('fslm_nb_rows_by_page', '15'));  

                    $lkllimit = 'LIMIT '. $lklp . ', ' . $lklnb;

                    if((isset($_GET['lklp']) && isset($_GET['lklnb'])) && ($_GET['lklp'] != '' && $_GET['lklnb'] != '') && ((int)$_GET['lklp'] >= 0 && (int)$_GET['lklnb'] >= 1)) {
                        $lkllimit = 'LIMIT ' . ((int) $_GET['lklp']*(int) $_GET['lklnb']) . ', ' . (int) $_GET['lklnb'];

                        $lklp = (int) $_GET['lklp'];
                        $lklnb = (int) $_GET['lklnb'];  
                    }


                    $kgrc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules");
                    $kgrp = 0;
                    $kgrnb =  esc_attr( get_option('fslm_nb_rows_by_page', '15'));   

                    $kgrlimit = 'LIMIT ' . $kgrp . ', ' . $kgrnb;

                    if((isset($_GET['kgrp']) && isset($_GET['kgrnb'])) && ($_GET['kgrp'] != '' && $_GET['kgrnb'] != '') && ((int)$_GET['kgrp'] >= 0 && (int)$_GET['kgrnb'] >= 1)) {
                        $kgrlimit = 'LIMIT ' . ((int) $_GET['kgrp']*(int) $_GET['kgrnb']) . ', ' . (int) $_GET['kgrnb'];

                        $kgrp = (int) $_GET['kgrp'];
                        $kgrnb = (int) $_GET['kgrnb'];  
                    }

                    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys ORDER BY license_id DESC " . $lkllimit);

                    ?>

                    <div class="pages">
                        <span class="displaying-num"><?php echo $lklc; ?> items</span>
                        <a href="<?php echo admin_url('admin.php?page=license-manager&lklp=0&lklnb=' . $lklnb . '&kgrp=' . $kgrp . '&kgrnb=' . $kgrnb); ?>"><span class="pagination-links"><span class="tablenav-pages-navspan button" aria-hidden="true">«</span></a>
                        <a href="<?php echo admin_url('admin.php?page=license-manager&lklp=' . ($lklp-1) . '&lklnb=' . $lklnb . '&kgrp=' . $kgrp . '&kgrnb=' . $kgrnb); ?>"><span class="tablenav-pages-navspan button" aria-hidden="true">‹</span></a>
                        <span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><?php echo ($lklp+1); ?> of <span class="total-pages"><?php echo ceil($lklc/$lklnb); ?></span></span>
                        <a href="<?php echo admin_url('admin.php?page=license-manager&lklp=' . ($lklp+1<ceil($lklc/$lklnb)?$lklp+1:$lklp) . '&lklnb=' . $lklnb . '&kgrp=' . $kgrp . '&kgrnb=' . $kgrnb); ?>"><span class="pagination-links"><span class="tablenav-pages-navspan button" aria-hidden="true">›</span></a>
                        <a href="<?php echo admin_url('admin.php?page=license-manager&lklp=' . (ceil($lklc/$lklnb)-1) . '&lklnb=' . $lklnb . '&kgrp=' . $kgrp . '&kgrnb=' . $kgrnb); ?>"><span class="tablenav-pages-navspan button" aria-hidden="true">»</span></span></a>
                    </div>

                </div>
                <br class="clear">
                    
                    <div class="alignleft actions bulkactions fslm-filter">
                       
                        <p class="filter_title"><?php echo __('Filter:', 'fslm'); ?></p>
                       	
                       	<input type="text" placeholder="<?php echo __('License key', 'fslm'); ?>" id="filter-license_key" name="filter_by_license_key">
                       	
                       	
                       	<input type="text" placeholder="<?php echo __('Customer First Name', 'fslm'); ?>" id="filter-name" name="filter_by_name">
                       	<input type="text" placeholder="<?php echo __('Customer E-mail', 'fslm'); ?>" id="filter-mail" name="filter_by_email">
                       	
                       	
                       	
						<select id="filter-product" name="product_id">      
							<option value="-1"><?php echo __('Product', 'fslm'); ?></option>
							<option value="-1" disabled><?php echo __('Product ID - Product Name', 'fslm'); ?></option>
						                                            
							<?php
							
							
							global $wpdb;
							
							// A sql query to return all post titles
							$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product"), ARRAY_A );
							
							
							foreach ($results as $index => $post) {
								echo '<option value="' . $post['ID'] . '">' . $post['ID'] . ' - ' . $post['post_title'] . '</option>';
							}
							
							?>

						</select>


                        <select id="filter-variation" name="variation_id">
                            <option value="-1"><?php echo __('Variation', 'fslm'); ?></option>
                            <option value="-1" disabled><?php echo __('Variation ID - Variation Name', 'fslm'); ?></option>

                            <?php


                            global $wpdb;

                            // A sql query to return all post titles
                            $variation = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product_variation"), ARRAY_A );


                            foreach ($variation as $index => $post) {
                                echo '<option value="' . $post['ID'] . '">' . $post['ID'] . ' - ' . $post['post_title'] . '</option>';
                            }

                            ?>

                        </select>

                       	
                       	<select id="filter-status" name="status">
                           <option value="-1"><?php echo __('License key Status', 'fslm'); ?></option>
                            <?php
                                foreach($status as $key => $statu){
                                    
                                    
                                    echo '<option value="' . $key . '">' . __($statu, 'fslm') . '</option>';
                                }
                            ?>
                        </select>

                        <button class="button action" id="fslm_filter"><?php echo __('Filter', 'fslm'); ?></button>
                        <button class="button action" id="fslm_clear"><?php echo __('Clear Filter Result', 'fslm'); ?></button>

                        <br>

                        <label><input type="checkbox" id="filter-html_ml" name="filter_by_html"><?php echo __('Extended Filter', 'fslm'); ?></label>



                        <div class="filter-helper-container">
                            <div class="helper filter-helper">?
                                <div class="tip">
                                    <p class="first"><?php echo __('1. You can just use a part of the First name not the whole name.', 'fslm'); ?></p>
                                    <p><?php echo __('2. You Don\'t have to use all the options.', 'fslm'); ?></p>
    
                                    <p><?php echo __('3. Extended Filter is for HTML/Multi-line license keys, use the unique part of the license key not the whole code, Make sure to use all the filter option possible to narrow down the number of keys that have to be processed.', 'fslm'); ?></p>
    
                                    <p><?php echo __('4. Enable Extended Filter to filter by a part of the license key not the whole key.', 'fslm'); ?></p>
    
                                </div>
                            </div>
                        </div>

                        <div class="fslm-filter-count-container"><strong><span class="fslm-filter-count"></span> <?php echo __("items found.", "fslm"); ?></strong></div>
                        

                    </div>

                    <table id="licenses" class="wp-list-table widefat fixed striped posts">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php echo __('Select All', 'fslm'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                                <th scope="col" id="license_id" class="manage-column sortable desc" data-sort="int"><span class="tips"><?php echo __('ID', 'fslm'); ?></span></th>
                                <th scope="col" id="product_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Product', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="product_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Variation', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="license_key" class="manage-column column-primary"><span><?php echo __('License Key', 'fslm'); ?></span></span></th>
                                <th scope="col" id="owner_name" class="manage-column sortable desc" data-sort="string-ins"><span><?php echo __('Owner', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="max_instance_number" class="manage-column"><span><?php echo __('Instance', 'fslm'); ?></span></th>
                                <th scope="col" id="deliver_x_times" class="manage-column"><span><?php echo __('Deliver times', 'fslm'); ?></span></th>
                                <th scope="col" id="creation_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Created', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="activation_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Activated', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="expiration_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Expires', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="valid" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Validity', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="license_status" class="manage-column sortable desc" data-sort="string-ins"><span><?php echo __('Status', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            </tr>
                        </thead>

                        <tbody id="the-list">

                            <?php 

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

                                    if($valid == "0") {

                                        $valid = __("No Validity Period","fslm");

                                    } else if(intval($valid) == 1) {

                                        $valid = $valid . ' ' .  __("Day","fslm");

                                    } else {

                                        $valid = $valid . ' ' . __("Days","fslm");

                                    }

                                    $license_status = $query->license_status;

                                    if($image_license_key != '') {
                                        $upload_directory = wp_upload_dir();
                                        $image_license_key = '<img class="ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' .  $image_license_key . '">';
                                    }
	
	                                $order = '';
	
	                                if(isset($query->order_id) && $query->order_id != 0) {
		
		                                $order_id = $query->order_id;
		                                $order    = '<a href="' . admin_url("post.php?post=$order_id&action=edit") . '" target="_blank">' . __('Order #') . $order_id .'</a>';
                                    
                                    }

                                    ?>

                                    <tr id="post-<?php echo $license_id ?>">
                                        <td class="check-column">
                                            <label class="screen-reader-text" for="cb-select-<?php echo $license_id ?>">Select <?php echo $license_id ?></label>
                                            <input id="cb-select-<?php echo $license_id ?>" name="post[]" value="<?php echo $license_id ?>" type="checkbox">
                                            <div class="locked-indicator"></div>
                                        </td>
                                        <td>
                                            <?php echo '<spen class="rhidden">' . __('ID', 'fslm') . ': </spen>'  ?> <?php echo $license_id ?>
                                            <div class="row-actions fsactions">
                                                <span class="inline"><a href="<?php echo admin_url('admin.php') ?>?page=license-manager&function=edit_license&license_id=<?php echo $license_id ?>" class="editinline"><?php echo __('Edit', 'fslm'); ?></a> | </span>
                                                <span class="trash"><a href="<?php echo admin_url('admin.php') ?>?action=delete_license&license_id=<?php echo $license_id ?>" class="submitdelete" ><?php echo __('Delete', 'fslm'); ?></a></span>
                                                <span><a class="fslm_cpy_encrypted_key" href="#" data-ek="<?php echo $query->license_key; ?>"><?php echo __('Copy Encrypted Key', 'fslm'); ?></a></span>
                                            </div>
                                        </td>
                                        <td><?php echo '<spen class="rhidden">' . __('Product', 'fslm') . ': </spen>'  ?><?php echo get_the_title($product_id) ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Variation', 'fslm') . ': </spen>'  ?><?php echo $variation_id ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('License Key', 'fslm') . ': </spen>'  ?><?php echo $image_license_key . $license_key ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Owner', 'fslm') . ': </spen>'  ?><?php echo ($owner_name=='&nbsp;')?'none':$owner_name . ' - ' . $owner_email_address . ' ' .$order?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Activations', 'fslm') . ': </spen>'  ?><?php echo ($max_instance_number - $number_use_remaining). '/' . $max_instance_number ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Delivery', 'fslm') . ': </spen>'  ?><?php echo ($delivre_x_times - $remaining_delivre_x_times). '/' . $delivre_x_times ?></td>

                                        <td><?php echo '<spen class="rhidden">' . __('Created', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($creation_date) ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Activated', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($activation_date) ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Expires', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($expiration_date, true) ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Validity', 'fslm') . ': </spen>'  ?><?php echo $valid ?></td>
                                        <td><?php echo '<spen class="rhidden">' . __('Status', 'fslm') . ': </spen>'  ?><?php echo __(ucfirst($license_status), 'fslm'); ?></td>
                                    </tr>


                                <?php
                                } 

                            }else { ?>

                                <tr>
                                   <td colspan="13" class="center"><?php echo __('There is no license key in the database', 'fslm'); ?></td>
                                </tr>

                            <?php
                            }
                            ?>

                        </tbody>

                        <tfoot>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php echo __('Select All', 'fslm'); ?></label><input id="cb-select-all-2" type="checkbox"></td>
                                <th scope="col" id="license_id" class="manage-column sortable desc" data-sort="int"><span class="tips"><?php echo __('ID', 'fslm'); ?></span></th>
                                <th scope="col" id="product_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Product', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="variation_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Variation', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="license_key" class="manage-column column-primary"><span><?php echo __('License Key', 'fslm'); ?></span></span></th>
                                <th scope="col" id="owner_name" class="manage-column sortable desc" data-sort="string-ins"><span><?php echo __('Owner', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="max_instance_number" class="manage-column"><span><?php echo __('Instance', 'fslm'); ?></span></th>
                                <th scope="col" id="deliver_x_times" class="manage-column"><span><?php echo __('Deliver times', 'fslm'); ?></span></th>
                                <th scope="col" id="creation_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Created', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="activation_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Activated', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="expiration_date" class="manage-column sortable desc" data-sort="date"><span><?php echo __('Expires', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="valid" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Validity', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                                <th scope="col" id="license_status" class="manage-column sortable desc" data-sort="string-ins"><span><?php echo __('Status', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            </tr>
                        </tfoot>
                    </table>
            </div>
    </form>

</div>

<script type="text/javascript">
    var fslm_cek = "<?php echo __('Use this if you want to replace a license key in the order page, Ctrl+C', 'fslm') ?>";
</script>