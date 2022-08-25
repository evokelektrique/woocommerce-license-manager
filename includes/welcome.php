<?php
defined( 'ABSPATH' ) or  die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;
?>

<div class="wrap fslm">

    <div class="postbox">
        <div class="inside">

            <h1><?php echo __('Welcome to WooCommerce License Manager', 'fslm')?></h1>

            <p>
                <?php echo __('Before starting to use the plugin please go through the setting page and configure the plugin.', 'fslm') ?>
            </p>

            <ol>
                <li><b><?php echo __('Set your Encryption Keys', 'fslm')?>:</b> <?php echo __('Set your secret KEY and secret VI, they will be used to encrypt the license keys in the database', 'fslm')?></li>
                <li><b><?php echo __('Notifications Settings', 'fslm')?>:</b> <?php echo __('Receiver email, and minimum stock quantity before starting to get emails', 'fslm')?></li>
                <li><b><?php echo __('Order Status Settings', 'fslm')?>:</b> <?php echo __('Set when to send and when to remove license keys', 'fslm')?></li>
            </ol>
            <br><br>
            <h1><?php echo __('Activation', 'fslm')?></h1>

            <p  class="error">
		        <?php
		        if(isset($_GET['c'])) {
			        echo base64_decode( urldecode($_GET['c']));
		        }
		        ?>
            </p>
            
            <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="action" value="fslm_lr">



                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Envato Buyer Username', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="pcu" id="pcu" type="text" placeholder="Username">
                    </div>
                </div>

                <div class="input-box">
                    <div class="label">
                        <span><?php echo __('Purchase Code', 'fslm'); ?></span>
                    </div>
                    <div class="input">
                        <input class="input-field" name="pc" id="pc" type="text" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX">
                        <div class="helper">?
                            <div style="width: 350px" class="tip">
                                <?php echo __('You can find your purchase code in your Envato account/downloads', 'fslm'); ?>
                                <img class="ilksrc" src="<?php echo plugins_url() ?>/FS-License-Manager/assets/images/find-item-purchase-code.png" alt="Find Purchase code">
                            </div>
                        </div>

                    </div>
                </div>

                <p class="submit">
                    <input name="save" id="save-license-key" class="button button-primary" value="<?php echo __('Activate', 'fslm'); ?>" type="submit">
                    <br class="clear">
                </p>
                
            </form>

        </div>
    </div>
</div>
