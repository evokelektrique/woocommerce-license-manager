jQuery(function () {
    "use strict";
    
    jQuery(document).ready(function (jQuery){
  
        var date_from_string = function (str) {

            if(str === 'none' || str ===  'Doesn\'t Expire' || str === '') {
                return new Date(0, 0, 0);
            }

            var months = ["jan", "feb", "mar", "apr", "may", "jun", "jul",
                          "aug", "sep", "oct", "nov", "dec"];
            var pattern = "^([a-zA-Z]{3})\\s*(\\d{2}),\\s*(\\d{4})$";
            var re = new RegExp(pattern);
            var DateParts = re.exec(str).slice(1);

            var Year = DateParts[2];
            var Month = jQuery.inArray(DateParts[0].toLowerCase(), months);
            var Day = DateParts[1];

            return new Date(Year, Month, Day);
        };

        jQuery("#licenses").sorttable({
            "date": function (a, b) {
                // Get these into date objects for comparison.

                var aDate = date_from_string(a);
                var bDate = date_from_string(b);

                return aDate - bDate;
            }
        });

        jQuery("#rules").sorttable({
            "date": function (a, b) {
                // Get these into date objects for comparison.

                var aDate = date_from_string(a);
                var bDate = date_from_string(b);

                return aDate - bDate;
            }
        });

        jQuery('#fslm-wc-licenses .hndle').append(jQuery('#fslm-metabox-actions-content').html());

        jQuery('#fslm-metabox-actions-content').html('');

        jQuery("#fslm_reload").on("click", function(e) {
            e.stopPropagation();
            e.preventDefault();

            var data = {
                'action': 'fslm_reload_mb',
                'product_id': jQuery("#mbs_product_id").val()
            };

            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data,
                success : function(response) {
                    jQuery("#fslm-wc-licenses .inside").html(response);
                }
            });

            return false;

        });

        jQuery("body").on("click", "#fslm_tabs li a", function(e) {
            e.preventDefault();

            jQuery('.fslm .the-tab-content').hide();

            jQuery("#fslm_tabs li a").removeClass('active');
            jQuery(this).addClass('active');

            jQuery('#' + jQuery(this).data('tab')).show();

            if(jQuery(this).data('savebtn') === 0) {
                jQuery('.fslm .fslm-save').hide();
            }else {
                jQuery('.fslm .fslm-save').show();
            }

        });



        try {

            if(jQuery('.fslm_mail_message').length) {
                tinymce.init({
                    selector: '.fslm_mail_message',
                    height: 500,
                    theme: 'modern',
                    plugins: [
                        'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                        'searchreplace wordcount visualblocks visualchars code fullscreen',
                        'insertdatetime media nonbreaking save table contextmenu directionality',
                        'emoticons template paste textcolor colorpicker textpattern imagetools codesample toc'
                    ],
                    toolbar1: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
                    toolbar2: 'print preview media | forecolor backcolor emoticons | codesample',
                    image_advtab: true,
                    content_css: [
                        '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
                        '//www.tinymce.com/css/codepen.min.css'
                    ],
					relative_urls: false,
					convert_urls: false,
					remove_script_host : false,
                });
            }
        }catch (e){

        }

        try {

            jQuery('body').on('click', '#fslm_json_ui_close', function (e) {
                jQuery("#fslm_json_ui").hide();
                jQuery('html').css({
                    "overflow-y": "scroll"
                });
            });

            var meta_key = jQuery("input[value='fslm_json_license_details']").attr('ID');
            var meta_value = meta_key.replace('key', 'value');

            var tmp = JSON.parse(jQuery("#" + meta_value).val());
            var count = ObjectLength(tmp);

            jQuery('body').append('<div id="fslm_json_ui"><div id="fslm_json_ui_close">&#10006;</div><div id="jsoncontainer"></div></div>');
            var element = document.getElementById('jsoncontainer');
            var schema = {
                "title": "License Details",
                "type": "object",
                "properties": schemaRepeater(count)
            };

            var editor = new JSONEditor(element, {
                schema: schema,
                no_additional_properties: true,
                required_by_default: true,
                iconlib: "fontawesome4"
            });

            jQuery("#fslm_edit_alk").on('click', function (e) {
                jQuery('html').css({
                    "overflow-y": "hidden"
                });

                jQuery("#fslm_json_ui").show();

                editor.setValue(JSON.parse(jQuery("#" + meta_value).val()));

                editor.on('change',function(){
                    var json = editor.getValue();

                    jQuery("#" + meta_value).val(JSON.stringify(json));
                });
            });
        }catch (e){

        }

        jQuery("body").on("click", "#add-license-key", function(e) {
            e.preventDefault();

            var data = new FormData();

            data.append('action', 'fslm_add_license_ajax');
            data.append('alk_product_id', jQuery("#mbs_product_id").val());
            data.append('alk_variation_id', jQuery("#alk_variation_id").val());
            data.append('alk_license_key', jQuery("#alk_license_key").val());
            data.append('alk_display', jQuery("#alk_display").val());
            data.append('alk_show_in', jQuery("#alk_show_in").val());
            data.append('alk_deliver_x_times', jQuery("#alk_deliver_x_times").val());
            data.append('alk_max_instance_number', jQuery("#alk_max_instance_number").val());
            data.append('alk_xpdday', jQuery("#alk_xpdday").val());
            data.append('alk_xpdmonth', jQuery("#alk_xpdmonth").val());
            data.append('alk_xpdyear', jQuery("#alk_xpdyear").val());
            data.append('alk_valid', jQuery("#alk_valid").val());

            data.append('alk_image_license_key', jQuery("#alk_image_license_key")[0].files[0]);

            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                cache : false,
                contentType : false,
                processType : false,
                processData: false,
                data: data,
                success : function(response) {
                    jQuery("#mb_licenses_list").html(response);
                    jQuery("#alk_license_key").val('');
                }
            });

            return false;
        });

        function ObjectLength(a) {
            var count = 0;
            var i;

            for (i in a) {
                if (a.hasOwnProperty(i)) {
                    count++;
                }
            }

            return count;
        }

        function schemaRepeater(nb) {
            var output = '{'
            for(var i=0; i<nb; i++) {
                output = output + '"' + i + '":{"type":"object","title":"License #' + i + '","properties":{"license_id": { "type": "integer", "description": "License ID", "minLength": 1, "default": "40" }, "item_id": { "type": "integer", "description": "Item ID", "minLength": 1, "default": "72" }, "product_id": { "type": "integer", "description": "Product ID", "minLength": 1, "default": "9" }, "variation_id": {"type": "integer", "description": "Variation ID", "minLength": 1, "default": "" }, "license_key": { "type": "string", "description": "License Key", "minLength": 1, "default": "" }, "max_instance_number": { "type": "integer", "description": "Maximum Instances Number", "minLength": 1, "default": "0" }, "visible": { "type": "string", "description": "Visible", "minLength": 1, "enum": ["Yes","No"], "default": "Yes" }, "expiration_date": {"type": "string", "description": "Expiration Date", "minLength": 1, "default": "0000-00-00" }}}';
                if(i<(nb-1)) output = output + ',';
            }

            output = output + '}';
            return JSON.parse(output);
        }
        
        jQuery("#fslm_resend").on("click", function(e) {
            e.preventDefault();

            var data = {
                'action': 'fslm_resend',
                'fslm_resend_order_id': jQuery("#fslm_resend_order_id").val()
            };

            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data,
                success : function(response) {
                    jQuery("#fslm_resend_respons").html(response);
                    jQuery("#fslm_resend_respons").show();
                    setTimeout(function() {
                        jQuery("#fslm_resend_respons").hide();
                        jQuery("#fslm_resend_respons").html('');
                    }, 5000);
                }
            });

            return false;
        });

        jQuery("body").on("click", "#mb-save", function(e) {
            e.preventDefault();
            
            jQuery("#mbs_save_response").hide();
            
            var data = {
                'action': 'fslm_save_metabox',
                'mbs_product_id': jQuery("#mbs_product_id").val(),
                'mbs_variation_id': jQuery("#mbs_variation_id").val(),
                'mbs_licensable': jQuery("#mbs_licensable").is(':checked')?'1':'0',
                'fslm_nb_delivered_lk': jQuery("#fslm_nb_delivered_lk").val(),
                'mbs_active': jQuery("#mbs_active").is(':checked').toString(),
                'mbs_prefix': jQuery("#mbs_prefix").val(),
                'mbs_chunks_number': jQuery("#mbs_chunks_number").val(),
                'mbs_chunks_length': jQuery("#mbs_chunks_length").val(),
                'mbs_suffix': jQuery("#mbs_suffix").val(),
                'mbs_max_instance_number': jQuery("#mbs_max_instance_number").val(),
                'mbs_valid': jQuery("#mbs_valid").val(),

                'fslm_display': jQuery("#fslm_display").val(),
                'fslm_show_in': jQuery("#fslm_show_in").val(),

                // import prefixes/suffixes
                'fslm_import_prefix': jQuery("#fslm_import_prefix").val(),
                'fslm_import_suffix': jQuery("#fslm_import_suffix").val(),

                'fslm_sn': jQuery("#fslm_sn").val(),
                'fslm_sid': jQuery("#fslm_sid").val(),
                'fslm_sv': jQuery("#fslm_sv").val(),
                'fslm_sa': jQuery("#fslm_sa").val(),
                'fslm_surl': jQuery("#fslm_surl").val(),
                'fslm_slu': jQuery("#fslm_slu").val(),
                'fslm_sed': jQuery("#fslm_sed").val()
            };

            jQuery(".mbs_licensable_variations").each(function () {
                var name  = jQuery(this).attr("name");
                var value = jQuery(this).is(':checked')?'1':'0';

                data[name] = value;
            });

            // import prefixes/suffixes
            jQuery(".fslm_import_prefix_variations").each(function () {
                var name  = jQuery(this).attr("name");
                var value = jQuery(this).val();

                data[name] = value;
            });

            jQuery(".fslm_import_suffix_variations").each(function () {
                var name  = jQuery(this).attr("name");
                var value = jQuery(this).val();

                data[name] = value;
            });
            
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data,
                success : function(response) {
                    jQuery("#mbs_save_response").html(response);
                    jQuery("#mbs_save_response").show();

                    var data = {
                        'action': 'fslm_generator_rules',
                        'mbs_product_id': jQuery("#mbs_product_id").val()
                    };

                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        success: function (response) {
                            jQuery("#mb_generator_rules").html(response);
                        }
                    });
                }
            });

            return false;
        });

        jQuery(document).on("click", '.fslm_cpy_encrypted_key', function (e) {
            e.preventDefault();

            copyToClipboard(jQuery(this).data("ek"));
        });
		
		jQuery('#fslm_filter').on('click', function(e) {
			
			e.preventDefault();
			
			var data = {
                'action'     : 'fslm_filter',
				'license_key': jQuery("#filter-license_key").val(),
				'product': jQuery("#filter-product").val(),
				'variation': jQuery("#filter-variation").val(),
				'name': jQuery("#filter-name").val(),
				'mail': jQuery("#filter-mail").val(),
				'status': jQuery("#filter-status").val(),
				'html_ml': jQuery("#filter-html_ml").is(':checked')?'1':'0'
			}
			
			
			jQuery.ajax({
				
				type    : "POST",
				url     : ajaxurl,
				data    : data,
				success : function(response) {
					jQuery("#the-list").html(response);
					jQuery(".fslm .fslm-filter-count").text(jQuery(".fslm .filter-result-item").length);
					jQuery(".fslm .fslm-filter-count-container").show();
				}
				
			});
			
		});
		
		jQuery('#fslm_clear').on('click', function(e) {
			
			e.preventDefault();

			var data = {
                'action'     : 'fslm_filter',
				'license_key': '',
				'product': '-1',
				'name': '',
				'mail': '',
				'status': '-1',
				'html_ml': '0'
			}
			
			
			jQuery.ajax({
				
				type    : "POST",
				url     : ajaxurl,
				data    : data,
				success : function(response) {
					jQuery("#the-list").html(response);
				}
				
			});

            jQuery(".fslm .fslm-filter-count-container").hide();
			
		});
		
		
		jQuery("#filter-product").select2();
		jQuery("#filter-variation").select2();
		jQuery(".elk_product_id").select2();
		jQuery(".egr_product_id").select2();
		jQuery("#product_id_select").select2();
		jQuery("#variation_id_select").select2();
		jQuery("#product_id_select_2").select2();
		jQuery("#variation_id_select_2").select2();


        function copyToClipboard(text) {
            window.prompt(fslm_cek, text);
        }



        // ---------------------------
        jQuery("#fslm_replace_key").on("click", function(e) {
            e.preventDefault();

            var r = confirm(fslm.replace_key);

            if (r == true) {

                var data = {
                    'action': 'fslm_replace_key',
                    'fslm_resend_order_id': jQuery("#fslm_resend_order_id").val()
                };

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success : function(response) {
                        jQuery("#fslm_resend_respons").html(response);
                        jQuery("#fslm_resend_respons").show();
                        setTimeout(function() {
                            jQuery("#fslm_resend_respons").hide();
                            jQuery("#fslm_resend_respons").html('');
                        }, 5000);
                        location.reload();
                    }
                });

            }

        });


        jQuery(".generate-btn").on("click", function(e) {
            e.preventDefault();

            var rule_id = jQuery(this).data('id');
            var quantity = jQuery("#fslm-q-" + rule_id).val();

            var r = confirm(fslm.generate + " " + quantity + " " + fslm.license_keys);
            if (r == true) {

                var data = {
                    'action': 'fslm_bulk_generate',
                    'fslm_rule_id': rule_id,
                    'fslm_quantity': quantity
                };

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success : function(response) {
                        alert(response);
                    }
                });

            }

        });




        //-----------------------------------

        jQuery('#fslm-bulk-image-import-btn').on('click', function (e) {
            var percent = jQuery('#fslm-percent');
            jQuery('#fslm-bulk-image-import').ajaxForm({
                beforeSubmit: function() {
                    jQuery('.fslm-percent').show();
                    var percentVal = '0%';
                    percent.html(percentVal);
                },

                uploadProgress: function(event, position, total, percentComplete) {
                    var percentVal = percentComplete + '%';
                    percent.html(percentVal);
                },

                success: function() {
                    var percentVal = '100%';
                    percent.html(percentVal);
                }
            });

        });


        jQuery('.fslm-replace-item-keys').on('click', function (e) {
            e.preventDefault();
            var itemID = jQuery(this).data('itemid');
            var orderID = jQuery("#fslm_resend_order_id").val();

            var r = confirm(fslm.replace_keys);
            if (r == true) {

                var data = {
                    'action': 'fslm_replace_item_keys',
                    'fslm_item_id': itemID,
                    'fslm_order_id': orderID
                };

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success : function(response) {
                        location.reload();
                    }
                });

            }

        });

        jQuery('.fslm-new-item-key').on('click', function (e) {
            e.preventDefault();
            var itemID = jQuery(this).data('itemid');
            var orderID = jQuery("#fslm_resend_order_id").val();

            var r = confirm(fslm.new_item_key);
            if (r == true) {

                var data = {
                    'action': 'fslm_new_item_key',
                    'fslm_item_id': itemID,
                    'fslm_order_id': orderID
                };

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success : function(response) {
                        location.reload();
                    }
                });

            }

        });

        jQuery('.fslm-replace-item-key').on('click', function (e) {
            e.preventDefault();
            var key = jQuery(this).data('key');
            var orderID = jQuery("#fslm_resend_order_id").val();

            var r = confirm(fslm.replace_item_key);
            if (r == true) {

                var data = {
                    'action': 'fslm_replace_item_key',
                    'fslm_order_id': orderID,
                    'fslm_key': key
                };

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success : function(response) {
                        location.reload();
                    }
                });

            }

        });

    });


    jQuery("body").on("click", '.fslm .fslm-mb-delete-btn', function (e) {
        e.preventDefault();

        var r = confirm(fslm.delete_this_item);
        if (r == true) {

            jQuery.ajax({
                url: jQuery(this).attr('href'),
                type: 'GET',
            });

            jQuery(this).parent().closest('tr').hide();
        }

    });


    jQuery('.fslm .submitdelete').on('click', function (e) {
        e.preventDefault();

        var r = confirm(fslm.delete_this_item);
        if (r == true) {
            window.location.href = jQuery(this).attr('href');
        }

    });


});