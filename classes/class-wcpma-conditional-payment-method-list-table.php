<?php
/*  Copyright 2011  Matthew Van Andel  (email : matt@mattvanandel.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */



/* == NOTICE ===================================================================
 * Please do not alter this file. Instead: make a copy of the entire plugin, 
 * rename it, and work inside the copy. If you modify this plugin directly and 
 * an update is released, your changes will be lost!
 * ========================================================================== */

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class WCPMA_Conditional_Payment_Method_List_Table extends WP_List_Table {

    var $example_data = array();

    function __construct() {
        global $status, $page;

        //Set parent defaults
        parent::__construct(array(
                    'singular' => 'payment_method', //singular name of the listed records
                    'plural' => 'payment_methods', //plural name of the listed records
                    'ajax' => false        //does this table support ajax?
                ));
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'payment_method':
                return $item[$column_name];
            case 'user_type':
                return $item[$column_name];
            case 'user_country':
                return $item[$column_name]; 
            case 'payment_method_status':
                return $item[$column_name];                 
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    
    function column_payment_method($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=wcpma-payment-settings&action=edit&payment_method_id=%s">Edit</a>',$item['ID']),
            
        );

        return sprintf('%1$s %2$s', $item['payment_method'], $this->row_actions($actions) );

    }

    function column_cb($item) {
        global $wpdb;
        $user_id = get_current_user_id();

       
        $checkbox_field = '<input type="checkbox" name="%1$s[]" value="%2$s" />';

        return sprintf(
                $checkbox_field,
                /* $1%s */ $this->_args['singular'], 
                /* $2%s */ $item['ID']      
        );
    }


    
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'payment_method' => __('Payment Method','wcpma'),
            'user_type' => __('User Type','wcpma'),
            'user_country' => __('Country','wcpma'),
            'payment_method_status' => __('Status','wcpma'),
        );
        return $columns;
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'payment_method' => array('payment_method', false),
            'user_type' => array('user_type', false),
            'user_country' => array('user_country', false),
            'payment_method_status' => array('payment_method_status', false)
        );
        return $sortable_columns;
    }
    

    function process_bulk_action() {
        global $wpdb;
        //Detect when a bulk action is being triggered...
        
    }

    function prepare_items() {
        global $wpdb; //This is used only if making any database queries


        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $data = $this->example_data;


        function usort_reorder($a, $b) {
           // $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'user_registered'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : $result; //Send final sort direction to usort
        }


        $current_page = $this->get_pagenum();


        $total_items = count($data);

        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);


        $this->items = $data;

        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }

}

function wcpma_conditional_payment_methods_list_page() {
    global $wpdb,$wcpma;
    $conditional_payment_table = $wpdb->prefix . 'wsmea_conditional_payment_methods';

    $testListTable = new WCPMA_Conditional_Payment_Method_List_Table();

    $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'payment_method'; //If no sort, default to title
    $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            

    if(isset($_POST['wcpma_conditional_payment_search_hidden'])){
        $search_text = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
        $sql_total  = "SELECT * FROM $conditional_payment_table WHERE 
             (payment_method like '%".$search_text."%' or user_type like '%".$search_text."%' 
             or product_types like '%".$search_text."%' ) order by $orderby
              $order  ";
        $result = $wpdb->get_results($sql_total);
    
    }else{
        $sql_total  = $wpdb->prepare( "SELECT * FROM $conditional_payment_table WHERE payment_method != '%s' order by $orderby
              $order", '' );
         $result = $wpdb->get_results($sql_total);

    }
   
    if($result){ 

        $available_gateways = WC()->payment_gateways->payment_gateways;
        $available_gateways_title = array();
        foreach ($available_gateways as $key => $available_gateway) {
            $available_gateways_title[$available_gateway->id] = $available_gateway->method_title;
        }


        
        $user_types = array("all" => __('Everyone','wcpma'), "guest" => __('Guests','wcpma'), "members" => __('Members','wcpma') 
        );
        $product_types = wc_get_product_types();

        $countries_obj   = new WC_Countries();
        $countries   = ($countries_obj->__get('countries'));



        foreach ($result as $key => $payment_method) {

            $sel_countries = json_decode($payment_method->user_country);
            $sel_countries_list = array();
            foreach ($sel_countries as $sel_country) {
                $sel_countries_list[] = $countries[$sel_country];
            }
            $sel_countries_list = implode(',', $sel_countries_list);

            array_push($testListTable->example_data, array("ID" => $payment_method->id, 
                "payment_method" => $available_gateways_title[$payment_method->payment_method],
                "user_type" => $user_types[$payment_method->user_type],
                "product_types" => $payment_method->product_types,                
                "user_country" => $sel_countries_list,
                "payment_method_status" => ucfirst($payment_method->payment_method_status),
            ));
      
        }
    }

    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();

?>
    <div class="wrap">

        <div id="icon-users" class="icon32"><br/></div>
        <h2><?php echo __('Conditional Payment Method Details','wcpma'); ?></h2>

        <form method="post">
          <input type="hidden" name="wcpma_conditional_payment_search_hidden" value="1" />
          <?php $testListTable->search_box('Search', 'wcpma_search'); ?>
        </form>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="topics-filter" method="POST">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>
    </div>
<?php
}