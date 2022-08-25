<?php
defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

global $wpdb;

$query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE rule_id='" . (int) $_GET['rule_id'] . "'");

if($query) {
    
    $query = $query[0];


    $rule_id = $query->rule_id;
    $product_id = $query->product_id;
    $variation_id = $query->variation_id;
    $prefix = $query->prefix;
    $chunks_number = $query->chunks_number;
    $chunks_length = $query->chunks_length;
    $suffix = $query->suffix;
    $max_instance_number = $query->max_instance_number;
    $valid = $query->valid;


    $active = $query->active;


    ?>
<div class="wrap fslm">

    <h1><?php echo __('Edit Generator Rule', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">

            <form action="<?php echo admin_url('admin.php?action=edit_rule') ?>" method="post">

                <input name="rule_id" type="hidden" value="<?php echo $rule_id ?>"> 
                
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
                        <span><?php echo __('Prefix', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="prefix" id="prefix" type="text" value="<?php echo $prefix ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Number of chunks', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="chunks_number" id="chunks_number" type="text" value="<?php echo $chunks_number ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Chunk length', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="chunks_length" id="chunks_length" type="text" value="<?php echo $chunks_length ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Suffix', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="suffix" id="suffix" type="text" value="<?php echo $suffix ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Instance', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="max_instance_number" id="max_instance_number" type="number" value="<?php echo $max_instance_number ?>">
                    </div>
                </div>
                
                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Validity (Days)', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="valid" id="valid" type="number" value="<?php echo $valid ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Active', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" name="active">
                            
                            <?php
    
                            $noSelected = $active=='0'?'selected':'';
                            $yesSelected = $active=='1'?'selected':'';
    
                            ?>
                            
                            <option value="0" <?php echo $noSelected ?>><?php echo __('No', 'fslm'); ?></option>
                            <option value="1" <?php echo $yesSelected ?>><?php echo __('Yes', 'fslm'); ?></option>
                        </select>
                    </div>
                </div>


                <p class="submit">
                    <input name="save" id="save-license-key" class="button button-primary" value="<?php echo __('Edit Generator Rule', 'fslm'); ?>" type="submit">
                    <br class="clear">
                </p>

            </form>
        </div>
    </div>
</div>

<?php } ?>
                            
