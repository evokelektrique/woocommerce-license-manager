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

?>


<div class="wrap fslm">

    <h1><?php echo __('License Key Generator', 'fslm'); ?></h1>

            <?php

            global $wpdb;


            $kgrc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules");
            $kgrp = 0;
            $kgrnb =  esc_attr( get_option('fslm_nb_rows_by_page', '15'));

            $kgrlimit = 'LIMIT ' . $kgrp . ', ' . $kgrnb;

            if((isset($_GET['kgrp']) && isset($_GET['kgrnb'])) && ($_GET['kgrp'] != '' && $_GET['kgrnb'] != '') && ((int)$_GET['kgrp'] >= 0 && (int)$_GET['kgrnb'] >= 1)) {
                $kgrlimit = 'LIMIT ' . ((int) $_GET['kgrp']*(int) $_GET['kgrnb']) . ', ' . (int) $_GET['kgrnb'];

                $kgrp = (int) $_GET['kgrp'];
                $kgrnb = (int) $_GET['kgrnb'];
            }

   ?>

        <div>
            <div class="inside">
                <h3 id="hgr"><?php echo __('Automatic License Key Generator Rules', 'fslm'); ?> <a href="<?php echo admin_url('admin.php') ?>?page=license-manager-license-generator" class="page-title-action"><?php echo __('Add New', 'fslm'); ?></a></h3>

                <form action="<?php echo admin_url('admin.php?action=generator_bulk_action') ?>" method="post">

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-top" class="screen-reader-text"><?php echo __('Select bulk action', 'fslm'); ?></label>
                            <select name="baction" id="bulk-action-selector-top">
                                <option value="-1"><?php echo __('Bulk Actions', 'fslm'); ?></option>
                                <option value="activate"><?php echo __('Activate', 'fslm'); ?></option>
                                <option value="deactivate"><?php echo __('Deactivate', 'fslm'); ?></option>
                                <option value="trash"><?php echo __('Delete', 'fslm'); ?></option>
                            </select>
                            <input id="doaction" class="button action" value="<?php echo __('Apply', 'fslm'); ?>" type="submit">

                        </div>


                        <?php

                        global $wpdb;

                        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules ORDER BY rule_id DESC " . $kgrlimit);

                        ?>

                        <div class="pages">
                            <span class="displaying-num"><?php echo $kgrc; ?> items</span>
                            <a href="<?php echo admin_url('admin.php?page=license-manager&kgrp=0&kgrnb=' . $kgrnb); ?>"><span class="pagination-links"><span class="tablenav-pages-navspan button" aria-hidden="true">«</span></a>
                            <a href="<?php echo admin_url('admin.php?page=license-manager&kgrp=' . ($kgrp-1) . '&kgrnb=' . $kgrnb); ?>"><span class="tablenav-pages-navspan button" aria-hidden="true">‹</span></a>
                            <span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><?php echo ($kgrp+1); ?> of <span class="total-pages"><?php echo ceil($kgrc/$kgrnb); ?></span></span>
                            <a href="<?php admin_url('admin.php?page=license-manager&kgrp=' . ($kgrp+1<ceil($kgrc/$kgrnb)?$kgrp+1:$kgrp) . '&kgrnb=' . $kgrnb); ?>"><span class="pagination-links"><span class="tablenav-pages-navspan button" aria-hidden="true">›</span></a>
                            <a href="<?php echo admin_url('admin.php?page=license-manager&kgrp=' . (ceil($kgrc/$kgrnb)-1) . '&kgrnb=' . $kgrnb); ?>"><span class="tablenav-pages-navspan button" aria-hidden="true">»</span></span></a>
                        </div>

                    </div>
                    <br class="clear">

                    <table id="rules" class="wp-list-table widefat fixed striped posts">
                        <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php echo __('Select All', 'fslm'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                            <th scope="col" id="rule_id" class="manage-column sortable desc" data-sort="int"><span class="tips"><?php echo __('ID', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="alkgr_product_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Product', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="alkgr_variation_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Variation', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="prefix" class="manage-column column-primary" data-sort="string"><span><?php echo __('Prefix', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="chunks_number" class="manage-column column-primary" data-sort="int"><span><?php echo __('Chunks Number', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="chunks_length" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Chunks Length', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="suffix" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Suffix', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="instance" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Instance', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="valid" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Validity', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="generate" class="manage-column" data-sort="string"><span><?php echo __('Active', 'fslm'); ?></span></th>
                            <th scope="col" id="generate" class="manage-column" data-sort="string"><span><?php echo __('Generate', 'fslm'); ?></span></th>
                        </tr>
                        </thead>

                        <tbody id="the-list">

                        <?php

                        if($query) {
                            foreach ($query as $query) {

                                $rule_id = $query->rule_id;
                                $product_id = $query->product_id;

                                if($query->variation_id!=0) {
                                    $single_variation = new WC_Product_Variation($query->variation_id);
                                    $variation_id = $single_variation->get_formatted_name();
                                }else {
                                    $variation_id = 'Main Product';
                                }
                                $prefix = $query->prefix;
                                $chunks_number = $query->chunks_number;
                                $chunks_length = $query->chunks_length;
                                $suffix = $query->suffix;
                                $max_instance_number = $query->max_instance_number;
                                $valid = $query->valid;
                                $active = $query->active;


                                ?>

                                <tr id="post-<?php echo $rule_id ?>">
                                    <td class="check-column">
                                        <label class="screen-reader-text" for="cb-select-<?php echo $rule_id ?>"><?php echo __('Select', 'fslm'); ?> <?php echo $rule_id ?></label>
                                        <input id="cb-select-<?php echo $rule_id ?>" name="post[]" value="<?php echo $rule_id ?>" type="checkbox">
                                        <div class="locked-indicator"></div>
                                    </td>
                                    <td>
                                        <?php echo '<spen class="rhidden">' . __('ID', 'fslm') . ': </spen>'  ?><?php echo $rule_id ?>
                                        <div class="row-actions fsactions">
                                            <span class="inline"><a href="<?php echo admin_url('admin.php') ?>?page=license-manager&function=edit_rule&rule_id=<?php echo $rule_id ?>" class="editinline"><?php echo __('Edit', 'fslm'); ?></a> | </span>
                                            <span class="trash"><a href="<?php echo admin_url('admin.php') ?>?action=delete_rule&rule_id=<?php echo $rule_id ?>" class="submitdelete" ><?php echo __('Delete', 'fslm'); ?></a></span>
                                        </div>
                                    </td>
                                    <td><?php echo '<spen class="rhidden">' . __('Product', 'fslm') . ': </spen>'  ?><?php echo get_the_title($product_id) ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Variation', 'fslm') . ': </spen>'  ?><?php echo $variation_id ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Prefix', 'fslm') . ': </spen>'  ?><?php echo $prefix ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Chunks', 'fslm') . ': </spen>'  ?><?php echo $chunks_number ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Chunk Length', 'fslm') . ': </spen>'  ?><?php echo $chunks_length ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Suffix', 'fslm') . ': </spen>'  ?><?php echo $suffix ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Activations', 'fslm') . ': </spen>'  ?><?php echo $max_instance_number ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Validity', 'fslm') . ': </spen>'  ?><?php echo $valid ?></td>
                                    <td><?php echo '<spen class="rhidden">' . __('Status', 'fslm') . ': </spen>'  ?><?php echo $active=='1'?__('Yes', 'fslm'):__('No', 'fslm') ?></td>
                                    <td>
                                        <?php echo '<spen class="rhidden">' . __('Generate', 'fslm') . ': </spen>'  ?>
                                        <div method="post" class="action-form">
                                            <input id="fslm-q-<?php echo $rule_id ?>" name="quantity" type="number" placeholder="100" value="100">
                                            <a href="#" class="generate-btn button action dashicons dashicons-yes" data-id="<?php echo $rule_id ?>"></a>
                                        </div>
                                    </td>
                                </tr>


                                <?php
                            }

                        }else { ?>

                            <tr>
                                <td colspan="11" class="center"><?php echo __('There is no automatic license Key generator rules in the database', 'fslm'); ?></td>
                            </tr>

                            <?php
                        }

                        ?>

                        </tbody>

                        <tfoot>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php echo __('Select All', 'fslm'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                            <th scope="col" id="rule_id" class="manage-column sortable desc" data-sort="int"><span class="tips"><?php echo __('ID', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="alkgr_product_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Product', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="alkgr_variation_id" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Variation', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="prefix" class="manage-column column-primary" data-sort="string"><span><?php echo __('Prefix', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="chunks_number" class="manage-column column-primary" data-sort="int"><span><?php echo __('Chunks Number', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="chunks_length" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Chunks Length', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="suffix" class="manage-column sortable desc" data-sort="string"><span><?php echo __('Suffix', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="instance" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Instance', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="valid" class="manage-column sortable desc" data-sort="int"><span><?php echo __('Validity', 'fslm'); ?></span><span class="sorting-indicator"></span></th>
                            <th scope="col" id="generate" class="manage-column" data-sort="string"><span><?php echo __('Active', 'fslm'); ?></span></th>
                        </tr>
                        </tfoot>
                    </table>
                </form>
            </div>
        </div>
    </div>