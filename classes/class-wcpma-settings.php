<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* Manage settings of WP Private Content Plus plugin */
class WCPMA_Settings{
    
    public $template_locations;
    public $current_user;
    
    /* Intialize actions for plugin settings */
    public function __construct(){
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array(&$this, 'admin_settings_menu'), 9);
        add_action('init', array($this,'save_settings_page') );
        
    }

    public function init(){
        $this->current_user = get_current_user_id(); 
    }
    
    /*  Save settings tabs */
    public function save_settings_page(){

        if(!is_admin())
            return;
        
        $wcpma_settings_pages = array('wcpma-payment-settings');
        if(isset($_POST['wcpma_tab']) && isset($_GET['page']) && in_array($_GET['page'],$wcpma_settings_pages)){
            $tab = '';
            if ( isset ( $_POST['wcpma_tab'] ) )
               $tab = $_POST['wcpma_tab']; 

            if($tab != ''){
                $func = 'save_'.$tab;
                
                if(method_exists($this,$func))
                    $this->$func();
            }
        }
    }  
    
    
    /* Intialize settings page and tabs */
    public function admin_settings_menu(){
        add_menu_page(__('Woo Conditional Payment Methods', 'wcpma' ), __('Woo Conditional Payment Methods', 'wcpma' ),'manage_options','wcpma-payment-settings',array(&$this,'payment_settings'));
    }  
    
    /* Display settings */

    public function payment_settings(){
        global $wcpma,$wcpma_settings_data;
        
        add_settings_section( 'wcpma_conditional_payment_section_general', __('Conditional Payment Methods','wcpma'), array( &$this, 'wcpma_conditional_payment_section_general_desc' ), 'wcpma-payment-general' );
        
        
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'wcpma_conditional_payment_section_general';
        $wcpma_settings_data['tab'] = $tab;
        
        $tabs = $this->plugin_options_tabs('conditional_payment_general',$tab);
   
        $wcpma_settings_data['tabs'] = $tabs;
        
        $tab_content = $this->plugin_options_tab_content($tab);
        $wcpma_settings_data['tab_content'] = $tab_content;
        
        ob_start();
        $wcpma->template_loader->get_template_part( 'menu-page-container');
        $display = ob_get_clean();
        echo $display;
        
    
    }
    
    /* Manage settings tabs for the plugin */
    public function plugin_options_tabs($type,$tab) {
        $current_tab = $tab;
        $this->plugin_settings_tabs = array();
        
        switch($type){

  
            case 'conditional_payment_general':
                $this->plugin_settings_tabs['wcpma_conditional_payment_section_general']  = __('Conditional Payment Methods','wcpma');
                break;

        }
        
        ob_start();
        ?>

        <h2 class="nav-tab-wrapper">
        <?php 
            foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            $page = isset($_GET['page']) ? $_GET['page'] : '';
        ?>
                <a class="nav-tab <?php echo $active; ?> " href="?page=<?php echo $page; ?>&tab=<?php echo $tab_key; ?>"><?php echo $tab_caption; ?></a>
            
        <?php } ?>
        </h2>

        <?php
                
        return ob_get_clean();
    }
    
    /* Manage settings tab contents for the plugin */
    public function plugin_options_tab_content($tab,$params = array()){
        global $wcpma,$wcpma_mailchimp_settings_data;

        $wcpma_options = get_option('wsmea_options');

        $this->load_wcpma_select2_scripts_style();
        
        ob_start();
        switch($tab){
            

            case 'wcpma_conditional_payment_section_general':                
                $data = isset($wcpma_options['conditional_payment_general']) ? $wcpma_options['conditional_payment_general'] : array();
      
                $wcpma_mailchimp_settings_data['tab'] = $tab;
                
                $wcpma->template_loader->get_template_part('conditional-payment-method-settings');            
                break;         
            
        }
        
        $display = ob_get_clean();
        return $display;
        
    }

    
    /* Display settings saved message */  
    public function admin_notices(){
        ?>
        <div class="updated">
          <p><?php esc_html_e( 'Settings saved successfully.', 'wcpma' ); ?></p>
       </div>
        <?php
    }

    public function load_wcpma_select2_scripts_style(){          

        wp_register_script('wcpma_select2_js', WCPMA_PLUGIN_URL . 'js/select2/wcpma-select2.min.js');
        wp_enqueue_script('wcpma_select2_js');
        
        wp_register_style('wcpma_select2_css', WCPMA_PLUGIN_URL . 'js/select2/wcpma-select2.min.css');
        wp_enqueue_style('wcpma_select2_css');

    }

}
