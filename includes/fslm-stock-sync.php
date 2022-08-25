<?php

defined('ABSPATH')or  die('No script kiddies please!');

class StockSyncBackgroundProcess extends WP_Async_Request {
	
	protected $action = 'fslm_stock_sync';
	
	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		
		//error_log("CR");
		
		if (get_option('fslm_stock_sync', '') == 'on') {
			global $fs_wc_licenses_manager;
			
			$fs_wc_licenses_manager->sync_stock();
		}
	
		return false;
	}
	
}