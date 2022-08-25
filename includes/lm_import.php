<?php
defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;
?>

<div class="wrap fslm">

    <h1><?php echo __('Import License Manager Data & Settings', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">

            <div class="elko">
                <h3><?php echo __('1. Import Image License Keys From .zip File', 'fslm'); ?></h3>
                <form id="fslm-bulk-image-import" method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_ilko">

                    <div class="input-box">
                        <p class="tip"><?php echo __('A zip file that contains image license keys(No sub-folders)', 'fslm'); ?></p>
                        <div class="label">
                            <span><?php echo __('Zip File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept="application/zip">
                        </div>
                    </div>

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
                                <option value="0">Main Product</option>
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
                            <div class="helper">?<div class="tip">
							        <?php echo __('Requires the implementation for the Tracking API, Ignore this field if your product is untraceable(the Tracking API is designed for software products, themes... digital products in general)', 'fslm'); ?>
                                </div></div>
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
							        <?php echo __('Keep Expiration Date fields empty if your product doesn\'t expire', 'fslm'); ?>
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

                    <p class="submit mb">
                        <input id="fslm-bulk-image-import-btn" name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <span class="fslm-percent"><?php echo __('Upload progress:', 'fslm'); ?> <span id="fslm-percent">0%</span></span>
                        <br class="clear">
                    </p>

                </form>
            </div>


            <div class="elko">
                <h3><?php echo __('2. Import License Keys From .txt File', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_lko">

                    <div class="input-box">
                        <p class="tip"><?php echo __('A text file that contains license keys only one key in each line', 'fslm'); ?></p>
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept=".txt,.csv">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" id="product_id_select_2" name="product_id">
                                <?php

                                global $wpdb;

                                // A sql query to return all post titles
                                $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product"), ARRAY_A );


                                foreach ($results as $index => $post) {

                                    $terms = get_the_terms ( $post['ID'], 'product_cat' );
                                    
                                    $product_cats = '';

                                    if($terms) {
                                        $product_cats  = ' (';

                                        foreach ( $terms as $term ) {
                                            $product_cats .= $term->name . ', ';
                                        }

                                        $product_cats = rtrim($product_cats, ', ');
                                        $product_cats  .= ')';
    
                                    }

                                   
	                                echo '<option value="' . $post['ID'] . '">' . $post['ID'] . ' - ' . $post['post_title'] . $product_cats . '</option>';
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
                            <select class="input-field" id="variation_id_select_2" name="variation_id">
                                <option value="0">Main Product</option>
                                <?php


                                $variations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'", "product_variation"), ARRAY_A );

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
                            <div class="helper">?<div class="tip">
                                    <?php echo __('Requires the implementation for the Tracking API, Ignore this field if your product is untraceable(the Tracking API is designed for software products, themes... digital products in general)', 'fslm'); ?>
                                </div></div>
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
                                    <?php echo __('Keep Expiration Date fields empty if your product doesn\'t expire', 'fslm'); ?>
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

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>


            <div class="elk">
                <h3><?php echo __('3. Import License Keys', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_csv_lk">

                    <div class="input-box">
                        <p class="tip"><?php echo __('CSV file generated by the plugin in the export page', 'fslm'); ?></p>
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept=".csv">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>

            <div class="elk">
                <h3><?php echo __('4. Import License Keys (File modified in Excel or any other CSV file editor)', 'fslm'); ?></h3>
                <p><?php echo __('Note: Only file exported using "Export License Keys (Editable Unencrypted License keys)" are supported by this option.', 'fslm'); ?></p>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_csv_lk_une_edit">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept=".csv">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="elk">
                <h3><?php echo __('5. Import License Keys (Unencrypted License Keys)', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_csv_lk_une">

                    <div class="input-box">
                        <p class="tip"><?php echo __('CSV file generated by the plugin in the export page', 'fslm'); ?></p>
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept=".csv">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            
            
            <div class="elk">
                <h3><?php echo __('6. Import License Keys (Compatibility Mode)', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_csv_cpm_lk">

                    <div class="input-box">
                        <p class="tip"><?php echo __('CSV file generated by the plugin in the export page', 'fslm'); ?></p>
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ilk_source_file" type="file" accept=".csv">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="egr">
                <h3><?php echo __('7. Import Generator Rules', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_csv_gr">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('CSV File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="igr_source_file" type="file" accept=".csv">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import Generator Rules', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="epg">
                <h3><?php echo __('8. Import Plugin Settings', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>"  enctype="multipart/form-data">

                    <input type="hidden" name="action" value="fslm_import_ps">
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('.fslmsettings File', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="ips_source_file" type="file" accept=".fslmsettings">
                        </div>
                    </div>

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Import Plugin Settings', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
        </div>
    </div>

</div>
        