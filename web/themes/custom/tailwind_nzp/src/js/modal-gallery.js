(function ($, Drupal) {
  $(document).ready(function () {

    $('.modal-window').hide();
    $('.thumbnail-item').on('click', function(){
      $('.modal-window').show();
    });

    const slider = new Swiper(".modal-window", {
      slidesPerView: 1,
      centeredSlides: true,
      observer: true,
      observeParents: true,
      loop: false,
      navigation: {
        nextEl: ".swiper-next",
        prevEl: ".swiper-prev",
      },
      pagination: {
        el: '.thumbnail-container',
        clickable: true,
        type: 'custom',
        bulletClass: 'thumbnail-item',

    }
  });
});


})(jQuery, Drupal);
