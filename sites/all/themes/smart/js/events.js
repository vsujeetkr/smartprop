// jQuery(function($){
// 	jQuery( ".view-testimonials .owl-carousel .owl-wrapper .owl-item:nth-child(2)").addClass("middle");
// 	jQuery( ".view-testimonials .owl-carousel .owl-wrapper .owl-item:nth-child(5)").addClass("middle");
// });

(function($) {

  Drupal.behaviors.mh_custom = {
    attach : function(context, settings) {
      var callbacks = {autoplay: true, center: true, loop: true, nav: true,};
      for (var carousel in settings.owlcarousel) {
        if (carousel === 'owl-carousel-block_14') {

          var abc = $.extend(true, settings.owlcarousel[carousel].settings, callbacks);
          console.log(abc);
        }
      }
    }
  };

 // /**
 //   * Owl Carousel afterInit callback.
 //   */
 //  function afterOWLinit(elem) {
	// elem.owlCarousel({
 //  		center:true,
 //  	});
 //  }
}(jQuery));