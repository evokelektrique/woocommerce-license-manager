<?php
defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;
?>

<div class="wrap fslm">

    <h1><?php echo __('Export License Manager Data & Settings', 'fslm'); ?></h1>

    <div class="postbox">
        <div class="inside">
            <div class="elk">
                <h3><?php echo __('1. Export License Keys', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                    <input type="hidden" name="action" value="fslm_export_csv_lk">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Status', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" id="elk_license_status" name="elk_license_status">
                                <option value="all">All</option>
                                <?php
                                    foreach($status as $key => $statu){
                                        echo '<option value="' . strtolower($key) . '">' . __($statu, 'fslm') . '</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field elk_product_id" id="elk_product_id" name="elk_product_id">     
                                <option value="all">All</option>
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

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Export License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>

            <div class="elk">
                <h3><?php echo __('2. Export License Keys (Editable Unencrypted License keys)', 'fslm'); ?></h3>
                <p><?php echo __('Note: Use this option if you are going to edit the file in Excel or any other CSV file editor.', 'fslm'); ?></p>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                    <input type="hidden" name="action" value="fslm_export_csv_lk_une_edit">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Status', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" id="elk_license_status" name="elk_license_status">
                                <option value="all">All</option>
						        <?php
						        foreach($status as $key => $statu){
							        echo '<option value="' . strtolower($key) . '">' . __($statu, 'fslm') . '</option>';
						        }
						        ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field elk_product_id" id="elk_product_id" name="elk_product_id">
                                <option value="all">All</option>
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

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Export License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="elk">
                <h3><?php echo __('3. Export License Keys (Unencrypted License keys)', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                    <input type="hidden" name="action" value="fslm_export_csv_lk_une">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Status', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" id="elk_license_status" name="elk_license_status">
                                <option value="all">All</option>
						        <?php
						        foreach($status as $key => $statu){
							        echo '<option value="' . strtolower($key) . '">' . __($statu, 'fslm') . '</option>';
						        }
						        ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field elk_product_id" id="elk_product_id" name="elk_product_id">
                                <option value="all">All</option>
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

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Export License Keys', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="egr">
                <h3><?php echo __('4. Export Generator Rules', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                    <input type="hidden" name="action" value="fslm_export_csv_gr">

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" id="egr_product_id" name="egr_product_id">     
                                <option value="all">All</option>
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

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Export Generator Rules', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
            <div class="epg">
                <h3><?php echo __('5. Export Plugin Settings', 'fslm'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                    <input type="hidden" name="action" value="fslm_export_ps">

                    <p class="submit mb">
                        <input name="save" class="button button-primary" value="<?php echo __('Export Plugin Settings', 'fslm'); ?>" type="submit">
                        <br class="clear">
                    </p>
                </form>
            </div>
            
        </div>
    </div>
</div>
        