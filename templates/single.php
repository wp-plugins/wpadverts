<?php
    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );
    wp_enqueue_style( 'adverts-icons-animate' );

    wp_enqueue_script( 'adverts-frontend' );
    
?>

<?php $images = get_children(array('post_parent'=>$post_id)) ?>
<?php wp_enqueue_script( 'responsive-slides' ); ?>
<?php if( !empty($images) ): ?>
<div class="rslides_container">
    <ul id="slides1" class="rslides rslides1">
        <?php foreach($images as $tmp_post): ?>
            <?php $image = wp_get_attachment_image_src( $tmp_post->ID, 'large' ) ?>
            <?php if(isset($image[0])): ?>
            <li>
                <img src="<?php esc_attr_e($image[0]) ?>" alt="">
                
                <?php if($tmp_post->post_excerpt || $tmp_post->post_content): ?>
                <p class="caption">
                    <strong><?php esc_html_e($tmp_post->post_excerpt) ?></strong>
                    <?php esc_html_e($tmp_post->post_content) ?>
                </p>
                <?php endif; ?>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>


<div class="adverts-single-box">
    <div class="adverts-single-author">
        <div class="adverts-single-author-avatar">
            <?php echo get_avatar( get_post_meta($post_id, 'adverts_email', true), 48 ) ?>
        </div>
        <div class="adverts-single-author-name">
            <?php printf( __("by <strong>%s</strong>", "adverts"), get_post_meta($post_id, 'adverts_person', true) ) ?><br/>
            <?php printf( __('Published: %1$s (%2$s ago)', "adverts"), date_i18n( get_option( 'date_format' ), get_post_time( 'U', false, $post_id ) ), human_time_diff( get_post_time( 'U', false, $post_id ), current_time('timestamp') ) ) ?>
        </div>
    </div>
    
    <?php if( get_post_meta( $post_id, "adverts_price", true) ): ?>
    <div class="adverts-single-price" style="">
        <span class="adverts-price-box"><?php echo adverts_price( get_post_meta( $post_id, "adverts_price", true) ) ?></span>
    </div>
    <?php endif; ?>
</div>

<div class="adverts-grid adverts-grid-closed-top adverts-grid-with-icons adverts-single-grid-details">
    <?php $advert_category = get_the_terms( $post_id, 'advert_category' ) ?>
    <?php if(!empty($advert_category)): ?> 
    <div class="adverts-grid-row ">
        <div class="adverts-grid-col adverts-col-30">
            <span class="adverts-round-icon adverts-icon-tags"></span>
            <span class="adverts-row-title"><?php _e("Category", "adverts") ?></span>
        </div>
        <div class="adverts-grid-col adverts-col-65">
            <?php foreach($advert_category as $c): ?> 
            <a href="<?php esc_attr_e( get_term_link( $c ) ) ?>"><?php echo join( " / ", advert_category_path( $c ) ) ?></a><br/>
            <?php endforeach; ?>
        </div>
    </div>        
    
    <?php endif; ?>
        
    <?php if(get_post_meta( $post_id, "adverts_location", true )): ?>
    <div class="adverts-grid-row">
        <div class="adverts-grid-col adverts-col-30">
            <span class="adverts-round-icon adverts-icon-location"></span>
            <span class="adverts-row-title"><?php _e("Location", "adverts") ?></span>
        </div>
        <div class="adverts-grid-col adverts-col-65">
            <?php esc_html_e( get_post_meta( $post_id, "adverts_location", true ) ) ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php do_action( "adverts_tpl_single_details", $post_id ) ?>
</div>

<div class="adverts-content">
    <?php echo $content ? $content : apply_filters("the_content", get_post( $post_id )->post_content) ?>
</div>

<div class="adverts-single-actions">
    <a href="#" class="adverts-button adverts-show-contact" data-id="<?php echo $post_id ?>">
        <?php esc_html_e("Show Contact Information", "adverts") ?>
        <span class="adverts-icon-down-open"></span>
    </a>
    <span class="adverts-loader adverts-icon-spinner animate-spin"></span>
</div>

<div class="adverts-contact-box">

    <p class="adverts-contact-method">
        <span class="adverts-icon-phone adverts-contact-icon" title="<?php _e("Phone", "adverts") ?>"></span>
        <span class="adverts-contact-phone"></span>
    </p>

    <p class="adverts-contact-method">
       <span class="adverts-icon-mail-alt adverts-contact-icon" title="<?php _e("Email", "adverts") ?>"></span>
       <span class="adverts-contact-email"></span>
    </p>
</div>
