<?php

if ( class_exists( 'FS_WC_licenses_Manager' ) && ! class_exists( 'FS_WC_licenses_Manager_SMS' ) ) :
	class FS_WC_licenses_Manager_SMS extends FS_WC_licenses_Manager {

		public $shortcode = '{licenses}';

		public function __construct() {
			add_filter( 'persian_woo_sms_shortcode_list', array( $this, 'add_shortcode' ), 99, 1 );
			add_filter( 'persian_woo_sms_content', array( $this, 'replace_shortcode' ), 99, 4 );
		}

		public function add_shortcode( $shortcodes ) {
			$shortcodes .= "
				<strong>افزونه مدیریت لایسنس : </strong><br/>
				<code>{$this->shortcode}</code> = لایسنس های خریداری شده<br/>
				طراحی افزونه فروش لایسنس و شارژ توسط woocommerce.ir<br/>
			";

			return $shortcodes;
		}


		public function replace_shortcode( $content, $order_id, $order, $product_ids ) {

			$meta = get_post_meta( $order_id, 'fslm_json_license_details', true );

			$replace           = '';
			$visible_key_found = false;

			if ( ! empty( $meta ) ) {

				$values = json_decode( str_replace( "\\", "", $meta ), true );

				if ( ! empty( $values ) && is_array( $values ) ) {

					foreach ( $values as $value ) {

						$meta = '';

						if ( ! empty( $value['visible'] ) && strtolower( $value['visible'] ) == 'yes' ) {

							$visible_key_found = true;

							$display = get_post_meta( (int) $value['product_id'], 'fslm_display', true );

							if ( $display != '1' ) {
								$license_key = $this->encrypt_decrypt( 'decrypt', $value['license_key'], ENCRYPTION_KEY, ENCRYPTION_VI );
								$meta        = $license_key;
							}

							if ( $value['max_instance_number'] > 0 ) {
								$meta .= ' - (' . __( 'Can be used', 'pwlm' ) . ' : ' . $value['max_instance_number'] . ' ' . __( 'time(s)', 'pwlm' ) . ')';
							}

							if ( ( $value['expiration_date'] != '0000-00-00' ) && ( $value['expiration_date'] != '' ) ) {
								$meta .= ' - (' . __( '<strong>Expires</strong> ', 'pwlm' ) . ' : ' . ')';
							}
							$meta = strip_tags( $meta );
							
							$image_license_key = '';
							if ( $display != '0' ) {
								$image_license_key = $this->get_image_name( $value['license_id'] );
								if ( $image_license_key != '' ) {
									$upload_directory  = wp_upload_dir();
									$image_license_key = $upload_directory['baseurl'] . '/fslm_keys/' . $image_license_key;
								}
							}

							$replace .= get_the_title( $value['product_id'] ) . ':' . PHP_EOL . $meta . $image_license_key . PHP_EOL;
						}
					}
				}
			}


			if ( $visible_key_found == false ) {
				$replace = '';
			}

			return str_ireplace( $this->shortcode, $replace, $content );
		}


	}
endif;

new FS_WC_licenses_Manager_SMS();