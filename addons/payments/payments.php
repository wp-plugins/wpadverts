<?php
/**
 * Payments Module
 * 
 * This module allows to charge users for posting ads.
 * 
 * Note. In order to use this module you should enabled at least one payment gateway module.
 *
 * @package Adverts
 * @subpackage Payments
 * @author Grzegorz Winiarski
 * @version 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $adverts_namespace;

$adverts_namespace['payments'] = array(
    'option_name' => 'adext_payments_config',
    'default' => array(
        'default_gateway' => '',
        'default_pricing' => ''
    )
);

add_action( 'init', 'adext_payments_init' );
add_action( 'adverts_core_initiated', 'adext_payments_core_init');

if(is_admin() ) {
    add_action( 'init', 'adext_payments_init_admin' );
} else {
    add_action( 'init', 'adext_payments_init_frontend' );
}

function adext_payments_init() {

    $args = array(
        'labels'        => array(),
        'public'        => false,
        'show_ui'       => false,
        'supports'      => array( 'title' ),
        'has_archive'   => false,
    );
  
    register_post_type( 'adverts-pricing', apply_filters( 'adverts_post_type', $args, 'adverts-pricing') ); 
    
    $args = array(
        'labels'        => array(),
        'public'        => false,
        'show_ui'       => false,
        'supports'      => array( 'title' ),
        'has_archive'   => false,
    );
    
    register_post_type( 'adverts-payment', apply_filters( 'adverts_post_type', $args, 'adverts-payment') ); 
    
    register_post_status( 'completed', array(
        'label'                     => _x( 'Completed', 'completed status payment', 'adverts' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>', 'adverts' ),
    ) );
    
    register_post_status( 'failed', array(
        'label'                     => _x( 'Failed', 'failed status payment', 'adverts' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'adverts' ),
    ) );
    
    register_post_status( 'pending', array(
        'label'                     => _x( 'Pending', 'pending status payment', 'adverts' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'adverts' ),
    ) );
    
    register_post_status( 'refunded', array(
        'label'                     => _x( 'Refunded', 'refunded status payment', 'adverts' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'adverts' ),
    ) );
    
    register_post_status( 'advert-pending', array(
        'label'        => _x( 'Pending', 'post' ),
        'public'       => is_admin() || current_user_can( "edit_pages" ),
        'label_count'  => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'adverts' )
     ) );
    
    adverts_form_add_field( 'adverts_field_listing_type', array(
        ''
    ));
    
    add_action("adverts_install_module_payments", "adext_payments_install");
    add_filter("adverts_form_load", "adext_payments_form_load");
}

/**
 * Payments module installer
 * 
 * This function is executed when Payments module is activated. It creates
 * new default pricings if the pricings list is empty.
 * 
 * @since 0.2
 * @return void
 */
function adext_payments_install() {
    
    $args = array(
       'posts_per_page' => 1,
       'post_type' => 'adverts-pricing',
    );
    $query = new WP_Query( $args );

    if( $query->found_posts > 0) {
        return;
    }
    
    $id = wp_insert_post( array( 
        'post_title' => "Free",
        'post_content' => "Free ads are displayed for 30 days.",
        'post_type' => "adverts-pricing"
    ) );

    add_post_meta( $id, 'adverts_visible', '30' );
    add_post_meta( $id, 'adverts_price', '0' );
    
    $id = wp_insert_post( array( 
        'post_title' => "Premium",
        'post_content' => "Premium ads are displayed for 45 days.",
        'post_type' => "adverts-pricing"
    ) );

    add_post_meta( $id, 'adverts_visible', '45' );
    add_post_meta( $id, 'adverts_price', '50' );
}

/**
 * Function initiates Payments module in the frontend
 * 
 * @since 1.0
 */
function adext_payments_init_frontend() {
    wp_register_script('adext-payments', plugins_url().'/wpadverts/addons/payments/assets/js/payments.js', array('jquery'));
    
    add_filter("adverts_action", "adext_payments_add_action_payment");
    add_filter("adverts_action", "adext_payments_add_action_notify");
    
    add_filter("adverts_action_payment", "adext_payments_action_payment", 10, 2);
}

/**
 * Switch shortcode_adverts_add action to "payment"
 * 
 * Function checks if next current action in shortcode shortcode_adverts_add
 * is "save" and if listing price is greater than 0. If so then current action 
 * is changed to "payment".
 * 
 * @see shortcode_adverts_add()
 * @since 1.0
 * 
 * @param type $action
 * @return string
 */
function adext_payments_add_action_payment( $action ) {
    
    if( $action != "save" ) {
        return $action;
    }
    
    $listing_type = get_post_meta( adverts_request("_post_id", null), "payments_listing_type", true );
    
    if( $listing_type === false || empty($listing_type) ) {
        return $action;
    } 
    
    $price = get_post_meta( $listing_type, "adverts_price", true );

    if( $price === false || empty($price) ) {
        return $action;
    }
    
    return "payment";
}

/**
 * Switch shortcode_adverts_add action to $gateway_name
 * 
 * Function checks if adverts-notify-id param is sent via GET or POST if so
 * then the function will try to get payment gateway for this payment object and
 * switch action to gateway_name.
 * 
 * In other words the function will trigger "adverts_action_$gateway_name" filter 
 * execution.
 * 
 * @see shortcode_adverts_add()
 * @since 1.0
 * 
 * @param string $action
 * @return string
 */
function adext_payments_add_action_notify( $action ) {
    if( $action != "" || !adverts_request( "adverts-notify-id" ) ) {
        return $action;
    }
    
    $payment = get_post( adverts_request( "adverts-notify-id" ) );
    
    if( !$payment ) {
        return $action;
    }
    
    $gateway_name = get_post_meta( $payment->ID, "_adverts_payment_gateway", true );
    $gateway = adext_payment_gateway_get( $gateway_name );
    
    return $gateway_name;
}

/**
 * Adds Listing Type field to Add Advert form.
 * 
 * This function is applied to "adverts_form_load" filter in Adverts_Form::load()
 * when Advert form is being loaded.
 * 
 * @since 1.0
 * @see Adverts_Form::load()
 * 
 * @param array $form
 * @return array
 */
function adext_payments_form_load( $form ) {
    
    if($form["name"] != 'advert' || is_admin()) {
        return $form;
    }
    
    // do not show payment options when editing Ad.
    $id = adverts_request( "advert_id" );
    $ad = get_post( $id );
    if( intval($id) && $ad && in_array($ad->post_status, array("publish", "expired") ) ) {
        return $form;
    }
    
    $form["field"][] = array(
        "name" => "_listing_information",
        "type" => "adverts_field_header",
        "order" => 50,
        "label" => __( 'Listing Information', 'adverts' )
    );
    
    $opts = array();
    $pricings = new WP_Query( array( 
        'post_type' => 'adverts-pricing',
        'post_status' => 'draft'
    ) );

    foreach($pricings->posts as $data) {
        
        if($data->post_content) {
            $post_content = '<br/><small style="padding-left:25px">'.$data->post_content.'</small>' ;
        } else {
            $post_content = '';
        }
        
        if( get_post_meta( $data->ID, 'adverts_price', true ) ) {
            $adverts_price = adverts_price( get_post_meta( $data->ID, 'adverts_price', true ) );
        } else {
            $adverts_price = __("Free", "adverts");
        }
        
        $text = sprintf(
            __('<b>%1$s</b> - %2$s for %3$d days.%4$s', 'adverts'), 
            $data->post_title, 
            $adverts_price, 
            get_post_meta( $data->ID, 'adverts_visible', true ),
            $post_content
        );
        $opts[] = array("value"=>$data->ID, "text"=>$text);
    }

    $form["field"][] = array(
        "name" => "payments_listing_type",
        "type" => "adverts_field_radio",
        "label" => __("Listing", "adverts"),
        "order" => 50,
        "empty_option" => true,
        "options" => $opts,
        "value" => ""
    );
    
    add_filter("adverts_form_bind", "adext_payments_form_bind");
    
    return $form;
}

/**
 * Binds default payment_listing_type value
 * 
 * @see adext_payments_form_load() Function which adds this function to filters list
 * @uses adverts_form_bind Filter which exexutes this function
 * 
 * @since 1.0
 * @access public
 * @param Adverts_Form $form
 * @return Adverts_Form
 */
function adext_payments_form_bind( Adverts_Form $form ) {
    
    if( ! $form->get_value( "payments_listing_type" ) ) {
        $form->set_value("payments_listing_type", adverts_config('payments.default_pricing'));
    }
    return $form;
}

/**
 * Payment action
 * 
 * This function is executed when "payment" action is run shortcode_adverts_add
 * 
 * @see shortcode_adverts_add()
 * @since 1.0
 * 
 * $param string $content 
 * @param Adverts_Form $form
 * @return null
 */
function adext_payments_action_payment($content, Adverts_Form $form ) {
    
    $info[] = __("Thank you for submitting your ad!", "adverts");
    $error = array();
    
    wp_enqueue_script( 'adext-payments' );
    
    $adverts_flash = array( "error" => $error, "info" => $info );
    
    $post_id = adverts_request( "_post_id" );
    $post = get_post( $post_id );
    
    wp_update_post( array( 
        "ID" => $post_id,
        "post_status" => "advert-pending"
    ) );

    if( !is_user_logged_in() && get_post_meta( $post_id, "_adverts_account", true) == 1 ) {
        adverts_create_user_from_post_id( $post_id, true );
    }
    
    $listing_id = get_post_meta( $post_id, "payments_listing_type", true );
    $listing = get_post( $listing_id );
    
    $price = get_post_meta($listing_id, 'adverts_price', true);
    
    ob_start();
    include ADVERTS_PATH . 'addons/payments/templates/add-payment.php';
    return ob_get_clean();
}

/**
 * Function initiates Payments module in wp-admin
 * 
 * @since 1.0
 */
function adext_payments_init_admin() {
    
    include_once ADVERTS_PATH . 'addons/payments/includes/admin-pages.php';
    include_once ADVERTS_PATH . 'addons/payments/includes/ajax.php';
    
    add_action( 'admin_menu', 'adext_payments_add_history_link');
    add_filter( 'display_post_states', 'adext_payments_display_pending_state' );
    add_action( 'admin_head', 'adext_payments_admin_head' );
}

/**
 * Adds "Payment History" link to wp-admin menu.
 * 
 * @see admin_menu
 * @since 1.0
 */
function adext_payments_add_history_link() {
    
   $menu_page = apply_filters('adverts_menu_page', array(
        "parent_slug" => "edit.php?post_type=advert",
        "page_title" => __( 'Adverts Payment History', 'adverts' ),
        "menu_title" => __( 'Payment History', 'adverts' ),
        "capability" => "install_plugins",
        "menu_slug" => 'adext-payment-history',
        "function" => "adext_payments_page_history"
    ));
    
    add_submenu_page(
        $menu_page["parent_slug"], 
        $menu_page["page_title"], 
        $menu_page["menu_title"], 
        $menu_page["capability"], 
        $menu_page["menu_slug"], 
        $menu_page["function"]
    );
    
}

/**
 * Payments Init
 * 
 * Payments module init functions, this function is executed when Adverts 
 * core is initiated.
 * 
 * @see adverts_core_init
 * @since 1.0
 */
function adext_payments_core_init() {
    
    include_once ADVERTS_PATH . 'addons/payments/includes/payment-actions.php';
    
    add_action("adverts_payment_completed", "adext_payment_completed_publish");
    add_action("adverts_payment_completed", "adext_payment_completed_notify_user");
    add_action("adverts_payment_completed", "adext_payment_completed_notify_admin");
    
    do_action("adext_register_payment_gateway");
}

/**
 * Registers new payment method
 * 
 * @see Adverts
 * @since 1.0
 * 
 * @param string $name
 * @param array $data
 */
function adext_payment_gateway_add( $name, $data ) {
    
    $pg = Adverts::instance()->get("payment_gateways");

    if(!is_array($pg)) {
        $pg = array( $name => $data );
    } else {
        $pg[$name] = $data;
    }

    Adverts::instance()->set("payment_gateways", $pg);
}

/**
 * Returns payment gateway by $name, if $name is NULL then all payment
 * gateways are returned.
 * 
 * @see Adverts
 * @since 1.0
 * 
 * @param string $name
 * @return mixed
 */
function adext_payment_gateway_get( $name = null ) {
    $pg = Adverts::instance()->get("payment_gateways");
    
    if( $name === null ) {
        return $pg;
    } elseif( isset( $pg[$name] ) ) {
        return $pg[$name];
    } else {
        return null;
    }
}

/**
 * Adds message log to payment object.
 * 
 * @param string $name
 * @since 0.1
 * @return void
 */
function adext_payments_log( $payment_id, $message ) {
    
    $payment = get_post( $payment_id );
    
    if( $payment->post_type != 'adverts-payment' ) {
        return new WP_Error("Invalid Post Type.");
    }
    
    $pattern = apply_filters('adext_payments_log', '%1$s - %2$s');
    $log = sprintf( $pattern, current_time('mysql'), $message);
    
    wp_update_post( array(
        "ID" => $payment_id,
        "post_content" => $payment->post_content . "\r\n" . $log
    ));
}