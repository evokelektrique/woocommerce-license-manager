<?php
/**
 * Plugin Name: افزونه فروش کارت شارژ و لایسنس ووکامرس
 * Plugin URI: https://woocommerce.ir
 * Description: افزونه تجاری فروش کارت شارژ و لایسنس و انواع کد ها و رمز ها تحت سیستم ووکامرس. پیاده سازی توسط تیم ووکامرس فارسی.
 * Version: 4.3
 * Author: ووکامرس فارسی
 * Author URI: http://woocommerce.ir
 */

defined('ABSPATH')or  die('No script kiddies please!');
define("FSLM_PLUGIN_BASE", plugin_dir_path(__FILE__));
define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);


include(FSLM_PLUGIN_BASE . "/includes/sms.php");
																						
$upload_directory = wp_upload_dir();
$target_file = $upload_directory['basedir'] . '/fslm_files/encryption_key.php';

include(FSLM_PLUGIN_BASE . "/includes/functions.php");

global $months;
global $status;

if(!@include_once($target_file)) {
    
    set_encryption_key('5RdRDCmG89DooltnMlUG', '2Ve2W2g9ANKpvQNXuP3w');
    @include_once($target_file);
}

class FS_WC_licenses_Manager {

    public function __construct(){
		update_option('fslm_status', 'N/A');
		update_option('fslm_lk', 'babiato');
		update_option('fslm_ebun', 'babak');
		update_option('fslm_lks', 'active');
		//update_option('fslm_exp', '01.01.2030');
	    add_action('init', array($this, 'stock_sync_background_process'));
	    add_action('init', array($this, 'register_session'));
        add_action('init', array($this, 'add_plugin_actions'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'requestHandler'));
        add_action('admin_init', array($this, 'action_add_metaboxes'));

        add_action('save_post', array($this, 'save_product'));

        add_action('admin_notices', array($this, 'plugin_deactivation_notices'));

        add_action('add_meta_boxes', array($this, 'order_meta_boxe'));

        add_action('activated_plugin',  array($this, 'activation_redirect'));

        if($this->is_active()) {

            add_action('init', array($this,'license_viewer'));

            if(get_option('fslm_disable_api_v1', '') != 'on') {
                add_action('init', array($this,'api_requests_handler'));
            }

            if(get_option('fslm_auto_expire', '') == 'on') {
                add_action('init', array($this,'auto_expire_license_keys'));
            }

            if(get_option('fslm_delete_keys', '') == 'on') {
                add_action('before_delete_post', array($this,'delete_license_keys'));
                add_action('woocommerce_delete_product_variation', array($this,'delete_license_keys_variation'));
            }

            if(get_option('fslm_auto_redeem', '0') > 0) {
                add_action('init', array($this,'auto_redeem_license_keys'));
            }

            add_action('init', array($this,'api_requests_handler_v2'));
            add_action('init', array($this,'plugin_init'));
            add_action('init', array($this,'add_actions'));
            add_action('init', array($this,'redeem_key'));

            add_action("woocommerce_email_after_order_table", array($this, "add_license_key_to_the_email"), 1, 1);

            add_action('woocommerce_order_status_changed', array($this,'action_woocommerce_order_status_changed'), 1, 3);

            if(get_option('fslm_enable_cart_validation', '')!='on'){
                add_action('woocommerce_check_cart_items', array($this, 'validate_cart_content'));
            }

            if(get_option('fslm_show_adminbar_notifs', 'on')=='on'){
                add_action('admin_bar_menu',  array($this,'admin_notifications'), 999);
            }

            add_action('woocommerce_before_order_itemmeta', array($this,'fslm_before_order_itemmeta'), 10, 3);
            add_action('woocommerce_order_details_before_order_table', array($this,'fslm_order_item_meta_start'), 10, 1);

            add_shortcode('license_keys', array($this, 'license_keys_shortcode'));
	
	        //add_action( 'woocommerce_subscription_payment_complete', array($this, 'license_renewal'), 10, 2 );

        }

        add_action('wp_ajax_fslm_lr', array($this, 'lr_callback'));
        add_action('wp_ajax_nopriv_fslm_lr', array($this, 'lr_callback'));

        add_action('wp_ajax_fslm_deactivate', array($this, 'fslm_deactivate_callback'));
        add_action('wp_ajax_nopriv_fslm_deactivate', array($this, 'fslm_deactivate_callback'));

        add_action('woocommerce_process_shop_order_meta', array($this, 'process_shop_order_meta'), 1010, 1);

    }
    
    public function stock_sync_background_process() {
	
	    if (!function_exists('sem_get')) {
		    function sem_get($key, $max = null, $permissions = null, $autorelease = null) {
			    return fopen(__FILE__ . '.sem.' . $key, 'w+');
		    }
		    function sem_acquire($sem_id) {
			    return flock($sem_id, LOCK_EX);
		    }
		    function sem_release($sem_id) {
			    return flock($sem_id, LOCK_UN);
		    }
	    }
     
	    if (get_option('fslm_stock_sync', '') == 'on') {
	        
	        $interval = get_option('fslm_stock_sync_frequency', '300');
	        $time     = time();
	        
	        if($interval < 10) $interval = 10;
		
		    if(get_option('fslm_stock_sync_last_run', '0') < ($time - $interval)) {
		     
			    // exclusive control
			    $semaphore_key = 64537;		// unique integer key for this semaphore (Rush fan!)
			    $semaphore_max = 1;		// The number of processes that can acquire this semaphore
			    $semaphore_permissions = 0666;	// Unix style permissions for this semaphore
			    $semaphore_autorelease = 1;	// Auto release the semaphore if the request shuts down
			
			    // open a new or get an existing semaphore
			    $semaphore = sem_get($semaphore_key, $semaphore_max, $semaphore_permissions, $semaphore_autorelease);
			    if($semaphore) {
				
				    // acquire exclusive control
				    sem_acquire($semaphore);
				
				    //error_log("SR");
				
				    require_once('includes/fslm-stock-sync.php');
				
				    $stock_sync_background_process = new StockSyncBackgroundProcess();
				    $stock_sync_background_process->data(array())->dispatch();
				
			
				    if(!add_option('fslm_stock_sync_last_run', $time)){
					    update_option('fslm_stock_sync_last_run', $time);
				    }
				    
				    // release exclusive control
				    sem_release($semaphore);
				
			    }
			
			
		    }
		
	    }
    }
    
//    public function license_renewal($subscription) {
//        global $wpdb;
//	    $order_id = $subscription->get_parent_id();
//
//	    $meta = get_post_meta((int)$order_id, 'fslm_json_license_details', true);
//	    $meta = str_replace("\\", "", $meta);
//	    $meta = json_decode($meta);
//
//	    foreach ($meta as $license) {
//		    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license->license_key}'");
//
//		    if ($query) {
//			    $query = $query[0];
//
//			    unset($query);
//		    }
//	    }
//
//    }
    
    
    public function update_stock($product_id, $variation_id) {
	    if(get_option('fslm_stock_sync', '') == 'on') {
		
		    global $wpdb;
			
		
		    $target = $product_id;
		    if($variation_id != 0) {
			    $target = $variation_id;
		    }
		
		    $manage_stock = get_post_meta($target, '_manage_stock', true);
		
		    if($manage_stock == 'yes') {
			    if (($this->is_licensing_enabled($product_id, $variation_id)) && (!$this->isGeneratorActive($product_id, $variation_id))) {
				    $query = $wpdb->get_results("SELECT remaining_delivre_x_times FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id='{$product_id}' AND variation_id = '{$variation_id}' AND LOWER(license_status)='available'");
				    $lk_count = 0;
				
				    if ($query) {
					    foreach ($query as $query) {
						    $lk_count = $lk_count + (int) ($query->remaining_delivre_x_times);
					    }
				    }
				    $quantity = $lk_count;
				
				    if ($quantity > 0) {
				        
                        update_post_meta($target, '_stock', $quantity);
                        update_post_meta($target, '_stock_status', 'instock');
                        wp_remove_object_terms($target, 'outofstock', 'product_visibility');
                        wp_remove_object_terms($target, 'exclude-from-catalog', 'product_visibility');
					
				    } else {
				        
                        update_post_meta($target, '_stock', $quantity);
                        update_post_meta($target, '_stock_status', 'outofstock');
                        wp_set_post_terms($target, 'outofstock', 'product_visibility', true);
                        
					    
				    }
				
			    }
		    }
	    }
    }

    public function delete_license_keys($post_id) {
        global $post_type;
        global $wpdb;

        if ( $post_type == 'product' ) {
            $wpdb->delete($wpdb->prefix . 'wc_fs_product_licenses_keys', array('product_id' => $post_id));
	
	        if(get_option('fslm_stock_sync', '') == 'on') {
		        if(!add_option('fslm_stock_sync_last_run', '0')){
			        update_option('fslm_stock_sync_last_run', '0');
		        }
            }
        }

    }

    public function delete_license_keys_variation($post_id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'wc_fs_product_licenses_keys', array('variation_id' => $post_id));
	
	    if(get_option('fslm_stock_sync', '') == 'on') {
		    if(!add_option('fslm_stock_sync_last_run', '0')){
			    update_option('fslm_stock_sync_last_run', '0');
		    }
	    }

    }

    public function add_plugin_actions() {
        
        if((current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' )) && $this->is_active()){
            add_action('wp_ajax_fslm_export_csv_lk', array($this, 'export_csv_lk_callback'));
            add_action('wp_ajax_nopriv_fslm_export_csv_lk', array($this, 'export_csv_lk_callback'));

            add_action('wp_ajax_fslm_export_csv_gr', array($this, 'export_csv_gr_callback'));
            add_action('wp_ajax_nopriv_fslm_export_csv_gr', array($this, 'export_csv_gr_callback'));

            add_action('wp_ajax_fslm_export_ps', array($this, 'export_ps_callback'));
            add_action('wp_ajax_nopriv_fslm_export_ps', array($this, 'export_ps_callback'));

            add_action('wp_ajax_fslm_add_license_ajax', array($this, 'add_license_ajax_callback'));
            add_action('wp_ajax_nopriv_fslm_add_license_ajax', array($this, 'add_license_ajax_callback'));

            add_action('wp_ajax_fslm_save_metabox', array($this, 'fslm_save_metabox_callback'));
            add_action('wp_ajax_nopriv_fslm_save_metabox', array($this, 'fslm_save_metabox_callback'));

            add_action('wp_ajax_fslm_generator_rules', array($this, 'fslm_generator_rules_callback'));
            add_action('wp_ajax_nopriv_fslm_generator_rules', array($this, 'fslm_generator_rules_callback'));

            add_action('wp_ajax_fslm_import_csv_lk', array($this, 'import_csv_lk_callback'));
            add_action('wp_ajax_nopriv_fslm_import_csv_lk', array($this, 'import_csv_lk_callback'));

            add_action('wp_ajax_fslm_import_csv_gr', array($this, 'import_csv_gr_callback'));
            add_action('wp_ajax_nopriv_fslm_import_csv_gr', array($this, 'import_csv_gr_callback'));

            add_action('wp_ajax_fslm_import_ps', array($this, 'import_ps_callback'));
            add_action('wp_ajax_nopriv_fslm_import_ps', array($this, 'import_ps_callback'));

            add_action('wp_ajax_fslm_import_lko', array($this, 'import_lko_callback'));
            add_action('wp_ajax_nopriv_fslm_import_lko', array($this, 'import_lko_callback'));

            add_action('wp_ajax_fslm_import_ilko', array($this, 'import_ilko_callback'));
            add_action('wp_ajax_nopriv_fslm_import_ilko', array($this, 'import_ilko_callback'));

            add_action('wp_ajax_fslm_resend', array($this, 'fslm_resend_callback'));
            add_action('wp_ajax_nopriv_fslm_resend', array($this, 'fslm_resend_callback'));


            add_action('wp_ajax_fslm_replace_key', array($this, 'fslm_replace_key_callback'));
            add_action('wp_ajax_nopriv_fslm_replace_key', array($this, 'fslm_replace_key_callback'));

            add_action('wp_ajax_fslm_reload_mb', array($this, 'fslm_reload_mb_callback'));
            add_action('wp_ajax_nopriv_fslm_reload_mb', array($this, 'fslm_reload_mb_callback'));


            add_action('wp_ajax_fslm_export_csv_lk_une', array($this, 'export_csv_lk_une_callback'));
            add_action('wp_ajax_nopriv_fslm_export_csv_lk_une', array($this, 'export_csv_lk_une_callback'));

            add_action('wp_ajax_fslm_export_csv_lk_une_edit', array($this, 'fslm_export_csv_lk_une_edit_callback'));
            add_action('wp_ajax_nopriv_fslm_export_csv_lk_une_edit', array($this, 'fslm_export_csv_lk_une_edit_callback'));

            add_action('wp_ajax_fslm_import_csv_lk_une', array($this, 'import_csv_lk_une_callback'));
            add_action('wp_ajax_nopriv_fslm_import_csv_lk_une', array($this, 'import_csv_lk_une_callback'));

            add_action('wp_ajax_fslm_import_csv_lk_une_edit', array($this, 'fslm_import_csv_lk_une_edit_callback'));
            add_action('wp_ajax_nopriv_fslm_import_csv_lk_une_edit', array($this, 'fslm_import_csv_lk_une_edit_callback'));

            add_action('wp_ajax_fslm_import_csv_cpm_lk', array($this, 'import_csv_lk_cpm_callback'));
            add_action('wp_ajax_nopriv_fslm_import_csv_cpm_lk', array($this, 'import_csv_lk_cpm_callback'));


            add_action('wp_ajax_fslm_bulk_generate', array($this, 'fslm_bulk_generate_callback'));
            add_action('wp_ajax_nopriv_fslm_bulk_generate', array($this, 'fslm_bulk_generate_callback'));





            add_action('wp_ajax_fslm_filter', array($this, 'license_key_filter_callback'));
            add_action('wp_ajax_nopriv_fslm_filter', array($this, 'license_key_filter_callback'));

            add_action('wp_ajax_fslm_replace_item_keys', array($this, 'fslm_replace_item_keys_callback'));
            add_action('wp_ajax_nopriv_fslm_replace_item_keys', array($this, 'fslm_replace_item_keys_callback'));

            add_action('wp_ajax_fslm_new_item_key', array($this, 'fslm_new_item_key_callback'));
            add_action('wp_ajax_nopriv_fslm_new_item_key', array($this, 'fslm_new_item_key_callback'));

            add_action('wp_ajax_fslm_replace_item_key', array($this, 'fslm_replace_item_key_callback'));
            add_action('wp_ajax_nopriv_fslm_replace_item_key', array($this, 'fslm_replace_item_key_callback'));


            if(get_option('fslm_show_available_license_keys_column', '') == 'on') {
                add_filter( 'manage_edit-product_columns', array($this, 'available_license_keys_column'), 20 );
                add_action( 'manage_posts_custom_column',  array($this, 'populate_available_license_keys' ), 10, 2);
            }

        }

    }

    function available_license_keys_column( $columns_array ) {

        return array_slice( $columns_array, 0, 3, true )
            + array( 'licenses' => 'Licenses' )
            + array_slice( $columns_array, 3, NULL, true );


    }

    function populate_available_license_keys( $column_name, $post_id ) {

        if( $column_name  == 'licenses' ) {
            global $wpdb;

            $count = $wpdb->get_var("SELECT SUM(remaining_delivre_x_times ) FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '{$post_id}' AND LOWER(license_status)='available'");
            echo $count;
        }

    }






    public function get_encrypted_license_key_status($license_key_encrypted, $all = false){
        global $wpdb;

        if(get_option('fslm_auto_expire', '') == 'on') {
            $this->auto_expire_license_keys();
        }

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key_encrypted}'");

        if($all) {
            return $query;
        }

        if($query){
            $query = $query[0];

            return strtolower($query->license_status);

            unset($query);
        }

        return "none";
    }

    public function redeem_key() {
        global $wpdb;


        if(isset($_POST['fslm_redeem_type']) && isset($_POST['fslm_redeem_key'])) {

            if($_POST['fslm_redeem_type'] == "id") {

                $order_id = $wpdb->get_var("SELECT order_id FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_id='". intval($_POST['fslm_redeem_key']) ."'");
                $order = wc_get_order($order_id);

                $user_id         = $order->get_user_id();
                $current_user_id = get_current_user_id();

                if(is_user_logged_in() && $user_id == $current_user_id) {

                    $data = array(
                        'license_status' => 'redeemed'
                    );

                    $where = array(
                        "license_id" => intval($_POST['fslm_redeem_key'])
                    );

                    $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

                }

            } else if($_POST['fslm_redeem_type'] == "key") {

                $meta = get_post_meta($_POST['fslm_order_id'], 'fslm_json_license_details', true);
                $json = str_replace("\\", "", $meta);
                $values = json_decode($json, true);

                if($values){

                    foreach($values as $key => $value){
                        if (isset($value['item_id']) && isset($_POST['fslm_redeem_key']) && $_POST['fslm_redeem_key'] == $value['item_id']) {
                            $order_id = $wpdb->get_var("SELECT order_id FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='". $value['license_key'] ."'");

                            $order = wc_get_order($order_id);

                            $user_id         = $order->get_user_id();
                            $current_user_id = get_current_user_id();

                            if(is_user_logged_in() && $user_id == $current_user_id) {

                                $data = array(
                                    'license_status' => 'redeemed'
                                );

                                $where = array(
                                    "license_key" => $value['license_key']
                                );

                                $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
                            }

                        }
                    }
                }
            }
        }
    }

    public function sync_stock() {
        global $wpdb;

        $configs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_licensed_products WHERE active='1'");

        if($configs) {
            foreach ($configs as $config) {

                $generator_active = $this->isGeneratorActive($config->product_id, $config->variation_id);

                if($generator_active == false) {
                    $target = $config->product_id;
                    if($config->variation_id != 0) {
                        $target = $config->variation_id;
                    }

                    $manage_stock = get_post_meta($target, '_manage_stock', true);


                    if($manage_stock == 'yes') {
                        $available_keys_count = $wpdb->get_var("SELECT COUNT(*)FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id='" . $config->product_id . "' AND variation_id = '" . $config->variation_id . "' AND LOWER(license_status)='available'");

                        update_post_meta($target, '_stock', $available_keys_count);

                        if($available_keys_count == 0) {
                            update_post_meta($target, '_stock_status', wc_clean('outofstock'));
                            wp_set_post_terms( $target, 'outofstock', 'product_visibility', true );
                        } else {
                            update_post_meta($target, '_stock_status', wc_clean('instock'));
                            wp_remove_object_terms( $target, 'outofstock', 'product_visibility' );
                            wp_remove_object_terms( $target, 'exclude-from-catalog', 'product_visibility' );
                        }


                        wc_delete_product_transients($target);
                    }

                }
            }
        }

    }

    public function auto_expire_license_keys() {
        global $wpdb;

        if((time()-86400) > get_option('fslm_last_auto_expire_license_keys', '0')) {
            $todayDate = date("Y-m-d");

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys
                  SET license_status = 'expired'
                  WHERE
                      expiration_date  != '0000-00-00' AND
                      expiration_date  != '' AND
                      expiration_date  IS NOT NULL AND
                      expiration_date  <= '$todayDate'
               ";


            $wpdb->query($sql);

            update_option('fslm_last_auto_expire_license_keys', time());
        }

    }

    public function auto_redeem_license_keys() {
        global $wpdb;

        $todayDate = date("Y-m-d");


        $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys
                  SET license_status = 'redeemed'
                  WHERE
                      license_status = 'sold' AND
                      sold_date  != '0000-00-00' AND
                      sold_date  != '' AND
                      sold_date  IS NOT NULL AND
                      DATE_ADD(sold_date, INTERVAL 30 DAY) <= '$todayDate'
               ";

        $wpdb->query($sql);
    }

	public function register_session(){
		//if( !session_id() )
		//	session_start();
		
		$debug = get_option('fslm_debug_enabled', 'off');
		if((isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'wp-plugins.local') || $debug == 'on') {
		 
			ini_set('display_errors', 0);
			ini_set('log_errors', 1);
			ini_set('error_log', dirname(__FILE__) . '/logs/error_log.txt');
			error_reporting(E_ALL | E_STRICT);
			
		} else {
			
			error_reporting(0);
			
		}
		
	}

    public function fslm_replace_item_keys_callback() {
        global $wpdb;

	    $item_id  = $_POST['fslm_item_id'];
	    $order_id = $_POST['fslm_order_id'];

	    $order = wc_get_order($order_id);

	    $meta = get_post_meta($order_id, 'fslm_json_license_details', true);
	    $json = str_replace("\\", "", $meta);

	    $values = json_decode($json, true);

	    $order_type = get_option("fslm_key_delivery", "fifo");
	    $order_by   = 'ASC';
	    if($order_type == 'lifo') {
		    $order_by = 'DESC';
	    }

	    $json_license_details = "";
	    $json_license_details_array = array();

	    $key_assigned = false;

	    if($values){

		    foreach($values as $key => $value){
			    if (isset($value['item_id']) && $item_id == $value['item_id']) {

			        //----------------------------------
				    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND license_status = 'available' AND remaining_delivre_x_times > 0 ORDER BY license_id {$order_by} LIMIT 1");

				    if($query){
					    $query= $query[0];

                        $license_key = $query->license_key;

                        $max_instance_number = $query->max_instance_number;
                        $expiration_date = $query->expiration_date;
                        $valid = $query->valid;
                        $remaining_delivre_x_times = $query->remaining_delivre_x_times;


                        $nd_delivered = $remaining_delivre_x_times;


					    $json_license_details_array = array(
                            "license_id" => $query->license_id,
                            "item_id" => $value['item_id'],
                            "product_id" => $value['product_id'],
                            "variation_id" => $value['variation_id'],
                            "license_key" => $license_key,
                            "max_instance_number" => $max_instance_number,
                            "visible" => "Yes",
                            "uses"                => $nd_delivered
                        );

					    if ($valid > 0) {
						    $json_license_details_array["expiration_date"] = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
					    } else if (($expiration_date != '0000-00-00') && ($expiration_date != '')) {
						    $json_license_details_array["expiration_date"] = $expiration_date;
					    } else {
						    $json_license_details_array["expiration_date"] = "0000-00-00";
					    }
					
					
					    $data = array(
						    "license_status"            => "sold",
						    "order_id"                  => $order_id,
						    "remaining_delivre_x_times" => 0,
						    "owner_first_name"          => $order->get_billing_first_name(),
						    "owner_last_name"           => $order->get_billing_last_name(),
						    "owner_email_address"       => $order->get_billing_email(),
                            "expiration_date"           => $json_license_details_array["expiration_date"],
                            'sold_date' => date('Y-m-d')
					    );
					
					    if($remaining_delivre_x_times == 1){
						
						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => 0,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );
						
					    }else if($remaining_delivre_x_times <= $nd_delivered){
						
						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );
						
					    }else if($remaining_delivre_x_times > $nd_delivered){
						
						    $data = array(
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );
						
					    }
					
					    $where = array(
						    "license_id" => $query->license_id
					    );
					
					    $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
					
					    $this->update_stock($value['product_id'], $value['variation_id']);

					    $key_assigned = true;

                    }


				    if($this->isGeneratorActive($value['product_id'], $value['variation_id'])){

					    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND  active = '1'");

					    if($query){
						    $query = $query[0];

						    if($key_assigned == false) {
							    $prefix = $query->prefix;
							    $chunks_number = $query->chunks_number;
							    $chunks_length = $query->chunks_length;
							    $suffix = $query->suffix;
							    $max_instance_number = $query->max_instance_number;
							    $valid = $query->valid;
							    $expires = "0000-00-00";



							    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
							    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    while($this->licenseKeyExist($license_key)){
								    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
								    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    }

                                if ($valid > 0) {
                                    $expires = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
                                } else {
                                    $expires = "0000-00-00";
                                }

							    $json_license_details_array = array(
								    "license_id" => 0,
								    "item_id" => $value['item_id'],
								    "product_id" => $value['product_id'],
								    "variation_id" => $value['variation_id'],
								    "license_key" => $license_key,
								    "max_instance_number" => $max_instance_number,
								    "visible" => "Yes",
                                    "expiration_date"           => $expires
							    );

							    $data = array(
								    'product_id' => $value['product_id'],
								    'license_key' => $license_key,
								    'variation_id' => $value['variation_id'],
								    'max_instance_number' => $max_instance_number,
								    'owner_first_name' => $order->get_billing_first_name(),
								    'owner_last_name' => $order->get_billing_last_name(),
								    'owner_email_address' => $order->get_billing_email(),
								    'number_use_remaining' => $max_instance_number,
								    'creation_date' => date('Y-m-d H:i:s'),
								    'expiration_date' => $expires . ' 0:0:0',
								    'delivre_x_times' => '0',
								    'remaining_delivre_x_times' => '0',
								    'valid' => $valid,
								    'license_status' => 'sold',
								    'order_id' => (int)$order_id,
                                    'sold_date' => date('Y-m-d')
							    );
							    $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

						    }
					    }
				    }
			        //----------------------------------

				    $values[$key] = $json_license_details_array;

                    delete_post_meta($order_id, 'fslm_json_license_details');


                    add_post_meta($order_id, 'fslm_json_license_details', json_encode($values), true);

			    }

            }

	    }

	    die();
    }

    public function fslm_replace_item_key_callback() {
        global $wpdb;

	    $l_key  = $_POST['fslm_key'];
	    $order_id = $_POST['fslm_order_id'];

	    $order = wc_get_order($order_id);

	    $meta = get_post_meta($order_id, 'fslm_json_license_details', true);
	    $json = str_replace("\\", "", $meta);

	    $values = json_decode($json, true);

	    $order_type = get_option("fslm_key_delivery", "fifo");
	    $order_by   = 'ASC';
	    if($order_type == 'lifo') {
		    $order_by = 'DESC';
	    }

	    $json_license_details = "";
	    $json_license_details_array = array();

	    $key_assigned = false;

	    if($values){

		    foreach($values as $key => $value){

		        //echo $l_key . "<br>";
		        //echo $value['license_key'] . "<br>";

			    if (isset($value['license_key']) && $l_key == $value['license_key']) {

			        //----------------------------------
				    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND license_status = 'available' AND remaining_delivre_x_times > 0 ORDER BY license_id {$order_by} LIMIT 1");

				    if($query){
					    $query= $query[0];

                        $license_key = $query->license_key;

                        $max_instance_number = $query->max_instance_number;
                        $expiration_date = $query->expiration_date;
                        $valid = $query->valid;
                        $remaining_delivre_x_times = $query->remaining_delivre_x_times;


                        $nd_delivered = $remaining_delivre_x_times;


					    $json_license_details_array = array(
                            "license_id" => $query->license_id,
                            "item_id" => $value['item_id'],
                            "product_id" => $value['product_id'],
                            "variation_id" => $value['variation_id'],
                            "license_key" => $license_key,
                            "max_instance_number" => $max_instance_number,
                            "visible" => "Yes",
                            "uses"                => $nd_delivered
                        );

					    if ($valid > 0) {
						    $json_license_details_array["expiration_date"] = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
					    } else if (($expiration_date != '0000-00-00') && ($expiration_date != '')) {
						    $json_license_details_array["expiration_date"] = $expiration_date;
					    } else {
						    $json_license_details_array["expiration_date"] = "0000-00-00";
					    }


					    $data = array(
						    "license_status"            => "sold",
						    "order_id"                  => $order_id,
						    "remaining_delivre_x_times" => 0,
						    "owner_first_name"          => $order->get_billing_first_name(),
						    "owner_last_name"           => $order->get_billing_last_name(),
						    "owner_email_address"       => $order->get_billing_email(),
                            "expiration_date"           => $json_license_details_array["expiration_date"],
                            'sold_date' => date('Y-m-d')
					    );

					    if($remaining_delivre_x_times == 1){

						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => 0,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }else if($remaining_delivre_x_times <= $nd_delivered){

						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }else if($remaining_delivre_x_times > $nd_delivered){

						    $data = array(
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }

					    $where = array(
						    "license_id" => $query->license_id
					    );

					    $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
					
					    $this->update_stock($value['product_id'], $value['variation_id']);

					    $key_assigned = true;

                    }


				    if($this->isGeneratorActive($value['product_id'], $value['variation_id'])){

					    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND  active = '1'");

					    if($query){
						    $query = $query[0];

						    if($key_assigned == false) {
							    $prefix = $query->prefix;
							    $chunks_number = $query->chunks_number;
							    $chunks_length = $query->chunks_length;
							    $suffix = $query->suffix;
							    $max_instance_number = $query->max_instance_number;
							    $valid = $query->valid;
							    $expires = "0000-00-00";



							    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
							    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    while($this->licenseKeyExist($license_key)){
								    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
								    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    }

                                if ($valid > 0) {
                                    $expires = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
                                } else {
                                    $expires = "0000-00-00";
                                }

							    $json_license_details_array = array(
								    "license_id" => 0,
								    "item_id" => $value['item_id'],
								    "product_id" => $value['product_id'],
								    "variation_id" => $value['variation_id'],
								    "license_key" => $license_key,
								    "max_instance_number" => $max_instance_number,
								    "visible" => "Yes",
                                    "expiration_date"           => $expires
							    );



							    $data = array(
								    'product_id' => $value['product_id'],
								    'license_key' => $license_key,
								    'variation_id' => $value['variation_id'],
								    'max_instance_number' => $max_instance_number,
								    'owner_first_name' => $order->get_billing_first_name(),
								    'owner_last_name' => $order->get_billing_last_name(),
								    'owner_email_address' => $order->get_billing_email(),
								    'number_use_remaining' => $max_instance_number,
								    'creation_date' => date('Y-m-d H:i:s'),
								    'expiration_date' => $expires . ' 0:0:0',
								    'delivre_x_times' => '0',
								    'remaining_delivre_x_times' => '0',
								    'valid' => $valid,
								    'license_status' => 'sold',
								    'order_id' => (int)$order_id,
                                    'sold_date' => date('Y-m-d')
							    );
							    $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

						    }
					    }
				    }
			        //----------------------------------

				    $values[$key] = $json_license_details_array;

                    delete_post_meta($order_id, 'fslm_json_license_details');


                    add_post_meta($order_id, 'fslm_json_license_details', json_encode($values), true);

			    }

            }

	    }

	    die();
    }

    public function fslm_new_item_key_callback() {
        global $wpdb;

	    $order_id = $_POST['fslm_order_id'];
        $item_id = $_POST['fslm_item_id'];

	    $order = wc_get_order($order_id);

        $meta = get_post_meta($order_id, 'fslm_json_license_details', true);
        $json = str_replace("\\", "", $meta);

        $values = json_decode($json, true);

	    $order_type = get_option("fslm_key_delivery", "fifo");
	    $order_by   = 'ASC';
	    if($order_type == 'lifo') {
		    $order_by = 'DESC';
	    }

	    $json_license_details = "";
	    $json_license_details_array = array();

	    $key_assigned = false;


            $items = $order->get_items();

            foreach($items as $item => $value){

                if ($item_id == $item) {


			        //----------------------------------
				    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND license_status = 'available' AND remaining_delivre_x_times > 0 ORDER BY license_id {$order_by} LIMIT 1");

				    if($query){
					    $query= $query[0];

                        $license_key = $query->license_key;

                        $max_instance_number = $query->max_instance_number;
                        $expiration_date = $query->expiration_date;
                        $valid = $query->valid;
                        $remaining_delivre_x_times = $query->remaining_delivre_x_times;


                        $nd_delivered = $remaining_delivre_x_times;


					    $json_license_details_array = array(
                            "license_id" => $query->license_id,
                            "item_id" => $item,
                            "product_id" => $value['product_id'],
                            "variation_id" => $value['variation_id'],
                            "license_key" => $license_key,
                            "max_instance_number" => $max_instance_number,
                            "visible" => "Yes",
                            "uses"                => $nd_delivered
                        );

					    if ($valid > 0) {
						    $json_license_details_array["expiration_date"] = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
					    } else if (($expiration_date != '0000-00-00') && ($expiration_date != '')) {
						    $json_license_details_array["expiration_date"] = $expiration_date;
					    } else {
						    $json_license_details_array["expiration_date"] = "0000-00-00";
					    }


					    $data = array(
						    "license_status"            => "sold",
						    "order_id"                  => $order_id,
						    "remaining_delivre_x_times" => 0,
						    "owner_first_name"          => $order->get_billing_first_name(),
						    "owner_last_name"           => $order->get_billing_last_name(),
						    "owner_email_address"       => $order->get_billing_email(),
                            "expiration_date"           => $json_license_details_array["expiration_date"],
                            'sold_date' => date('Y-m-d')
					    );

					    if($remaining_delivre_x_times == 1){

						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => 0,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }else if($remaining_delivre_x_times <= $nd_delivered){

						    $data = array(
							    "license_status"            => "sold",
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }else if($remaining_delivre_x_times > $nd_delivered){

						    $data = array(
							    "order_id"                  => $order_id,
							    "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
							    "owner_first_name"          => $order->get_billing_first_name(),
							    "owner_last_name"           => $order->get_billing_last_name(),
							    "owner_email_address"       => $order->get_billing_email(),
                                "expiration_date"           => $json_license_details_array["expiration_date"],
                                'sold_date' => date('Y-m-d')
						    );

					    }

					    $where = array(
						    "license_id" => $query->license_id
					    );

					    $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
					
					    $this->update_stock($value['product_id'], $value['variation_id']);

					    $key_assigned = true;

                    }


				    if($this->isGeneratorActive($value['product_id'], $value['variation_id'])){

					    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND  active = '1'");

					    if($query){
						    $query = $query[0];

						    if($key_assigned == false) {
							    $prefix = $query->prefix;
							    $chunks_number = $query->chunks_number;
							    $chunks_length = $query->chunks_length;
							    $suffix = $query->suffix;
							    $max_instance_number = $query->max_instance_number;
							    $valid = $query->valid;
							    $expires = "0000-00-00";



							    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
							    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    while($this->licenseKeyExist($license_key)){
								    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
								    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
							    }

                                if ($valid > 0) {
                                    $expires = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
                                } else {
                                    $expires = "0000-00-00";
                                }

							    $json_license_details_array = array(
								    "license_id" => 0,
								    "item_id" => $item,
								    "product_id" => $value['product_id'],
								    "variation_id" => $value['variation_id'],
								    "license_key" => $license_key,
								    "max_instance_number" => $max_instance_number,
								    "visible" => "Yes",
                                    "expiration_date"           => $expires
							    );



							    $data = array(
								    'product_id' => $value['product_id'],
								    'license_key' => $license_key,
								    'variation_id' => $value['variation_id'],
								    'max_instance_number' => $max_instance_number,
								    'owner_first_name' => $order->get_billing_first_name(),
								    'owner_last_name' => $order->get_billing_last_name(),
								    'owner_email_address' => $order->get_billing_email(),
								    'number_use_remaining' => $max_instance_number,
								    'creation_date' => date('Y-m-d H:i:s'),
								    'expiration_date' => $expires . ' 0:0:0',
								    'delivre_x_times' => '0',
								    'remaining_delivre_x_times' => '0',
								    'valid' => $valid,
								    'license_status' => 'sold',
								    'order_id' => (int)$order_id,
                                    'sold_date' => date('Y-m-d')
							    );
							    $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

						    }
					    }
				    }
			        //----------------------------------

				    $values[] = $json_license_details_array;

                    delete_post_meta($order_id, 'fslm_json_license_details');


                    add_post_meta($order_id, 'fslm_json_license_details', json_encode($values), true);
                    add_post_meta($order_id, 'fslm_licensed', 'true', true);

                }
            }

	    die();
    }

    public function fslm_bulk_generate_callback() {
        global $wpdb;

        $rule_id  = $_POST['fslm_rule_id'];
        $quantity = $_POST['fslm_quantity'];


        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE rule_id = '$rule_id'");

	    if($query){
		    $query = $query[0];

		    for($i=0; $i<$quantity; $i++){
			    $prefix = $query->prefix;
			    $chunks_number = $query->chunks_number;
			    $chunks_length = $query->chunks_length;
			    $suffix = $query->suffix;
			    $max_instance_number = $query->max_instance_number;
			    $valid = $query->valid;
			    $expires = "0000-00-00";



			    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
			    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
			    while($this->licenseKeyExist($license_key)){
				    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
				    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
			    }


			    $data = array(
				    'product_id' => $query->product_id,
				    'license_key' => $license_key,
				    'variation_id' => $query->variation_id,
				    'max_instance_number' => $max_instance_number,
				    'owner_first_name' => "",
				    'owner_last_name' => "",
				    'owner_email_address' => "",
				    'number_use_remaining' => $max_instance_number,
				    'creation_date' => date('Y-m-d H:i:s'),
				    'expiration_date' => $expires . ' 0:0:0',
				    'delivre_x_times' => '1',
				    'remaining_delivre_x_times' => '1',
				    'valid' => $valid,
				    'license_status' => 'available',
				    'order_id' => 0,
                    'sold_date' => date('Y-m-d')
			    );
			    $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

		    }
	    }

	    echo $quantity . ' license key generated';
        die();
    }

	public function fslm_replace_key_callback() {
		$order_id = $_POST['fslm_resend_order_id'];

		delete_post_meta($order_id, 'fslm_licensed');
		delete_post_meta($order_id, 'fslm_json_license_details');

		$this->fslm_send_license_keys($order_id);

		echo __('Done, Reloading...', 'fslm');
		die();
	}


	public function license_key_filter_callback() {
		
		global $wpdb;
		
		require_once('includes/functions.php');
		
		$next_add_and = false;
		$filter_args = "";
		$limit = "";
		
		
		if($_POST['html_ml'] == "0" && $_POST['license_key'] != "") {
			
			$filter_args .= ' license_key = "' . encrypt_decrypt('encrypt', $_POST['license_key'], ENCRYPTION_KEY, ENCRYPTION_VI) . '"';
			$next_add_and = true;
			
		}
		
		if($_POST['mail'] != "") {

			if($next_add_and) {
				$filter_args = $filter_args . ' AND ';
			}
			
			$filter_args .= ' owner_email_address = "' . $_POST['mail'] . '"';
			
			$next_add_and = true;
			
		}
		
		if($_POST['name'] != "") {
			
			if($next_add_and) {
				$filter_args = $filter_args . ' AND ';
			}
			
			$filter_args .= ' owner_first_name LIKE "%' . $_POST['name'] . '%"';
			
			$next_add_and = true;
			
		}
		
		if($_POST['status'] != "-1") {
			
			if($next_add_and) {
				$filter_args = $filter_args . ' AND ';
			}
			
			$filter_args .= ' license_status = "' . $_POST['status'] . '"';
			
			$next_add_and = true;
			
		}
		
		if($_POST['product'] != "-1") {
			
			if($next_add_and) {
				$filter_args = $filter_args . ' AND ';
			}
			
			$filter_args .= ' product_id = "' . $_POST['product'] . '"';
			
			$next_add_and = true;
			
		}

        if($_POST['variation'] != "-1") {

            if($next_add_and) {
                $filter_args = $filter_args . ' AND ';
            }

            $filter_args .= ' variation_id = "' . $_POST['variation'] . '"';

            $next_add_and = true;

        }
		
		if($next_add_and) {
			$filter_args = ' WHERE ' . $filter_args;
		}else {
			
			$start_from          = 0;
			$license_keys_number = esc_attr(get_option('fslm_nb_rows_by_page', '15'));

			$limit = ' LIMIT '. $start_from . ', ' . $license_keys_number;
			
		}
		
		$querys = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys " . $filter_args . " ORDER BY license_id DESC " . $limit);

		//echo("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys " . $filter_args . " ORDER BY creation_date DESC" . $limit);
		
		$no_keys_found = true;
		if($querys) {
			foreach ($querys as $query) {
				
				$license_key = $query->license_key;
				$license_key = encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

				$license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
				
				$strpos_filter = true;
				
				if($_POST['license_key'] != "") {
					
					$strpos_filter = strpos($license_key, $_POST['license_key']);
					
				}
				
				if($strpos_filter !== false || $_POST['license_key'] == "") {
					
					$no_keys_found = false;
					
					$license_id = $query->license_id;
					$product_id = $query->product_id;

					if($query->variation_id!=0) {
						$single_variation = new WC_Product_Variation($query->variation_id);
						$variation_id = $single_variation->get_formatted_name();
					}else {
						$variation_id = 'Main Product';
					}


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
					$license_status = $query->license_status;

					if($image_license_key != '') {
						$upload_directory = wp_upload_dir();
						$image_license_key = '<img class="ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' .  $image_license_key . '">';
					}

					?>

					<tr id="post-<?php echo $license_id ?>" class="filter-result-item">
						<td class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo $license_id ?>">Select <?php echo $license_id ?></label>
							<input id="cb-select-<?php echo $license_id ?>" name="post[]" value="<?php echo $license_id ?>" type="checkbox">
							<div class="locked-indicator"></div>
						</td>
						<td>
                            <?php echo '<spen class="rhidden">' . __('ID', 'fslm') . ': </spen>'  ?><?php echo $license_id ?>
							<div class="row-actions fsactions">
								<span class="inline"><a href="<?php echo admin_url('admin.php') ?>?page=license-manager&function=edit_license&license_id=<?php echo $license_id ?>" class="editinline"><?php echo __('Edit', 'fslm'); ?></a> | </span>
								<span class="trash"><a href="<?php echo admin_url('admin.php') ?>?action=delete_license&license_id=<?php echo $license_id ?>" class="submitdelete" ><?php echo __('Delete', 'fslm'); ?></a></span>
								<span><a class="fslm_cpy_encrypted_key" href="#" data-ek="<?php echo $query->license_key; ?>"><?php echo __('Copy Encrypted Key', 'fslm'); ?></a></span>
							</div>
						</td>
						<td><?php echo '<spen class="rhidden">' . __('Product', 'fslm') . ': </spen>'  ?><?php echo get_the_title($product_id) ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Variation', 'fslm') . ': </spen>'  ?><?php echo $variation_id ?></td>
						<td><?php echo '<spen class="rhidden">' . __('License Key', 'fslm') . ': </spen>'  ?><?php echo $image_license_key . $license_key ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Owner', 'fslm') . ': </spen>'  ?><?php echo ($owner_name=='&nbsp;')?'none':$owner_name . ' - ' . $owner_email_address?></td>
						<td><?php echo '<spen class="rhidden">' . __('Activations', 'fslm') . ': </spen>'  ?><?php echo ($max_instance_number - $number_use_remaining). '/' . $max_instance_number ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Delivery', 'fslm') . ': </spen>'  ?><?php echo ($delivre_x_times - $remaining_delivre_x_times). '/' . $delivre_x_times ?></td>

						<td><?php echo '<spen class="rhidden">' . __('Created', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($creation_date) ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Activated', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($activation_date) ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Expires', 'fslm') . ': </spen>'  ?><?php echo fslm_format_date($expiration_date, true) ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Validity', 'fslm') . ': </spen>'  ?><?php echo $valid ?></td>
						<td><?php echo '<spen class="rhidden">' . __('Status', 'fslm') . ': </spen>'  ?><?php echo $license_status ?></td>
					</tr>


					<?php
					
				}
				
				
			}

		}
		
		if($no_keys_found){ ?>

			<tr>
			   <td colspan="13" class="center"><?php echo __('There is no license key in the database matching your filter settings', 'fslm'); ?></td>
			</tr>

		<?php
		}
  
		
		die();
		
	}
	
	function process_shop_order_meta($order_id) {

		
		$meta = get_post_meta((int)$order_id, 'fslm_json_license_details', true);

		if($meta != "") {
		    $meta = str_replace("\\", "", $meta);

            update_post_meta($order_id, 'fslm_json_license_details', $meta);
		}
		
	}
    
    public function export_csv_lk_une_callback(){

        $license_status = '';
        $product_id = '';

        if(isset($_REQUEST['elk_license_status'])&& $_REQUEST['elk_license_status'] != 'all'){
            $license_status = $_REQUEST['elk_license_status'];
        }

        if(isset($_REQUEST['elk_product_id'])&& $_REQUEST['elk_product_id'] != 'all'){
            $product_id = $_REQUEST['elk_product_id'];
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="license_manager__license_keys__' . date("__d_m_Y__H_i_s"). '__' . $_REQUEST['elk_product_id'] .'__' . $_REQUEST['elk_license_status'] . '.csv";');
        echo $this->generate_license_keys_csv_une($license_status, $product_id);
        die();
    }

    public function fslm_export_csv_lk_une_edit_callback(){

        $license_status = '';
        $product_id = '';

        if(isset($_REQUEST['elk_license_status'])&& $_REQUEST['elk_license_status'] != 'all'){
            $license_status = $_REQUEST['elk_license_status'];
        }

        if(isset($_REQUEST['elk_product_id'])&& $_REQUEST['elk_product_id'] != 'all'){
            $product_id = $_REQUEST['elk_product_id'];
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="license_manager__license_keys__' . date("__d_m_Y__H_i_s"). '__' . $_REQUEST['elk_product_id'] .'__' . $_REQUEST['elk_license_status'] . '.csv";');
        echo $this->generate_license_keys_csv_une($license_status, $product_id, true);
        die();
    }
    
    public function generate_license_keys_csv_une($license_status = '', $product_id = '', $editable = false){
        global $wpdb;

        $args = '';

        if($license_status != '' && $product_id != ''){
            $args .= "WHERE license_status = '{$license_status}' AND product_id = '{$product_id}'";
        }else if($license_status != ''){
            $args .= "WHERE license_status = '{$license_status}'";
        }else if($product_id != ''){
            $args .= "WHERE product_id = '{$product_id}'";
        }

        $output = "sep=,\n";

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys {$args}", ARRAY_A);

        if($query){

            $output .= '"'.implode('","',array_keys($query[0])).'"'."\n";

            foreach($query as $row){
                
                $row['license_key'] = $this->encrypt_decrypt('decrypt', $row['license_key'], ENCRYPTION_KEY, ENCRYPTION_VI);

                if($editable) {
	                $row['activation_date'] = "(" . $row['activation_date'] . ")";
	                $row['creation_date']   = "(" . $row['creation_date'] . ")";
	                $row['expiration_date'] = "(" . $row['expiration_date'] . ")";
                }
	
	            $row['device_id'] = str_replace('","', '|',$row['device_id']);

                $output .= '"'.implode('","',$row).'"'."\n";
                
            }
        }

        
        return $output;
    }

    
    public function fslm_import_csv_lk_une_edit_callback(){
        global $wpdb;

        if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
            move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);


            $columns = array();
            $flipped   = array_flip($columns);
            $indexes_found = false;

            /////////////////////////////////
	        $handle = fopen($tmp, 'r');
	        $delimiter = $this->detectDelimiter($tmp);
	        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $num = count($data);
                
                if(in_array('license_key', $data) && $indexes_found == false) {
                    $columns = $data;
                    $flipped = array_flip($columns);
                    $indexes_found = true;
                }

		        if(!in_array('license_key', $data)) {

                    // DEBUG DELETE
                    // echo $data[$flipped['device_id']] . '<br>';
		            // echo (isset($flipped['device_id']) && isset($data[$flipped['device_id']]) && $data[$flipped['device_id']] != "")?'["' . substr(str_replace('|', '","', $data[$flipped['device_id']]), 1, -2) . ']':''  . '<br>';
                    // END DEBUG DELETE

			        if(strpos($data[0], 'sep') === false) {

                        $_data = array(
                            'product_id'                => $data[$flipped['product_id']],
                            'variation_id'              => $data[$flipped['variation_id']],
                            'license_key'               => $this->encrypt_decrypt('encrypt', $data[$flipped['license_key']], ENCRYPTION_KEY, ENCRYPTION_VI),
                            'image_license_key'         => $data[$flipped['image_license_key']],
                            'license_status'            => $data[$flipped['license_status']],
                            'owner_first_name'          => $data[$flipped['owner_first_name']],
                            'owner_last_name'           => $data[$flipped['owner_last_name']],
                            'owner_email_address'       => $data[$flipped['owner_email_address']],
                            'delivre_x_times'           => $data[$flipped['delivre_x_times']],
                            'remaining_delivre_x_times' => $data[$flipped['remaining_delivre_x_times']],
                            'max_instance_number'       => $data[$flipped['max_instance_number']],
                            'number_use_remaining'      => $data[$flipped['number_use_remaining']],
                            'activation_date'           => substr($data[$flipped['activation_date']], 1, -1),
                            'creation_date'             => substr($data[$flipped['creation_date']], 1, -1),
                            'expiration_date'           => substr($data[$flipped['expiration_date']], 1, -1),
                            'valid'                     => $data[$flipped['valid']],
                            'order_id'                  => (isset($flipped['order_id']) && isset($data[$flipped['order_id']]))?$data[$flipped['order_id']]:'0',
                            'sold_date'                 => (isset($flipped['sold_date']) && isset($data[$flipped['sold_date']]))?$data[$flipped['sold_date']]:'0',
                            'device_id'                 => (isset($flipped['device_id']) && isset($data[$flipped['device_id']]) && $data[$flipped['device_id']] != "")?'["' . substr(str_replace('|', '","', $data[$flipped['device_id']]), 1, -2) . ']':'' 
                        );
    
                        $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $_data);
    
                        $this->set_licensing($data[1], $data[2], '1');
			        
                    }

		        }
	        }
	        fclose($handle);
            ////////////////////////////////
        }


        // DEBUG DELETE
        // die();
        // END DEBUG DELETE

	    if(get_option('fslm_stock_sync', '') == 'on') {
		    if(!add_option('fslm_stock_sync_last_run', '0')){
			    update_option('fslm_stock_sync_last_run', '0');
		    }
	    }
        
        $link = admin_url('admin.php?page=license-manager#hlk');
        wp_redirect($link);
        die();
    }

    public function import_csv_lk_une_callback(){
        global $wpdb;

        if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
            move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);

            $columns = array();
            $flipped   = array_flip($columns);
            $indexes_found = false;

            /////////////////////////////////
	        $handle = fopen($tmp, 'r');
	        $delimiter = $this->detectDelimiter($tmp);
	        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $num = count($data);
                
                if(in_array('license_key', $data) && $indexes_found == false) {
                    $columns = $data;
                    $flipped = array_flip($columns);
                    $indexes_found = true;
                }

		        if(!in_array('license_key', $data)) {
			
			        if(strpos($data[0], 'sep') === false) {
			            
                    $_data = array(
                            'product_id'                => $data[$flipped['product_id']],
                            'variation_id'              => $data[$flipped['variation_id']],
                            'license_key'               => $this->encrypt_decrypt('encrypt', $data[$flipped['license_key']], ENCRYPTION_KEY, ENCRYPTION_VI),
                            'image_license_key'         => $data[$flipped['image_license_key']],
                            'license_status'            => $data[$flipped['license_status']],
                            'owner_first_name'          => $data[$flipped['owner_first_name']],
                            'owner_last_name'           => $data[$flipped['owner_last_name']],
                            'owner_email_address'       => $data[$flipped['owner_email_address']],
                            'delivre_x_times'           => $data[$flipped['delivre_x_times']],
                            'remaining_delivre_x_times' => $data[$flipped['remaining_delivre_x_times']],
                            'max_instance_number'       => $data[$flipped['max_instance_number']],
                            'number_use_remaining'      => $data[$flipped['number_use_remaining']],
                            'activation_date'           => $data[$flipped['activation_date']],
                            'creation_date'             => $data[$flipped['creation_date']],
                            'expiration_date'           => $data[$flipped['expiration_date']],
                            'valid'                     => $data[$flipped['valid']],
                            'order_id'                  => (isset($flipped['order_id']) && isset($data[$flipped['order_id']]))?$data[$flipped['order_id']]:'0',
                            'sold_date'                 => (isset($flipped['sold_date']) && isset($data[$flipped['sold_date']]))?$data[$flipped['sold_date']]:'0',
                            'device_id'                 => (isset($flipped['device_id']) && isset($data[$flipped['device_id']]) && $data[$flipped['device_id']] != "")?'["' . substr(str_replace('|', '","', $data[$flipped['device_id']]), 1, -2) . ']':'' 
                        );
    
    
                        $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $_data);
    
                        $this->set_licensing($data[1], $data[2], '1');
			        
                    }

		        }
	        }
	        fclose($handle);
            ////////////////////////////////
        }
	
	    if(get_option('fslm_stock_sync', '') == 'on') {
		    if(!add_option('fslm_stock_sync_last_run', '0')){
			    update_option('fslm_stock_sync_last_run', '0');
		    }
	    }

        $link = admin_url('admin.php?page=license-manager#hlk');
        wp_redirect($link);
        die();
    }

	public function detectDelimiter($csvFile) {
		$delimiters = array(
			';' => 0,
			',' => 0,
			"\t" => 0,
			"|" => 0
		);

		$handle = fopen($csvFile, "r");
		$firstLine = fgets($handle);
		if($firstLine == "sep=,") $firstLine = fgets($handle);
		foreach ($delimiters as $delimiter => &$count) {
			$count = count(str_getcsv($firstLine, $delimiter));
		}

		return array_search(max($delimiters), $delimiters);
	}
    
    public function import_csv_lk_cpm_callback(){
        global $wpdb;

        if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
            move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);

            $columns = array();
            $flipped   = array_flip($columns);
            $indexes_found = false;

            /////////////////////////////////
            $handle = fopen($tmp, 'r');
            $delimiter = $this->detectDelimiter($tmp);
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $num = count($data);

                if(in_array('license_key', $data) && $indexes_found == false) {
                    $columns = $data;
                    $flipped = array_flip($columns);
                    $indexes_found = true;
                }

                if(!in_array('license_key', $data)) {

                    if(strpos($data[0], 'sep') === false) {

                        $_data = array(
                            'product_id'                => $data[$flipped['product_id']],
                            'variation_id'              => $data[$flipped['variation_id']],
                            'license_key'               => $this->encrypt_decrypt('encrypt', $data[$flipped['license_key']], ENCRYPTION_KEY, ENCRYPTION_VI),
                            'image_license_key'         => (isset($flipped['image_license_key']) && isset($data[$flipped['image_license_key']]))?$data[$flipped['image_license_key']]:'',
                            'license_status'            => $data[$flipped['license_status']],
                            'owner_first_name'          => $data[$flipped['owner_first_name']],
                            'owner_last_name'           => $data[$flipped['owner_last_name']],
                            'owner_email_address'       => $data[$flipped['owner_email_address']],
                            'delivre_x_times'           => (isset($flipped['delivre_x_times']) && isset($data[$flipped['delivre_x_times']]))?$data[$flipped['delivre_x_times']]:1,
                            'remaining_delivre_x_times' => (isset($flipped['remaining_delivre_x_times']) && isset($data[$flipped['remaining_delivre_x_times']]))?$data[$flipped['remaining_delivre_x_times']]:1,
                            'max_instance_number'       => (isset($flipped['max_instance_number']) && isset($data[$flipped['max_instance_number']]))?$data[$flipped['max_instance_number']]:1,
                            'number_use_remaining'      => (isset($flipped['number_use_remaining']) && isset($data[$flipped['number_use_remaining']]))?$data[$flipped['number_use_remaining']]:1,
                            'activation_date'           => $data[$flipped['activation_date']],
                            'creation_date'             => $data[$flipped['creation_date']],
                            'expiration_date'           => $data[$flipped['expiration_date']],
                            'valid'                     => $data[$flipped['valid']],
                            'order_id'                  => (isset($flipped['order_id']) && isset($data[$flipped['order_id']]))?$data[$flipped['order_id']]:'0',
                            'sold_date'                 => (isset($flipped['sold_date']) && isset($data[$flipped['sold_date']]))?$data[$flipped['sold_date']]:'0',
                            'device_id'                 => (isset($flipped['device_id']) && isset($data[$flipped['device_id']]) && $data[$flipped['device_id']] != "")?'["' . substr(str_replace('|', '","', $data[$flipped['device_id']]), 1, -2) . ']':''
                        );


                        $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $_data);

                        $this->set_licensing($data[1], $data[2], '1');

                    }

                }
            }
            fclose($handle);
            ////////////////////////////////
        }

        if(get_option('fslm_stock_sync', '') == 'on') {
            if(!add_option('fslm_stock_sync_last_run', '0')){
                update_option('fslm_stock_sync_last_run', '0');
            }
        }

        $link = admin_url('admin.php?page=license-manager#hlk');
        wp_redirect($link);
        die();
    }
    
    function fslm_reload_mb_callback() {

        require dirname(__FILE__) . '/includes/product_metabox.php';
		

        die();
    }

    function license_keys_shortcode() {

        $output = '';
        $order_id = isset($_GET['lk'])?$_GET['lk']:-1;

        if($order_id != -1) {
            if (!is_user_logged_in()) {
                return __('Please Login to be able to see license key details', 'fslm');
            }
	
	        $order = new WC_Order($order_id);
            
            if (current_user_can( 'manage_options' ) || get_current_user_id() == $order->get_user_id()) {
                $meta = get_post_meta((int)$order_id, 'fslm_json_license_details', true);
				
				$meta = str_replace("\\", "", $meta);

                $output .= $this->json_data_formatting_frontend($meta, $order_id);
            }

        }

        return $output;
    }

    function fslm_resend_callback() {

        $order_id = $_REQUEST['fslm_resend_order_id'];

        $this->send_mail($order_id);
        
        $order = new WC_Order($order_id);

        die(__('Done', 'fslm'));

    }

    public function order_meta_boxe() {

        add_meta_box('fslm_order_actions', __('License Manager'), array($this, 'order_meta_box_content'), 'shop_order', 'side', 'default');

    }

    public function order_meta_box_content() { ?>

        <?php if(isset($_GET['post']) && $_GET['post']) { ?>
            <input type="hidden" class="button button-primary" id="fslm_resend_order_id" value="<?php echo $_GET['post'] ?>">
            <input type="button" class="button button-primary" id="fslm_resend" value="<?php echo __('Resend License Keys Email', 'fslm'); ?>">
            <p></p>
        <?php } ?>
        <input type="button" class="button button-primary" id="fslm_replace_key" value="<?php echo __('Assign New License Keys', 'fslm'); ?>"><p></p>
        <input type="button" class="button button-primary" id="fslm_edit_alk" value="<?php echo __('Edit Assigned License Keys', 'fslm'); ?>"><p></p>
        <div id="fslm_resend_respons"></div>

    <?php }

    function send_mail($order_id){
        global $woocommerce;

        //$to   = get_post_meta($order_id, '_billing_email', true);
        
        $order = new WC_Order($order_id);
        $to = $order->get_billing_email();

        if(!$to || '' == trim($to))
            return true;

        $heading = $this->apply_mail_text_filters(get_option('fslm_mail_heading', __('License Keys for Order #[order_id]', 'fslm')), $order_id);
        $subject = $this->apply_mail_text_filters(get_option('fslm_mail_subject', __('[site_name] | License Keys for Order #[order_id]', 'fslm')), $order_id);
        $message = $this->apply_mail_text_filters(get_option('fslm_mail_message', __('<p>Dear [bfname] [blname]</p><p>Thank you for your order, those are your license keys for the order #[order_id]</p><p>you can see all your past orders and license keys <a title="My Account" href="[myaccount_url]">here</a>.</p>', 'fslm')), $order_id);

        $headers = apply_filters('woocommerce_email_headers', '', 'rewards_message');
        $attachments = array();

        $meta_value = get_post_meta($order_id, 'fslm_json_license_details', true);
		
		$meta_value = str_replace("\\", "", $meta_value);
		
		$formatted_table = $this->json_data_formatting_email($meta_value, $order_id);
		
		if($formatted_table == '') {
			return false;
		}

        $message .= '<br>' . $formatted_table;

        $mailer = $woocommerce->mailer();

        if(get_option('fslm_add_wc_header_and_footer', 'on') == 'on') {
            $message = $mailer->wrap_message($heading, $message);
        }

		if($formatted_table != "") {
			$mailer->send($to, $subject, $message, $headers, $attachments);
		}
    
    }

    function action_woocommerce_order_status_changed($order_id, $current_status, $new_status){
	
	
	    /*$myfile = fopen("/newfile.txt", "a") or die("Unable to open file!");
	    $txt = "$order_id, $current_status, $new_status\n";
	    $txt .= "\n";
	    fwrite($myfile, $txt);
	    fclose($myfile);*/
        
        $order_status_key = str_replace(" ", "-", strtolower($new_status));

        $on_status_send   = array('completed', 'processing');
        $on_status_revoke = array('refunded');
        $on_status_hide   = array();

	    $default_send   = 'off';
	    $default_revoke = 'off';
	    $default_hide   = 'off';

        if (in_array($order_status_key, $on_status_send))   $default_send = 'on';
        if (in_array($order_status_key, $on_status_revoke)) $default_revoke = 'on';
        if (in_array($order_status_key, $on_status_hide))   $default_hide = 'on';

        
        if(get_option('fslm_send_when_' . $order_status_key, $default_send)== 'on'){
            $this->fslm_send_license_keys($order_id);

            if(get_option('fslm_add_lk_se')== 'on') {
                
                $this->send_mail($order_id);
                
            }
    
        }
        

        if(get_option('fslm_revoke_when_' . $order_status_key, $default_revoke)== 'on'){
            $this->fslm_revoke_license_keys($order_id);
        }

        if(get_option('fslm_hide_when_' . $order_status_key, $default_hide)== 'on'){
            $this->fslm_hide_license_keys($order_id);
        }

    }

    function fslm_before_order_itemmeta($item_id, $item, $product){
        global $post;

        if($post) {

            $meta = get_post_meta($post->ID, 'fslm_json_license_details', true);

            $meta = str_replace("\\", "", $meta);

            echo $this->json_data_formatting_admin($meta, $item_id);

        }

    }

    function fslm_order_item_meta_start($order){

        if(get_option("fslm_guest_customer", "on") != "on") {

            $user_id         = $order->get_user_id();
            $current_user_id = get_current_user_id();

            if(!is_user_logged_in() || $user_id != $current_user_id) {

                return(false);

            }
        }

        $meta = get_post_meta($order->get_id(), 'fslm_json_license_details', true);
		
		$meta = str_replace("\\", "", $meta);

        echo $this->json_data_formatting_order_history($meta, $order->get_id());
    }

    function json_data_formatting_frontend($json, $order_id = 0){

        $values = json_decode($json, true);
        $val = "";
        $visible_key_found = false;
        $meta = '';

        if($values){
            $val =     "<table style=\"width: 100%;\">
                            <thead>
                                <tr>
                                    <th style=\"text-align:left;width: 20%;\"><strong>" . __('Product', 'fslm'). "</strong></th>
                                    <th style=\"text-align:left;width: 80%;\"><strong>" . (count($values) == 1 ? get_option('fslm_meta_key_name', 'License Key') : get_option('fslm_meta_key_name_plural', 'License Key')). "</strong></th>
                                </tr>
                            </thead>
                        <tbody>";

            foreach($values as $value) {
                if ($value['visible'] == 'Yes') {

                    $show_in = get_post_meta((int)$value['product_id'], 'fslm_show_in', true);
                    if ($show_in == "") {
	                    $show_in = get_option('fslm_show_in', '2');
                    }

                    if ($show_in == "1" || $show_in == "2") {
                        $visible_key_found = true;

                        $display = get_post_meta((int)$value['product_id'], 'fslm_display', true);
	                    if ($display == "") {
		                    $display = get_option('fslm_display', '2');
	                    }

                        $license_key = $value['license_key'];
                        $license_key = $this->encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

						$license_key = br2newLine($license_key);
						$license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
						$license_key = $this->newLine2br($license_key);
						

                       
                        $meta = $license_key . '<br>';
                        

                        $image_license_key = '';

                        if($display != '0') {
                            $image_license_key = $this->get_image_name($value['license_id']);

                            if ($image_license_key != '') {
                                $upload_directory = wp_upload_dir();
                                $image_license_key = '<img  style="width: auto" class="fslm_ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key . '">' . '<br>';
                            }
                        }

                        if ($value['max_instance_number'] > 0) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['max_instance_number'] . ' ' . __('time(s)', 'fslm') . '<br>';
                        }

                        if (($value['max_instance_number'] == 0) && isset($value['uses']) && $value['uses'] > 1) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['uses'] . ' ' . __('times', 'fslm') . '<br>';
                        }

                        if (($value['expiration_date'] != '0000-00-00') && ($value['expiration_date'] != '')) {
                            $meta .= ' ' . __('<strong>Expires</strong> ', 'fslm') . $this->fslm_format_date($value['expiration_date']) . '<br>';
                        }
	
	                    $product_name = get_the_title($value['product_id']);
                        
                        if($value['variation_id'] != 0) {
	                        $variation = wc_get_product($value['variation_id']);
	                        $product_name = $variation->get_formatted_name();
                        }


                        $redeem = "";

                        if((get_option('fslm_redeem_btn', '') == 'on') && ($this->get_encrypted_license_key_status($value['license_key']) != "redeemed")) {
                            $redeem_prams = $value['license_id']!="0"?$value['license_id']:$value['item_id'];
                            $redeem_type  = $value['license_id']!="0"?"id":"key";

                            $redeem = '<br><form method="post">
                                                <input type="hidden" name="fslm_redeem_type" value="' . $redeem_type . '">
                                                <input type="hidden" name="fslm_redeem_key" value="' . $redeem_prams . '">
                                                <input type="hidden" name="fslm_order_id" value="' . $order_id . '">
                                                <button type="submit">' . __("Set as redeemed", "fslm") . '</button>
                                            </form>';
                        }
	                    
                        $val .= '<tr><td style="text-align:left;width: 20%;">' . $product_name . '</td><td style="text-align:left;width: 80%;">' . $meta . $image_license_key . $redeem . '</td></tr>';

                    }
                }
            }

            $val .= "</tbody></table>";
        }

        if($visible_key_found == false) $val = '';

        return $val;
    }

    function json_data_formatting_admin($json, $item_id){

        $values = json_decode($json, true);
        $val = "";
        $visible_key_found = false;

        if($values){
            $val .= '<table cellspacing="0" class="display_meta"><tbody>';
            $meta_key_name = (count($values) == 1 ? get_option('fslm_meta_key_name', 'License Key') : get_option('fslm_meta_key_name_plural', 'License Key'));


            foreach($values as $value){
                if (isset($value['item_id']) && $item_id == $value['item_id']) {
                    if ($value['visible'] == 'Yes') {
                        $visible_key_found = true;

                        $license_key = $value['license_key'];
                        $license_key = $this->encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
						
						$license_key = br2newLine($license_key);
						$license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
						$license_key = $this->newLine2br($license_key);

                        $meta = $license_key;

                        $image_license_key = $this->get_image_name($value['license_id']);

                        if ($image_license_key != '') {
                            $upload_directory = wp_upload_dir();
                            $image_license_key = '<br><img class="ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key . '">';
                        }

                        if ($value['max_instance_number'] > 0) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['max_instance_number'] . ' ' . __('time(s)', 'fslm') . '<br>';
                        }

                        if (($value['max_instance_number'] == 0) && isset($value['uses']) && $value['uses'] > 1) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['uses'] . ' ' . __('times', 'fslm') . '<br>';
                        }

                        if (($value['expiration_date'] != '0000-00-00') && ($value['expiration_date'] != '')) {
                            $meta .= ' ' . __('<strong>Expires</strong> ', 'fslm') . $this->fslm_format_date($value['expiration_date']);
                        }

                        $replace = "<button data-key=" . $value['license_key'] . " class='fslm-replace-item-key button button-primary'>" . __('Replace license key', 'fslm') . "</button>";

                        $val .= '<tr><th>' . $meta_key_name . ': </th><td><p>' . $meta . '</p>' . $image_license_key . $replace . '</td></tr>';
                    }
                }
            }

            $val .= "</tbody></table><button class='fslm-replace-item-keys button button-primary' data-itemid=\"$item_id\">" . __('Replace license key(s)', 'fslm') . "</button>" . __(' or ', 'fslm');

        }

        if($visible_key_found == false) $val = '';

        $val .= "<button class='fslm-new-item-key button button-primary' data-itemid=\"$item_id\">" . __('Assign new license key', 'fslm') . "</button>";

        return $val;
    }

    function fslm_format_date($date, $expiration_date = false){
    
        if($date == '0000-00-00' && $expiration_date) {
            return __('Doesn\'t Expire', 'fslm');
        }

        if($date == '0000-00-00') {
            return __('None', 'fslm');
        }

        if($date != '') {
            $date = strtotime($date);
            return date( 'M d, Y', $date);
        }

        return __('None', 'fslm');
    }

    function json_data_formatting_email($json, $order_id = -1){

        $values = json_decode($json, true);
        $val = "";
        $visible_key_found = false;
        $view_in_website = false;

        if($values){
            $val = "<h4>" . (count($values) == 1 ? get_option('fslm_meta_key_name', 'License Key') : get_option('fslm_meta_key_name_plural', 'License Key')). "</h4>
                        <table class=\"td\" cellspacing=\"0\" cellpadding=\"6\"  style=\"border-collapse: collapse; width: 100%; border: 1px solid #e5e5e5; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\">
                            <thead>
                                <tr>
                                    <th class=\"td\" scope=\"col\" style=\"text-align:left;width: 20%;\"><strong>" . __('Product', 'fslm'). "</strong></th>
                                    <th class=\"td\" scope=\"col\" style=\"text-align:left;width: 80%;\"><strong>" . (count($values) == 1 ? get_option('fslm_meta_key_name', 'License Key') : get_option('fslm_meta_key_name_plural', 'License Key')). "</strong></th>
                                </tr>
                            </thead>
                        <tbody>";

            foreach($values as $value) {
                if ($value['visible'] == 'Yes') {

                    $show_in = get_post_meta((int)$value['product_id'], 'fslm_show_in', true);
	                if ($show_in == "") {
		                $show_in = get_option('fslm_show_in', '2');
	                }

                    if ($show_in == "1") {
                        $view_in_website = true;
                        $visible_key_found = true;
                        break;
                    }

                    if ($show_in == "0" || $show_in == "2") {
                        $visible_key_found = true;

                        $display = get_post_meta((int)$value['product_id'], 'fslm_display', true);
	                    if ($display == "") {
		                    $display = get_option('fslm_display', '2');
	                    }

                        $license_key = $value['license_key'];
                        $license_key = $this->encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
						
						$license_key = br2newLine($license_key);
						$license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
						$license_key = $this->newLine2br($license_key);

                        if ($display != '1') {
                            $meta = $license_key . '<br>';;
                        }

                        $image_license_key = '';

                        if ($display != '0') {
                            $image_license_key = $this->get_image_name($value['license_id']);

                            if ($image_license_key != '') {
                                $upload_directory = wp_upload_dir();
                                $image_license_key = '<img  style="width: auto" class="fslm_ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key . '">';
                            }
                        }

                        if ($value['max_instance_number'] > 0) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['max_instance_number'] . ' ' . __('time(s)', 'fslm') . '<br>';
                        }

                        if (($value['max_instance_number'] == 0) && isset($value['uses']) && $value['uses'] > 1) {
                            $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['uses'] . ' ' . __('times', 'fslm') . '<br>';
                        }

                        if (($value['expiration_date'] != '0000-00-00') && ($value['expiration_date'] != '')) {
                            $meta .= ' ' . __('<strong>Expires</strong> ', 'fslm') . $this->fslm_format_date($value['expiration_date']);
                        }
	
	                    $product_name = get_the_title($value['product_id']);
	
	                    if($value['variation_id'] != 0) {
		                    $variation = wc_get_product($value['variation_id']);
		                    $product_name = $variation->get_formatted_name();
	                    }
                        
                        $val .= '<tr><td class="td" scope="col" style="text-align:left;width: 20%;">' . $product_name . '</td><td class="td" scope="col" style="text-align:left;width: 80%;">' . $meta . $image_license_key . '</td></tr>';
                    }
                }
            }

            $val .= "</tbody></table><br><br>";
        }

        if($view_in_website) {
            $val = "<h4>" . get_option('fslm_meta_key_name', 'License Key'). "</h4> <p>" . __('Click here to see your') . ' <a href="' . get_permalink(get_option('fslm_page_id')) . '?lk=' . $order_id . '">' . get_option('fslm_meta_key_name_plural', 'License Keys') . "</a></p>";
        }

        if($visible_key_found == false) $val = '';

        return $val;
    }

    public function json_data_formatting_order_history($json, $order_id = 0, $output_array = false) {

        $values = json_decode($json, true);
        $val = "";
        $visible_key_found = false;
        $output = [];

        if($values) {
            $val = "";

            if(!is_account_page()) {
                $val .= "<h2 class='title is-size-6 mt-0'>" . (count($values) === 1 ? 'اکانت' : 'اکانت ها') . "</h2>";
            }

            foreach($values as $value) {
                if ($value['visible'] == 'Yes') {

                    $visible_key_found = true;

                    $display = get_post_meta((int)$value['product_id'], 'fslm_display', true);
	                if ($display == "") {
		                $display = get_option('fslm_display', '2');
	                }

                    $license_key = $value['license_key'];
                    $license_key = $this->encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
					
					$license_key = br2newLine($license_key);
					$license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $license_key);
					$license_key = $this->newLine2br($license_key);

                    if ($display != '1') {
                        $meta = $license_key . '<br>';
                    }

                    $image_license_key = '';

                    if ($display != '0') {
                        $image_license_key = $this->get_image_name($value['license_id']);

                        if ($image_license_key != '') {
                            $upload_directory = wp_upload_dir();
                            $image_license_key = '<img  style="width: auto" class="fslm_ilksrc" src="' . $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key . '">';
                        }
                    }

                    if ($value['max_instance_number'] > 0) {
                        $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['max_instance_number'] . ' ' . __('time(s)', 'fslm') . '<br>';
                    }

                    if (($value['max_instance_number'] == 0) && isset($value['uses']) && $value['uses'] > 1) {
                        $meta .= ' <strong>' . __('Can be used', 'fslm') . '</strong> ' . $value['uses'] . ' ' . __('times', 'fslm') . '<br>';
                    }

                    if (($value['expiration_date'] != '0000-00-00') && ($value['expiration_date'] != '')) {
                        $meta .= ' ' . __('<strong>Expires</strong> ', 'fslm') . $this->fslm_format_date($value['expiration_date']);
                    }
	
	                $product_name = get_the_title($value['product_id']);
	
	                if($value['variation_id'] != 0) {
		                $variation = wc_get_product($value['variation_id']);
		                $product_name = $variation->get_formatted_name();
	                }

                    $redeem = "";

                    if((get_option('fslm_redeem_btn', '') == 'on') && ($this->get_encrypted_license_key_status($value['license_key']) != "redeemed")) {
                        $redeem_prams = $value['license_id']!="0"?$value['license_id']:$value['item_id'];
                        $redeem_type  = $value['license_id']!="0"?"id":"key";

                        $redeem = '<br><form method="post">
                                            <input type="hidden" name="fslm_redeem_type" value="' . $redeem_type . '">
                                            <input type="hidden" name="fslm_redeem_key" value="' . $redeem_prams . '">
                                            <input type="hidden" name="fslm_order_id" value="' . $order_id . '">
                                            <button type="submit">' . __("Set as redeemed", "fslm") . '</button>
                                       </form>';
                    }
                        
                    // Order date
                    $order_date = (new WC_Order($order_id))->order_date;
                    $order_date = human_time_diff( strtotime($order_date), current_time( 'timestamp' ) );
                    // Expire date
                    $expire_date = "";
                    if(!empty($value['expiration_date'])) {
                        $expire_date = 'تاریخ انقضا:</strong> ' . human_time_diff( strtotime($value['expiration_date']), current_time( 'timestamp' ) ) . '  دیگر';
                    }

                    if($output_array) {
                        $output[] = [
                            "created_at" => reset($this->get_encrypted_license_key_status($value['license_key'], true))->{'creation_date'},
                            "order_date" => $order_date,
                            "license_key" => $license_key,
                            "redeem" => $redeem,
                            "expire_date" => $expire_date
                        ];
                    }

                    $val .= '<div class="is-block mb-4 box has-background-white is-shadowless custom_rounded order_note">
                            <div class="level mb-2 is-mobile is-align-items-start">
                                <div class="level-right">
                                   <div class="level-item">
                                      <span class="icon icon-story mb-1 ml-2" style="vertical-align: middle;"></span>
                                      <p class="has-text-weight-bold has-text-dark is-size-custom is-pulled-right">
                                         مشخصات اکانت'. '' . ':
                                      </p>
                                   </div>
                                </div>
                                <div class="level-left is-align-self-flex-end">
                                   <div class="level-item">
                                      <span class="icon icon-clock mb-1 ml-2" style="vertical-align: middle;"></span>
                                      <span class="has-text-weight-bold has-text-dark is-size-custom is-pulled-left">' . $order_date . ' پیش
                                      </span>
                                   </div>
                                </div>
                            </div>
                            <div class="content is-block has-text-left" dir="ltr">
                               <p class="mb-0">
                                <p class="mb-0 has-text-right has-text-danger" dir="rtl"><strong class="has-text-danger">'.$expire_date.'</p>
                               </p>
                               <p class="mb-0">
                                    <span style="font-family: monospace;">'.$license_key.'</span>
                               </p>
                           </div>';
                    $val .= $redeem;
                    $val .= "</div>";
                }
            }

            $val .= "";

        }

        if($visible_key_found == false) $val = '';

        if($output_array) {
            return $output;
        }

        if(!is_account_page()) {
            return $val;
        }
    }

    function fslm_generator_rules_callback(){
        $product_id = $_POST['mbs_product_id'];

        require_once('includes/metabox_rules_list.php');

	    echo fslm_metabox_rules_list($product_id);

	    die();
    }

    /*****************/
    //   Add license key to the email
    /*****************/
    function add_license_key_to_the_email($order){

        $meta = get_post_meta($order->get_id(), 'fslm_json_license_details', true);
		
		$meta = str_replace("\\", "", $meta);

        if(get_option('fslm_add_lk_wc_de', 'on') == 'on') {
            echo $this->json_data_formatting_email($meta, $order->get_id());
        }

    }

    /*****************/
    //   See the license keys on the website
    /*****************/
    public function license_viewer(){
        if(isset($_GET['license'])){
            die();
        }
    }

    /*****************/
    //   Tracking API
    /*****************/
    public function api_requests_handler(){
        if(isset($_POST['fslmapirequest'])){


            if(($_POST['fslmapirequest'] == 'activate')&&(isset($_POST['license_key']))){
                $result = $this->activate_license_key(esc_sql($_POST['license_key']));

                echo $result;
            }else if(($_POST['fslmapirequest'] == 'deactivate')&&(isset($_POST['license_key']))){
                $result = $this->deactivate_license_key(esc_sql($_POST['license_key']));

                echo $result;
            }else if(($_POST['fslmapirequest'] == 'licenseVerification')&&(isset($_POST['license_key']))){
                $result = $this->license_verification(esc_sql($_POST['license_key']));

                echo $result;
            }else if(($_POST['fslmapirequest'] == 'details')&&(isset($_POST['license_key']))){
                $result = $this->license_details(esc_sql($_POST['license_key']));

                echo $result;
            }else if(($_POST['fslmapirequest'] == 'extra_data')&&(isset($_POST['product_id']))){
                $result = $this->get_product_extra_data($_POST['product_id']);

                echo $result;
            }

            die();

        }
    }

    public function get_product_extra_data($product_id) {

        $json = '{';

        $json .= '"software_name":"' . get_post_meta((int)$product_id, 'fslm_sn', true) . '",';
        $json .= '"software_id":"' . get_post_meta((int)$product_id, 'fslm_sid', true) . '",';
        $json .= '"software_version":"' . get_post_meta((int)$product_id, 'fslm_sv', true) . '",';
        $json .= '"software_author":"' . get_post_meta((int)$product_id, 'fslm_sa', true) . '",';
        $json .= '"software_url":"' . get_post_meta((int)$product_id, 'fslm_surl', true) . '",';
        $json .= '"software_last_update":"' . get_post_meta((int)$product_id, 'fslm_slu', true) . '",';

        $software_extra_data = json_decode(get_post_meta((int)$product_id, 'fslm_sed', true));
        if (json_last_error() === JSON_ERROR_NONE && get_post_meta((int)$product_id, 'fslm_sed', true) != "") {
            $json .= '"software_extra_data":' . get_post_meta((int)$product_id, 'fslm_sed', true);
        } else {
            $json .= '"software_extra_data":"' . get_post_meta((int)$product_id, 'fslm_sed', true) . '"';
        }

        $json .= "}";

        return $json;
    }

    /*****************/
    //   Tracking API: Get license key details
    /*****************/
    public function license_details($license_key_decrypted){
        global $wpdb;

        $license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

        if($query){
            $query = $query[0];

            $query->license_key = $license_key_decrypted;
            
            return json_encode($query);

            unset($query);
        }

        return 'ERROR:INVALID_LICENSE_KEY';
    }

    /*****************/
    //   Tracking API: Get license key details
    /*****************/
    public function get_license_status($license_key_decrypted){
        global $wpdb;

        if(get_option('fslm_auto_expire', '') == 'on') {
            $this->auto_expire_license_keys();
        }

        $license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

        if($query){
            $query = $query[0];

	        return strtolower($query->license_status);

            unset($query);
        }

	    return json_encode(FSLM_APIv2_Responses::INVALID_LICENSE_KEY);
    }

    /*****************/
    //   Tracking API: activate license key
    /*****************/
    public function activate_license_key($license_key_decrypted){
        global $wpdb;

        $license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $verification = $this->license_verification($license_key_decrypted);

        if($verification == 'VALID'){
            $query = $wpdb->get_results("SELECT number_use_remaining FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

            if($query){
                $query = $query[0];

                $number_use_remaining = $query->number_use_remaining;

                if($number_use_remaining > 0){

                    $data = array(
                        'number_use_remaining' => $number_use_remaining-1,
                        'activation_date' => date('Y-m-d H:i:s'),
                        'license_status' => 'active'
                   );

                    $where = array(
                        'license_key' => $license_key
                   );
                    $result = $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

                    return($result==1)?'OK':'ERROR';
                }else {
                    return 'ERROR:MAX';
                }

            }

            return 'ERROR:INVALID_LICENSE_KEY';
        }else {
            return $verification;
        }

    }

    /*****************/
    //   Tracking API: License Verification
    /*****************/
    public function license_verification($license_key_decrypted){
        global $wpdb;

        $license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $query = $wpdb->get_results("SELECT expiration_date FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

            if($query){
                $query = $query[0];

                $expiration_date = $query->expiration_date;

                unset($query);
                if(strtotime($expiration_date)> time()|| $expiration_date == '0000-00-00'){
                    return 'VALID';
                }

                return 'ERROR:EXPIRED';

            }

            return 'ERROR:INVALID_LICENSE_KEY';

    }

    /*****************/
    //   Tracking API: deactivate license key
    /*****************/
    public function deactivate_license_key($license_key_decrypted){
        global $wpdb;

        $license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $verification = $this->license_verification($license_key_decrypted);

        if($verification == 'VALID'){
            $query = $wpdb->get_results("SELECT max_instance_number, number_use_remaining, license_status FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");
            if($query){
                $query = $query[0];

                $max_instance_number = $query->max_instance_number;
                $number_use_remaining = $query->number_use_remaining;
                $license_status = $query->license_status;

                $active =($number_use_remaining+1 == $max_instance_number)?'inactive':$license_status;

                if($number_use_remaining < $max_instance_number){

                    $data = array(
                        'number_use_remaining' => $number_use_remaining+1,
                        'license_status' => $active
                   );

                    $where = array(
                        'license_key' => $license_key
                   );
                    $result = $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

                    return($result==1)?'OK':'ERROR';
                }else {
                    return 'ERROR:INACTIVE';
                }

            }

            return 'ERROR:INVALID_LICENSE_KEY';
        }else {
            return $verification;
        }

    }

    /*
     * API v2.0
     */
	public function api_requests_handler_v2(){

		if(isset($_POST['fslm_v2_api_request'])){

			@header( 'Content-Type: application/json; charset=UTF-8');
			require_once('includes/fslm-api-responses.php');

		    if(isset($_POST['fslm_api_key']) && $_POST['fslm_api_key'] ==  get_option('fslm_api_key', '0A9Q5OXT13in3LGjM9F3')) {

                if(($_POST['fslm_v2_api_request'] == 'activate')&&(isset($_POST['fslm_v2_api_request']))){

                    $device_id = isset($_POST['device_id'])?$_POST['device_id']:'none';

                    $result = $this->activate_license_key_v2(esc_sql($_POST['license_key']), esc_sql($device_id));

                    echo $result;
                }else if(($_POST['fslm_v2_api_request'] == 'deactivate')&&(isset($_POST['license_key']))){

                    $device_id = isset($_POST['device_id'])?$_POST['device_id']:'none';

                    $result = $this->deactivate_license_key_v2(esc_sql($_POST['license_key']), esc_sql($device_id));

                    echo $result;
                }else if(($_POST['fslm_v2_api_request'] == 'verify')&&(isset($_POST['license_key']))){

                    $device_id = isset($_POST['device_id'])?$_POST['device_id']:'none';

                    $result = $this->license_verification_v2(esc_sql($_POST['license_key']), esc_sql($device_id));

                    echo $result;
                }else if(($_POST['fslm_v2_api_request'] == 'details')&&(isset($_POST['license_key']))){
                    $result = $this->license_details(esc_sql($_POST['license_key']));

                    echo $result;
                }else if(($_POST['fslm_v2_api_request'] == 'extra_data')&&(isset($_POST['product_id']))){
                    $result = $this->get_product_extra_data($_POST['product_id']);

                    echo $result;
                } else if(($_POST['fslm_v2_api_request'] == 'license_status')&&(isset($_POST['license_key']))){
                    $result = $this->get_license_status($_POST['license_key']);

                    echo $result;
                } else {
	                echo json_encode(FSLM_APIv2_Responses::INVALID_PARAMETERS);
                }

            } else {
			    echo json_encode(FSLM_APIv2_Responses::INVALID_API_KEY);
		    }

			die();

        }
	}



	/*****************/
	//   Tracking API v2: activate license key
	/*****************/
	public function activate_license_key_v2($license_key_decrypted, $device_id = 'none'){
		global $wpdb;

		$license_key = $license_key_decrypted;
		$license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

		$verification = $this->license_verification_v2($license_key_decrypted);

		if(json_decode($verification)->code == '500'){
			$query = $wpdb->get_results("SELECT number_use_remaining, device_id FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

			if($query){
                $query = $query[0];
                
                $validated = false;

				$number_use_remaining = $query->number_use_remaining;
                $device_id_current    = $query->device_id;
                if($query->device_id == '[]') {
                    $device_id_current = '';
                }
				
				$devices = array();
				
				$device_id_json = json_decode($device_id_current);
				
				if($device_id != 'none' && $device_id != '') {

                    $used = $number_use_remaining-1;

                    if ( $device_id_json != null  && is_array($device_id_json) && json_last_error() === JSON_ERROR_NONE) {
                        if(in_array($device_id, $device_id_json) && $device_id_current != 'none') {
                            $devices = $device_id_json;
                            $used = $number_use_remaining;
                            $validated = true;
                        } else if(!in_array($device_id, $device_id_json) && $device_id_current != 'none') {
                            $device_id_json[] = $device_id;
                            $devices = $device_id_json;

                            if($number_use_remaining <= 0) {
                                return json_encode(FSLM_APIv2_Responses::ACTIVATION_MAX_REACHED);
                            }
                        }
                    } else if($device_id_current == $device_id && $device_id_current != 'none' && $device_id_current != '') {
                        $devices[] = $device_id;
                        $used = $number_use_remaining;
                        $validated = true;
                    } else if($device_id_current != $device_id && $device_id_current != 'none' && $device_id_current != '') {
                        $devices[] = $device_id_current;
                        $devices[] = $device_id;

                        if($number_use_remaining <= 0) {
                            return json_encode(FSLM_APIv2_Responses::ACTIVATION_MAX_REACHED);
                        }
                    } else if($device_id_current != $device_id && $device_id_current != 'none' && $device_id_current == '') {
                        $devices[] = $device_id;

                        if($number_use_remaining <= 0) {
                            return json_encode(FSLM_APIv2_Responses::ACTIVATION_MAX_REACHED);
                        }
                    }

					$data = array(
						'number_use_remaining' => $used,
						'activation_date' => date('Y-m-d H:i:s'),
						'license_status' => 'active',
						'device_id' => json_encode($devices)
                    );
                

					$where = array(
						'license_key' => $license_key
					);
                    $result = $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

					if ($result == 1 || $validated) {
					    return json_encode(FSLM_APIv2_Responses::LICENSE_KEY_ACTIVATED);
                    } else {
						return json_encode(FSLM_APIv2_Responses::ERROR);
                    }

				} else if($number_use_remaining > 0 && ($device_id == 'none' || $device_id == '')) {
					
					if(($device_id_current != 'none' && $device_id_current != '' && $device_id_json != null  && is_array($device_id_json) && count($device_id_json) > 0) && ($device_id == 'none' || $device_id == '')) {
						return json_encode(FSLM_APIv2_Responses::DEVICE_ID_REQUIRED_ACTIVATION);
					}
	
	                $used = $number_use_remaining - 1;
	
	                $data = array('number_use_remaining' => $used, 'activation_date' => date('Y-m-d H:i:s'), 'license_status' => 'active');
	
	                $where = array('license_key' => $license_key);
	                $result = $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
	
	                if ($result == 1) {
		                return json_encode(FSLM_APIv2_Responses::LICENSE_KEY_ACTIVATED);
	                } else {
		                return json_encode(FSLM_APIv2_Responses::ERROR);
	                }
                
                } else {
					return json_encode(FSLM_APIv2_Responses::ACTIVATION_MAX_REACHED);
				}

			}

			return json_encode(FSLM_APIv2_Responses::INVALID_LICENSE_KEY);
		}else {
			return $verification;
		}

	}


	/*****************/
	//   Tracking API v2: License Verification
	/*****************/
	public function license_verification_v2($license_key_decrypted, $device_id = 'none'){
		global $wpdb;

		$license_key = $license_key_decrypted;
        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $query = $wpdb->get_results("SELECT expiration_date, device_id, license_status FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");

		if($query){
			$query = $query[0];

            $expiration_date = $query->expiration_date;
            
            if($device_id != 'none') {
                $device_id_json = json_decode($query->device_id);
                if (json_last_error() === JSON_ERROR_NONE && $query->device_id != "" && $query->device_id != null) {
                    if(in_array($device_id, $device_id_json)) {
                        
                        if((strtotime($expiration_date) > time() || $expiration_date == '0000-00-00' || $expiration_date == '' || $expiration_date == null) && strtolower($query->license_status) != 'expired'){
                            return json_encode(FSLM_APIv2_Responses::VALID);
                        }
                        unset($query);
                    } else {
                        return json_encode(FSLM_APIv2_Responses::INVALID_DEVICE_ID);
                    }
                }
            }

            
            if((strtotime($expiration_date) > time() || $expiration_date == '0000-00-00' || $expiration_date == '' || $expiration_date == null) && strtolower($query->license_status) != 'expired'){
                return json_encode(FSLM_APIv2_Responses::VALID);
            }

            unset($query);
			return json_encode(FSLM_APIv2_Responses::EXPIRED);

		}

		return json_encode(FSLM_APIv2_Responses::INVALID_LICENSE_KEY);

	}

	/*****************/
	//   Tracking API v2: deactivate license key
	/*****************/
	public function deactivate_license_key_v2($license_key_decrypted, $device_id = 'none'){
		global $wpdb;

		$license_key = $license_key_decrypted;
		$license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

		$verification = $this->license_verification_v2($license_key_decrypted);

		if(json_decode($verification)->code == '500'){

			$query = $wpdb->get_results("SELECT max_instance_number, number_use_remaining, license_status, device_id FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'");
			if($query){
				$query = $query[0];

				$max_instance_number = $query->max_instance_number;
				$number_use_remaining = $query->number_use_remaining;
				$license_status = $query->license_status;
                $device_id_current = $query->device_id;
                

				$active =($number_use_remaining+1 == $max_instance_number)?'inactive':$license_status;

				if($number_use_remaining < $max_instance_number){

                    $data = array();

					$data = array(
						'number_use_remaining' => $number_use_remaining+1,
						'license_status' => $active
                    );
					
					$devices = array();
					
					$device_id_json = array_values(json_decode($query->device_id));
					
                    if($device_id != 'none' && $device_id != '') {
                        
                        if ( $device_id_json != null  && is_array($device_id_json) && json_last_error() === JSON_ERROR_NONE) {
                            if(in_array($device_id, $device_id_json) && $device_id_current != 'none') {
                                if (($key = array_search($device_id, $device_id_json)) !== false) {
                                    unset($device_id_json[$key]);
                                    $devices = array_values($device_id_json);
                                    $data['device_id'] = json_encode($devices);
                                }
                            } else if (!in_array($device_id, $device_id_json)) {
                                return json_encode(FSLM_APIv2_Responses::INVALID_DEVICE_ID);
                            }
                        } else if($device_id_current == $device_id && $device_id_current != 'none') {
                            $devices = array_values($device_id_json);
                            $data['device_id'] = json_encode($devices);
                        } else if($device_id_current != $device_id && $device_id_current != 'none') {
                            return json_encode(FSLM_APIv2_Responses::INVALID_DEVICE_ID);
                        } else {
                            return json_encode(FSLM_APIv2_Responses::ERROR);
                        }
                    } else {
                        if(($device_id_current != 'none' && $device_id_current != '' && $device_id_json != null  && is_array($device_id_json) && count($device_id_json) > 0) && ($device_id == 'none' || $device_id == '')) {
	                        return json_encode(FSLM_APIv2_Responses::DEVICE_ID_REQUIRED_DEACTIVATION);
                        }
                    }

					$where = array(
						'license_key' => $license_key
					);
					$result = $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

					if ($result == 1) {
						return json_encode(FSLM_APIv2_Responses::LICENSE_KEY_DEACTIVATED);
					} else {
						return json_encode(FSLM_APIv2_Responses::ERROR);
					}
				}else {
					return json_encode(FSLM_APIv2_Responses::LICENSE_ALREADY_INACTIVE);
				}

			}

			return json_encode(FSLM_APIv2_Responses::INVALID_LICENSE_KEY);
		}else {
			return $verification;
		}

	}

    /*****************/
    //   Cart check
    /*****************/
    function validate_cart_content(){
        global $wpdb;

        foreach(WC()->cart->cart_contents as $cart_product){

            $product = $cart_product['data'];

            if(($this->is_licensing_enabled($cart_product['product_id'], $cart_product['variation_id'])) && (!$this->isGeneratorActive($cart_product['product_id'], $cart_product['variation_id']))){

                $nb_delivered_keys = get_post_meta((int)$cart_product['product_id'], 'fslm_nb_delivered_lk', true);
                $lk_count = 0;
                $query = $wpdb->get_results("SELECT remaining_delivre_x_times FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id='{$cart_product['product_id']}' AND variation_id = '{$cart_product['variation_id']}' AND license_status='available'");
                $porduct_name = $product->get_title();

                if($query){
                    foreach($query as $query){
                        $lk_count = $lk_count +(int)($query->remaining_delivre_x_times);
                    }
                }

                $nb_licenses = get_post_meta((int)$cart_product['product_id'], 'fslm_nb_delivered_lk', true);
                if(empty($nb_licenses)|| $nb_licenses == 0)$nb_licenses = 1;

                $qty =(int)$cart_product['quantity'] *(int)$nb_licenses;

                $item = '<strong>' . $porduct_name . '</strong>';

                if($cart_product['variation_id']!='0'){
                    $single_variation = new WC_Product_Variation($cart_product['variation_id']);
                    $item .= ' ' . __('product variation', 'fslm'). ' ' . $single_variation->get_formatted_name();
                }

                $pqty = '';
                if((int)$nb_delivered_keys > 1){
                    $pqty = '<br>' . $nb_delivered_keys . ' ' . __('License Key(s) are delivered per purchase. Only', 'fslm'). ' ' . floor($lk_count/$nb_delivered_keys). ' ' . __('purchase(s) possible.', 'fslm');
                }


                if($lk_count < $qty){
                    wc_add_notice(__('Sorry, There is no license keys available for', 'fslm').' '. $item . ', <br>'. __('Please remove this item or lower the quantity, For now we have', 'fslm'). ' ' . $lk_count . ' ' . __('License key(s)', 'fslm'). ' ' .  __('for this product.', 'fslm'). $pqty . '<br>', 'error');
                }
            }
        }

    }

    /*****************/
    //   check if automatic license key generator if active
    /*****************/
    function isGeneratorActive($product_id, $variation_id = 0){
        global $wpdb;
        return $wpdb->get_var("SELECT active FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '{$product_id}' AND variation_id = '{$variation_id}' AND  active = '1'")=='1'?true:false;
    }


    public function fslm_revoke_license_keys($order_id){
        global $wpdb;

        $post_meta = get_post_meta($order_id, 'fslm_json_license_details', true);
		
		$meta = str_replace("\\", "", $post_meta);

        $values = json_decode($post_meta, true);
        $license_ids = array();

        if($values) {
            foreach ($values as $value) {
                $license_ids[] = $value['license_id'];
            }

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'returned' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $license_ids);

            $wpdb->query($sql);
        }

        delete_post_meta($order_id, 'fslm_licensed');
        delete_post_meta($order_id, 'fslm_json_license_details');
    }

    public function fslm_hide_license_keys($order_id){
        $post_meta = get_post_meta($order_id, 'fslm_json_license_details', true);
		
		$post_meta = str_replace("\\", "", $post_meta);
		
        $values = json_decode($post_meta, true);
        $json = '';

        if($values) {
            foreach ($values as $key => $value) {
                $values[$key]['visible'] = 'No';
            }

            $values = array_values($values);
            $json = json_encode($values, JSON_FORCE_OBJECT);


            update_post_meta($order_id, 'fslm_json_license_details', $json);
        }



    }

    /*****************/
    //   Send license key(s)
    /*****************/
    public function fslm_send_license_keys($order_id){
        global $wpdb;

        $order = new WC_Order($order_id);
	
        if(class_exists('WC_Subscriptions')) {
            
            if(WC_Subscriptions_Renewal_Order::is_renewal($order)){
                add_post_meta($order_id, 'fslm_licensed', 'true', true);
                return false;
            }
            
        }
        
        $order_type = get_option("fslm_key_delivery", "fifo");
	    $order_by   = 'ASC';
        if($order_type == 'lifo') {
	        $order_by = 'DESC';
        }
		
        $on_status = array('completed', 'processing');
        $order_status_key = str_replace("wc-", "", strtolower($order->get_status()));
        $default = 'off';
        if(in_array($order_status_key, $on_status))$default = 'on';
	
	    //add_post_meta($order_id, 'fslm_status', $order_status_key);

        //if(get_option('fslm_send_when_' . $order_status_key, $default) != 'on'){
        //    return false;
        //}

        if(add_post_meta($order_id, 'fslm_licensed', 'true', true)){
         
            $temp_licenses = [];
	        $license_details = array();
            $index = 0;

            $ids = array();
            $items = $order->get_items();

            foreach($items as $item => $value){
                $nb_licenses = get_post_meta((int)$value['product_id'], 'fslm_nb_delivered_lk', true);
                if(empty($nb_licenses))$nb_licenses = 1;

                $qty =(int)$value['qty'] *(int)$nb_licenses;
                $nd_delivered = 0;

                if($this->is_licensing_enabled($value['product_id'], $value['variation_id'])){
                    
                    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND license_status = 'available' AND remaining_delivre_x_times > 0 ORDER BY license_id {$order_by} LIMIT 0, {$qty}");

                    if($query){
                        foreach($query as $q){
                            if($qty <= 0)break;
                            
                            $ids[]                     = $q->license_id;
                            $license_key               = $q->license_key;
                            $max_instance_number       = $q->max_instance_number;
                            $expiration_date           = $q->expiration_date;
                            $valid                     = $q->valid;
                            $remaining_delivre_x_times = $q->remaining_delivre_x_times;

                            $temp_license_key = encrypt_decrypt('decrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
                            $temp_license_key = preg_replace( "/\\\\\"|\\\\'/", '"', $temp_license_key);

                            $temp_licenses[] = [
                                "license_key" => $temp_license_key, 
                                "expiration_date" => $expiration_date,
                            ];
                            
                            $served = false;

                            if($qty > 0){

                                if(($remaining_delivre_x_times > 1)&&($remaining_delivre_x_times <= $qty)){
                                    $served = true;
                                    $nd_delivered = $remaining_delivre_x_times;
                                    $qty = $qty -($q->remaining_delivre_x_times);
                                }

                                if(($remaining_delivre_x_times > 1)&&($remaining_delivre_x_times > $qty)&&($served == false)){
                                    $served = true;
                                    $nd_delivered = $qty;
                                    $qty = 0;
                                }

                                if(($remaining_delivre_x_times == 1)&&($served == false)){
                                    $qty--;
                                }
	
	                            // JSON
	                            $license_details[$index] = array(
		                            "license_id"          => $q->license_id,
		                            "item_id"             => $item,
		                            "product_id"          => $value['product_id'],
		                            "variation_id"        => $value['variation_id'],
		                            "license_key"         => $license_key,
		                            "max_instance_number" => $max_instance_number,
		                            "visible"             => "Yes",
                                    "uses"                => $nd_delivered
	                            );
	
	                            if ($valid > 0) {
		                            $license_details[$index]["expiration_date"] = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
	                            } else if (($expiration_date != '0000-00-00') && ($expiration_date != '')) {
		                            $license_details[$index]["expiration_date"] = $expiration_date;
	                            } else {
		                            $license_details[$index]["expiration_date"] = "0000-00-00";
	                            }
	                            // End JSON
	
	                            $data = array(
		                            "license_status"            => "sold",
		                            "order_id"                  => $order_id,
		                            "remaining_delivre_x_times" => 0,
		                            "owner_first_name"          => $this->removeEmoji($order->get_billing_first_name()),
		                            "owner_last_name"           => $this->removeEmoji($order->get_billing_last_name()),
		                            "owner_email_address"       => $order->get_billing_email(),
		                            "expiration_date"           => $license_details[$index]["expiration_date"],
                                    'sold_date' => date('Y-m-d')
	                            );
                                
                                if($remaining_delivre_x_times == 1){
	
	                                $data = array(
		                                "license_status"            => "sold",
		                                "order_id"                  => $order_id,
		                                "remaining_delivre_x_times" => 0,
		                                "owner_first_name"          => $this->removeEmoji($order->get_billing_first_name()),
		                                "owner_last_name"           => $this->removeEmoji($order->get_billing_last_name()),
		                                "owner_email_address"       => $order->get_billing_email(),
		                                "expiration_date"           => $license_details[$index]["expiration_date"],
                                        'sold_date' => date('Y-m-d')
	                                );
                                 
                                }else if($remaining_delivre_x_times <= $nd_delivered){
                                 
	                                $data = array(
		                                "license_status"            => "sold",
		                                "order_id"                  => $order_id,
		                                "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
		                                "owner_first_name"          => $this->removeEmoji($order->get_billing_first_name()),
		                                "owner_last_name"           => $this->removeEmoji($order->get_billing_last_name()),
		                                "owner_email_address"       => $order->get_billing_email(),
		                                "expiration_date"           => $license_details[$index]["expiration_date"],
                                        'sold_date' => date('Y-m-d')
	                                );
	                               
                                }else if($remaining_delivre_x_times > $nd_delivered){
                                 
	                                $data = array(
		                                "order_id"                  => $order_id,
		                                "remaining_delivre_x_times" => $remaining_delivre_x_times - $nd_delivered,
		                                "owner_first_name"          => $this->removeEmoji($order->get_billing_first_name()),
		                                "owner_last_name"           => $this->removeEmoji($order->get_billing_last_name()),
		                                "owner_email_address"       => $order->get_billing_email(),
		                                "expiration_date"           => $license_details[$index]["expiration_date"],
                                        'sold_date' => date('Y-m-d')
	                                );
                                 
                                }
	
	                            $where = array(
		                            "license_id" => $q->license_id
	                            );
	
	                            $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);
	
	                            

                                $index++;

                            }
                        }

                    }


                    if($this->isGeneratorActive($value['product_id'], $value['variation_id'])){

                        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '{$value['product_id']}' AND variation_id = '{$value['variation_id']}' AND  active = '1'");

                        if($query){
                            $query = $query[0];

                            for($i=0; $i<$qty; $i++){
                                $prefix = $query->prefix;
                                $chunks_number = $query->chunks_number;
                                $chunks_length = $query->chunks_length;
                                $suffix = $query->suffix;
                                $max_instance_number = $query->max_instance_number;
                                $valid = $query->valid;
                                $expires = "0000-00-00";



                                $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
                                $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
                                while($this->licenseKeyExist($license_key)){
                                    $license_key = $this->generate_license_key($prefix, $chunks_number, $chunks_length, $suffix);
                                    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
                                }
                                
	                            // JSON
	                            $license_details[$index] = array(
		                            "license_id"          => "0",
		                            "item_id"             => $item,
		                            "product_id"          => $value['product_id'],
		                            "variation_id"        => $value['variation_id'],
		                            "license_key"         => $license_key,
		                            "max_instance_number" => $max_instance_number,
		                            "visible"             => "Yes"
	                            );
	
	                            if ($valid > 0) {
		                            $license_details[$index]["expiration_date"] = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $valid . ' ' . 'days'));
	                            } else {
		                            $license_details[$index]["expiration_date"] = "0000-00-00";
	                            }
	                            // End JSON

                                $data = array(
                                    'product_id' => $value['product_id'],
                                    'license_key' => $license_key,
                                    'variation_id' => $value['variation_id'],
                                    'max_instance_number' => $max_instance_number,
	                                "owner_first_name"          => $this->removeEmoji($order->get_billing_first_name()),
	                                "owner_last_name"           => $this->removeEmoji($order->get_billing_last_name()),
	                                'owner_email_address' => $order->get_billing_email(),
                                    'number_use_remaining' => $max_instance_number,
                                    'creation_date' => date('Y-m-d H:i:s'),
                                    'expiration_date' => $license_details[$index]["expiration_date"],
                                    'delivre_x_times' => '0',
                                    'remaining_delivre_x_times' => '0',
                                    'valid' => $valid,
                                    'license_status' => 'sold',
                                    'order_id' => (int)$order_id,
                                    'sold_date' => date('Y-m-d')
                               );
                                $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

                                $index++;
                            }
                        }
                    }
                }
            }
	
            if(!empty($temp_licenses)) {
                $order_note_text = "";
                foreach($temp_licenses as $temp_license) {
                    $order_note_text .= $temp_license["license_key"] . "<br>";
                }

                $order->add_order_note( $order_note_text, 1);
            }

	        $json_license_details = json_encode($license_details);
            add_post_meta($order_id, 'fslm_json_license_details', $json_license_details, true);

            if($json_license_details == "[]") {
	            delete_post_meta($order_id, 'fslm_json_license_details');
	            delete_post_meta($order_id, 'fslm_licensed');
            }

        }

    }
	
	public function removeEmoji($string) {
        return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F415}](?:\x{200D}\x{1F9BA})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BD})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9AF})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}-\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6D5}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6FA}\x{1F7E0}-\x{1F7EB}\x{1F90D}-\x{1F93A}\x{1F93C}-\x{1F945}\x{1F947}-\x{1F971}\x{1F973}-\x{1F976}\x{1F97A}-\x{1F9A2}\x{1F9A5}-\x{1F9AA}\x{1F9AE}-\x{1F9CA}\x{1F9CD}-\x{1F9FF}\x{1FA70}-\x{1FA73}\x{1FA78}-\x{1FA7A}\x{1FA80}-\x{1FA82}\x{1FA90}-\x{1FA95}]/u', '', $string);
    }



    /*****************/
    //   Check if a license key is unique
    /*****************/
    public function licenseKeyExist($license_key){
        global $wpdb;

        return((int)$wpdb->get_var("SELECT COUNT(*)FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='{$license_key}'")>0)?true:false;
    }

    /*****************/
    //   Generate License Key
    /*****************/
    public function generate_license_key($prefix, $chunks_number, $chunk_length, $suffix){
        
        ///////////////////////
	    $characters = get_option('fslm_generator_chars', '0123456789ABCDEF');
        $characters_length = strlen($characters);
	    $license_chunks = array();
	
	    for ($i = 0; $i < $chunks_number; $i++) {
		
		    $chunk = '';
		
		    for ($j = 0; $j < $chunk_length; $j++) {
			    $chunk .= $characters[rand(0, $characters_length - 1)];
		    }
		
		    $license_chunks[] = $chunk;
	    }
        ///////////////////////

        return $prefix . implode('-', $license_chunks) . $suffix;
    }

    /*****************/
    //   Save Metabox on post save
    /*****************/
    public function save_product($post_id){
        global $wpdb;

        if((isset($_POST['post_type'])&& $_POST['post_type'] != 'product')||(!isset($_POST['post_type'])))
            return false;

        $product_id =(isset($_POST['mbs_product_id']))?$_POST['mbs_product_id']:'';
        $variation_id =(isset($_POST['mbs_variation_id']))?$_POST['mbs_variation_id']:'0';

        $software_name         = $_POST['fslm_sn'];
        $software_ID           = $_POST['fslm_sid'];
        $software_version      = $_POST['fslm_sv'];
        $software_author       = $_POST['fslm_sa'];
        $software_url         = $_POST['fslm_surl'];
        $software_last_update  = $_POST['fslm_slu'];
        $software_extra_data   = $_POST['fslm_sed'];

        if(!add_post_meta((int)$product_id, 'fslm_sn', $software_name, true)){
            update_post_meta((int)$product_id, 'fslm_sn', $software_name);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sid', $software_ID, true)){
            update_post_meta((int)$product_id, 'fslm_sid', $software_ID);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sv', $software_version, true)){
            update_post_meta((int)$product_id, 'fslm_sv', $software_version);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sa', $software_author, true)){
            update_post_meta((int)$product_id, 'fslm_sa', $software_author);
        }

        if(!add_post_meta((int)$product_id, 'fslm_surl', $software_url, true)){
            update_post_meta((int)$product_id, 'fslm_surl', $software_url);
        }

        if(!add_post_meta((int)$product_id, 'fslm_slu', $software_last_update, true)){
            update_post_meta((int)$product_id, 'fslm_slu', $software_last_update);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sed', $software_extra_data, true)){
            update_post_meta((int)$product_id, 'fslm_sed', $software_extra_data);
        }

        $licensable             = (isset($_POST['mbs_licensable'])&& $_POST['mbs_licensable'] == 'on')?'1':'0';
        $active                 = (isset($_POST['mbs_active'])&& $_POST['mbs_active'] == 'on')?'1':'0';
        $prefix                 = (isset($_POST['mbs_prefix']))?$_POST['mbs_prefix']:'';
        $chunks_number          = (isset($_POST['mbs_chunks_number']))?$_POST['mbs_chunks_number']:'';
        $chunks_length          = (isset($_POST['mbs_chunks_length']))?$_POST['mbs_chunks_length']:'';
        $suffix                 = (isset($_POST['mbs_suffix']))?$_POST['mbs_suffix']:'';
        $max_instance_number    = (isset($_POST['mbs_max_instance_number']))?$_POST['mbs_max_instance_number']:'';
        $valid                  = (isset($_POST['mbs_valid']))?$_POST['mbs_valid']:'';

        $this->set_licensing($product_id, '0', $licensable);
	
        // Import prefix/suffix
        if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {
         
	        if(!add_post_meta((int)$product_id, "fslm_import_prefix", $_POST["fslm_import_prefix"], true)){
		        update_post_meta((int)$product_id, "fslm_import_prefix", $_POST["fslm_import_prefix"]);
	        }
	
	        if(!add_post_meta((int)$product_id, "fslm_import_suffix", $_POST["fslm_import_suffix"], true)){
		        update_post_meta((int)$product_id, "fslm_import_suffix", $_POST["fslm_import_suffix"]);
	        }
	        
        }

        $handle = new WC_Product_Variable($product_id);

        $variations = $handle->get_children();
        foreach ($variations as $variation) {
            $single_variation = new WC_Product_Variation($variation);
            $licensable_variation =(isset($_POST["mbs_licensable_{$variation}"])&& $_POST["mbs_licensable_{$variation}"] == 'on')?'1':'0';

            $this->set_licensing($product_id, $variation, $licensable_variation);
	
	        // Import prefix/suffix
	        if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {
		        if (!add_post_meta((int)$product_id, "fslm_import_prefix_{$variation}", $_POST["fslm_import_prefix_{$variation}"], true)) {
			        update_post_meta((int)$product_id, "fslm_import_prefix_{$variation}", $_POST["fslm_import_prefix_{$variation}"]);
		        }
		
		        if (!add_post_meta((int)$product_id, "fslm_import_suffix_{$variation}", $_POST["fslm_import_suffix_{$variation}"], true)) {
			        update_post_meta((int)$product_id, "fslm_import_suffix_{$variation}", $_POST["fslm_import_suffix_{$variation}"]);
		        }
	        }
        }

        $show_in = (isset($_POST['fslm_show_in']))?$_POST['fslm_show_in']:get_option('fslm_show_in', '2');
        $display = (isset($_POST['fslm_display']))?$_POST['fslm_display']:get_option('fslm_display', '2');

        if(!add_post_meta((int)$product_id, 'fslm_show_in', $show_in, true)){
            update_post_meta((int)$product_id, 'fslm_show_in', $show_in);
        }

        if(!add_post_meta((int)$product_id, 'fslm_display', $display, true)){
            update_post_meta((int)$product_id, 'fslm_display', $display);
        }

        $nb_delivered_lk       = $_POST['fslm_nb_delivered_lk'];

        if(!add_post_meta((int)$product_id, 'fslm_nb_delivered_lk', $nb_delivered_lk, true)){
            update_post_meta((int)$product_id, 'fslm_nb_delivered_lk', $nb_delivered_lk);
        }

        $data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'prefix' => $prefix,
            'chunks_number' => $chunks_number,
            'chunks_length' => $chunks_length,
            'suffix' => $suffix,
            'max_instance_number' => $max_instance_number,
            'valid' => $valid,
            'active' => $active
       );

        $exist =(int)$wpdb->get_var("SELECT COUNT(*)FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '" . $product_id . "'");

        if($exist == 0){
            $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data);
        }else {
            $where = array(
                'product_id' => $product_id
           );

            $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data, $where);
        }
    }
	
	public function import_lko_callback(){
		global $wpdb;
		
		$result = array(
			"total"     => 0,
			"imported"  => 0,
			"duplicate" => 0,
			"skipped"   => 0
		);
		
		$license_keys = array();
		
		$allow_duplicate = get_option('fslm_duplicate_license', '');
		
		if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
			$tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
			move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);
			
			$file = fopen($tmp, 'r');
			$content = fread($file, $_FILES['ilk_source_file']['size']);
			fclose($file);
			
			$lines = preg_split("/(\r\n|\n|\r)/", $content);
			
			$product_id = $_POST['product_id'];
			$variation_id = $_POST['variation_id'];
			$max_instance_number =(int)$_POST['max_instance_number'];
			$yy =(int)$_POST['yy'];
			$mm =(int)$_POST['mm'];
			$dd =(int)$_POST['dd'];
			$valid =(int)$_POST['valid'];
			
			$this->set_licensing($product_id, $variation_id, '1');
			
			
			$deliver_x_times = $_POST['deliver_x_times'];
			
			foreach($lines as $line){
				if($line != ''){
					$license_key = str_replace(array("\r", "\n"), '', $line);
					
					// Import prefix/suffix
					if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {
						
						$prefix = get_post_meta((int)$product_id, 'fslm_import_prefix', true);
						$suffix = get_post_meta((int)$product_id, 'fslm_import_suffix', true);
						
						if($variation_id != 0) {
							$prefix = get_post_meta((int)$product_id, 'fslm_import_prefix_' . $variation_id, true);
							$suffix = get_post_meta((int)$product_id, 'fslm_import_suffix_' . $variation_id, true);
						}
						
						$license_key = $prefix . $license_key . $suffix;
						
					}
					
					$license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
					
					$formatted_date = $yy . '-' . $mm . '-' . $dd . ' 0:0:0';
					if($yy == 0 || $mm == 0 || $dd == 0 || $yy < 1970) {
						$formatted_date = "null";
					}
					
					$exist       = $this->licenseKeyExist($license_key);
					
					if((in_array($license_key, $license_keys) || $exist) && $allow_duplicate != 'on') {
						$result['duplicate']++;
					} else {
						
						if(in_array($license_key, $license_keys) || $exist) {
							$result['duplicate']++;
						}
					 
						$result['imported']++;
						
						$data = array(
							'product_id' => $product_id,
							'variation_id' => $variation_id,
							'license_key' => $license_key,
							'image_license_key' => '',
							'delivre_x_times' => $deliver_x_times,
							'remaining_delivre_x_times' => $deliver_x_times,
							'max_instance_number' => $max_instance_number,
							'number_use_remaining' => $max_instance_number,
							'creation_date' => date('Y-m-d H:i:s'),
							'expiration_date' => $formatted_date,
							'valid' => $valid,
							'license_status' => 'available'
						);
						$wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);
					}
					
					$license_keys[] = $license_key;
					$result['total']++;
				}
			}
		}
		
		$link = admin_url('admin.php?page=license-manager&result=' . urlencode( base64_encode( json_encode( $result ) ) ));
		wp_redirect($link);
		die();
	}

    /*****************/
    //   Import Image License Keys Only callback
    /*****************/
    public function import_ilko_callback(){
        global $wpdb;


	    $first = true;

        if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
            move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);

	        $za = new ZipArchive();

	        $za->open($tmp);
	
	        $product_id = $_POST['product_id'];
	        $variation_id = $_POST['variation_id'];
	        $max_instance_number =(int)$_POST['max_instance_number'];
	        $yy =(int)$_POST['yy'];
	        $mm =(int)$_POST['mm'];
	        $dd =(int)$_POST['dd'];
	        $valid =(int)$_POST['valid'];
	
	
	        $deliver_x_times = $_POST['deliver_x_times'];

	        for( $i = 0; $i < $za->numFiles; $i++ ){

                $stat = $za->statIndex($i);

                if (strpos($stat['name'], '__MACOSX') === false) {

                    $license_key = $this->generate_license_key("LK-", 4, 4, "-IMAGE");
                    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
                    while ($this->licenseKeyExist($license_key)) {
                        $license_key = $this->generate_license_key("LK-", 4, 4, "-IMAGE");
                        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
                    }

                    if (!empty($stat['name'])) {
                        $upload_directory = wp_upload_dir();
                        $target_dir = $upload_directory['basedir'] . '/fslm_keys/';
                        if (!file_exists($target_dir)) {
                            wp_mkdir_p($target_dir);
                            $fp = fopen($target_dir . 'index.php', 'w');
                            fwrite($fp, '<?php');
                            fclose($fp);
                        }

                        if ($image = $za->getFromName($stat["name"])) {
                            file_put_contents($stat["name"], $image);
                        }

                        $imageFileType = strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION));
                        $file_name = basename($this->fslm_uuid_v4_no_dashes() . '.' . $imageFileType);
                        $target_file = $target_dir . $file_name;
                        $image_license_key = $file_name;
                        $uploadOk = 1;

                        // Check if image file is a actual image or fake image

                        $check = getimagesize($stat["name"]);
                        $uploadOk = ($check !== false) ? 1 : 0;

                        // Check if file already exists
                        while (file_exists($target_file)) {
                            $file_name = basename($this->fslm_uuid_v4_no_dashes() . '.' . $imageFileType);
                            $target_file = $target_dir . $file_name;
                            $image_license_key = $file_name;
                        }

                        //error_log($stat["name"]);

                        if ($image = $za->getFromName($stat["name"])) {
                            file_put_contents($target_dir . $file_name, $image);
                        }

                    }


                    // Import prefix/suffix
                    if (get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {

                        $prefix = get_post_meta((int)$product_id, 'fslm_import_prefix', true);
                        $suffix = get_post_meta((int)$product_id, 'fslm_import_suffix', true);

                        if ($variation_id != 0) {
                            $prefix = get_post_meta((int)$product_id, 'fslm_import_prefix_' . $variation_id, true);
                            $suffix = get_post_meta((int)$product_id, 'fslm_import_suffix_' . $variation_id, true);
                        }

                        $license_key = $prefix . $license_key . $suffix;

                    }

                    if ($first) $this->set_licensing($product_id, $variation_id, '1');
                    $first = false;

                    $formatted_date = $yy . '-' . $mm . '-' . $dd . ' 0:0:0';
                    if ($yy == 0 || $mm == 0 || $dd == 0 || $yy < 1970) {
                        $formatted_date = "null";
                    }

                    $data = array(
                        'product_id' => $product_id,
                        'variation_id' => $variation_id,
                        'license_key' => $license_key,
                        'image_license_key' => $image_license_key,
                        'delivre_x_times' => $deliver_x_times,
                        'remaining_delivre_x_times' => $deliver_x_times,
                        'max_instance_number' => $max_instance_number,
                        'number_use_remaining' => $max_instance_number,
                        'creation_date' => date('Y-m-d H:i:s'),
                        'expiration_date' => $formatted_date,
                        'valid' => $valid,
                        'license_status' => 'available'
                    );
                    $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);

                }

	        }
	
	        $this->update_stock($product_id, $variation_id);
        }

        $link = admin_url('admin.php?page=license-manager#hlk');
        wp_redirect($link);
        die();
    }
	
	
	public function fslm_uuid_v4_no_dashes() {
		return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
			
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

    /*****************/
    //   Import License Keys callback
    /*****************/
    public function import_csv_lk_callback(){
        global $wpdb;

        if(isset($_FILES['ilk_source_file'])&& $_FILES['ilk_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ilk_source_file']['name']);
            move_uploaded_file($_FILES['ilk_source_file']['tmp_name'], $tmp);
	
	        $columns = array();
	        $flipped   = array_flip($columns);
	        $indexes_found = false;
	
	        /////////////////////////////////
	        $handle = fopen($tmp, 'r');
	        $delimiter = $this->detectDelimiter($tmp);
	        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
		        $num = count($data);
		
		        if(in_array('license_key', $data) && $indexes_found == false) {
			        $columns = $data;
			        $flipped = array_flip($columns);
			        $indexes_found = true;
		        }
		
		        if(!in_array('license_key', $data)) {
		    
		            if(strpos($data[0], 'sep') === false) {
		                
		             
			
                        $_data = array(
                            'product_id'                => $data[$flipped['product_id']],
                            'variation_id'              => $data[$flipped['variation_id']],
                            'license_key'               => $data[$flipped['license_key']],
                            'image_license_key'         => $data[$flipped['image_license_key']],
                            'license_status'            => $data[$flipped['license_status']],
                            'owner_first_name'          => $data[$flipped['owner_first_name']],
                            'owner_last_name'           => $data[$flipped['owner_last_name']],
                            'owner_email_address'       => $data[$flipped['owner_email_address']],
                            'delivre_x_times'           => $data[$flipped['delivre_x_times']],
                            'remaining_delivre_x_times' => $data[$flipped['remaining_delivre_x_times']],
                            'max_instance_number'       => $data[$flipped['max_instance_number']],
                            'number_use_remaining'      => $data[$flipped['number_use_remaining']],
                            'activation_date'           => $data[$flipped['activation_date']],
                            'creation_date'             => $data[$flipped['creation_date']],
                            'expiration_date'           => $data[$flipped['expiration_date']],
                            'valid'                     => $data[$flipped['valid']],
                            'order_id'                  => (isset($flipped['order_id']) && isset($data[$flipped['order_id']]))?$data[$flipped['order_id']]:'0',
                            'sold_date'                 => (isset($flipped['sold_date']) && isset($data[$flipped['sold_date']]))?$data[$flipped['sold_date']]:'0',
                            'device_id'                 => (isset($flipped['device_id']) && isset($data[$flipped['device_id']]) && $data[$flipped['device_id']] != "")?'["' . substr(str_replace('|', '","', $data[$flipped['device_id']]), 1, -2) . ']':'' 
                        );
                
                
                        $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $_data);
                
                        $this->set_licensing($data[1], $data[2], '1');
			        }
			
		        }
	        }
	        fclose($handle);
	        ////////////////////////////////
        }
	
	    if(get_option('fslm_stock_sync', '') == 'on') {
		    if(!add_option('fslm_stock_sync_last_run', '0')){
			    update_option('fslm_stock_sync_last_run', '0');
		    }
	    }

        $link = admin_url('admin.php?page=license-manager#hlk');
        wp_redirect($link);
        die();
    }


    /*****************/
    //   Import Generator Rules callback
    /*****************/
    public function import_csv_gr_callback(){
        global $wpdb;

        if(isset($_FILES['igr_source_file'])&& $_FILES['igr_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['igr_source_file']['name']);
            move_uploaded_file($_FILES['igr_source_file']['tmp_name'], $tmp);

            $file = fopen($tmp, 'r');
            $content = fread($file, $_FILES['igr_source_file']['size']);
            fclose($file);

            $lines = explode("\n", $content);

            $i = 0;
            $query = "INSERT INTO {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules(";
            $columns_array = "";
            $values_array = "";
            $values = "";

            foreach($lines as $line){
                if($i == 0 || $line == ''){
                    $i++;
                    continue;
                }else if($i == 1){

                    $columns_array = explode('","', substr(trim($line), 1, strlen($line)-2));
                    $columns_array = array_splice($columns_array, 1);
                    $query .= implode(', ', $columns_array). ')VALUES(';

                    $i++;
                    continue;
                }else {

                    $values_array = explode('","', substr(trim($line), 1, strlen($line)-2));
                    $values_array = array_splice($values_array, 1);
                    $values = '"' . implode('","', $values_array). '")ON DUPLICATE KEY UPDATE ';

                    $c = 0;
                    foreach($columns_array as $column){
                        $values .= $column . ' = "' . $values_array[$c] . '", ';
                        $c++;
                    }

                    $values = substr(trim($values), 0, strlen($values)-2);

                    $wpdb->query($query . $values);
                }
            }
        }


        $link = admin_url('admin.php?page=license-manager-license-key-generator');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Import Plugin Settings callback
    /*****************/
    public function import_ps_callback(){

        if(isset($_FILES['ips_source_file'])&& $_FILES['ips_source_file']['size'] > 0){
            $tmp = wp_tempnam($_FILES['ips_source_file']['name']);
            move_uploaded_file($_FILES['ips_source_file']['tmp_name'], $tmp);

            $file = fopen($tmp, 'r');
            $content = fread($file, $_FILES['ips_source_file']['size']);
            fclose($file);

            $lines = explode("\n", $content);

            foreach($lines as $line){
                if($line != ''){
                    $element = explode(" => ", $line);
                    if($element[0] == "fslm_encryption") {
                        $keys = explode("[SEP]", $element[1]);

                        $this->set_encryption_key($keys[0], $keys[1]);
                    }else {
                        update_option($element[0], str_replace("[NL]", "\n", $element[1]));
                    }
                }
            }
        }

        $link = admin_url('admin.php?page=license-manager-settings');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Export License Keys callback
    /*****************/
    public function export_csv_lk_callback(){

        $license_status = '';
        $product_id = '';

        if(isset($_REQUEST['elk_license_status'])&& $_REQUEST['elk_license_status'] != 'all'){
            $license_status = $_REQUEST['elk_license_status'];
        }

        if(isset($_REQUEST['elk_product_id'])&& $_REQUEST['elk_product_id'] != 'all'){
            $product_id = $_REQUEST['elk_product_id'];
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="license_manager__license_keys__' . date("__d_m_Y__H_i_s"). '__' . $_REQUEST['elk_product_id'] .'__' . $_REQUEST['elk_license_status'] . '.csv";');
        echo $this->generate_license_keys_csv($license_status, $product_id);
        die();
    }

    /*****************/
    //   Export Generator Rules callback
    /*****************/
    public function export_csv_gr_callback(){
        $product_id = '';
        if(isset($_REQUEST['egr_product_id'])&& $_REQUEST['egr_product_id'] != 'all'){
            $product_id = $_REQUEST['egr_product_id'];
        }
        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="license_manager__generator_rules_' . date("__d_m_Y__H_i_s"). '__' . $_REQUEST['egr_product_id'] . '.csv";');
        echo $this->generate_generator_rules_csv();
        die();
    }

    /*****************/
    //   Export Plugin Settings callback
    /*****************/
    public function export_ps_callback(){
        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="license_manager__plugin_settings_' . date("__d_m_Y__H_i_s"). '.fslmsettings";');
        echo $this->generate_plugin_settings();
        die();
    }

    /*****************/
    //   Admin Notifications
    /*****************/
    public function admin_notifications(){
        global $wp_admin_bar;
        global $wpdb;
        global $wp_query;
        global $woocommerce;

        $args = array();


        $to = get_option('fslm_notif_mail_to');
        $subject = __('License Keys stock running low', 'fslm');
        $message = '';
        $headers = "MIME-Version: 1.0" . PHP_EOL;
        $headers .= "Content-Type: text/html; charset=UTF-8" . PHP_EOL;
        $send_mail = false;
        $c = 0;

        /////////////////////////////////////////

        $configs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_licensed_products WHERE active='1'");

        if($configs) {
            foreach ($configs as $config) {

                $generator_active = $this->isGeneratorActive($config->product_id, $config->variation_id);
	
	   

                if($generator_active == false) {
                    $_product_id = $config->variation_id == 0? $config->product_id : $config->variation_id;
                    
                    $product = wc_get_product( $_product_id );
                    if($product && $product->get_status() == "publish") {
                        
                        if(($config->variation_id == 0) || (($config->variation_id != 0) && ($product->get_parent_id() == $config->product_id))) {
	
	
	                        $available_keys_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE product_id = '$config->product_id' AND variation_id = '$config->variation_id' AND license_status = 'available'");
	
	                        $product_name = $product->get_formatted_name();
	
	                        $terms = get_the_terms($_product_id, 'product_cat');
	
	                        if ($terms) {
		                        $product_name .= '(';
		
		                        foreach ($terms as $term) {
			                        $product_name .= $term->name . ', ';
		                        }
		
		                        $product_name = trim($product_name, ', ');
		                        $product_name .= ')';
	                        }
	
	                        if ($available_keys_count < get_option('fslm_notif_min_licenses_nb', '10')) {
		
		                        $id = 'fslm_notif_' . $c;
		                        $title = __('Please add license keys for', 'fslm') . ' ' . $product_name . ', ' . $available_keys_count . ' ' . __('License Key left', 'fslm');
		
		                        $message .= $title . '<br>';
		
		                        array_push($args, array('id' => $id, 'title' => $title, 'parent' => 'fslm_notifications', 'meta' => array('class' => 'fslm_warning')));
		
		                        $send_mail = true;
		                        $c++;
	                        }
	
                        }
                    }
                
                }
            }
        }

        /////////////////////////////////////////

        if((current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ))){

            $title = __('License Manager', 'fslm'). ' <span class="fslm_nb_notif">' . $c . '</span>';

            $wp_admin_bar->add_node(array(
                'id'     => 'fslm_notifications',
                'title'	=>	$title,
                'meta'   => array('class' => 'first-toolbar-group'),
            ));
            
            if($c == 0) {
                array_push($args, array(
                    'id'		=>	'fslm-no-notifications',
                    'title'		=>	__('There is no notifications', 'fslm'),
                    'parent'	=>	'fslm_notifications',
                    'meta'      =>  array(
                                        'class' => 'fslm_warning'
                                    )
                ));

                $c++;
            }

            for($i=0; $i<$c; $i++){
                $wp_admin_bar->add_node($args[$i]);
            }

        }

        if(($send_mail) && (get_option('fslm_notif_mail')== 'on') && ((time() - 86400) > get_option('fslm_last_sent_notification_email_date', '0'))){

            $headers = apply_filters('woocommerce_email_headers', '', 'rewards_message');
            $attachments = array();

            $heading = __('Please add more license key for the following items', 'fslm');

            $mailer  = $woocommerce->mailer();
            $message = $mailer->wrap_message($heading, $message);

            $mailer->send($to, $subject, $message, $headers, $attachments);

            update_option('fslm_last_sent_notification_email_date', time());
        }

    }

    /*****************/
    //   Save product license settings
    /*****************/
    public function fslm_save_metabox_callback(){
        global $wpdb;

        $product_id            = $_POST['mbs_product_id'];

        $software_name         = $_POST['fslm_sn'];
        $software_ID           = $_POST['fslm_sid'];
        $software_version      = $_POST['fslm_sv'];
        $software_author       = $_POST['fslm_sa'];
        $software_url         = $_POST['fslm_surl'];
        $software_last_update  = $_POST['fslm_slu'];
        $software_extra_data   = $_POST['fslm_sed'];

        if(!add_post_meta((int)$product_id, 'fslm_sn', $software_name, true)){
            update_post_meta((int)$product_id, 'fslm_sn', $software_name);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sid', $software_ID, true)){
            update_post_meta((int)$product_id, 'fslm_sid', $software_ID);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sv', $software_version, true)){
            update_post_meta((int)$product_id, 'fslm_sv', $software_version);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sa', $software_author, true)){
            update_post_meta((int)$product_id, 'fslm_sa', $software_author);
        }

        if(!add_post_meta((int)$product_id, 'fslm_surl', $software_url, true)){
            update_post_meta((int)$product_id, 'fslm_surl', $software_url);
        }

        if(!add_post_meta((int)$product_id, 'fslm_slu', $software_last_update, true)){
            update_post_meta((int)$product_id, 'fslm_slu', $software_last_update);
        }

        if(!add_post_meta((int)$product_id, 'fslm_sed', $software_extra_data, true)){
            update_post_meta((int)$product_id, 'fslm_sed', $software_extra_data);
        }

        $licensable            = $_POST['mbs_licensable'];
	    $variation_id          = $_POST['mbs_variation_id'];
        $active                = $_POST['mbs_active'] == 'true'?'1':'0';
        $prefix                = $_POST['mbs_prefix'];
        $chunks_number         = $_POST['mbs_chunks_number'];
        $chunks_length         = $_POST['mbs_chunks_length'];
        $suffix                = $_POST['mbs_suffix'];
        $max_instance_number   = $_POST['mbs_max_instance_number'];
        $valid                 = $_POST['mbs_valid'];

        $this->set_licensing($product_id, '0', $licensable);
	
	    // Import prefix/suffix
	    if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {
		    if(!add_post_meta((int)$product_id, "fslm_import_prefix", $_POST["fslm_import_prefix"], true)){
			    update_post_meta((int)$product_id, "fslm_import_prefix", $_POST["fslm_import_prefix"]);
		    }
		
		    if(!add_post_meta((int)$product_id, "fslm_import_suffix", $_POST["fslm_import_suffix"], true)){
			    update_post_meta((int)$product_id, "fslm_import_suffix", $_POST["fslm_import_suffix"]);
		    }
	    }

        $handle = new WC_Product_Variable($product_id);

        $variations = $handle->get_children();
        foreach ($variations as $variation) {
            $single_variation = new WC_Product_Variation($variation);
            $licensable_variation = $_POST["mbs_licensable_{$variation}"];

            $this->set_licensing($product_id, $variation, $licensable_variation);
	
	        // Import prefix/suffix
	        if(get_option("fslm_is_import_prefix_suffix_enabled", "off") == "on") {
		        if(!add_post_meta((int)$product_id, "fslm_import_prefix_{$variation}", $_POST["fslm_import_prefix_{$variation}"], true)){
			        update_post_meta((int)$product_id, "fslm_import_prefix_{$variation}", $_POST["fslm_import_prefix_{$variation}"]);
		        }
		
		        if(!add_post_meta((int)$product_id, "fslm_import_suffix_{$variation}", $_POST["fslm_import_suffix_{$variation}"], true)){
			        update_post_meta((int)$product_id, "fslm_import_suffix_{$variation}", $_POST["fslm_import_suffix_{$variation}"]);
		        }
	        }
        }

        $show_in               = $_POST['fslm_show_in'];
        $display               = $_POST['fslm_display'];

        if(!add_post_meta((int)$product_id, 'fslm_show_in', $show_in, true)){
            update_post_meta((int)$product_id, 'fslm_show_in', $show_in);
        }

        if(!add_post_meta((int)$product_id, 'fslm_display', $display, true)){
            update_post_meta((int)$product_id, 'fslm_display', $display);
        }

        $this->set_licensing($product_id, $variation_id, $licensable);

        $nb_delivered_lk       = $_POST['fslm_nb_delivered_lk'];

        if(!add_post_meta((int)$product_id, 'fslm_nb_delivered_lk', $nb_delivered_lk, true)){
            update_post_meta((int)$product_id, 'fslm_nb_delivered_lk', $nb_delivered_lk);
        }

        $data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'prefix' => $prefix,
            'chunks_number' => $chunks_number,
            'chunks_length' => $chunks_length,
            'suffix' => $suffix,
            'max_instance_number' => $max_instance_number,
            'valid' => $valid,
            'active' => $active
       );

        $exist =(int)$wpdb->get_var("SELECT COUNT(*)FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id = '" . $product_id . "' and variation_id = '" . $variation_id . "'");

        if($exist == 0){
            $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data);
        }else {
            $where = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id
           );

            $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data, $where);
        }

        echo "Saved";
        die();
    }

    /*****************/
    //   Add License ajax Callback
    /*****************/
    public function add_license_ajax_callback(){
        global $wpdb;

	    $this->set_licensing($_POST['alk_product_id'], $_REQUEST['alk_variation_id'], '1');
	
	    $product_id  = $_POST['alk_product_id'];
	    $license_key = $this->newLine2br($_POST['alk_license_key']);
	    $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
	
	    $exist = false;
	    
	    if(get_option('fslm_duplicate_license', '') != 'on') {
            $exist       = $this->licenseKeyExist($license_key);
            if($exist) {
                
                echo '<span class="fslm_nb_notif">' . __('Duplicate License key was not added to the datebase.', 'fslm') . '</span><br><br>';
                
                require_once('includes/metabox_licenses_list.php');
            
                echo fslm_metabox_licenses_list($product_id);
            
                die();
            }
	    }
	    
        if(! $exist){
            $variation_id = $_REQUEST['alk_variation_id'];
            $max_instance_number =(int)$_REQUEST['alk_max_instance_number'];
            $yy =(int)$_REQUEST['alk_xpdyear'];
            $mm =(int)$_REQUEST['alk_xpdmonth'];
            $dd =(int)$_REQUEST['alk_xpdday'];
            $valid =(int)$_REQUEST['alk_valid'];
            $image_license_key = '';


            $deliver_x_times =(int)$_REQUEST['alk_deliver_x_times'];


            if(!empty($_FILES["alk_image_license_key"]['name'])){
                $upload_directory = wp_upload_dir();
                $target_dir = $upload_directory['basedir'] . '/fslm_keys/';
                if(!file_exists($target_dir)){
                    wp_mkdir_p($target_dir);
                    $fp = fopen($target_dir . 'index.php', 'w');
                    fwrite($fp, '<?php');
                    fclose($fp);
                }

                $imageFileType = strtolower(pathinfo($_FILES["alk_image_license_key"]["name"], PATHINFO_EXTENSION));
                $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
                $target_file = $target_dir . $file_name;
                $image_license_key = $file_name;
                $uploadOk = 1;

                // Check if image file is a actual image or fake image

                $check = getimagesize($_FILES["alk_image_license_key"]["tmp_name"]);
                $uploadOk =($check !== false)? 1 : 0;

                // Check if file already exists
                while(file_exists($target_file)){
                    $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
                    $target_file = $target_dir . $file_name;
                    $image_license_key = $file_name;
                }
                // Check file size
                if($_FILES["alk_image_license_key"]["size"] > 10 * MB){
                    $uploadOk = 0;
                }
                // Allow certain file formats
                $allowed_formats = array('jpg', 'png', 'jpeg', 'gif');
                if(!in_array($imageFileType, $allowed_formats)){
                    $uploadOk = 0;
                }
                // Check if $uploadOk is set to 0 by an error
                if($uploadOk == 0){
                    echo "Sorry, your file was not uploaded.";
                    // if everything is ok, try to upload file
                } else {
                    if(move_uploaded_file($_FILES["alk_image_license_key"]["tmp_name"], $target_file)){
                        echo "The file " . basename($_FILES["alk_image_license_key"]["name"]). " has been uploaded.";
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }
            }

            $data = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'license_key' => $license_key,
                'image_license_key' => $image_license_key,
                'delivre_x_times' => $deliver_x_times,
                'remaining_delivre_x_times' => $deliver_x_times,
                'max_instance_number' => $max_instance_number,
                'number_use_remaining' => $max_instance_number,
                'creation_date' => date('Y-m-d H:i:s'),
                'valid' => $valid,
                'license_status' => 'available'
            );
	
	        $expiration_date = $yy . '-' . $mm . '-' . $dd . ' 0:0:0';
         
	        if($expiration_date != "0-0-0 0:0:0") {
		        $data['expiration_date'] = $expiration_date;
	        }
            
            $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);
	
	        $this->update_stock($product_id, $variation_id);
        }

        require_once('includes/metabox_licenses_list.php');

        echo fslm_metabox_licenses_list($product_id);

        die();
    }

    /*****************/
    //   Plugins initial settings
    /*****************/
    public function activate(){
        global $wpdb;
        $query = array();
        $query[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_fs_product_licenses_keys(
                        license_id                int(11)NOT NULL AUTO_INCREMENT,
                        product_id                int(11)NOT NULL,
                        variation_id              int(11)NOT NULL,
                        license_key               TEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        image_license_key         TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        license_status            TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        owner_first_name          TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        owner_last_name           TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        owner_email_address       TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                        delivre_x_times           int(11)DEFAULT NULL,
                        remaining_delivre_x_times int(11)DEFAULT NULL,
                        max_instance_number       int(11)DEFAULT NULL,
                        number_use_remaining      int(11)DEFAULT NULL,
                        activation_date           date DEFAULT NULL,
                        creation_date             date DEFAULT NULL,                 
                        expiration_date           date DEFAULT NULL,
                        valid                     int(11)NOT NULL,
                        PRIMARY KEY(license_id)
                   )";

        $query[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_fs_licensed_products(
                        config_id int(11)NOT NULL AUTO_INCREMENT,
                        product_id int(11)NOT NULL,
                        variation_id int(11)NOT NULL,
                        active tinyint(1)NOT NULL,
                        PRIMARY KEY(config_id),
                        UNIQUE KEY product_id(product_id, variation_id)
                   )";

        $query[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules(
                        rule_id int(11)NOT NULL AUTO_INCREMENT,
                        product_id int(11)NOT NULL,
                        variation_id int(11)NOT NULL,
                        prefix varchar(100)DEFAULT NULL,
                        chunks_number int(11)NOT NULL,
                        chunks_length int(11)NOT NULL,
                        suffix varchar(100)DEFAULT NULL,
                        max_instance_number int(11)DEFAULT NULL,
                        valid int(11)DEFAULT NULL,
                        active tinyint(1)NOT NULL,
                        PRIMARY KEY(rule_id),
                        UNIQUE KEY product_id(product_id, variation_id)
                   )";

        foreach($query as $q){
            $wpdb->query($q);
        }
	    
	    
        add_option('fslm_nb_rows_by_page', '15');
        add_option('fslm_show_adminbar_notifs', 'on');
        add_option('fslm_guest_customer', 'on');
        add_option('fslm_show_available_license_keys_column', '');
        add_option('fslm_enable_cart_validation', '');
        add_option('fslm_meta_key_name', 'License Key');
        add_option('fslm_generator_chars', '0123456789ABCDEF');
        add_option('fslm_meta_key_name_plural', 'License Keys');
        add_option('fslm_key_delivery', 'fifo');

        add_option('fslm_api_key', '0A9Q5OXT13in3LGjM9F3');
        add_option('fslm_disable_api_v1', '');
        add_option('fslm_auto_expire', '');
        add_option('fslm_auto_redeem', '0');
        add_option('fslm_redeem_btn', '0');
        add_option('fslm_stock_sync', 'off');

        add_option('fslm_show_in', '2');
        add_option('fslm_display', '2');

        add_option('fslm_send_when_completed', 'on');
        add_option('fslm_send_when_processing', 'on');

        add_option('fslm_prefix', '');
        add_option('fslm_chunks_number', '4');
        add_option('fslm_chunks_length', '4');
        add_option('fslm_suffix', '');
        add_option('fslm_max_instance_number', '1');
        add_option('fslm_valid', '0');

        add_option('fslm_active', '0');


        add_option('fslm_notif_min_licenses_nb', '10');
        add_option('fslm_notif_mail', 'off');
        add_option('fslm_notif_mail_to', '');


        add_option('fslm_last_sent_notification_email_date', '0');
        add_option('fslm_page_id', '-1');


        $page_id = get_option('fslm_page_id', '-1');
        if($page_id == '-1') {

            if(!is_page($page_id)) {
                $license_keys_page = array(
                    'post_title' => __('License Key', 'fslm'),
                    'post_content' => '[license_keys]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                    'post_date' => date('Y-m-d H:i:s')
                );

                $page_id = wp_insert_post($license_keys_page);

                update_option('fslm_page_id', $page_id);
            }

        }

    }

    function activation_redirect($plugin) {
        if(!$this->is_active() && $plugin == plugin_basename(__FILE__)) {
            exit(wp_redirect(admin_url('admin.php?page=license-manager-welcome')));
        }
    }

    function is_licensed($product_id, $variation_id = 0) {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(active) FROM {$wpdb->prefix}wc_fs_licensed_products WHERE product_id = '{$product_id}' AND variation_id = '{$variation_id}'")==0?false:true;
    }

    function is_licensing_enabled($product_id, $variation_id = 0) {
        global $wpdb;
        return $wpdb->get_var("SELECT active FROM {$wpdb->prefix}wc_fs_licensed_products WHERE product_id = '{$product_id}' AND variation_id = '{$variation_id}' AND  active = '1'")=='1'?true:false;
    }

    function set_licensing($product_id, $variation_id, $active) {
        global $wpdb;

        if($this->is_licensed($product_id, $variation_id)) {
            $data = array(
                'active' => $active
            );

            $where = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id
            );

            $wpdb->update("{$wpdb->prefix}wc_fs_licensed_products", $data, $where);

        }else{
            $data = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'active' => $active
            );

            $wpdb->insert("{$wpdb->prefix}wc_fs_licensed_products", $data);
        }
    }

    public function deactivate(){
	    global $wpdb;
        $query = array();

        if(get_option('fslm_delete_lk_db_tables', '')=='on') {
            
            $query[] = "DROP TABLE {$wpdb->prefix}wc_fs_product_licenses_keys";
	
	        if(!add_option('fslm_db_version', '0')){
		        update_option('fslm_db_version', '0');
	        }

        }
        
        if(get_option('fslm_delete_gr_db_tables', '')=='on') {
            
            $query[] = "DROP TABLE {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules";

        }
        
        if(get_option('fslm_delete_lp_db_tables', '')=='on') {
            
            $query[] = "DROP TABLE {$wpdb->prefix}wc_fs_licensed_products";

        }
        
        foreach($query as $q){
                $wpdb->query($q);
            }
    }

    /*****************/
    //   Add Product page License Manager metabox
    /*****************/
    public function action_add_metaboxes(){
        add_meta_box('fslm-wc-licenses', __('License', 'fslm'), array($this, 'metabox_licenses'), 'product', 'advanced');
    }

    /*****************/
    //   Product page License Manager metabox
    /*****************/
    public function metabox_licenses(){
        require dirname(__FILE__). '/includes/product_metabox.php';
    }

    /*****************/
    //   Admin Requests Handler
    /*****************/
    public function requestHandler(){
        global $wpdb;

        if((current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ))){
            $action = isset($_REQUEST['action'])? $_REQUEST['action'] : null;
            if(!$action)
                return false;

            if($action == 'delete_license'){
                $this->delete_license();

            }else if($action == 'delete_rule'){
               $this->delete_rule();

            }else if($action == 'add_license'){
                $this->add_license();

            }else if($action == 'add_rule'){
                $this->add_rule();

            }else if($action == 'edit_rule'){
                $this->edit_rule();

            }else if($action == 'edit_license'){
                $this->edit_license();

            }else if($action == 'licenses_bulk_action'){
                $this->licenses_bulk_action();

            }else if($action == 'generator_bulk_action'){
                $this->generator_bulk_action();
            }else if($action == 'save_encryption_setting'){
                $this->save_encryption_setting();
            }else if($action == 'delete_product_license_keys'){
                $this->delete_product_license_keys();
            }
        }
    }

    function delete_product_license_keys() {
        global $wpdb;

        $count = $wpdb->delete($wpdb->prefix . 'wc_fs_product_licenses_keys', array('product_id' =>(int)$_REQUEST['product_id']));

        $link = admin_url('admin.php?page=license-manager-settings&tab=extra&dc=') . $count;
        wp_redirect($link);
        die();
    }

    function save_encryption_setting() {

        $key = $_POST['fslm_encryption_key'];
        $vi = $_POST['fslm_encryption_vi'];

        $this->set_encryption_key($key, $vi, 'update');

        $link = admin_url('admin.php?page=license-manager-settings&tab=encryption');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Add License
    /*****************/
    function add_license(){
        global $wpdb;

        $license_key = $this->newLine2br($_POST['license_key']);

        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);
	
	    $exist = $this->license_key_existe($license_key);
	
	    if(get_option('fslm_duplicate_license', '') == 'on') {
		    $exist = false;
        }
        
        if(! $exist){
            $product_id = $_REQUEST['product_id'];
            $variation_id = $_REQUEST['variation_id'];

	        $this->set_licensing($product_id, $variation_id, '1');

            $max_instance_number =(int)$_REQUEST['max_instance_number'];
            $yy =(int)$_REQUEST['yy'];
            $mm =(int)$_REQUEST['mm'];
            $dd =(int)$_REQUEST['dd'];
            $valid =(int)$_REQUEST['valid'];
            $image_license_key = '';

            $deliver_x_times =(int)$_REQUEST['deliver_x_times'];


            if(!empty($_FILES["image_license_key"]['name'])){
                $upload_directory = wp_upload_dir();
                $target_dir = $upload_directory['basedir'] . '/fslm_keys/';
                if(!file_exists($target_dir)){
                    wp_mkdir_p($target_dir);
                    $fp = fopen($target_dir . 'index.php', 'w');
                    fwrite($fp, '<?php');
                    fclose($fp);
                }

                $imageFileType = strtolower(pathinfo($_FILES["image_license_key"]["name"], PATHINFO_EXTENSION));
                $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
                $target_file = $target_dir . $file_name;
                $image_license_key = $file_name;
                $uploadOk = 1;

                // Check if image file is a actual image or fake image

                $check = getimagesize($_FILES["image_license_key"]["tmp_name"]);
                $uploadOk =($check !== false)? 1 : 0;

                // Check if file already exists
                while(file_exists($target_file)){
                    $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
                    $target_file = $target_dir . $file_name;
                    $image_license_key = $file_name;
                }
                // Check file size
                if($_FILES["image_license_key"]["size"] > 10 * MB){
                    $uploadOk = 0;
                }
                // Allow certain file formats
                $allowed_formats = array('jpg', 'png', 'jpeg', 'gif');
                if(!in_array($imageFileType, $allowed_formats)){
                    $uploadOk = 0;
                }
                // Check if $uploadOk is set to 0 by an error
                if($uploadOk == 0){
                    echo "Sorry, your file was not uploaded.";
                    // if everything is ok, try to upload file
                } else {
                    if(move_uploaded_file($_FILES["image_license_key"]["tmp_name"], $target_file)){
                        echo "The file " . basename($_FILES["image_license_key"]["name"]). " has been uploaded.";
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }
            }
	
	        $expiration_date = $yy . '-' . $mm . '-' . $dd . ' 0:0:0';
            
            $data = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'license_key' => $license_key,
                'image_license_key' => $image_license_key,
                'delivre_x_times' => $deliver_x_times,
                'remaining_delivre_x_times' => $deliver_x_times,
                'max_instance_number' => $max_instance_number,
                'number_use_remaining' => $max_instance_number,
                'creation_date' => date('Y-m-d H:i:s'),
                'valid' => $valid,
                'license_status' => 'available'
            );
	
	        if($expiration_date != "0-0-0 0:0:0") {
		        $data['expiration_date'] = $expiration_date;
	        }
            
            $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys", $data);
	
	        $this->update_stock($product_id, $variation_id);
        }

        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);
        die();

    }

    function license_key_existe($license_key){
        global $wpdb;

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_key='" . $license_key . "'");

        if($query){
            return true;
        }

        return false;

    }

    /*****************/
    //   Edit License
    /*****************/
    function edit_license(){
        global $wpdb;

        $license_key = $this->newLine2br($_POST['license_key']);

        $license_key = $this->encrypt_decrypt('encrypt', $license_key, ENCRYPTION_KEY, ENCRYPTION_VI);

        $license_id = $_REQUEST['license_id'];
        $product_id = $_REQUEST['product_id'];
        $variation_id = $_REQUEST['variation_id'];
        $max_instance_number = $_REQUEST['max_instance_number'];
        $number_use_remaining =(int)$_REQUEST['number_use_remaining'];

        $owner_first_name = $_REQUEST['owner_first_name'];
        $owner_last_name = $_REQUEST['owner_last_name'];
        $owner_email_address = $_REQUEST['owner_email_address'];

        $creation_day =(int)$_REQUEST['creation_day'];
        $creation_month =(int)$_REQUEST['creation_month'];
        $creation_year =(int)$_REQUEST['creation_year'];

        $activation_day =(int)$_REQUEST['activation_day'];
        $activation_month =(int)$_REQUEST['activation_month'];
        $activation_year =(int)$_REQUEST['activation_year'];

        $expiration_day =(int)$_REQUEST['expiration_day'];
        $expiration_month =(int)$_REQUEST['expiration_month'];
        $expiration_year =(int)$_REQUEST['expiration_year'];

        $valid =(int)$_REQUEST['valid'];
        $order_id =(int)$_REQUEST['order_id'];

        $license_status = $_REQUEST['status'];

        $deliver_x_times =(int)$_REQUEST['deliver_x_times'];
        $remaining_delivre_x_times =(int)$_REQUEST['remaining_delivre_x_times'];


        $image_license_key = $this->get_image_name($license_id);
        if((isset($_REQUEST['rmoi']) && $_REQUEST['rmoi'] == 'on')&&($image_license_key != '')){
            $this->delete_image($image_license_key);

            $image_license_key = '';
        }

        if(!empty($_FILES["image_license_key"]['name'])){
            if((isset($_REQUEST['rmoi']) && $_REQUEST['rmoi'])&&($image_license_key != '')){
                $this->delete_image($image_license_key);

                $image_license_key = '';
            }
            $upload_directory = wp_upload_dir();
            $target_dir = $upload_directory['basedir'] . '/fslm_keys/';
            if(!file_exists($target_dir)){
                wp_mkdir_p($target_dir);
                $fp = fopen($target_dir . 'index.php', 'w');
                fwrite($fp, '<?php');
                fclose($fp);
            }

            $imageFileType = strtolower(pathinfo($_FILES["image_license_key"]["name"], PATHINFO_EXTENSION));
            $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
            $target_file = $target_dir . $file_name;
            $image_license_key = $file_name;
            $uploadOk = 1;

            // Check if image file is a actual image or fake image

            $check = getimagesize($_FILES["image_license_key"]["tmp_name"]);
            $uploadOk =($check !== false)? 1 : 0;

            // Check if file already exists
            while(file_exists($target_file)){
                $file_name = basename($this->fslm_uuid_v4_no_dashes(). '.' . $imageFileType);
                $target_file = $target_dir . $file_name;
                $image_license_key = $file_name;
            }
            // Check file size
            if($_FILES["image_license_key"]["size"] > 10 * MB){
                $uploadOk = 0;
            }
            // Allow certain file formats
            $allowed_formats = array('jpg', 'png', 'jpeg', 'gif');
            if(!in_array($imageFileType, $allowed_formats)){
                $uploadOk = 0;
            }
            // Check if $uploadOk is set to 0 by an error
            if($uploadOk == 0){
                echo "Sorry, your file was not uploaded.";
                // if everything is ok, try to upload file
            } else {
                if(move_uploaded_file($_FILES["image_license_key"]["tmp_name"], $target_file)){
                    echo "The file " . basename($_FILES["image_license_key"]["name"]). " has been uploaded.";
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        }
	
	    $new_devices = array();
	    $devices_list = $wpdb->get_var("SELECT device_id FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_id = $license_id");
	    
	    if(isset($_POST['fslm_device'])) {
		    $deleted_devices_list = $_POST['fslm_device'];
		
		    if($devices_list != '' && $devices_list != '[]' && $devices_list != null) {
			    $devices_list = json_decode($devices_list);
			    if (is_array($devices_list)) {
				    foreach ($devices_list as $device) {
					    if (!in_array($device, $deleted_devices_list)) {
						    $new_devices[] = $device;
					    }
				    }
			    }
			
			    $new_devices = json_encode($new_devices);
		    } else{
			    $new_devices = '';
            }
	    } else {
		    $new_devices = $devices_list;
        }
	    
	
	    $data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'license_key' => $license_key,
            'image_license_key' => $image_license_key,
            'delivre_x_times' => $deliver_x_times,
            'remaining_delivre_x_times' => $remaining_delivre_x_times,
            'max_instance_number' => $max_instance_number,
            'number_use_remaining' => $number_use_remaining,
            'owner_first_name' => $owner_first_name,
            'owner_last_name' => $owner_last_name,
            'owner_email_address' => $owner_email_address,
            'creation_date' => $creation_year . '-' . $creation_month . '-' . $creation_day . ' 0:0:0',
            'activation_date' => $activation_year . '-' . $activation_month . '-' . $activation_day . ' 0:0:0',
            'expiration_date' => $expiration_year . '-' . $expiration_month . '-' . $expiration_day . ' 0:0:0',
            'valid' => $valid,
            'license_status' => $license_status,
            'device_id' => $new_devices,
            'order_id' => $order_id
       );

        $where = array(
            'license_id' => $license_id
       );
        $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys", $data, $where);

        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);
        die();
    }

    function get_image_name($license_id){
        global $wpdb;

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_id='" . $license_id . "'");

        if($query){
            $query = $query[0];

            return $query->image_license_key;
        }

        return '';
    }

    function delete_image($file){
        $upload_directory = wp_upload_dir();
        $target_file = $upload_directory['basedir'] . '/fslm_keys/' . $file;
        if(file_exists($target_file)){
            chmod($target_file, 0777);
            unlink($target_file);
        }
    }

    /*****************/
    //   Delete License
    /*****************/
    function delete_license(){
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'wc_fs_product_licenses_keys', array('license_id' =>(int)$_REQUEST['license_id']));

        $image_license_key = $this->get_image_name((int)$_REQUEST['license_id']);
        if($image_license_key != ''){
            $this->delete_image($image_license_key);
        }
	
	    if(get_option('fslm_stock_sync', '') == 'on') {
		    if(!add_option('fslm_stock_sync_last_run', '0')){
			    update_option('fslm_stock_sync_last_run', '0');
		    }
	    }

        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Add Generator rule
    /*****************/
    function add_rule(){
        global $wpdb;

        $product_id = $_REQUEST['product_id'];
        $variation_id = $_REQUEST['variation_id'];
        $prefix = $_REQUEST['prefix'];
        $chunks_number =(int)$_REQUEST['chunks_number'];
        $chunks_length =(int)$_REQUEST['chunks_length'];
        $suffix = $_REQUEST['suffix'];
        $max_instance_number = $_REQUEST['max_instance_number'];
        $valid = $_REQUEST['valid'];
        $active =(int)$_REQUEST['active'];


        $data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'prefix' => $prefix,
            'chunks_number' => $chunks_number,
            'chunks_length' => $chunks_length,
            'suffix' => $suffix,
            'max_instance_number' => $max_instance_number,
            'valid' => $valid,
            'active' => $active
       );
        $wpdb->insert("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data);
        $link = admin_url('admin.php?page=license-manager-license-key-generator');
        wp_redirect($link);
        die();

    }

    /*****************/
    //   Edit Generator rule
    /*****************/
    function edit_rule(){
        global $wpdb;

        $rule_id = $_REQUEST['rule_id'];
        $product_id = $_REQUEST['product_id'];
        $variation_id = $_REQUEST['variation_id'];
        $prefix = $_REQUEST['prefix'];
        $chunks_number =(int)$_REQUEST['chunks_number'];
        $chunks_length =(int)$_REQUEST['chunks_length'];
        $suffix = $_REQUEST['suffix'];
        $max_instance_number = $_REQUEST['max_instance_number'];
        $valid = $_REQUEST['valid'];
        $active =(int)$_REQUEST['active'];

        $data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'prefix' => $prefix,
            'chunks_number' => $chunks_number,
            'chunks_length' => $chunks_length,
            'suffix' => $suffix,
            'max_instance_number' => $max_instance_number,
            'valid' => $valid,
            'active' => $active
       );
        $where = array(
            'rule_id' => $rule_id
       );
        $wpdb->update("{$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules", $data, $where);
        $link = admin_url('admin.php?page=license-manager-license-key-generator');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Delete Generator rule
    /*****************/
    function delete_rule(){
         global $wpdb;
        $wpdb->delete($wpdb->prefix . 'wc_fs_product_licenses_keys_generator_rules', array('rule_id' =>(int)$_REQUEST['rule_id']));
        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);
        die();
    }

    /*****************/
    //   Licenses Bulk action Handler
    /*****************/
    function licenses_bulk_action(){
        global $wpdb;

        if(!isset($_POST['post'])) {
            $link = admin_url('admin.php?page=license-manager');
            wp_redirect($link);

            die();
        }

        if($_POST['baction'] == 'trash'){

            $ids = $_POST['post'];

            $sql = "DELETE FROM {$wpdb->prefix}wc_fs_product_licenses_keys WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

            foreach($ids as $id){
                $image_license_key = $this->get_image_name($id);
                if($image_license_key != ''){
                    $this->delete_image($image_license_key);
                }
            }

        }else if($_POST['baction'] == 'available'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'available', remaining_delivre_x_times = 1,  delivre_x_times = 1  WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'active'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'active' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'expired'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'expired' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'inactive'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'inactive' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'sold'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'sold' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'returned'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys SET license_status = 'returned' WHERE license_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }

        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);

        die();
    }

    /*****************/
    //   Generator Bulk action Handler
    /*****************/
    function generator_bulk_action(){
        global $wpdb;

        if($_POST['baction'] == 'trash'){

            $ids = $_POST['post'];

            $sql = "DELETE FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE rule_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'activate'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules SET active = '1' WHERE rule_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }else if($_POST['baction'] == 'deactivate'){

            $ids = $_POST['post'];

            $sql = "UPDATE {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules SET active = '0' WHERE rule_id IN([IN])";
            $sql = $this->prepare_in($sql, $ids);

            $wpdb->query($sql);

        }

        $link = admin_url('admin.php?page=license-manager');
        wp_redirect($link);

        die();
    }


    /*****************/
    //   Prepared statements with IN and NOT IN
    /*****************/
    public function prepare_in($sql, $vals){
        global $wpdb;
        $not_in_count = substr_count($sql, '[IN]');
        if($not_in_count > 0){
            $args = array(str_replace('[IN]', implode(', ', array_fill(0, count($vals), '%d')), str_replace('%', '%%', $sql)));
            // This will populate ALL the [IN]'s with the $vals, assuming you have more than one [IN] in the sql
            for($i=0; $i < substr_count($sql, '[IN]'); $i++){
                $args = array_merge($args, $vals);
            }
            $sql = call_user_func_array(array($wpdb, 'prepare'), array_merge($args));
        }
        return $sql;
    }

    /*****************/
    //   Generate license keys csv
    /*****************/
    public function generate_license_keys_csv($license_status = '', $product_id = ''){
        global $wpdb;

        $args = '';

        if($license_status != '' && $product_id != ''){
            $args .= "WHERE license_status = '{$license_status}' AND product_id = '{$product_id}'";
        }else if($license_status != ''){
            $args .= "WHERE license_status = '{$license_status}'";
        }else if($product_id != ''){
            $args .= "WHERE product_id = '{$product_id}'";
        }

        $output = "sep=,\n";

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys {$args} ORDER BY license_id DESC ", ARRAY_A);
	
	    if($query){
		
		    $output .= '"'.implode('","',array_keys($query[0])).'"'."\n";
		
		    foreach($query as $row){
			
			    $row['device_id'] = str_replace('","', '|',$row['device_id']);
			
			    $output .= '"'.implode('","',$row).'"'."\n";
			
		    }
	    }



        return $output;
    }

    /*****************/
    //   Generate generator rules csv
    /*****************/
    public function generate_generator_rules_csv($product_id = ''){
        global $wpdb;

        $args = '';

        if($product_id != ''){
            $args .= "WHERE product_id = '{$product_id}'";
        }

        $output = "sep=,\n";

        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules {$args}", ARRAY_A);

        if($query){

            $output .= '"'.implode('","',array_keys($query[0])).'"'."\n";

            foreach($query as $row){
                $output .= '"'.implode('","',$row).'"'."\n";
            }
        }



        return $output;
    }

    /*****************/
    //   Generate Plugin Settings
    /*****************/
    public function generate_plugin_settings($product_id = ''){
        $output = '';
        
        /////////////////////////
        
        
        $order_statuses = (array) $this->get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));

		if($order_statuses && !is_wp_error($order_statuses)) {
            foreach($order_statuses as $s) {

                if(version_compare(WOOCOMMERCE_VERSION, '2.2', '>=' )) {
                    
                    $s->slug = str_replace('wc-', '', $s->slug);
                    
                }
                
                $output .= 'fslm_send_when_' . $s->slug . ' => ' . get_option('fslm_send_when_' . $s->slug). "\n";
                $output .= 'fslm_revoke_when_' . $s->slug . ' => ' . get_option('fslm_revoke_when_' . $s->slug). "\n";
                $output .= 'fslm_hide_when_' . $s->slug . ' => ' . get_option('fslm_hide_when_' . $s->slug). "\n";

            }
        }
		
        
        
        /////////////////////////

        $output .= 'fslm_show_adminbar_notifs => ' . get_option('fslm_show_adminbar_notifs'). "\n";
        $output .= 'fslm_enable_cart_validation => ' . get_option('fslm_enable_cart_validation'). "\n";
        $output .= 'fslm_nb_rows_by_page => ' . get_option('fslm_nb_rows_by_page'). "\n";
        $output .= 'fslm_meta_key_name => ' . get_option('fslm_meta_key_name'). "\n";
        $output .= 'fslm_meta_key_name_plural => ' . get_option('fslm_meta_key_name_plural'). "\n";
        $output .= 'fslm_guest_customer => ' . get_option('fslm_guest_customer'). "\n";
        $output .= 'fslm_guest_customer => ' . get_option('fslm_show_available_license_keys_column'). "\n";
        $output .= 'fslm_key_delivery => ' . get_option('fslm_key_delivery'). "\n";
        $output .= 'fslm_show_in => ' . get_option('fslm_show_in'). "\n";
        $output .= 'fslm_display => ' . get_option('fslm_display'). "\n";

        $output .= 'fslm_generator_chars => ' . get_option('fslm_generator_chars'). "\n";

        $output .= 'fslm_prefix => ' . get_option('fslm_prefix'). "\n";
        $output .= 'fslm_chunks_number => ' . get_option('fslm_chunks_number'). "\n";
        $output .= 'fslm_chunks_length => ' . get_option('fslm_chunks_length'). "\n";
        $output .= 'fslm_suffix => ' . get_option('fslm_suffix'). "\n";
        $output .= 'fslm_max_instance_number => ' . get_option('fslm_max_instance_number'). "\n";
        $output .= 'fslm_valid => ' . get_option('fslm_valid'). "\n";
        $output .= 'fslm_active => ' . get_option('fslm_active'). "\n";

        $output .= 'fslm_notif_min_licenses_nb => ' . get_option('fslm_notif_min_licenses_nb'). "\n";
        $output .= 'fslm_notif_mail => ' . get_option('fslm_notif_mail'). "\n";
        $output .= 'fslm_notif_mail_to => ' . get_option('fslm_notif_mail_to'). "\n";

        $output .= 'fslm_last_sent_notification_email_date => ' . get_option('fslm_last_sent_notification_email_date'). "\n";
        $output .= 'fslm_page_id => ' . get_option('fslm_page_id'). "\n";

        $output .= 'fslm_mail_heading => ' . get_option('fslm_mail_heading'). "\n";
        $output .= 'fslm_mail_subject => ' . get_option('fslm_mail_subject'). "\n";
        $output .= 'fslm_mail_message => ' . str_replace(array("\r\n","\n\r","\n","\r"), "[NL]", get_option('fslm_mail_message')) . "\n";

        $output .= 'fslm_encryption => ' . ENCRYPTION_KEY . '[SEP]' . ENCRYPTION_VI . "\n";


        $output .= 'fslm_delete_lk_db_tables => ' . get_option('fslm_delete_lk_db_tables'). "\n";
        $output .= 'fslm_delete_gr_db_tables => ' . get_option('fslm_delete_gr_db_tables'). "\n";
        $output .= 'fslm_delete_lp_db_tables => ' . get_option('fslm_delete_lp_db_tables'). "\n";
        
        $output .= 'fslm_add_lk_wc_de => ' . get_option('fslm_add_lk_wc_de'). "\n";
        $output .= 'fslm_add_lk_se => ' . get_option('fslm_add_lk_se'). "\n";
        $output .= 'fslm_add_wc_header_and_footer => ' . get_option('fslm_add_wc_header_and_footer'). "\n";
        
        $output .= 'fslm_is_import_prefix_suffix_enabled => ' . get_option('fslm_is_import_prefix_suffix_enabled'). "\n";

        $output .= 'fslm_api_key => ' . get_option('fslm_api_key'). "\n";
        $output .= 'fslm_disable_api_v1 => ' . get_option('fslm_disable_api_v1'). "\n";
        $output .= 'fslm_auto_expire => ' . get_option('fslm_auto_expire'). "\n";
        $output .= 'fslm_auto_redeem => ' . get_option('fslm_auto_redeem'). "\n";
        $output .= 'fslm_redeem_btn => ' . get_option('fslm_redeem_btn'). "\n";
        $output .= 'fslm_stock_sync => ' . get_option('fslm_stock_sync'). "\n";
        $output .= 'fslm_delete_keys => ' . get_option('fslm_delete_keys'). "\n";
        $output .= 'fslm_stock_sync_frequency => ' . get_option('fslm_stock_sync_frequency'). "\n";
        $output .= 'fslm_duplicate_license => ' . get_option('fslm_duplicate_license'). "\n";

        return $output;
    }

    /*****************/
    //   Plugin Init
    /*****************/
    public function plugin_init(){
        require_once(FSLM_PLUGIN_BASE . "/includes/updater.php");
        
        load_plugin_textdomain('fslm', false, basename(dirname(__FILE__)). '/languages/');
	    
    }

	function plugin_deactivation_notices(){
		global $pagenow;

		if(($pagenow == 'plugins.php') && ((get_option('fslm_delete_lp_db_tables', '')=='on') || (get_option('fslm_delete_lk_db_tables', '')=='on') || (get_option('fslm_delete_gr_db_tables', '')=='on'))){
			echo "<div class='updated'><p>" . __('Export your data before deactivating', 'fslm').' <strong>' . __('FS License Manager', 'fslm'). '</strong> <br>' . __('Database tables will be deleted, to deactivate without deleting the database table disable this option in <b>License Manager -> Settings -> Extra Settings</b>', 'fslm'). "</p></div>";
		}
	}

	function newLine2br($str){
        $newLineArray = array("\r\n","\n\r","\n","\r");
        $output = str_replace($newLineArray,"<br>",$str);

        return $output;
    }

    function br2newLine($str){
        $newLineArray = array("<br>","<br />","<br/>");
        $output = str_replace($newLineArray,"\n\r",$str);

        return $output;
    }

    function set_encryption_key($key, $vi, $action = 'set') {
        $upload_directory = wp_upload_dir();
        $target_dir = $upload_directory['basedir'] . '/fslm_files/';

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);

            $fp = fopen($target_dir . '.htaccess', 'w');
            fwrite($fp, 'deny from all');
            fclose($fp);

            $fp = fopen($target_dir . 'encryption_key.php', 'w');
            fwrite($fp, "<?php define(\"ENCRYPTION_KEY\", \"" . $key . "\");\ndefine(\"ENCRYPTION_VI\", \"" . $vi . "\");");
            fclose($fp);

            $fp = fopen($target_dir . 'index.php', 'w');
            fwrite($fp, '<?php');
            fclose($fp);
        }else if ($action = 'update'){
            $fp = fopen($target_dir . 'encryption_key.php', 'w');
            fwrite($fp, "<?php define(\"ENCRYPTION_KEY\", \"" . $key . "\");\ndefine(\"ENCRYPTION_VI\", \"" . $vi . "\");");
            fclose($fp);
        }
    }

    function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {
        $output = false;

        if ($secret_key == "" && $secret_iv == "") {
            return $string;
        }

        if (!extension_loaded('openssl')) {
            return $string;
        }

        $encrypt_method = "AES-256-CBC";

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if($action == 'decrypt'){
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    function apply_mail_text_filters($text, $order_id){

        if('' == trim($text))
            return $text;

        $meta = get_post_custom($order_id, true);

        $sitename = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        $siteurl = wp_specialchars_decode(get_option('home'), ENT_QUOTES);

        if(false !== strpos($text, '[order_id]')){
            $text = str_replace('[order_id]', $order_id, $text);

        }

        if(false !== strpos($text, '[bfname]')){

            $fname = $meta['_billing_first_name'][0];

            if(!$fname || empty($fname))
                $text = str_replace('[bfname]', '', $text);
            else
                $text = str_replace('[bfname]', $fname, $text);
        }

        if(false !== strpos($text, '[blname]')){

            $lname = $meta['_billing_last_name'][0];

            if(!$lname || empty($lname))
                $text = str_replace('[blname]', '', $text);
            else
                $text = str_replace('[blname]', $lname, $text);
        }

        if(false !== strpos($text, '[sfname]')){

            $fname = $meta['_shipping_first_name'][0];

            if(!$fname || empty($fname))
                $text = str_replace('[sfname]', '', $text);
            else
                $text = str_replace('[sfname]', $fname, $text);
        }

        if(false !== strpos($text, '[slname]')){

            $lname = $meta['_shipping_last_name'][0];

            if(!$lname || empty($lname))
                $text = str_replace('[slname]', '', $text);
            else
                $text = str_replace('[slname]', $lname, $text);
        }

        if(false !== strpos($text, '[site_name]'))
            $text = str_replace('[site_name]', $sitename, $text);

        if(false !== strpos($text, '[url]'))
            $text = str_replace('[url]', $siteurl, $text);

        if(false !== strpos($text, '[myaccount_url]')){
            $ma_id = wc_get_page_id('myaccount');
            $ma_url = get_permalink($ma_id);
            $text = str_replace('[myaccount_url]', $ma_url, $text);
        }

        return $text;
    }

    function add_actions(){ /**/
     
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

                if(get_option('fslm_send_when_' . $s->slug, $default_send)== 'on'){
                    
                    add_action('woocommerce_order_status_' . $s->slug, array($this, 'fslm_send_license_keys'), 1, 1);
                }

                if(get_option('fslm_revoke_when_' . $s->slug, $default_revoke)== 'on'){
                    add_action('woocommerce_order_status_' . $s->slug, array($this, 'fslm_revoke_license_keys'), 1, 1);
                }

                if(get_option('fslm_hide_when_' . $s->slug, $default_hide)== 'on'){
                    add_action('woocommerce_order_status_' . $s->slug, array($this, 'fslm_hide_license_keys'), 1, 1);
                }

            }
        }
		
        
        /////////////////////////
 
        /**/

    }
    
    
    public static function get_terms($taxo = 'shop_order_status', $args = array()) {
	
		if(version_compare(WOOCOMMERCE_VERSION, '2.2', '<')) {
		
			return get_terms( $taxo, $args );
			
		} else if(version_compare(WOOCOMMERCE_VERSION, '2.2', '>=')) {
		
			$s = wc_get_order_statuses();

			if(!empty($s)) {
			
				$i = 1;
				
				foreach($s as $key => $val) {
			
					if(empty($key) || empty($val))
						continue;
						
					$status = new stdClass();
			
					$status->term_id = $i;
					
					$status->slug = $key;
					
					$status->name = $val;
					
					$statuses[$i] = $status;

					$i++;
					
				}

				return $statuses;
			}
			
		}
	}
    
    
    public function license($c, $eu) {
    	
    	update_option('fslm_status', 'N/A');
update_option('fslm_lk', $c);
update_option('fslm_ebun', $eu);
update_option('fslm_lks', 'active');
update_option('fslm_su', 'January 01, 2034 00:00');
return array('status'=>'activated','message'=>'License key activated for this domain');

	    $postURL   = base64_decode("aHR0cHM6Ly9nb29nbGUuY29tLw==");
	    $secretKey = base64_decode("NWJiNmM2ODAzNTZlMjMuODg1MDcwODE=");
	    $user      = get_currentuserinfo();

	    $first_name = $user->user_firstname;
	    $last_name  = $user->user_lastname;
	    if($user->user_firstname == "") {
	        $first_name = $user->display_name . "*";
        }

	    if($user->user_lastname == "") {
		    $last_name  = $user->display_name . "*";
	    }

        $data = array(
             'slm_action' => 'slm_envato_create_new',
             'secret_key' => $secretKey,
             'license_key' => $c,
             'fx_username' => $eu,
             'email' => $user->user_email,
             'registered_domain' => $_SERVER['SERVER_NAME'],
             'product_ref' => '16636748',
             'first_name' => $first_name,
             'last_name' => $last_name,
             'company_name' => '',
             'txn_id' => '',
             'lic_status' => 'active',
        );

	    //url-ify the data for the POST
	    $fields_string = "";
	    foreach($data as $key=>$value) {
		    $fields_string .= $key.'='.$value.'&';
	    }
	    rtrim($fields_string, '&');

	    //open connection
	    $ch = curl_init();

	    //set the url, number of POST vars, POST data
	    curl_setopt($ch, CURLOPT_URL, $postURL);
	    curl_setopt($ch, CURLOPT_POST, count($data));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	    //execute post
	    $aresult = curl_exec($ch);
	    curl_close($ch);

        $json_result = json_decode($aresult);



        if($json_result != null && property_exists($json_result, 'code') ) {

            if($json_result->code == 15 || $json_result->code == 11) {
                $result = json_decode($this->fslm_activate($c, $eu));

                if($result->code == 200 || $result->code == 110) {

                    if(!add_option('fslm_status', 'N/A')){
                        update_option('fslm_status', 'N/A');
                    }

                    if(!add_option('fslm_lk', $c)){
                        update_option('fslm_lk', $c);
                    }

                    if(!add_option('fslm_ebun', $eu)){
                        update_option('fslm_ebun', $eu);
                    }

                    if(!add_option('fslm_lks', 'active')){
                        update_option('fslm_lks', 'active');
                    }

                    $support = $this->fslm_envato_api($c);
                    if($support) {
                        if(!add_option('fslm_su', $support)){
                            update_option('fslm_su', $support);
                        }
                    } else {
                        if(!add_option('fslm_su', '')){
                            update_option('fslm_su', '');
                        }
                    }

                    return array(
                        'status' => 'activated',
                        'message' => 'License key activated for this domain'
                    );
                } else {
                    return array(
                        'status' => 'fail',
                        'message' => $result->message
                    );
                }
            } else if($json_result->code == 400) {
                $result = json_decode($this->fslm_activate($c, $eu));

                if($result->code == 200 || $result->code == 110) {

                    if(!add_option('fslm_status', 'N/A')){
                        update_option('fslm_status', 'N/A');
                    }

                    if(!add_option('fslm_lk', $c)){
                        update_option('fslm_lk', $c);
                    }

                    if(!add_option('fslm_ebun', $eu)){
                        update_option('fslm_ebun', $eu);
                    }

                    if(!add_option('fslm_lks', 'active')){
                        update_option('fslm_lks', 'active');
                    }

                    return array(
                        'status' => 'activated',
                        'message' => 'License key activated for this domain'
                    );
                } else {
                    return array(
                        'status' => 'fail',
                        'message' => $result->message
                    );
                }
            } else {
                return array(
                    'status' => 'fail',
                    'message' => $json_result->message
                );
            }

        } else {

            $response = $this->fx_slm_envato_api($c, $eu, '16636748');

            if($response['status'] == 'success') {
                if(!add_option('fslm_status', 'N/A')){
                    update_option('fslm_status', 'N/A');
                }

                if(!add_option('fslm_lk', $c)){
                    update_option('fslm_lk', $c);
                }

                if(!add_option('fslm_ebun', $eu)){
                    update_option('fslm_ebun', $eu);
                }

                if(!add_option('fslm_lks', 'active')){
                    update_option('fslm_lks', 'active');
                }

                if(!add_option('fslm_su', $response['supported_until'])){
                    update_option('fslm_su', $response['supported_until']);
                }

                return array(
                    'status' => 'activated',
                    'message' => 'License key activated for this domain'
                );
            }else {
                return array(
                    'status' => 'fail',
                    'message' => 'Invalid purchase code or username'
                );
            }

        }

    }

    function fx_slm_envato_api($purchase_code, $buyer, $item_id) {
        $url = base64_decode('aHR0cHM6Ly9hcGkuZW52YXRvLmNvbS92My9tYXJrZXQvYXV0aG9yL3NhbGU/Y29kZT0lcHVyY2hhc2VfY29kZSU=');
        $url = str_replace('%purchase_code%', $purchase_code, $url);
        $request = curl_init($url);

        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';
        $header[] = 'Authorization: bearer ' . base64_decode('N3NzSFpQaFNrRzlLam9sdWVjSWplZHdmUXhPaTlxWmM=');

        curl_setopt($request, CURLOPT_HTTPHEADER, $header);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_HTTPHEADER,$header);

        $json_result = curl_exec($request);
        $data        = json_decode($json_result, true);

        curl_close($request);

        if(isset($data['item']['id']) && ($data['item']['id'] == $item_id) && (strtolower($data['buyer']) == strtolower($buyer))) {
            return array(
                'status'      => 'success',
                'max_domains' => $data['purchase_count'],
                'supported_until' => $data['supported_until']
            );
        } else if(isset($data['item']['id']) && ($data['item']['id'] == $item_id) && (strtolower($data['buyer']) != strtolower($buyer))) {
            return array(
                'status'      => 'fail',
                'error'       => 'username'
            );
        } else {
            return array(
                'status'      => 'fail'
            );
        }

    }

    function fx_slm_is_envato_purchase_code($pc) {
        if(preg_match("/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/", $pc)) {
            return true;
        }
        return false;
    }

    private function fslm_activate($c, $eu) {
	    $postURL   = base64_decode("aHR0cHM6Ly9nb29nbGUuY29tLw==");
	    $secretKey = base64_decode("NWJiNmM2ODAzNTZlMjMuODg1MDcwODE=");

	    $data = array(
		    'slm_action' => 'slm_activate',
		    'secret_key' => $secretKey,
		    'license_key' => $c,
		    'fx_username' => $eu,
		    'registered_domain' => $_SERVER['SERVER_NAME'],
		    'item_reference' => '16636748',
	    );

	    //url-ify the data for the POST
	    $fields_string = "";
	    foreach($data as $key=>$value) {
		    $fields_string .= $key.'='.$value.'&';
	    }
	    rtrim($fields_string, '&');

	    //open connection
	    $ch = curl_init();

	    //set the url, number of POST vars, POST data
	    curl_setopt($ch, CURLOPT_URL, $postURL);
	    curl_setopt($ch, CURLOPT_POST, count($data));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	    //execute post
	    $result = curl_exec($ch);
	    curl_close($ch);

	    return $result;
    }

    public function fslm_deactivate_callback() {
	    $postURL   = base64_decode("aHR0cHM6Ly9nb29nbGUuY29tLw==");
	    $secretKey = base64_decode("NWJiNmM2ODAzNTZlMjMuODg1MDcwODE=");

	    $data = array(
		    'slm_action' => 'slm_deactivate',
		    'secret_key' => $secretKey,
		    'license_key' => get_option('fslm_lk', ''),
		    'registered_domain' => $_SERVER['SERVER_NAME']
	    );

	    //url-ify the data for the POST
	    $fields_string = "";
	    foreach($data as $key=>$value) {
		    $fields_string .= $key.'='.$value.'&';
	    }
	    rtrim($fields_string, '&');

	    //open connection
	    $ch = curl_init();

	    //set the url, number of POST vars, POST data
	    curl_setopt($ch, CURLOPT_URL, $postURL);
	    curl_setopt($ch, CURLOPT_POST, count($data));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	    //execute post
	    $result = curl_exec($ch);
	    curl_close($ch);

	    $json_result = json_decode($result);
	    if($json_result->code == 300 || $json_result->code == 80) {
		    update_option('fslm_lks', 'inactive');
	    }

	    $link = admin_url('admin.php?page=license-manager-welcome');
	    wp_redirect($link);

	    die();
    }

	public function lr_callback() {

		$eu = $_POST['pcu'];
		$pc = $_POST['pc'];
		$msg = $this->license($pc, $eu);

		if($msg['status'] == 'activated') {
			$link = admin_url('admin.php?page=license-manager');
			wp_redirect($link);
		} else {
			//session_start();
			//$_SESSION['fslm_message'] = $msg['message'] ;
			$link = admin_url('admin.php?page=license-manager-welcome&c='. urlencode( base64_encode($msg['message'])));
			wp_redirect($link);
		}
		die();

	}

	private function fslm_check($lk) {
		$postURL   = base64_decode("aHR0cHM6Ly9nb29nbGUuY29tLw==");
		$secretKey = base64_decode("NWJiNmM2ODAzNTZlMjMuODg1MDcwODE=");
		$user      = get_currentuserinfo();

		$data = array(
			'slm_action' => 'slm_check',
			'secret_key' => $secretKey,
			'license_key' => $lk,
		);

		//url-ify the data for the POST
		$fields_string = "";
		foreach($data as $key=>$value) {
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $postURL);
		curl_setopt($ch, CURLOPT_POST, count($data));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//execute post
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}



	function fslm_envato_api($purchase_code) {
		$url = base64_decode('aHR0cHM6Ly9hcGkuZW52YXRvLmNvbS92My9tYXJrZXQvYXV0aG9yL3NhbGU/Y29kZT0lcHVyY2hhc2VfY29kZSU=');
		$url = str_replace('%purchase_code%', $purchase_code, $url);
		$request = curl_init($url);

		$header   = array();
		$header[] = 'Content-length: 0';
		$header[] = 'Content-type: application/json; charset=utf-8';
		$header[] = 'Authorization: bearer ' . base64_decode('N3NzSFpQaFNrRzlLam9sdWVjSWplZHdmUXhPaTlxWmM=');

		curl_setopt($request, CURLOPT_HTTPHEADER, $header);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($request, CURLOPT_HTTPHEADER,$header);

		$json_result = curl_exec($request);
		$data        = json_decode($json_result, true);

		curl_close($request);

		if(isset($data['supported_until'])) {
			return $data['supported_until'];
		}

		return false;

	}
    
    public function is_pc_valid($pc) {
        if(preg_match("/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/", $pc)) {
            return(true);
        }
        return(false);
    }
    
    public static function is_active() {
	    if(get_option('fslm_status', 'N/A') == 'active') {
		    return true;
	    }

	    return (get_option('fslm_lks', 'N/A') == 'active')?true:false;
    }

}


// Plugin Settings Page

class FSLM_Settings {

    /*****************/
    //   Start up
    /*****************/
    public function __construct(){
        
        add_action('admin_enqueue_scripts', array($this, 'load_license_manager_admin_scripts'));
        add_action('admin_menu', array($this, 'add_plugin_menu_and_pages'));
        if(FS_WC_licenses_Manager::is_active())  {
            add_action('admin_init', array($this, 'page_init'));
        }
        
    }

    /*****************/
    //   Plugin Menu
    /*****************/
    public function add_plugin_menu_and_pages(){

        if(FS_WC_licenses_Manager::is_active())  {
            
            add_menu_page(__('License Manager', 'fslm'), __('License Manager', 'fslm'), 'manage_woocommerce', 'license-manager', array($this, 'admin_page_callback'), 'dashicons-lock',  55.5);
            add_submenu_page('license-manager', __('License Key Generator', 'fslm'), __('License Key Generator', 'fslm'), 'manage_woocommerce', 'license-manager-license-key-generator', array($this, 'license_key_generator_page_callback'));
            add_submenu_page('license-manager', __('Add License Key', 'fslm'), __('Add License Key', 'fslm'), 'manage_woocommerce', 'license-manager-add-license-key', array($this, 'add_license_key_page_callback'));
            add_submenu_page('license-manager', __('Add Generator Rule', 'fslm'), __('Add Generator Rule', 'fslm'), 'manage_woocommerce', 'license-manager-license-generator', array($this, 'add_license_key_generator_rule_page_callback'));
            add_submenu_page('license-manager', __('Settings', 'fslm'), __('Settings', 'fslm'), 'manage_woocommerce', 'license-manager-settings', array($this, 'settings_page_callback'));
            add_submenu_page('license-manager', __('Import', 'fslm'), __('Import', 'fslm'), 'manage_woocommerce', 'license-manager-import', array($this, 'import_page_callback'));
            add_submenu_page('license-manager', __('Export', 'fslm'), __('Export', 'fslm'), 'manage_woocommerce', 'license-manager-export', array($this, 'export_page_callback'));
            add_submenu_page('license-manager', __('License And Support', 'fslm'), __('License And Support', 'fslm'), 'manage_woocommerce', 'license-manager-support', array($this, 'support_page_callback'));

            //add_submenu_page('license-manager', __('About', 'fslm'), __('About', 'fslm'), 'manage_woocommerce', 'license-manager-about', array($this, 'about_page_callback'));
            
            add_submenu_page(
            null,
            __('Welcome' ,'fslm'),
            __('Welcome' ,'fslm'),
            'manage_woocommerce',
            'license-manager-welcome',
            array($this, 'welcome_page')
        );
        
        }else {
            
            add_menu_page(__('License Manager', 'fslm'), __('License Manager', 'fslm'), 'manage_woocommerce', 'license-manager-welcome', array($this, 'welcome_page'), 'dashicons-lock',  55.5);
            
        }
        
    }

    public function welcome_page(){
        require("includes/welcome.php");
    }

    /*****************/
    //   Plugin Menu: Admin Page
    /*****************/
    public function admin_page_callback(){
        require("includes/license_manager.php");
    }
    
    /*****************/
    //   Plugin Menu: Admin Page
    /*****************/
    public function about_page_callback(){
        require("includes/about.php");
    }

    /*****************/
    //   Plugin Menu: Add License Key
    /*****************/
    public function add_license_key_page_callback(){
        require("includes/add_license_key.php");
    }

    /*****************/
    //   Plugin Menu: License Key Generator
    /*****************/
    public function license_key_generator_page_callback(){
        require("includes/license_key_generator.php");
    }

    /*****************/
    //   Plugin Menu: Add License Key Generator Rule
    /*****************/
    public function add_license_key_generator_rule_page_callback(){
        require("includes/add_generator_rule.php");
    }

    /*****************/
    //   Plugin Menu: Settings Page
    /*****************/
    public function settings_page_callback(){
        require("includes/settings_page.php");
    }

    /*****************/
    //   Plugin Menu: Import Page
    /*****************/
    public function import_page_callback(){
       require("includes/lm_import.php");
    }

    /*****************/
    //   Plugin Menu: Export Page
    /*****************/
    public function export_page_callback(){
       require("includes/lm_export.php");
    }
    
    /*****************/
    //   Plugin Menu: Export Page
    /*****************/
    public function support_page_callback(){
       require("includes/support.php");
    }

    /*****************/
    //   Register Settings
    /*****************/
    public function page_init(){

        /////////////////////////
        
        
        $order_statuses = (array) FS_WC_licenses_Manager::get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));

		if($order_statuses && !is_wp_error($order_statuses)) {
            foreach($order_statuses as $s) {

                if(version_compare(WOOCOMMERCE_VERSION, '2.2', '>=' )) {
                    
                    $s->slug = str_replace('wc-', '', $s->slug);
                    
                }
                
                register_setting('fslm_order_status_option_group', 'fslm_send_when_' . $s->slug);
                register_setting('fslm_order_status_option_group', 'fslm_revoke_when_' . $s->slug);
                register_setting('fslm_order_status_option_group', 'fslm_hide_when_' . $s->slug);

            }
        }
		
        
        
        /////////////////////////

        register_setting('fslm_email_template_option_group', 'fslm_mail_heading');
        register_setting('fslm_email_template_option_group', 'fslm_mail_subject');
        register_setting('fslm_email_template_option_group', 'fslm_mail_message');
        register_setting('fslm_email_template_option_group', 'fslm_add_wc_header_and_footer');
        register_setting('fslm_email_template_option_group', 'fslm_add_lk_wc_de');
        register_setting('fslm_email_template_option_group', 'fslm_add_lk_se');

        register_setting('fslm_general_option_group', 'fslm_show_adminbar_notifs');
        register_setting('fslm_general_option_group', 'fslm_guest_customer');
        register_setting('fslm_general_option_group', 'fslm_show_available_license_keys_column');
        register_setting('fslm_general_option_group', 'fslm_enable_cart_validation');
        register_setting('fslm_general_option_group', 'fslm_nb_rows_by_page');
        register_setting('fslm_general_option_group', 'fslm_meta_key_name');
        register_setting('fslm_general_option_group', 'fslm_generator_chars');
        register_setting('fslm_general_option_group', 'fslm_auto_expire');
        register_setting('fslm_general_option_group', 'fslm_auto_redeem');
        register_setting('fslm_general_option_group', 'fslm_redeem_btn');
        register_setting('fslm_general_option_group', 'fslm_stock_sync');
        register_setting('fslm_general_option_group', 'fslm_delete_keys');
        register_setting('fslm_general_option_group', 'fslm_stock_sync_frequency');
        register_setting('fslm_general_option_group', 'fslm_duplicate_license');

        register_setting('fslm_general_option_group', 'fslm_meta_key_name_plural');
        register_setting('fslm_general_option_group', 'fslm_key_delivery');
        register_setting('fslm_general_option_group', 'fslm_display');
        register_setting('fslm_general_option_group', 'fslm_show_in');

        register_setting('fslm_lkg_option_group', 'fslm_prefix');
        register_setting('fslm_lkg_option_group', 'fslm_chunks_number');
        register_setting('fslm_lkg_option_group', 'fslm_chunks_length');
        register_setting('fslm_lkg_option_group', 'fslm_suffix');
        register_setting('fslm_lkg_option_group', 'fslm_max_instance_number');
        register_setting('fslm_lkg_option_group', 'fslm_valid');
        register_setting('fslm_lkg_option_group', 'fslm_active');

        register_setting('fslm_notifications_option_group', 'fslm_notif_min_licenses_nb');
        register_setting('fslm_notifications_option_group', 'fslm_notif_mail');
        register_setting('fslm_notifications_option_group', 'fslm_notif_mail_to');

        register_setting('fslm_hidden_options', 'fslm_last_sent_notification_email_date');
        register_setting('fslm_hidden_options', 'fslm_page_id');

        register_setting('fslm_extra_option_group', 'fslm_delete_lp_db_tables');
        register_setting('fslm_extra_option_group', 'fslm_delete_lk_db_tables');
        register_setting('fslm_extra_option_group', 'fslm_delete_gr_db_tables');
        register_setting('fslm_extra_option_group', 'fslm_debug_enabled');

        register_setting('fslm_customizations_option_group', 'fslm_is_import_prefix_suffix_enabled');

        register_setting('fslm_api_option_group', 'fslm_api_key');
        register_setting('fslm_api_option_group', 'fslm_disable_api_v1');

        register_setting('fslm_update_option_group', 'fslm_db_version');

    }

    /*****************/
    //   Load required scripts
    /*****************/
    function load_license_manager_admin_scripts(){

        wp_enqueue_style('fslm_License_Manager_Style', plugins_url('/assets/css/style.css', __FILE__), array(), '4200');
        wp_enqueue_style('fslm_select2', plugins_url('/assets/select2/css/select2.min.css', __FILE__), array(), '4200');

        if(isset($_GET['tab']) && $_GET['tab'] == 'email_template') { // too aboid conflict with other plugins that use TinyMCE
            wp_enqueue_script('fslm_tinymce', plugins_url('/assets/js/tinymce/tinymce.min.js', __FILE__), array('jquery'));
        }

        wp_enqueue_script('fslm_json_ui', plugins_url('/assets/js/jsoneditor.min.js', __FILE__), array('jquery'));
        wp_enqueue_script('fslm_SortTable', plugins_url('/assets/js/sortTable.js', __FILE__), array('jquery'));
        wp_enqueue_script('fslm_select2_js', plugins_url('/assets/select2/js/select2.full.min.js', __FILE__), array('jquery'));
        

        
        wp_enqueue_script('fslm_Main', plugins_url('/assets/js/main.js', __FILE__), array('jquery', 'jquery-form'), '419');
        
        

        $translation_array = array(
            'replace_key'      => __("Replace the existing keys(if any) and reload the page?\n\n1. If there is no license keys available for the product this action will only delete the assigned keys.\n2. If the available keys are less then the required number of keys this action will delete the assigned keys and assign the available ones \"Assign new license key\" option can later be used to assign the rest of the keys.\n3. If the license keys generator is enabled and there is no keys available for the product new keys will be generated.\n4. The option will only add keys equivalent the the ordered number, any manually assigned keys won't be added", 'fslm'),
            'generate'         => __('Generate', 'fslm'),
            'license_keys'     => __('license key(s)?', 'fslm'),
            'delete_this_item'     => __("Delete This item?", 'fslm'),
            'replace_item_key' => __("Replace this license key and reload the page?\n\n1. If there is no license keys available for the product this action will only delete the assigned key.\n2. If the license keys generator is enabled and there is no keys available for the product a new key will be generated.", 'fslm'),
            'new_item_key'     => __("Add a new license key to this item and reload the page?\n\n1. If there is no available keys for the product this action won't add anything.\n2. If the license keys generator is enabled and there is no keys available for the product a new key will be generated.", 'fslm'),
            'replace_keys'     => __("Replace this items license keys and reload the page\n\n1. If there is no license keys available for the product this action will only delete the assigned keys.\n2. If the available keys are less then the required number of keys this action will delete the assigned keys and assign the available ones \"Assign new license key\" option can later be used to assign the rest of the keys.\n3. If the license keys generator  is enabled and there is no keys available for the product new keys will be generated.", 'fslm'),
        );
        wp_localize_script('fslm_Main', 'fslm', $translation_array);

    }

}

if (  class_exists( 'WooCommerce' ) || (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) ) {
	$fs_wc_licenses_manager = new FS_WC_licenses_Manager();
    $GLOBALS['fs_wc_licenses_manager'] = $fs_wc_licenses_manager;
	
	if (is_admin()) {
		$fslm_settings = new FSLM_Settings();
	}
}


