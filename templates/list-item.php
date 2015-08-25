    <div class="advert-item advert-item-col-<?php echo (int)$columns ?>">

        <?php $image = adverts_get_main_image( get_the_ID() ) ?>
        <div class="advert-img">
            <?php if($image): ?>
                <img src="<?php esc_attr_e($image) ?>" alt="" class="advert-item-grow" />
            <?php endif; ?>
        </div>
     
        <div class="advert-post-title">
            <span title="<?php esc_attr_e( get_the_title() ) ?>" class="advert-link"><?php the_title() ?></span>
            <a href="<?php the_permalink() ?>" title="<?php esc_attr_e( get_the_title() ) ?>" class="advert-link-wrap"></a>
        </div>
        
        <div class="advert-published ">
            
            <span class="advert-date"><?php echo date_i18n( get_option( 'date_format' ), get_post_time( 'U', false, get_the_ID() ) ) ?></span>
            
            <?php $price = get_post_meta( get_the_ID(), "adverts_price", true ) ?>
            <?php if( $price ): ?>
            <div class="advert-price"><?php esc_html_e( adverts_price( get_post_meta( get_the_ID(), "adverts_price", true ) ) ) ?></div>
            <?php endif; ?>
        </div>
        
        
    </div>