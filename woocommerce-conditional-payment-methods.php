<?php
/*
  Plugin Name: Conditional Payment Methods for WooCommerce
  Plugin URI: http://www.wpexpertdeveloper.com/woocommerce-conditional-payment-methods
  Description: Provides features conditionally display payment gateways on checkout based on wide range of conditins
  Version: 1.0
  Author: Rakhitha Nimesh
  Author URI: http://www.wpexpertdeveloper.com
 */



// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, 'wcpma_install' );


function wcpma_get_plugin_version() {
    $default_headers = array('Version' => 'Version');
    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');
    return $plugin_data['Version'];
}

function wcpma_install(){
    global $wpdb;
    
    $table_conditional_payment_methods = $wpdb->prefix . 'wsmea_conditional_payment_methods';

    $sql_conditional_payment_methods = "CREATE TABLE IF NOT EXISTS $table_conditional_payment_methods (
              id int(11) NOT NULL AUTO_INCREMENT,
              payment_method varchar(255) NOT NULL,
              user_type varchar(255) NOT NULL,
              user_roles longtext NOT NULL,
              product_types longtext NOT NULL,
              min_order_total float NOT NULL,
              max_order_total float NOT NULL,
              products longtext NOT NULL,
              user_country varchar(255) NOT NULL,
              payment_method_status varchar(255) NOT NULL,
              PRIMARY KEY (id)
            );";


    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_conditional_payment_methods );
}

/* Validating existence of required plugins */
add_action( 'plugins_loaded', 'wcpma_plugin_init' );

function wcpma_plugin_init(){
    if(!class_exists('WC_Product')){
        add_action( 'admin_notices', 'wcpma_woo_plugin_admin_notice' );
    }else{
        Woocommerce_Conditional_Payment_Methods();
    }
}

function wcpma_woo_plugin_admin_notice() {
   $message = __('<strong>Conditional Payment Methods for Woocommerce</strong> requires <strong>Woocommerce</strong> plugin to function properly','wcpma');
   echo '<div class="error"><p>'.$message.'</p></div>';
}

if( !class_exists( 'Woocommerce_Conditional_Payment_Methods' ) ) {
    
    class Woocommerce_Conditional_Payment_Methods{
    
        private static $instance;

        public static function instance() {
            
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Woocommerce_Conditional_Payment_Methods ) ) {
                self::$instance = new Woocommerce_Conditional_Payment_Methods();
                self::$instance->setup_constants();

                add_action('init', array( self::$instance, 'init' ) );

                add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
                self::$instance->includes();
                
                add_action('admin_enqueue_scripts',array(self::$instance,'load_admin_scripts'),9);
                add_action('wp_enqueue_scripts',array(self::$instance,'load_scripts'),9);
                add_action('wp_footer', array(self::$instance,'load_customizer_styles'), 100 );
                 
                self::$instance->template_loader            = new WCPMA_Template_Loader();
                self::$instance->settings                   = new WCPMA_Settings();
                self::$instance->payments_manager           = new WCPMA_Payments_Manager();
                self::$instance->users                      = new WCPMA_Users();
            }
            return self::$instance;
        }

        public function init(){
            self::$instance->options = get_option('wsmea_options');
        }

        public function setup_constants() {
            
        }
        
        public function load_scripts(){    



            wp_register_style('wcpma-front-css', WCPMA_PLUGIN_URL . 'css/wcpma-front.css');
            wp_enqueue_style('wcpma-front-css');

            wp_register_script('wcpma_front', WCPMA_PLUGIN_URL . 'js/wcpma-front.js', array('jquery'));
            wp_enqueue_script('wcpma_front');

            // wp_add_inline_style( 'wcpma-front-css', $custom_css );           
        }

        public function load_customizer_styles(){

        }
        
        public function load_admin_scripts(){

            if(is_admin()){
                wp_register_style('wcpma-admin-css', WCPMA_PLUGIN_URL . 'css/wcpma-admin.css');
                wp_enqueue_style('wcpma-admin-css');


                wp_register_script('wcpma-admin', WCPMA_PLUGIN_URL . 'js/wcpma-admin.js', array('jquery'));
                wp_enqueue_script('wcpma-admin');

                $custom_js_strings = array(        
                    'AdminAjax' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wcpma-admin'),   
                );

                wp_localize_script('wcpma-admin', 'WCPMAAdmin', $custom_js_strings);
            }
           

        }
        
        private function includes() {
            
            require_once WCPMA_PLUGIN_DIR . 'classes/class-wcpma-template-loader.php';      
            require_once WCPMA_PLUGIN_DIR . 'classes/class-wcpma-settings.php'; 
            require_once WCPMA_PLUGIN_DIR . 'classes/class-wcpma-payments-manager.php';
            require_once WCPMA_PLUGIN_DIR . 'classes/class-wcpma-conditional-payment-method-list-table.php';
            require_once WCPMA_PLUGIN_DIR . 'classes/class-wcpma-users.php';

                        
            if ( is_admin() ) {
            }
        }

        public function load_textdomain() {
            
        }
        
    }
}

// Plugin version
if ( ! defined( 'WCPMA_VERSION' ) ) {
    define( 'WCPMA_VERSION', '1.0' );
}

// Plugin Folder Path
if ( ! defined( 'WCPMA_PLUGIN_DIR' ) ) {
    define( 'WCPMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL
if ( ! defined( 'WCPMA_PLUGIN_URL' ) ) {
    define( 'WCPMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once WCPMA_PLUGIN_DIR . 'functions.php';

function Woocommerce_Conditional_Payment_Methods() {
    global $wcpma;
    $wcpma = Woocommerce_Conditional_Payment_Methods::instance();
}



