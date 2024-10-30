<?php 
    global $wcpma,$wcpma_settings_data,$wcpma_payment_temp_method_data; 
    extract($wcpma_settings_data);
    
    $available_gateways = WC()->payment_gateways->payment_gateways;
    $user_roles = $wcpma->users->get_user_roles();
    $product_types = wc_get_product_types();

    $countries_obj   = new WC_Countries();
    $countries   = $countries_obj->__get('countries');
    

    $mode = 'add';
    if($_GET['page'] == 'wcpma-payment-settings' && isset($_GET['action']) && $_GET['action'] == 'edit'){
        $mode = 'edit';
    }

?>

<?php if(isset($wcpma->payments_manager->message) && $wcpma->payments_manager->message != '') { ?>
  <div id="setting-error-settings_updated" class="<?php echo $wcpma->payments_manager->msg_class; ?> settings-error notice is-dismissible"> 
      <p><strong><?php echo $wcpma->payments_manager->message; ?></strong></p>
  </div>
<?php } ?>

<br/>

<?php if(!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] == 'edit') ) { ?>
<a href="<?php echo admin_url( 'admin.php?page=wcpma-payment-settings&action=wcpma_view_list' ); ?>" class='button button-primary'><?php _e('View Conditional Payment Methods List','wcpma'); ?></a>

<form action="" method="POST"  >
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label ><?php echo __('Payment Method','wcpma'); ?></label></th>
                <td>
                	<select name="wcpma_payment_method"  id="wcpma_payment_method" class="wcpma_filter_select" >
                		<?php 
                			foreach ($available_gateways as $key => $available_gateway) {
                		?>
                			<option <?php echo selected($available_gateway->id,$wcpma_payment_temp_method_data['payment_method'] ); ?> value="<?php echo $available_gateway->id; ?>" ><?php echo $available_gateway->method_title; ?></option>
                		<?php
                			}
                		?>
                	</select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label ><?php echo __('User Type','wcpma'); ?></label></th>
                <td>
                	<select name="wcpma_payment_method_user_type" id="wcpma_payment_method_user_type" class="wcpma_filter_select" >
               			<option <?php echo selected('all',$wcpma_payment_temp_method_data['payment_method_user_type'] ); ?> value="all" ><?php echo __('Everyone','wcpma'); ?></option>
               			<option <?php echo selected('guest',$wcpma_payment_temp_method_data['payment_method_user_type'] ); ?> value="guest" ><?php echo __('Guests','wcpma'); ?></option>
               			<option <?php echo selected('members',$wcpma_payment_temp_method_data['payment_method_user_type'] ); ?> value="members" ><?php echo __('Members','wcpma'); ?></option>
               			
                	</select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label ><?php echo __('Product Types','wcpma'); ?></label></th>
                <td>
                	<select multiple name="wcpma_payment_method_product_types[]" id="wcpma_payment_method_product_types" class="wcpma_filter_select" >
               			<?php foreach ($product_types  as $key => $value) { 
                            $selected_product_types = '';
                            if(is_array($wcpma_payment_temp_method_data['payment_method_product_types']) && in_array($key, $wcpma_payment_temp_method_data['payment_method_product_types'])){
                              $selected_product_types = ' selected ';
                            }
                    ?>
               				<option <?php echo $selected_product_types; ?> value="<?php echo $key; ?>" ><?php echo $value; ?></option>
               			<?php	
               			}
               			?>
               			
                	</select>
                </td>
            </tr>
            
            
            <tr>
                <th scope="row"><label ><?php echo __('User Country','wcpma'); ?></label></th>
                <td>
                	<select multiple name="wcpma_payment_method_user_country[]" id="wcpma_payment_method_user_country" class="wcpma_filter_select" >
               			<option value="0" ><?php echo __('Please Select','wcpma'); ?></option>
                    <?php foreach ($countries as $key => $value) { 

                            $selected_user_country = '';
                            if(is_array($wcpma_payment_temp_method_data['payment_method_user_country']) && in_array($key, $wcpma_payment_temp_method_data['payment_method_user_country'])){
                              $selected_user_country = ' selected ';
                            }?>
               				<option <?php echo $selected_user_country; ?> value="<?php echo $key; ?>" ><?php echo $value; ?></option>
               			<?php	
               			}
               			?>
               			
                	</select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label ><?php echo __('Active Status','wcpma'); ?></label></th>
                <td>
                  <select name="wcpma_payment_method_active_status" id="wcpma_payment_method_active_status" class="wcpma_filter_select" >
                    <option <?php echo selected('active',$wcpma_payment_temp_method_data['payment_method_active_status'] ); ?> value="active" ><?php echo __('Active','wcpma'); ?></option>
                    <option <?php echo selected('inactive',$wcpma_payment_temp_method_data['payment_method_active_status'] ); ?> value="inactive" ><?php echo __('Inactive','wcpma'); ?></option>
                  </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label >&nbsp;</label></th>
                <?php if($mode == 'add'){ ?>
                    <td><input name="wcpma_conditional_payment_method_add_submit" type="submit" id="wcpma_conditional_payment_method_add_submit" value="<?php echo __('Add','wcpma'); ?>" class="button button-primary"></td>
            
                <?php }else { ?>
                    <td><input name="wcpma_conditional_payment_method_edit_id" type="hidden" id="wcpma_conditional_payment_method_edit_id" value="<?php echo $wcpma_conditional_payment_method_edit_id; ?>" class="regular-text">
                        <input name="wcpma_conditional_payment_method_edit_submit" type="submit" id="wcpma_conditional_payment_method_edit_submit" value="<?php echo __('Update','wcpma'); ?>" class="button button-primary"></td>
            
                <?php } ?>
            </tr>
        </tbody>
</form>

<?php }else if(isset($_GET) && $_GET['page'] == 'wcpma-payment-settings' && $_GET['action'] == 'wcpma_view_list' ){ ?> 
        <a href="<?php echo admin_url( 'admin.php?page=wcpma-payment-settings' ); ?>" class='button button-primary'><?php _e('Add Conditional Payment Method','wcpma'); ?></a>

      <?php wcpma_conditional_payment_methods_list_page();
      } 
?>