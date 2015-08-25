<?php
/**
 * List of registered shortcodes
 * 
 * @package     Adverts
 * @copyright   Copyright (c) 2015, Grzegorz Winiarski
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Register shortcodes
add_shortcode('adverts_list', 'shortcode_adverts_list');
add_shortcode('adverts_add', 'shortcode_adverts_add');
add_shortcode('adverts_manage', 'shortcode_adverts_manage');
add_shortcode('adverts_categories', 'shortcode_adverts_categories');

// Shortcode functions

/**
 * Generates HTML for [adverts_list] shortcode
 * 
 * @param array $atts Shorcode attributes
 * @since 0.1
 * @return string Fully formatted HTML for adverts list
 */
function shortcode_adverts_list( $atts ) {

    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );

    wp_enqueue_script( 'adverts-frontend' );

    extract(shortcode_atts(array(
        'name' => 'default',
        'category' => null,
        'columns' => 2,
        'paged' => adverts_request("pg", 1),
        'posts_per_page' => 20,
    ), $atts));
    
    $taxonomy = null;
    $meta = array();
    
    $query = adverts_request("query");
    $location = adverts_request("location");
    
    if($location) {
        $meta[] = array('key'=>'adverts_location', 'value'=>$location, 'compare'=>'LIKE');
    }
    
    if($category) {
        $taxonomy =  array(
            array(
                'taxonomy' => 'advert_category',
                'field'    => 'term_id',
                'terms'    => $category,
            ),
	);
    }

    $loop = new WP_Query( array( 
        'post_type' => 'advert', 
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page, 
        'paged' => $paged,
        's' => $query,
        'meta_query' => $meta,
        'tax_query' => $taxonomy
    ) );

    $paginate_base = get_the_permalink() . '%_%';
    $paginate_format = stripos( $paginate_base, '?' ) ? '&pg=%#%' : '?pg=%#%';

    // adverts/templates/list.php
    ob_start();
    include_once ADVERTS_PATH . 'templates/list.php';
    return ob_get_clean();
}

/**
 * Generates HTML for [adverts_add] shortcode
 * 
 * @param array $atts Shortcode attributes
 * @since 0.1
 * @return string Fully formatted HTML for "post ad" form.
 */
function shortcode_adverts_add( $atts ) {
    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );
    wp_enqueue_style( 'adverts-icons-animate' );

    wp_enqueue_script( 'adverts-frontend' );
    wp_enqueue_script( 'adverts-auto-numeric' );
    
    extract(shortcode_atts(array(
        'name' => 'default',
        'moderate' => false
    ), $atts));
    
    include_once ADVERTS_PATH . 'includes/class-html.php';
    include_once ADVERTS_PATH . 'includes/class-form.php';

    $form = new Adverts_Form(Adverts::instance()->get("form"));
    $valid = null;
    $error = array();
    $info = array();
    $bind = array();
    $content = "";
    $adverts_flash = array( "error" => array(), "info" => array() );
    
    $action = apply_filters( 'adverts_action', adverts_request("_adverts_action", ""), __FUNCTION__ );
    $post_id = (adverts_request("_post_id", null));
    $post_id = ($post_id>0) ? $post_id : null;
    
    // $post_id hijack attempt protection here!
    
    if( $post_id>0 && get_post( $post_id )->post_author == get_current_user_id() ) {
        
        // if post was already saved in DB (for example for preview) then load it.
        $post = get_post( $post_id );
        
        // bind data by field name
        foreach( $form->get_fields() as $f ) {
            $bind[$f["name"]] = get_post_meta( $post_id, $f["name"], true );
        }
        
        $bind["post_title"] = $post->post_title;
        $bind["post_content"] = $post->post_content;
        $bind["advert_category"] = array();
        
        $terms = get_the_terms( $post_id, 'advert_category' );
        
        if(is_array($terms)) {
            foreach($terms as $term) {
                $bind["advert_category"][] = $term->term_id;
            }
        }
        
    } elseif( is_user_logged_in() ) {
        $bind["adverts_person"] = wp_get_current_user()->display_name;
        $bind["adverts_email"] = wp_get_current_user()->user_email;
    }
    
    if($action == "") {
        // show post ad form page
        wp_enqueue_style( 'adverts-frontend-add' );
        
        $bind["_post_id"] = $post_id;
        $bind["_adverts_action"] = "preview";
        
        $form->bind( $bind );
        
        // adverts/templates/list.php
        ob_start();
        include_once ADVERTS_PATH . 'templates/add.php';
        $content = ob_get_clean();
        
    } elseif($action == "preview") {
        // show preview page
        wp_enqueue_style( 'adverts-frontend-add' );

        $form->bind( (array)$_POST );
        $valid = $form->validate();

        $adverts_flash = array( "error" => $error, "info" => $info );
        
        // Allow to preview only if data in the form is valid.
        if($valid) {
            
            $init = array(
                "post" => array(
                    "ID" => $post_id,
                    "post_name" => sanitize_title( $form->get_value( "post_title" ) ),
                    "post_type" => "advert",
                    "post_author" => get_current_user_id(),
                    "post_date" => current_time( 'mysql' ),
                    "post_date_gmt" => current_time( 'mysql', 1 ),
                    "post_status" => adverts_tmp_post_status(),
                    "guid" => ""
                ),
                "meta" => array()
            );
            
            if( adverts_config( "config.visibility" ) > 0 ) {
                $init["meta"]["_expiration_date"] = array(
                    "value" => strtotime( current_time('mysql') . " +". adverts_config( "config.visibility" ) ." DAYS" ),
                    "field" => array(
                        "type" => "adverts_field_hidden"
                    )
                );
            }
            
            // Save post as temporary in DB
            $post_id = Adverts_Post::save($form, $post_id, $init);
            
            if(is_wp_error($post_id)) {
                $error[] = $post_id->get_error_message();
                $valid = false;
            } 
            
            $adverts_flash = array( "error" => $error, "info" => $info );
            
            // adverts/templates/add-preview.php
            ob_start();
            include_once ADVERTS_PATH . 'templates/add-preview.php';
            $content = ob_get_clean();
            
        } else {
            $error[] = __("There are errors in your form. Please correct them before proceeding.", "adverts");
            
            $adverts_flash = array( "error" => $error, "info" => $info );
            
            // adverts/templates/add.php
            ob_start();
            include_once ADVERTS_PATH . 'templates/add.php';
            $content = ob_get_clean();
            
        } // endif $valid

    } elseif( $action == "save") {
        
        // Save form in the database
        $post_id = wp_update_post( array(
            "ID" => $post_id,
            "post_status" => $moderate == "1" ? 'pending' : 'publish',
        ));
        
        $info[] = __("Thank you for submitting your ad!", "adverts");
        
        $adverts_flash = array( "error" => $error, "info" => $info );

        if( !is_user_logged_in() && get_post_meta( $post_id, "_adverts_account", true) == 1 ) {
            adverts_create_user_from_post_id( $post_id, true );
        }
    
        
        // adverts/templates/add-save.php
        ob_start();
        include_once ADVERTS_PATH . 'templates/add-save.php';
        $content = ob_get_clean();
        
    }
    
    return apply_filters("adverts_action_$action", $content, $form);
}

/**
 * Generates HTML for [adverts_manage] shortcode
 * 
 * @param array $atts Shortcode attributes
 * @since 0.1
 * @return string Fully formatted HTML for ads management panel.
 */
function shortcode_adverts_manage( $atts ) {
    
    ob_start();
    if(!get_current_user_id()) {
        wp_enqueue_style( 'adverts-frontend' );
        wp_enqueue_style( 'adverts-icons' );
        $permalink = get_permalink();
        $message = __('Only logged in users can access this page. <a href="%1$s">Login</a> or <a href="%2$s">Register</a>.', "adverts");
        $parsed = sprintf($message, wp_login_url( $permalink ), wp_registration_url( $permalink ) );
        adverts_flash( array( "error" => array( $parsed ) ) );
    } elseif(adverts_request("advert_id") == null) {
        _adverts_manage_list( $atts );
    } else {
        _adverts_manage_edit( $atts );
    }
    
    return ob_get_clean();
}

/**
 * Generates HTML for list of posted ads (in [adverts_manage] shortcode)
 * 
 * @param array $atts Shortcode attributes
 * @since 0.1
 * @return void 
 * @access private
 */
function _adverts_manage_list( $atts ) {
    
    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );

    wp_enqueue_script( 'adverts-frontend' );

    extract(shortcode_atts(array(
        'name' => 'default',
        'paged' => adverts_request("pg", 1),
        'posts_per_page' => 20,
    ), $atts));
    
    // Load ONLY current user data
    $loop = new WP_Query( array( 
        'post_type' => 'advert', 
        'post_status' => apply_filters("adverts_sh_manage_list_statuses", array('publish', 'pending', 'expired') ),
        'posts_per_page' => $posts_per_page, 
        'paged' => $paged,
        'author' => get_current_user_id()
    ) );

    $baseurl = get_the_permalink();
    $paginate_base = $baseurl . '%_%';
    $paginate_format = stripos( $paginate_base, '?' ) ? '&pg=%#%' : '?pg=%#%';
    $edit_format = stripos( $baseurl, '?' ) ? '&advert_id=%#%' : '?advert_id=%#%';

    // adverts/templates/manage.php
    include_once ADVERTS_PATH . 'templates/manage.php';
} 

/**
 * Generates HTML for ad edit form (in [adverts_manage] shortcode)
 * 
 * @param array $atts Shortcode attributes
 * @since 0.1
 * @return void 
 * @access private
 */
function _adverts_manage_edit( $atts ) {
    
    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );
    wp_enqueue_style( 'adverts-icons-animate' );

    wp_enqueue_script( 'adverts-frontend' );
    wp_enqueue_script( 'adverts-auto-numeric' );
    

    extract(shortcode_atts(array(
        'name' => 'default',
        'moderate' => false
    ), $atts));
    
    include_once ADVERTS_PATH . 'includes/class-html.php';
    include_once ADVERTS_PATH . 'includes/class-form.php';

    $form = new Adverts_Form(Adverts::instance()->get("form"));
    $valid = null;
    $error = array();
    $info = array();
    $bind = array();
    
    $action = apply_filters( 'adverts_action', adverts_request("_adverts_action", ""), __FUNCTION__ );
    $post_id = (adverts_request("advert_id", null));

    // $post_id hijack attempt protection here!

    $post = get_post( $post_id );
    
    if( $post === null) {
        $error[] = __("Ad does not exist.", "adverts");
        adverts_flash( array("error"=>$error) );
        return;
    }
    
    if( $post->post_author != get_current_user_id() ) {
        $error[] = __("You do not own this Ad.", "adverts");
        adverts_flash( array("error"=>$error) );
        return;
    }
    
    $slist = apply_filters("adverts_sh_manage_list_statuses", array( 'publish', 'expired', 'pending', 'draft') );
    
    if( !in_array( $post->post_status, $slist ) ) {
        $error[] = sprintf( __( "Incorrect post status [%s].", "adverts" ), $post->post_status );
        adverts_flash( array("error"=>$error) );
        return;
    }
    
    foreach( $form->get_fields() as $f ) {
        $bind[$f["name"]] = get_post_meta( $post_id, $f["name"], true );
    }
    
    $bind["_adverts_action"] = "update";
    $bind["_post_id"] = $post_id;
    
    $bind["post_title"] = $post->post_title;
    $bind["post_content"] = $post->post_content;
    $bind["advert_category"] = array();

    $terms = get_the_terms( $post_id, 'advert_category' );
    if( is_array( $terms ) ) {
        foreach( $terms as $term ) {
            $bind["advert_category"][] = $term->term_id;
        }
    }
    
    $form->bind( $bind );
    
    if($action == "update") {
        
        $form->bind( (array)$_POST );
        $valid = $form->validate();

        if($valid) {
            
            $post_id = wp_update_post( array(
                "ID" => $post_id,
                "post_type" => "advert",
                "post_author" => get_current_user_id(),
                "post_title" => $form->get_value("post_title"),
                "post_content" => $form->get_value("post_content")
            ));
            
            if(is_wp_error($post_id)) {
                $error[] = $post_id->get_error_message();
            } else {
                update_post_meta($post_id, "adverts_person", $form->get_value("adverts_person"));
                update_post_meta($post_id, "adverts_email", $form->get_value("adverts_email"));
                update_post_meta($post_id, "adverts_phone", $form->get_value("adverts_phone"));
                update_post_meta($post_id, "adverts_location", $form->get_value("adverts_location"));
                update_post_meta($post_id, "adverts_price", $form->get_value("adverts_price"));
                
                $info[] = __("Post has been updated.", "adverts");

            }
        } else {
            $error[] = __("Cannot update. There are errors in your form.", "adverts");
        }
    }
    
    $adverts_flash = array( "error" => $error, "info" => $info );
    
    // adverts/templates/manage-edit.php
    include_once ADVERTS_PATH . 'templates/manage-edit.php';
}

/**
 * Generates HTML for [adverts_categories] shortcode
 * 
 * @param array $atts Shortcode attributes
 * @since 0.3
 * @return string Fully formatted HTML for "categories" form.
 */
function shortcode_adverts_categories( $atts ) {
    
    extract(shortcode_atts(array(
        'name' => 'default',
        'show' => 'top',
        'columns' => 4,
        'default_icon' => 'adverts-icon-folder',
        'show_count' => true,
        'sub_count' => 5
    ), $atts));
    
    $columns = "adverts-flexbox-columns-" . (int)$columns;
    
    if($show != 'top') {
        $show = 'all';
    }
    
    $terms = get_terms( 'advert_category', array( 
        'hide_empty' => 0, 
        'parent' => null, 
    ) );
    
    wp_enqueue_style( 'adverts-frontend');
    wp_enqueue_style( 'adverts-icons' );

    ob_start();
    // adverts/templates/categories.php
    include_once ADVERTS_PATH . 'templates/categories-'.$show.'.php';
    return ob_get_clean();
}

/**
 * Renders flash messages
 * 
 * @param array $data
 * @since 0.1
 * @return void
 */
function adverts_flash( $data ) {

    ?>

    <?php if(isset($data["error"]) && is_array($data["error"]) && !empty($data["error"])): ?>
    <div class="adverts-flash-error">
    <?php foreach( $data["error"] as $key => $error): ?>
        <span><?php echo $error ?></span>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(isset($data["info"]) && is_array($data["info"]) && !empty($data["info"])): ?>
    <div class="adverts-flash-info">
    <?php foreach( $data["info"] as $key => $info): ?>
        <span><?php echo $info ?></span>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
}