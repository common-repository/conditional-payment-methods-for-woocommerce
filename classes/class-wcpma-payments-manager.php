<?php

class WCPMA_Payments_Manager{
    
    public function __construct(){   
    	add_action('wp_ajax_wcpma_load_payment_method_products', array($this, 'load_payment_method_products'));
    	add_action('init', array($this, 'init'));
    	add_action('init', array($this, 'save_conditional_payment_methods'));

    	add_filter('woocommerce_available_payment_gateways', array($this, 'display_conditional_payment_gateways'));

    }

    public function init(){
    	global $wpdb;
        $this->conditional_payment_table = $wpdb->prefix . 'wsmea_conditional_payment_methods';
    }

    

    public function save_conditional_payment_methods($mode){
    	global $wpdb,$wcpma_payment_temp_method_data;

    	$mode = 'add';
        $edit_payment_method_id = 0;
        if($_GET['page'] == 'wcpma-payment-settings' && isset($_GET['action']) && $_GET['action'] == 'edit'){
            $mode = 'edit';
            $payment_method_id = isset($_GET['payment_method_id']) ? (int) $_GET['payment_method_id'] : 0;


            $sql_total  = $wpdb->prepare( "SELECT * FROM $this->conditional_payment_table WHERE id = %d ", $payment_method_id );
            $result = $wpdb->get_results($sql_total);
            
            $wcpma_payment_method_data['wcpma_edit_payment_id'] = 0;
            if($result){
                $wcpma_payment_method_data['wcpma_edit_payment_id'] 		= $payment_method_id;
                $wcpma_payment_temp_method_data['payment_method'] 				= $result[0]->payment_method;
	            $wcpma_payment_temp_method_data['payment_method_user_type'] 		= $result[0]->user_type;
	            $wcpma_payment_temp_method_data['payment_method_user_roles'] 	= json_decode($result[0]->user_roles);
	    		$wcpma_payment_temp_method_data['payment_method_product_types'] 	= json_decode($result[0]->product_types);
	            $wcpma_payment_temp_method_data['payment_method_min_order_total'] 	= 0;
                $wcpma_payment_temp_method_data['payment_method_max_order_total']   = 0;
	            $wcpma_payment_temp_method_data['payment_method_products'] 		= json_decode($result[0]->products);
	            $wcpma_payment_temp_method_data['payment_method_user_country'] 	= json_decode($result[0]->user_country);
	            $wcpma_payment_temp_method_data['payment_method_active_status'] 	= $result[0]->payment_method_status;

            }
        }

        if(isset($_POST['wcpma_conditional_payment_method_add_submit']) ||
            isset($_POST['wcpma_conditional_payment_method_edit_submit'])){

            $wcpma_payment_method_data['wcpma_payment_method'] 					= isset($_POST['wcpma_payment_method']) ? $_POST['wcpma_payment_method'] : '';
            $wcpma_payment_method_data['wcpma_payment_method_user_type'] 		= isset($_POST['wcpma_payment_method_user_type']) ? $_POST['wcpma_payment_method_user_type'] : 'all';
            $wcpma_payment_method_data['wcpma_payment_method_user_roles'] 		= array();
    		$wcpma_payment_method_data['wcpma_payment_method_product_types'] 	= isset($_POST['wcpma_payment_method_product_types']) ? $_POST['wcpma_payment_method_product_types'] : array();
            $wcpma_payment_method_data['wcpma_payment_method_min_order_total'] 	= 0;
            $wcpma_payment_method_data['wcpma_payment_method_max_order_total']  = 0;
            $wcpma_payment_method_data['wcpma_payment_method_products'] 		= array();
            $wcpma_payment_method_data['wcpma_payment_method_user_country'] 	= isset($_POST['wcpma_payment_method_user_country']) ? $_POST['wcpma_payment_method_user_country'] : array();
            $wcpma_payment_method_data['wcpma_payment_method_active_status'] 	= isset($_POST['wcpma_payment_method_active_status']) ? $_POST['wcpma_payment_method_active_status'] : 'active';

    
            $message = '';
            if($message != ''){
                $msg_class = ' error ';
            }else{
                if(isset($_POST['wcpma_conditional_payment_method_add_submit'])){
                    if($this->add_conditional_payment_method($wcpma_payment_method_data)){
                        $this->message = __('Conditional payment method saved successfully.','wcpma');
                        $this->msg_class = ' upated ';
                        wp_redirect(admin_url( 'admin.php?page=wcpma-payment-settings&action=wcpma_view_list' ));
                        exit;
                    }else{
                        if(isset($this->msg_type) && 'already_exist' == $this->msg_type){
                            $this->message = __('Conditional payment already exist for the payment method.','wcpma');
                        }else{
                            $this->message = __('Conditional payment method save failed.','wcpma');
                        }
                        $this->msg_class = ' error ';
                    }
                }

                if(isset($_POST['wcpma_conditional_payment_method_edit_submit'])){
                    if($this->edit_conditional_payment_method($wcpma_payment_method_data)){
                        $this->message = __('Conditional payment method updated successfully.','wcpma');
                        $this->msg_class = ' updated ';
                        wp_redirect(admin_url( 'admin.php?page=wcpma-payment-settings&action=wcpma_view_list' ));
                    }else{

                        if(isset($this->msg_type) && 'already_exist' == $this->msg_type){
                            $this->message = __('Conditional payment already exist for the payment method.','wcpma');
                        }else{
                            $this->message = __('Conditional payment method update failed.','wcpma');
                        }
                        
                        $this->msg_class = ' error ';
                    }
                }
            }
        }
    }

    public function add_conditional_payment_method($wcpma_payment_method_data){
        global $wpdb;
        extract($wcpma_payment_method_data);

        $sql_payment_method_conditions  = $wpdb->prepare( "SELECT * FROM $this->conditional_payment_table WHERE payment_method = '%s'
            and payment_method_status = 'active' ", $wcpma_payment_method );
        $result = $wpdb->get_results($sql_payment_method_conditions);
        if($result){
            $this->msg_type = 'already_exist';
            return false;
        }

        $status = $wpdb->insert( 
                    $this->conditional_payment_table, 
                    array( 
                        'payment_method'  			=> $wcpma_payment_method, 
                        'user_type' 				=> $wcpma_payment_method_user_type,
                        'user_roles' 				=> json_encode($wcpma_payment_method_user_roles),
                        'product_types' 			=> json_encode($wcpma_payment_method_product_types),
                        'min_order_total' 			=> $wcpma_payment_method_min_order_total,
                        'max_order_total'           => $wcpma_payment_method_max_order_total,
                        'products' 					=> json_encode($wcpma_payment_method_products),
                        'user_country' 				=> json_encode($wcpma_payment_method_user_country),
                        'payment_method_status' 	=> $wcpma_payment_method_active_status
                    ), 
                    array( 
                        '%s', 
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s'
                    ) ); 

        return $status;
    }

    public function edit_conditional_payment_method($wcpma_payment_method_data){
        global $wpdb;
        extract($wcpma_payment_method_data);

        $status = $wpdb->update( 
                            $this->conditional_payment_table, 
                            array( 
                                'payment_method'  			=> $wcpma_payment_method, 
		                        'user_type' 				=> $wcpma_payment_method_user_type,
		                        'user_roles' 				=> json_encode($wcpma_payment_method_user_roles),
		                        'product_types' 			=> json_encode($wcpma_payment_method_product_types),
		                        'min_order_total' 			=> $wcpma_payment_method_min_order_total,
                                'max_order_total'           => $wcpma_payment_method_max_order_total,
		                        'products' 					=> json_encode($wcpma_payment_method_products),
		                        'user_country' 				=> json_encode($wcpma_payment_method_user_country),
		                        'payment_method_status' 	=> $wcpma_payment_method_active_status
                            ), 
                            array( 'id' => $wcpma_edit_payment_id ), 
                            array( 
                                '%s', 
		                        '%s',
		                        '%s',
		                        '%s',
		                        '%d',
                                '%d',
		                        '%s',
		                        '%s',
		                        '%s'  
                            ), 
                            array( '%d' ) 
                        );

        return $status;
    }

    public function display_conditional_payment_gateways($gateway_list){
    	global $wpdb;

    	$gateway_active_statuses = array();
    	foreach ($gateway_list as $gateway_key => $gateway_obj) {


    		$gateway_active_statuses[$gateway_key] = '1';

    		$sql_payment_method_conditions  = $wpdb->prepare( "SELECT * FROM $this->conditional_payment_table WHERE payment_method = '%s'
    		and payment_method_status = 'active' ", $gateway_key );
            $result = $wpdb->get_results($sql_payment_method_conditions);
            if($result){

            	foreach ($result as $key => $payment_method_condition) {
            		$gateway_active_statuses[$gateway_key] = $this->payment_method_display_user_type_check($gateway_active_statuses[$gateway_key] ,$payment_method_condition);
            		$gateway_active_statuses[$gateway_key] = $this->payment_method_display_user_country_check($gateway_active_statuses[$gateway_key] ,$payment_method_condition);
            		$gateway_active_statuses[$gateway_key] = $this->payment_method_display_product_type_check($gateway_active_statuses[$gateway_key] ,$payment_method_condition);
					
            	}
            }
    	}

	    foreach ($gateway_active_statuses as $gateway_key => $gateway_key_status )
	    {
	    	if($gateway_key_status == '0'){
	    		unset($gateway_list[$gateway_key]);
	    	}	        
	    }
	 
	    return $gateway_list;
	}

    public function payment_method_display_user_type_check($gateway_active_statuses_single , $payment_method_condition){
    	global $wcpma;

    	switch ($payment_method_condition->user_type) {
    		case 'all':
    			break;
    		
    		case 'guest':
    			if(is_user_logged_in()){
    				$gateway_active_statuses_single = 0;	
    			}
    			break;

    		case 'members':
    			if(!is_user_logged_in()){
    				$gateway_active_statuses_single = 0;	
    			}
    			break;

    		
    	}

    	return $gateway_active_statuses_single;
    }

    public function payment_method_display_user_country_check($gateway_active_statuses_single , $payment_method_condition){
    	global $wcpma,$woocommerce;

    	$user_country = $woocommerce->customer->get_shipping_country();

    	if($payment_method_condition->user_country != ''){

    		$payment_method_countries = json_decode($payment_method_condition->user_country);

    	
	    	if($user_country != '' && (count($payment_method_countries) > 0) &&  !in_array($user_country, $payment_method_countries)){
	    		$gateway_active_statuses_single = '0';

	    	}
    	}
    	
    	return $gateway_active_statuses_single;
    }

    

    public function payment_method_display_product_type_check($gateway_active_statuses_single , $payment_method_condition){
    	global $woocommerce,$wcpma;

    	$total_cart_items = count(WC()->cart->get_cart());
    	$matched_cart_items = array();
    	$pro_type_status = false;
    	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {            
		    $product = $cart_item['data'];
		    $product = wc_get_product( $product->id );


		    $product_types = json_decode($payment_method_condition->product_types);
		    if(count($product_types) > 0){
		    	$pro_type_status = true;

		    	foreach ($product_types as $key => $product_type) {
		    		if( $product->is_type( $product_type ) ) {
				        $matched_cart_items[] = 1;
				    }
		    	}
		    }		    
		}

		if($total_cart_items != count($matched_cart_items) && $pro_type_status){
			$gateway_active_statuses_single = 0;
		}
    		
    	return $gateway_active_statuses_single;
    }

    
}





