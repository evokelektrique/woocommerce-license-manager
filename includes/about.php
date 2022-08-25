
<div class="wrap fslm">

    <h1><?php echo __('About', 'fslm'); ?></h1>

    <div class="postbox">
        
        <h2><?php _e('This copy of FS License Manager is licensed to', 'fslm') ?></h2>

        <table id="licenses" class="wp-list-table widefat fixed striped posts">

            <tr>
                <td><strong><?php _e('Envato Username') ?></strong></td>
                <td><?php echo $buyer ?></td>
            </tr>
               
            <tr>
                <td><strong><?php _e('License Type') ?></strong></td>
                <td><?php echo $licence ?></td>
            </tr>
            
            <tr>
                <td><strong><?php _e('Envato Item Name') ?></strong></td>
                <td><?php echo $item_name ?></td>
            </tr>
            
            <tr>
                <td><strong><?php _e('Envato Item ID') ?></strong></td>
                <td><?php echo $item_id ?></td>
            </tr>
            
            <tr>
                <td><strong><?php _e('Purchsae Date') ?></strong></td>
                <td><?php echo $created_at ?></td>
            </tr>
            
            <tr>
                <td><strong><?php _e('Supported Until') ?></strong></td>
                <td><?php echo $supported_until ?></td>
            </tr>
               
            <tr>
                <td><strong><?php _e('Envato Purchase Code') ?></strong></td>
                <td><?php echo $purchase_code ?></td>
            </tr>
            
            <tr>
                <td><strong><?php _e('License Status') ?></strong></td>
                <td style="text-transform: capitalize"><?php echo $status ?></td>
            </tr>

        </table>
        
    </div>
          
</div>
           

           