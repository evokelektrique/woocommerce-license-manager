<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;
global $post;



if(isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
} else {
    $product_id = $post->ID;
}


global $wpdb;

$prefix = esc_attr( get_option('fslm_prefix', ''));
$chunks_number = esc_attr( get_option('fslm_chunks_number', '4'));
$chunks_length = esc_attr( get_option('fslm_chunks_length', '4'));
$suffix = esc_attr( get_option('fslm_suffix', ''));
$max_instance_number = esc_attr( get_option('fslm_max_instance_number', '1'));
$valid = esc_attr( get_option('fslm_valid', '0'));
$active = esc_attr( get_option('fslm_active', '0'));

$query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '" . (int)$product_id  . "' LIMIT 1");

if($query) {
    $query = $query[0];

    $prefix = $query->prefix;
    $chunks_number = $query->chunks_number;
    $chunks_length = $query->chunks_length;
    $suffix = $query->suffix;
    $max_instance_number = $query->max_instance_number;
    $valid = $query->valid;

    $active = $query->active=='1'?'checked':'';
}

?>

<div class="fslm metabox">
    
    <div class="tabs">
        <ul id="fslm_tabs">
            <li><a href="#" class="active" data-savebtn="1" data-tab="basic"><?php echo  __('Basic', 'fslm'); ?></a></li>
            <li><a href="#" data-savebtn="0" data-tab="lk"><?php echo  __('License Keys', 'fslm'); ?></a></li>
            <li><a href="#" data-savebtn="1" data-tab="lg"><?php echo  __('License Generator', 'fslm'); ?></a></li>
            <li><a href="#" data-savebtn="1" data-tab="api"><?php echo  __('API', 'fslm'); ?></a></li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="the-tab-content" id="basic">
            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Delivered Quantity', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $nb_licenses = get_post_meta((int)$product_id, 'fslm_nb_delivered_lk', true);
                    if(empty($nb_licenses)) $nb_licenses = 1;
                    ?>
                    <input class="input-field" name="fslm_nb_delivered_lk" id="fslm_nb_delivered_lk" type="number" min="1" value="<?php echo (int)$nb_licenses; ?>">
                    <div class="helper">?<div class="tip">
                            <?php echo __('The number of license key to be delivered upon purchase.', 'fslm'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo __('Show Delivered License Keys In ', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $show_in = get_post_meta((int)$product_id, 'fslm_show_in', true);
                    ?>
                    <select class="input-field" id="fslm_show_in" name="fslm_show_in">
                        <option value="2" <?php echo ($show_in=='2')?'selected':'' ?>><?php echo __('E-mail And Website', 'fslm') ?></option>
                        <option value="0" <?php echo ($show_in=='0')?'selected':'' ?>><?php echo __('E-mail', 'fslm') ?></option>
                        <option value="1" <?php echo ($show_in=='1')?'selected':'' ?>><?php echo __('Website', 'fslm') ?></option>
                    </select>
                    <div class="helper">?
                        <div class="tip">
                            <?php echo __('<strong>E-mail:</strong> The buyer will receive the key in an email<br><strong>Website:</strong> The buyer will be asked to click a link in the email to go to the website to see the license key, so you can collect data such as IP address, location...', 'fslm'); ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo __('Display License Key As', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $display = get_post_meta((int)$product_id, 'fslm_display', true);
                    ?>
                    <select class="input-field" id="fslm_display" name="fslm_display">
                        <option value="2" <?php echo ($display=='2')?'selected':'' ?>><?php echo __('Text and Image', 'fslm') ?></option>
                        <option value="0" <?php echo ($display=='0')?'selected':'' ?>><?php echo __('Text Only', 'fslm') ?></option>
                        <option value="1" <?php echo ($display=='1')?'selected':'' ?>><?php echo __('Image Only', 'fslm') ?></option>
                    </select>
                    <div class="helper">?
                        <div class="tip">
                            <?php echo __('What to show the buyer in the emails and order history', 'fslm'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <h4><?php echo  __('Enable sending license keys for this Product/Variation', 'fslm'); ?></h4>

            <div class="input-box">
                <div class="label">
                    <span><?php echo __('Main Product', 'fslm') ?></span>
                </div>
                <div class="input">

                    <?php echo '<input type="checkbox" id="mbs_licensable" name="mbs_licensable" ' . ($this->is_licensing_enabled($product_id)?'checked':'') . '>'; ?>

                </div>
            </div>

            <?php if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") { ?>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Import Prefix', 'fslm') ?></span>
                    </div>
                    <div class="input">

			            <?php echo '<input type="text" id="fslm_import_prefix" name="fslm_import_prefix" value="' . get_post_meta((int)$product_id, 'fslm_import_prefix', true) . '">'; ?>

                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Import Suffix', 'fslm') ?></span>
                    </div>
                    <div class="input">

			            <?php echo '<input type="text" id="fslm_import_suffix" name="fslm_import_suffix" value="' . get_post_meta((int)$product_id, 'fslm_import_suffix', true) . '">'; ?>

                    </div>
                </div>

	            <?php

            }

            $handle = new WC_Product_Variable($product_id);

            $variations = $handle->get_children();
            foreach ($variations as $variation) {
	
	            $single_variation = new WC_Product_Variation($variation); ?>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo $variation . ' - ' . $single_variation->get_formatted_name() ?></span>
                    </div>
                    <div class="input">
			
			            <?php echo '<input class="mbs_licensable_variations" type="checkbox" name="mbs_licensable_' . $variation . '" ' . ($this->is_licensing_enabled($product_id, $variation) ? 'checked' : '') . '>'; ?>

                    </div>
                </div>
	
	            <?php if (get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") { ?>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Variation Import Prefix', 'fslm') ?></span>
                        </div>
                        <div class="input">
				
				            <?php echo '<input type="text" class="fslm_import_prefix_variations" id="fslm_import_prefix_' . $variation . '" name="fslm_import_prefix_' . $variation . '" value="' . get_post_meta((int)$product_id, 'fslm_import_prefix_' . $variation, true) . '">'; ?>

                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Variation Import Suffix', 'fslm') ?></span>
                        </div>
                        <div class="input">
				
				            <?php echo '<input type="text"  class="fslm_import_suffix_variations" id="fslm_import_suffix_' . $variation . '" name="fslm_import_suffix_' . $variation . '" value="' . get_post_meta((int)$product_id, 'fslm_import_suffix_' . $variation, true) . '">'; ?>

                        </div>
                    </div>
	
	            <?php }
	
            }
        ?>

        </div>

        <div class="the-tab-content" id="lk">
            <h4><?php echo  __('Available license key for this product', 'fslm'); ?></h4>

            <div id="mb_licenses_list">
                <?php
                require_once('metabox_licenses_list.php');
                echo fslm_metabox_licenses_list($product_id);
                ?>
            </div>

            <div class="wrap fslm">

                <h4><?php echo  __('Add New License Key for this product', 'fslm'); ?></h4>


                <input name="mbs_product_id" id="mbs_product_id" type="hidden" value="<?php echo $product_id ?>">

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('Variation', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" id="alk_variation_id" name="alk_variation_id">
                            <option  value="NONE">Main Product</option>
                            <?php

                            $handle = new WC_Product_Variable($product_id);

                            $variations = $handle->get_children();
                            foreach ($variations as $variation) {

                                $single_variation = new WC_Product_Variation($variation);

                                echo '<option  value="'.$variation.'">' . $variation . ' - ' .  $single_variation->get_formatted_name() . '</option>';

                            }

                            ?>

                        </select>
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('License Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <textarea class="input-field" name="alk_license_key" id="alk_license_key" type="text"></textarea>
                        <div class="helper">?<div class="tip">
                                <?php echo __('1. This field support HTML<br>2. This field support multiline text<br><strong>Example:</strong><br>Cart Number: 1234321441411222<br>CVV: 552<br>Expiration Date: 01/19', 'fslm'); ?>
                            </div></div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Image License Key', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="alk_image_license_key" id="alk_image_license_key" type="file">
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
                        <input class="input-field" name="alk_deliver_x_times" id="alk_deliver_x_times" type="number" min="1" value="1">
                        <div class="helper">?
                            <div class="tip">
                                <?php echo __('The status will only change to sold after the key is sold the number of times in the input above', 'fslm'); ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('Maximum Instance Number', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="alk_max_instance_number" id="alk_max_instance_number" type="number" min="0" value="0">
                        <div class="helper">?<div class="tip">
                                <?php echo __('Requires the implementation for the Tracking API, Ignore this field if your product is untraceable(the Tracking API is designed for software products, themes... digital products in general)', 'fslm'); ?>
                            </div></div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('Expiration Date', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <div class="timestamp-wrap">
                            <select class="date" id="alk_xpdmonth" name="alk_xpdmonth">
                                <option value=""></option>
                                <?php
                                foreach($months as $month){
                                    echo '<option value="' . $month['number'] . '" data-text="' . __($month['text'], 'fslm') . '">' . $month['number'] . '-' . __($month['text'], 'fslm') . '</option>';
                                }
                                ?>
                            </select>

                            <input class="date" id="alk_xpdday" name="alk_xpdday" maxlength="2" type="number" placeholder="<?php _e('Day', 'fslm'); ?>" min="1" max="31">

                            <input class="date" id="alk_xpdyear" name="alk_xpdyear" size="4" maxlength="4" type="text" placeholder="<?php _e('Year', 'fslm'); ?>">
                        </div>
                        <div class="helper">?<div class="tip">
                                <?php echo __('Leave all fields blank if your product doesn\'t require an expiration date', 'fslm'); ?>
                            </div></div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('Validity (Days)', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="alk_valid" id="alk_valid" type="number" min="0" value="0">
                        <div class="helper">?<div class="tip">
                                <?php echo __('Number of <b>Days</b> before the license key expires<br>Expiration date will be calculated based on this value after purchase completed, keep <b>Expiration Date</b> fields empty if you want to use this option<br><b>Set to 0 if your product doesn\'t expire</b>', 'fslm'); ?>
                            </div></div>
                    </div>

                </div>


                <p class="submit">
                    <input name="save" id="add-license-key" class="button button-primary" value="Add License Key" type="button">
                </p>

            </div>
        </div>

        <div class="the-tab-content" id="lg">
            <div class="wrap fslm">
                <h4><?php echo  __('Automatic License key generator settings', 'fslm'); ?></h4>


                <input name="agr_product_id" id="agr_product_id" type="hidden" value="<?php echo $product_id ?>">

                <div id="mb_generator_rules">
                    <?php
                    require_once('metabox_rules_list.php');
                    echo fslm_metabox_rules_list($product_id);
                    ?>
                </div>
                <br>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Enable License Key Generator for this product/Variation', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input type="checkbox" name="mbs_active" id="mbs_active" <?php echo $active ?>>
                        <div class="helper">?<div class="tip">
                                <?php echo __('Use this option if you are selling a product that requires a license key generated for every purchase, like digital products (themes, templates, software products ...) you don\'t need to manually add license keys to use this option, but if you do your buyer will first get license keys from the manually added ones, then the system wil start generating license keys when they run out', 'fslm'); ?><br>
                                <?php echo __('No license key will be generated if this option is <b>unchecked</b>', 'fslm'); ?>
                            </div></div>
                    </div>

                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo  __('Variation', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <select class="input-field" id="mbs_variation_id" name="mbs_variation_id">
                            <option  value="NONE">Main Product</option>
                            <?php

                            $handle = new WC_Product_Variable($product_id);

                            $variations = $handle->get_children();
                            foreach ($variations as $variation) {

                                $single_variation = new WC_Product_Variation($variation);

                                echo '<option  value="'.$variation.'">' . $variation . ' - ' .  $single_variation->get_formatted_name() . '</option>';

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
                        <input class="input-field" name="mbs_prefix" id="mbs_prefix" type="text" value="<?php echo $prefix ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Number of chunks', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="mbs_chunks_number" id="mbs_chunks_number" type="number" min="1" value="<?php echo $chunks_number ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Chunk length', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="mbs_chunks_length" id="mbs_chunks_length" type="number" min="1" value="<?php echo $chunks_length ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Suffix', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="mbs_suffix" id="mbs_suffix" type="text" value="<?php echo $suffix ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Instance', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="mbs_max_instance_number" id="mbs_max_instance_number" type="number" min="0" value="<?php echo $max_instance_number ?>">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Validity (Days)', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="mbs_valid" id="mbs_valid" type="number" min="0" value="<?php echo $valid ?>">
                    </div>
                </div>



            </div>
        </div>

        <div class="the-tab-content" id="api">
            <h4><?php echo  __('Extra data for API use:', 'fslm'); ?></h4>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software Name', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_sn = get_post_meta((int)$product_id, 'fslm_sn', true);
                    ?>
                    <input class="input-field" name="fslm_sn" id="fslm_sn" type="text" value="<?php echo $fslm_sn; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software ID', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_sid = get_post_meta((int)$product_id, 'fslm_sid', true);
                    ?>
                    <input class="input-field" name="fslm_sid" id="fslm_sid" type="text" value="<?php echo $fslm_sid; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software Version', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_sv = get_post_meta((int)$product_id, 'fslm_sv', true);
                    ?>
                    <input class="input-field" name="fslm_sv" id="fslm_sv" type="text" value="<?php echo $fslm_sv; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software Author', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_sa = get_post_meta((int)$product_id, 'fslm_sa', true);
                    ?>
                    <input class="input-field" name="fslm_sa" id="fslm_sa" type="text" value="<?php echo $fslm_sa; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software URL', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_surl = get_post_meta((int)$product_id, 'fslm_surl', true);
                    ?>
                    <input class="input-field" name="fslm_surl" id="fslm_surl" type="text" value="<?php echo $fslm_surl; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software Last Update', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_slu = get_post_meta((int)$product_id, 'fslm_slu', true);
                    ?>
                    <input class="input-field" name="fslm_slu" id="fslm_slu" type="text" value="<?php echo $fslm_slu; ?>">
                </div>
            </div>

            <div class="input-box">
                <div class="label">
                    <span><?php echo  __('Software Extra Data', 'fslm'); ?></span>
                </div>
                <div class="input">
                    <?php
                    $fslm_sed = get_post_meta((int)$product_id, 'fslm_sed', true);
                    ?>
                    <textarea class="input-field" name="fslm_sed" id="fslm_sed" type="text"><?php echo $fslm_sed; ?></textarea>
                    <div class="helper">?<div class="tip">
                            <?php echo __('Extra data to get through the API that are not covered by the above fields<br><b>This field support JSON</b>', 'fslm'); ?>
                        </div></div>
                </div>
            </div>

        </div>

        <p class="submit fslm-save">
            <input name="save" id="mb-save" class="button button-primary" value="<?php echo __('Save Settings', 'fslm'); ?>" type="button">
        </p>

        <div id="mbs_save_response"></div>
    </div>

    <div class="clear"></div>


    <!-- Hidden content -->
    <div id="fslm-metabox-actions-content" class="hidden">
        <div class="fslm-metabox-actions-content fslm">
            <a href="#" id="fslm_reload"><?php echo __('Reload Metabox', 'fslm') ?></a>
        </div>
    </div>
</div>

