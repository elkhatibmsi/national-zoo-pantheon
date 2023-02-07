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

    }
  });
});


})(jQuery, Drupal);
