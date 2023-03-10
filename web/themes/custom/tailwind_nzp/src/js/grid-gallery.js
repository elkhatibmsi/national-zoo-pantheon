(function ($, Drupal) {

  $(document).ready(function () {

    new Swiper(".media-container", {
      slidesPerView: 1,
      centeredSlides: true,
      loop: false,
      navigation: {
        nextEl: ".swiper-next",
        prevEl: ".swiper-prev",
      },
      pagination: {
        el: '.grid-thumbnail-container',
        clickable: true,
        type: 'custom',
        bulletClass: 'grid-thumbnail-item',
        bulletActiveClass: 'active',

    }
  });
});

$('.grid-thumbnail-item').on('click', function(){
  $(this).addClass('active');

});

})(jQuery, Drupal);
