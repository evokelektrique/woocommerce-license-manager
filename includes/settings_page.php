<?php

defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

if(isset($_GET['sync_stock'])) {
    global $fs_wc_licenses_manager;
	$fs_wc_licenses_manager->sync_stock();
}

require_once('functions.php');

global $months;
global $status;

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : '';

?>
<div class="wrap fslm">

    <h1><?php echo  __('License Manager Settings', 'fslm'); ?></h1>

    <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'general' ) ?>" class="nav-tab <?php echo ($current_tab=='general' || $current_tab=='')?'nav-tab-active':'' ?>"><?php echo  __('General', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'order_status' ) ?>" class="nav-tab <?php echo $current_tab=='order_status'?'nav-tab-active':'' ?>"><?php echo  __('Order Status', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'licenses_keys_generator' ) ?>" class="nav-tab  <?php echo $current_tab=='licenses_keys_generator'?'nav-tab-active':'' ?>"><?php echo  __('License Keys Generator', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'notifications' ) ?>" class="nav-tab  <?php echo $current_tab=='notifications'?'nav-tab-active':'' ?>"><?php echo  __('Notifications', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'email_template' ) ?>" class="nav-tab  <?php echo $current_tab=='email_template'?'nav-tab-active':'' ?>"><?php echo  __('Email Template', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'encryption' ) ?>" class="nav-tab  <?php echo $current_tab=='encryption'?'nav-tab-active':'' ?>"><?php echo  __('Encryption', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'api' ) ?>" class="nav-tab  <?php echo $current_tab=='api'?'nav-tab-active':'' ?>"><?php echo  __('API', 'fslm'); ?></a>
    <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=' . 'extra' ) ?>" class="nav-tab  <?php echo $current_tab=='extra'?'nav-tab-active':'' ?>"><?php echo  __('Extra Settings', 'fslm'); ?></a>

    </h2>
    
    <div class="postbox">
        <div class="inside">
                <?php

                if(($current_tab == 'general') || ($current_tab == '')) {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_general_option_group');
                    do_settings_sections('fslm_general_option_group');
                    ?>
                
                    <h3><?php echo  __('UI Settings', 'fslm'); ?>:</h3>
                
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Number of rows per page', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="number" name="fslm_nb_rows_by_page"  min="1" value="<?php echo esc_attr( get_option('fslm_nb_rows_by_page', '15')); ?>">
                        </div>
                    </div>
                
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Show Admin bar notifications', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_show_adminbar_notifs" <?php echo esc_attr( get_option('fslm_show_adminbar_notifs', 'on'))=='on'?'checked':''; ?>>
                        </div>
                    </div>
                       
                       
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Allow The Customer to checkout Even If There is no license Keys for a Licensed Product', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_enable_cart_validation" <?php echo esc_attr( get_option('fslm_enable_cart_validation', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Allow Guest Customers To See The License Keys In The Thank-you Page', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_guest_customer" <?php echo esc_attr( get_option('fslm_guest_customer', 'on'))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('License Key Generator Characters', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="text" name="fslm_generator_chars" value="<?php echo esc_attr(get_option('fslm_generator_chars', '0123456789ABCDEF')); ?>">
                            <div class="helper">?<div class="tip">
                                    <?php echo __('Characters that can be used to generate license keys', 'fslm'); ?>
                                </div></div>
                        </div>

                    </div>

                    <div class="input-box">
                        <div class="label">
                        <span><?php echo  __('Show Available License Keys Count in the Products Table', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_show_available_license_keys_column" <?php echo esc_attr( get_option('fslm_show_available_license_keys_column', ''))=='on'?'checked':''; ?>>
                        </div>

                    </div>
                

                
                    <h3><?php echo  __('Property Names', 'fslm'); ?>:</h3>
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Meta key name', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="text" name="fslm_meta_key_name" value="<?php echo esc_attr(get_option('fslm_meta_key_name', 'License Key')); ?>">
                            <div class="helper">?<div class="tip">
                                <?php echo __('The values that are already in the database are not going to be changed.<br>This text appears on the emails, order received page, and purchase history.', 'fslm'); ?>
                            </div></div>
                        </div>

                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Meta key name(Plural form)', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="text" name="fslm_meta_key_name_plural" value="<?php echo esc_attr(get_option('fslm_meta_key_name_plural', 'License Keys')); ?>">
                        </div>

                    </div>

                    <h3><?php echo  __('License Keys Delivery', 'fslm'); ?>:</h3>
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Key Delivery', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" name="fslm_key_delivery">
				
				                <?php
				
                                $delivery = get_option('fslm_key_delivery', 'fifo');
                                
				                $fifo = $delivery=='fifo'?'selected':'';
				                $lifo = $delivery=='lifo'?'selected':'';
				
				                ?>

                                <option value="fifo" <?php echo $fifo ?>><?php echo __('First key added sent first', 'fslm'); ?></option>
                                <option value="lifo" <?php echo $lifo ?>><?php echo __('Last key added sent first', 'fslm'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Show Delivered License Keys In ', 'fslm'); ?></span>
                        </div>
                        <div class="input">
			                <?php
			                $show_in = get_option('fslm_show_in', '2');
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
			                $display = get_option('fslm_display', '2');
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

                    <h3><?php echo  __('Expiration', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Auto expire license keys', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_auto_expire" <?php echo esc_attr( get_option('fslm_auto_expire', ''))=='on'?'checked':''; ?>>
                        </div>
                        <blockquote><p class="description">
                            <?php echo __('Automatically change license keys status to expired after the expiration date.<br><b>The status updater runs once every 24 hours.</b>', 'fslm'); ?>
                        </p></blockquote>
                    </div>

                    <h3><?php echo  __('Auto Mark as Redeemed', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Mark as redeemed after(days)', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="number" name="fslm_auto_redeem"  min="0" value="<?php echo esc_attr( get_option('fslm_auto_redeem', '0')); ?>">
                        </div>
                        <blockquote><p class="description">
                            <?php echo __('Automatically mark sold license keys as redeemed. Works only if the license key status is "sold".<br><b>Set to 0 to disable this feature.</b>', 'fslm'); ?>
                        </p></blockquote>
                    </div>


                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Show the customers "Set as redeemed" button in the order page', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_redeem_btn" <?php echo esc_attr( get_option('fslm_redeem_btn', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>


                    <h3><?php echo  __('Stock Management', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Sync license keys stock with WooCommerce product stock', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_stock_sync" <?php echo esc_attr( get_option('fslm_stock_sync', ''))=='on'?'checked':''; ?>>
                        </div>
                        <blockquote>
                            <p class="description">
                                <?php echo  __('1. Enable this option.', 'fslm'); ?><br>
                                <?php echo  __('2. Enable stock management at product level for the products that you want to be automatically synced.', 'fslm'); ?><br><br>
                                <a href="<?php echo admin_url( 'admin.php?page=license-manager-settings&tab=general&sync_stock=1' ) ?>"><?php echo  __('Sync Now', 'fslm'); ?><br></a>
                                <?php echo  __('The sync now button can be used at anytime to re-sync the license keys stock.', 'fslm'); ?>
                        </p></blockquote>
                    </div>
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Background stock sync process frequency (Seconds)', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="number" name="fslm_stock_sync_frequency"  min="10" value="<?php echo esc_attr( get_option('fslm_stock_sync_frequency', '300')); ?>">
                        </div>
                        <blockquote><p class="description">
				                <?php echo __('Run stock sync process once every X seconds.', 'fslm'); ?>
                            </p></blockquote>
                    </div>



                    <h3><?php echo  __('Delete License keys', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Delete license keys when a product is deleted', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_delete_keys" <?php echo esc_attr( get_option('fslm_delete_keys', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <h3><?php echo  __('Duplicate License Keys', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Allow duplicate license keys to be added to the database', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_duplicate_license" <?php echo esc_attr( get_option('fslm_duplicate_license', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>


                <?php
                    submit_button();

                    echo '</form>';

                } else if ($current_tab == 'licenses_keys_generator') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_lkg_option_group'); 
                
                    $prefix = esc_attr( get_option('fslm_prefix', ''));
                    $chunks_number = esc_attr( get_option('fslm_chunks_number', '4'));
                    $chunks_length = esc_attr( get_option('fslm_chunks_length', '4'));
                    $suffix = esc_attr( get_option('fslm_suffix', ''));
                    $max_instance_number = esc_attr( get_option('fslm_max_instance_number', '1'));
                    $valid = esc_attr( get_option('fslm_valid', '0'));
                    $active = esc_attr( get_option('fslm_active', '0'));
                    
                ?>

                    <h3><?php echo  __('License Generator Default Settings', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Prefix', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_prefix" id="fslm_prefix" type="text" value="<?php echo $prefix ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Number of chunks', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_chunks_number" id="fslm_chunks_number" type="text" value="<?php echo $chunks_number ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Chunk length', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_chunks_length" id="fslm_chunks_length" type="text" value="<?php echo $chunks_length ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Suffix', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_suffix" id="fslm_suffix" type="text" value="<?php echo $suffix ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('Instance', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_max_instance_number" id="fslm_max_instance_number" type="number"  min="1" value="<?php echo $max_instance_number ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Validity (Days)', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_valid" id="fslm_valid" type="number"  min="0" value="<?php echo $valid ?>">
                        </div>
                    </div>


                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Active', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <select class="input-field" name="fslm_active">

                                <?php

                                $noSelected = $active=='0'?'selected':'';
                                $yesSelected = $active=='1'?'selected':'';

                                ?>

                                <option value="0" <?php echo $noSelected ?>><?php echo __('No', 'fslm'); ?></option>
                                <option value="1" <?php echo $yesSelected ?>><?php echo __('Yes', 'fslm'); ?></option>
                            </select>
                        </div>
                    </div>
                
                <?php
                    submit_button();
                    echo '</form>';
                } else if ($current_tab == 'notifications') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_notifications_option_group');
                
                    $notif_min_licenses_nb = esc_attr( get_option('fslm_notif_min_licenses_nb', '10'));
                    $notif_mail = esc_attr( get_option('fslm_notif_mail', 'off'))=='on'?'checked':'';
                    $notif_mail_to = esc_attr( get_option('fslm_notif_mail_to', ''));
                
                ?>

                    <h3><?php echo __('Show notifications in the admin panel when', 'fslm'); ?>:</h3>
                
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Number of available License Keys for licensable products is under', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_notif_min_licenses_nb" id="fslm_notif_min_licenses_nb" type="number" min="0" value="<?php echo $notif_min_licenses_nb ?>">
                        </div>
                    </div>
                
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Send E-mail', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_notif_mail" <?php echo $notif_mail; ?>>
                        </div>
                    </div>
                
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('To', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_notif_mail_to" id="fslm_notif_mail_to" type="email" value="<?php echo $notif_mail_to ?>" placeholder="<?php _e('E-mail Address', 'fslm'); ?>">
                        </div>
                    </div>
                
                <?php
                    
                    submit_button();

                    echo '</form>';

                }else if ($current_tab == 'email_template') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_email_template_option_group');

                    $heading = get_option('fslm_mail_heading', __('License Keys for Order #[order_id]', 'fslm'));
                    $subject = get_option('fslm_mail_subject', __('[site_name] | License Keys for Order #[order_id]', 'fslm'));
                    $message = get_option('fslm_mail_message', __('<p>Dear [bfname] [blname]</p><p>Thank you for your order, those are your license keys for the order #[order_id]</p><p>you can see all your past orders and license keys <a title="My Account" href="[myaccount_url]">here</a>.</p>', 'fslm'));

                ?>

                    <h3><?php echo __('Email Template', 'fslm'); ?>:</h3>

                    <p><?php echo __('Shortcodes:', 'fslm') ?></p>

                    <table class="wp-list-table widefat fixed striped posts">
                        <thead>
                            <tr>
                                <td><b><?php echo __('Shortcods:', 'fslm') ?></b></td>
                                <td><?php echo __('Function:', 'fslm') ?></td>
                                <td></td>
                                <td><b><?php echo __('Shortcods:', 'fslm') ?></b></td>
                                <td><?php echo __('Function:', 'fslm') ?></td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><b>[order_id]<b/></td>
                                <td><?php echo __('Order ID', 'fslm') ?></td>
                                <td></td>
                                <td><b>[slname]<b/></td>
                                <td><?php echo __('Shipping Last Name', 'fslm') ?></td>
                            </tr>

                            <tr>
                                <td><b>[bfname]<b/></td>
                                <td><?php echo __('Billing First Name', 'fslm') ?></td>
                                <td></td>
                                <td><b>[site_name]<b/></td>
                                <td><?php echo __('Site Name', 'fslm') ?></td>
                            </tr>

                            <tr>
                                <td><b>[blname]<b/></td>
                                <td><?php echo __('Billing Last Name', 'fslm') ?></td>
                                <td></td>
                                <td><b>[url]<b/></td>
                                <td><?php echo __('Site URL', 'fslm') ?></td>
                            </tr>

                            <tr>
                                <td><b>[sfname]<b/></td>
                                <td><?php echo __('Shipping First Name', 'fslm') ?></td>
                                <td></td>
                                <td><b>[myaccount_url]<b/></td>
                                <td><?php echo __('My Account page URL', 'fslm') ?></td>
                            </tr>


                        </tbody>
                    </table>

                    <br>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Add WooCommerce Default Email Header &amp; Footer', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_add_wc_header_and_footer" <?php echo esc_attr( get_option('fslm_add_wc_header_and_footer', 'on'))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Add License Keys to Default WooCommerce Email Too(Default WooCommerce email without the custom template)', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_add_lk_wc_de" <?php echo esc_attr( get_option('fslm_add_lk_wc_de', 'on'))=='on'?'checked':''; ?>>
                            <div class="helper">?<div class="tip">
                                    <?php echo __('In addition to the email with the custom template the license keys will be added to WooCommerce\'s default order email too', 'fslm'); ?>
                                </div></div>
                        </div>
                    </div>
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Send a second email that contain the license keys only and uses the template', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_add_lk_se" <?php echo esc_attr( get_option('fslm_add_lk_se', 'off'))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Heading', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_mail_heading" id="fslm_mail_heading" type="text" value="<?php echo $heading ?>">
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Subject', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" type="text" name="fslm_mail_subject"  id="fslm_mail_heading"  value="<?php echo $subject; ?>">
                        </div>
                    </div>

                    <div>
                        <div class="label">
                            <span class="mb15"><?php echo __('Message', 'fslm'); ?></span>
                        </div>
                        <div class="input xl">
                            <textarea class="fslm_mail_message" name="fslm_mail_message" id="fslm_mail_message" type="email" ><?php echo $message ?></textarea>
                        </div>
                    </div>

                <?php

                    submit_button(); ?>
            </form>
                    <?php
                }else if ($current_tab == 'encryption') {
                    ?>

                    <form action="<?php echo admin_url('admin.php?action=save_encryption_setting') ?>" method="post">

                        <h3><?php echo  __('Encryption Settings', 'fslm'); ?>:</h3>

                        <?php

                        $upload_directory = wp_upload_dir();
                        $target_file = $upload_directory['basedir'] . '/fslm_files/encryption_key.php';

                        if(!@include_once($target_file)) {
                            set_encryption_key('5RdRDCmG89DooltnMlUG', '2Ve2W2g9ANKpvQNXuP3w');
                            @include_once($target_file);
                        }

                        ?>

                        <div class="input-box">
                            <div class="label">
                                <span><?php echo  __('Data Encryption Key', 'fslm'); ?></span>
                            </div>
                            <div class="input">

                                <input class="input-field" type="text" name="fslm_encryption_key" value="<?php echo ENCRYPTION_KEY; ?>">
                                <div class="helper">?<div class="tip">
                                        <?php echo __('The key used to encrypt/decrypt license keys in the database', 'fslm'); ?>
                                    </div></div>
                            </div>

                        </div>

                        <div class="input-box">
                            <div class="label">
                                <span><?php echo  __('Data Encryption VI', 'fslm'); ?></span>
                            </div>
                            <div class="input">

                                <input class="input-field" type="text" name="fslm_encryption_vi" value="<?php echo ENCRYPTION_VI; ?>">
                                <div class="helper">?<div class="tip">
                                        <?php echo __('The VI used to encrypt/decrypt license keys in the database', 'fslm'); ?>
                                    </div></div>
                            </div>

                        </div>

                        <?php if (!extension_loaded('openssl')) { ?>
                            <p class="no_openssl"><?php echo __('Open SSL is not installed on this server license keys will be stored without encryption ', 'fslm') ?></p>
                        <?php } ?>

                            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>

                    </form>

                <?php

                }else if ($current_tab == 'order_status') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_order_status_option_group');
                    do_settings_sections('fslm_order_status_option_group');

                    ?>

                    <h3><?php echo  __('License Delivery Settings', 'fslm'); ?>:</h3>

                    <table class="wp-list-table widefat fixed striped posts">
                        <thead>
                            <tr>
                                <td><strong><?php echo __('Order Status', 'fslm') ?></strong></td>
                                <td><strong><?php echo __('Send', 'fslm') ?></strong></td>
                                <td>
                                    <strong><?php echo __('Revoke', 'fslm') ?></strong><div class="helper">?<div class="tip">
                                        <?php echo __('Remove the assigned license key and change its status to returned', 'fslm'); ?>
                                    </div></div>
                                </td>
                                <td>
                                    <strong><?php echo __('Hide', 'fslm') ?></strong><div class="helper">?<div class="tip">
                                        <?php echo __('Hide the assigned license key from the buyer in the order history and emails', 'fslm'); ?>
                                    </div></div>
                                </td>
                            </tr>
                        </thead>

                        <tbody>

                    <?php

                    $on_status_send   = array('completed', 'processing');
                    $on_status_revoke = array('refunded');
                    $on_status_hide   = array();
                    

                    /////////////////////////
        
        
                    $order_statuses = (array) FS_WC_licenses_Manager::get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));

                    if($order_statuses && !is_wp_error($order_statuses)) {
                        foreach($order_statuses as $s) {

                            if(version_compare(WOOCOMMERCE_VERSION, '2.2', '>=' )) {

                                $s->slug = str_replace('wc-', '', $s->slug);     

                            }
                            
                            $default_send   = 'off';
                            $default_revoke = 'off';
                            $default_hide   = 'off';

                            if (in_array($s->slug, $on_status_send))   $default_send = 'on';
                            if (in_array($s->slug, $on_status_revoke)) $default_revoke = 'on';
                            if (in_array($s->slug, $on_status_hide))   $default_hide = 'on';

                            ?>

                            <tr>

                                <td><strong><?php echo $s->name ?></strong></td>
                                <td><input type="checkbox" name="fslm_send_when_<?php echo $s->slug ?>" <?php echo esc_attr(get_option('fslm_send_when_' . $s->slug, $default_send)) == 'on' ? 'checked' : ''; ?>></td>
                                <td><input type="checkbox" name="fslm_revoke_when_<?php echo $s->slug ?>" <?php echo esc_attr(get_option('fslm_revoke_when_' . $s->slug, $default_revoke)) == 'on' ? 'checked' : ''; ?>></td>
                                <td><input type="checkbox" name="fslm_hide_when_<?php echo $s->slug ?>" <?php echo esc_attr(get_option('fslm_hide_when_' . $s->slug, $default_hide)) == 'on' ? 'checked' : ''; ?>></td>

                            </tr>

                        <?php } ?>

                        </tbody>
                    </table>

                    <?php

                    }
                    
                    submit_button();

                    echo '</form>';

                }else if ($current_tab == 'extra') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_extra_option_group');
                    do_settings_sections('fslm_extra_option_group');

                    ?>

                    <h3><?php echo  __('Extra Settings', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Delete Plugins License Keys Tables Before Deactivating The Plugin', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_delete_lk_db_tables" <?php echo esc_attr( get_option('fslm_delete_lk_db_tables', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Delete Plugins Generator Rules Tables Before Deactivating The Plugin', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_delete_gr_db_tables" <?php echo esc_attr( get_option('fslm_delete_gr_db_tables', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Delete Plugins Licensed Products Tables Before Deactivating The Plugin', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_delete_lp_db_tables" <?php echo esc_attr( get_option('fslm_delete_lp_db_tables', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Enable Debug Logging', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_debug_enabled" <?php echo esc_attr( get_option('fslm_debug_enabled', 'off'))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <?php

                    submit_button();

                    echo '</form>';

                    echo '</form>';
                    

                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_update_option_group');
                    do_settings_sections('fslm_update_option_group');

                    echo '<input type="hidden" name="fslm_db_version" value="0">';

                    submit_button(__('Run database update script', 'fslm'));

                    echo '</form>';

                    // Delete product license keys


                    ?>

                    <br>


                    <h3><?php echo  __('Delete product license keys', 'fslm'); ?>:</h3>

                    <form method="post" action="<?php echo admin_url('admin.php?action=delete_product_license_keys') ?> ">

                        <div class="input-box">


                            <?php if( isset($_GET['dc']) ) { ?>
                                <div class="updated"><p><?php echo $_GET['dc'] . ' ' . __('license keys got deleted.', 'fslm')?></p></div>
                            <?php } ?>

                            <div class="label">
                                <span><?php echo __('Product', 'fslm'); ?></span>
                            </div>
                            <div class="input">
                                <select class="input-field elk_product_id" id="elk_product_id" name="product_id">
                                    <option value="0">Select Product</option>
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


                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Delete', 'fslm'); ?>">
                        <blockquote><p class="description"><?php echo __('All the license keys assigned to that product will be deleted.', 'fslm') ?></p></blockquote>

                        <?php

                        echo '</form>';

                    }else if ($current_tab == 'customizations') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_customizations_option_group');
                    do_settings_sections('fslm_customizations_option_group');

                    ?>

                    <h3><?php echo  __('Customizations', 'fslm'); ?>:</h3>
                    
                    <p><?php  _e('Those are features added for some buyer, all customization will be added in this secret menu so I can keep them updated when I update the plugin.
                        <br>Only features that may make the plugin look overcomplicated will be added here, normal features will be added in the visible setting menus.', 'fslm'); ?>
                    
                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Import prefixes/suffixes in the product page', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_is_import_prefix_suffix_enabled" <?php echo esc_attr( get_option('fslm_is_import_prefix_suffix_enabled', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    
                    <?php

                    submit_button();

                    echo '</form>';

                }else if ($current_tab == 'api') {
                    echo '<form method="post" action="options.php">';
                    settings_fields('fslm_api_option_group');
                    do_settings_sections('fslm_api_option_group');

                    ?>

                    <h3><?php echo  __('API Settings', 'fslm'); ?>:</h3>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo __('Disable API v1', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input type="checkbox" name="fslm_disable_api_v1" <?php echo esc_attr( get_option('fslm_disable_api_v1', ''))=='on'?'checked':''; ?>>
                        </div>
                    </div>

                    <div class="input-box">
                        <div class="label">
                            <span><?php echo  __('API Key', 'fslm'); ?></span>
                        </div>
                        <div class="input">
                            <input class="input-field" name="fslm_api_key" id="fslm_api_key" type="text" value="<?php echo esc_attr( get_option('fslm_api_key', '0A9Q5OXT13in3LGjM9F3')); ?>">
                        </div>
                    </div>


                    <?php

                    submit_button();

                    echo '</form>';

                }

            ?>
        </div>
    </div>
</div>

