<?php 

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;
?>
<div class="wrap fslm">

    <h1><?php echo __('Add Generator Rule', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">

            <form action="<?php echo admin_url('admin.php?action=add_rule') ?>" method="post">


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
		                            echo '<option  value="' . $variation['ID'] . '">' . $variation['ID'] . ' - ' . $variation['post_title'] . '</option>';
	                            }
                            }


                            ?>

                        </select>
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Prefix', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="prefix" id="prefix" type="text" value="">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Number of chunks', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="chunks_number" id="chunks_number" type="number" min="1" value="1">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Chunk length', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="chunks_length" id="chunks_length" type="number" min="1" value="1">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Suffix', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="suffix" id="suffix" type="text" value="">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Maximum Instance Number', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="max_instance_number" id="max_instance_number" type="number" min="1" value="1">
                        <div class="helper">?<div class="tip">
                            <?php echo __('Requires the implementation for the Tracking API, Ignore this field if your product is untraceable(the Tracking API is designed for software products, themes... digital products in general)', 'fslm'); ?>
                        </div></div>
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
                        </div></div>
                    </div>

                </div>

                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Active', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" name="active">
                            <option value="0"><?php echo __('No', 'fslm'); ?></option>
                            <option value="1"><?php echo __('Yes', 'fslm'); ?></option>
                        </select>
                        <div class="helper">?<div class="tip">
                            <?php echo __('No license key will be generated if this option is set to <b>No</b>', 'fslm'); ?>
                        </div></div>
                    </div>

                </div>


                <p class="submit">
                    <input name="save" id="save-license-key" class="button button-primary" value="<?php echo __('Add Generator Rule', 'fslm'); ?>" type="submit">
                    <br class="clear">
                </p>

            </form>
        </div>
    </div>
</div>
