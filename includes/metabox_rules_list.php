<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('functions.php');

global $months;
global $status;

function fslm_metabox_rules_list($product_id) {

    $table = '<table id="licenses" class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
			    <th scope="col" class="manage-column sortable desc" data-sort="int"><span class="tips">' .   __('ID ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column sortable desc" data-sort="string"><span>' .   __('Variation ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column column-primary" data-sort="string"><span>' .  __('Prefix ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column column-primary" data-sort="int"><span>' .  __('Chunks Number ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column sortable desc" data-sort="int"><span>' .  __('Chunks Length ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column sortable desc" data-sort="string"><span>' .  __('Suffix ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" class="manage-column sortable desc" data-sort="int"><span>' .  __('Instance ', 'fslm') . '</span><span class="sorting-indicator"></span></th>
			    <th scope="col" id="valid" class="manage-column sortable desc" data-sort="int"><span>' .  __('Validity', 'fslm') . '</span><span class="sorting-indicator"></span></th>
                <th scope="col" class="manage-column" data-sort="string"><span>' .  __('Active ', 'fslm') . '</span></th>
		    </tr>
        </thead>
        <tbody id="list">';

                
    global $wpdb;


    $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_fs_product_licenses_keys_generator_rules WHERE product_id='" . $product_id . "'");

    if($query) {
        foreach ($query as $query) {

			$rule_id = $query->rule_id;

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



			$table .= '<tr id="post-' . $rule_id . '"><td>' . '<spen class="rhidden">' . __('ID', 'fslm') . ': </spen>' . $rule_id . '
                    <div class="row-actions fsactions">
                        <span class="inline"><a href="' . admin_url('admin.php') . '?page=license-manager&function=edit_rule&rule_id=' . $rule_id .'" class="editinline">' .  __('Edit', 'fslm') . '</a> | </span>
                        <span class="trash"><a href="' . admin_url('admin.php') . '?action=delete_rule&rule_id=' . $rule_id .'" class="submitdelete" >'.  __('Delete', 'fslm') .'</a></span>
                    </div>                    
                </td>
				<td>' . '<spen class="rhidden">' . __('Variation', 'fslm') . ': </spen>' . $variation_id . '</td>
				<td>' . '<spen class="rhidden">' . __('Prefix', 'fslm') . ': </spen>' . $prefix . '</td>
				<td>' . '<spen class="rhidden">' . __('Chunks', 'fslm') . ': </spen>' . $chunks_number . '</td>
				<td>' . '<spen class="rhidden">' . __('Chunk Length', 'fslm') . ': </spen>' . $chunks_length . '</td>
				<td>' . '<spen class="rhidden">' . __('Suffix', 'fslm') . ': </spen>' . $suffix . '</td>
				<td>' . '<spen class="rhidden">' . __('Activations', 'fslm') . ': </spen>' . $max_instance_number . '</td>
				<td>' . '<spen class="rhidden">' . __('Validity', 'fslm') . ': </spen>' . $valid . '</td>
				<td>' . '<spen class="rhidden">' . __('Status', 'fslm') . ': </spen>' . ($active=='1'? __('Yes', 'fslm'): __('No', 'fslm')) . '</td>
			</tr>';

        } 

    }else {

        $table .= '<tr>
            <td colspan="9" class="center">' .  __('There is no automatic license Key generator rules in the database', 'fslm') . '</td> 
        </tr>';


    }

    $table .= '</tbody></table>';
    
    return $table;

}

?>