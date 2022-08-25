<?php


if (!defined('ABSPATH')) {
    exit;
}

function fslm_install_000() {
    global $wpdb;

    if((int)get_option('fslm_db_version', '0') == 0) {
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

        if(!add_option('fslm_db_version', '100')){
            update_option('fslm_db_version', '100');
        }

    }
}

function fslm_update_200_db_column() {
    global $wpdb;

    if((int)get_option('fslm_db_version', '100') < 200) {

        $table_name = $wpdb->prefix . 'wc_fs_product_licenses_keys';
        if ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && !$wpdb->get_var( "SHOW COLUMNS FROM `{$table_name}` LIKE 'delivre_x_times';" ) ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys ADD delivre_x_times INT NULL DEFAULT 1");
        }

        if ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && !$wpdb->get_var( "SHOW COLUMNS FROM `{$table_name}` LIKE 'remaining_delivre_x_times';" ) ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys ADD remaining_delivre_x_times INT NULL DEFAULT 1");
        }

        if(!add_option('fslm_db_version', '200')){
            update_option('fslm_db_version', '200');
        }
    }
}

function fslm_update_300_db_column() {
    global $wpdb;
    
    if((int)get_option('fslm_db_version', '100') < 300) {
        $wpdb->query('UPDATE ' . $wpdb->prefix . 'wc_fs_product_licenses_keys SET expiration_date = null WHERE expiration_date="0000-00-00"');

        $table_name = $wpdb->prefix . 'wc_fs_product_licenses_keys';
        if ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && !$wpdb->get_var( "SHOW COLUMNS FROM `{$table_name}` LIKE 'order_id';" ) ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys ADD order_id INT NULL DEFAULT 0");
        }

        $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys
          CHANGE license_key license_key TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          CHANGE image_license_key image_license_key TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          CHANGE license_status license_status TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          CHANGE owner_first_name owner_first_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          CHANGE owner_last_name owner_last_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          CHANGE owner_email_address owner_email_address TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;"
        );

	    if(!add_option('fslm_db_version', '300')){
		    update_option('fslm_db_version', '300');
	    }
    } 
}

function fslm_update_330_db_column() {
    global $wpdb;

    if((int)get_option('fslm_db_version', '300') < 330) {

        $table_name = $wpdb->prefix . 'wc_fs_product_licenses_keys';
        if ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && !$wpdb->get_var( "SHOW COLUMNS FROM `{$table_name}` LIKE 'sold_date';" ) ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys ADD sold_date date NULL");
        }

        if(!add_option('fslm_db_version', '330')){
            update_option('fslm_db_version', '330');
        }
    }
}

function fslm_update_410_db_column() {
    global $wpdb;

    if((int)get_option('fslm_db_version', '330') < 410) {

        $table_name = $wpdb->prefix . 'wc_fs_product_licenses_keys';
        if ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && !$wpdb->get_var( "SHOW COLUMNS FROM `{$table_name}` LIKE 'device_id';" ) ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys ADD device_id TEXT(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL");
        }

        if(!add_option('fslm_db_version', '410')){
            update_option('fslm_db_version', '410');
        }
    }
}

function fslm_update_411_db_column() {
    global $wpdb;

    if((int)get_option('fslm_db_version', '410') < 411) {

        $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_fs_product_licenses_keys
          MODIFY order_id INT NULL DEFAULT 0;"
        );

        if(!add_option('fslm_db_version', '411')){
            update_option('fslm_db_version', '411');
        }
    }
}

fslm_install_000();
fslm_update_200_db_column();
fslm_update_300_db_column();
fslm_update_330_db_column();
fslm_update_410_db_column();
fslm_update_411_db_column();

