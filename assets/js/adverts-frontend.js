jQuery(function($) {
    var currentTallest = 0,
        currentRowStart = 0,
        rowDivs = new Array(),
        $el,
        topPosition = 0;

   $('.advert-post-title a').css('overflow', 'initial').css("height", 'auto');
   $('.advert-item .advert-link').each(function() {

    $el = $(this);
    // added closest(...)
    topPostion = $el.closest('.advert-item').position().top;

    if (currentRowStart != topPostion) {

      // we just came to a new row.  Set all the heights on the completed row
      for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
        rowDivs[currentDiv].height(currentTallest);
      }

      // set the variables for the new row
      rowDivs.length = 0; // empty the array
      currentRowStart = topPostion;
      currentTallest = $el.height();
      rowDivs.push($el);

    } else {

      // another div on the current row.  Add it to the list and check if it's taller
      rowDivs.push($el);
      currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);

   }

   // do the last row
    for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
      rowDivs[currentDiv].height(currentTallest);
    }
   
   }); 
   
   if( $(".rslides").length > 0 ) {
       $(".rslides").responsiveSlides({
        auto: false,
        pagination: true,
        nav: true,
        fade: 500,
        maxwidth: 800
      });
   }
   
    $(".adverts-show-contact").click(function(e) {
        
        e.preventDefault();
        
        $(".adverts-loader").show();
        //$(".adverts-loader").addClass("animate-spin");
        
        var data = {
            action: 'adverts_show_contact',
            security: 'nonce',
            id: $(this).data("id")
        };
        
        $.ajax(adverts_frontend_lang.ajaxurl, {
            data: data,
            dataType: 'json',
            type: 'post',
            success: function(response) {
                
                var phone = "\u2014";
                var email = "\u2014";
                
                if(response.phone) {
                    phone = $("<a></a>").attr("href", "tel:"+response.phone).text(response.phone);
                }
                
                if(response.email) {
                    email = $("<a></a>").attr("href", "mailto:"+response.email).text(response.email);
                }
                
                if(response.result == 1) {
                    $('.adverts-contact-phone').html(phone);
                    $('.adverts-contact-email').html(email);
                    $('.adverts-contact-box').slideToggle("fast");
                } else {
                    alert(response.error);
                }
                

                $('.adverts-loader').hide();
                //$(".adverts-loader").removeClass("animate-spin");
                
            }
        });
        

    });
    
    if($("#adverts_price").length > 0) {
        $("#adverts_price").autoNumeric('init', adverts_currency);
    }
    

    
});
