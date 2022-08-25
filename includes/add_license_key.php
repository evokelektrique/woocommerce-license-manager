<?php

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );
require_once('functions.php');

global $months;
global $status;

?>
<div class="wrap fslm">

    <h1><?php echo __('Add License Key', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">

            <form action="<?php echo admin_url('admin.php?action=add_license') ?>" method="post" enctype="multipart/form-data">


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
	                            echo '<option value="' . $post['ID'] . '">' . $post['ID'] . ' - ' . $post['post_title'] . '</option>';
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
	                        <option  value="0"><?php echo __('Main Product', 'fslm') ?></option>
                            <?php
                            $variations = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product_variation"), ARRAY_A );

                            foreach ($variations as $index => $variation) {
	
	                            if($variation['post_title'] != "") {
		                            echo '<option  value="' . $variation['ID'] . '">' . $variation['ID'] . ' - ' . $variation['post_title'] . '</option>';
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
                        <textarea class="input-field" name="license_key" id="license_key" type="text"></textarea>
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('1. This field support HTML<br>2. This field support multiline text<br><strong>Example:</strong><br>Cart Number: 1234321441411222<br>CVV: 552<br>Expiration Date: 01/19', 'fslm'); ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Image License Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="image_license_key" id="image_license_key" type="file">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('You can use an image as a license key', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Number Of Times To Deliver This Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="deliver_x_times" id="deliver_x_times" type="number" min="1" value="1">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('The status will only change to sold after the key is sold the number of times in the input above', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Maximum Instance Number', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="max_instance_number" id="max_instance_number" type="number" min="0" value="0">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('Requires the implementation for the Tracking API, Ignore this field if your product is untraceable(the Tracking API is designed for software products, themes... digital products in general)', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Expiration Date', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div class="timestamp-wrap">
                            <select class="date" id="month" name="mm">
                                <option value=""></option>
                            <?php
                                foreach($months as $month){
                                    echo '<option value="' . $month['number'] . '" data-text="' . __($month['text'], 'fslm') . '">' . $month['number'] . '-' . __($month['text'], 'fslm') . '</option>';
                                }
                            ?>
                            </select>

                            <input class="date" id="day" name="dd" maxlength="2" type="number" placeholder="<?php _e('Day', 'fslm'); ?>" min="1" max="31">

                            <input class="date" id="year" name="yy" size="4" maxlength="4" type="text" placeholder="<?php _e('Year', 'fslm'); ?>">
                        </div>
                        <div class="helper">?<div class="tip">
                                <?php echo __('Keep <b>Expiration Date</b></b> fields empty if your product doesn\'t expire', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>


                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Validity (Days)', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="valid" id="valid" type="number" min="0" value="0">
                        <div class="helper">?<div class="tip">
                                <?php echo __('Number of <b>Days</b> before the license key expires<br>Expiration date will be calculated based on this value after purchase completed, keep <b>Expiration Date</b> fields empty if you want to use this option<br><b>Set to 0 if your product doesn\'t expire</b>', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>

                <p class="submit">
                    <input name="save" id="save-license-key" class="button button-primary" value="<?php echo __('Add License Key', 'fslm'); ?>" type="submit">
                    <br class="clear">
                </p>

            </form>
        </div>
    </div>
</div>
