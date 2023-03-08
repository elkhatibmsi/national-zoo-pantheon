(function ($, Drupal) {

  $(document).ready(function () {
    function OpenModal() {
      $('.modal-wrapper').addClass('flex').removeClass('hidden');
    }
  
    function CloseModal() {
      $('.modal-wrapper').removeClass('flex').addClass('hidden');
    }
 
    $('.modal-thumbnail-item img').on('click', function(){
      OpenModal();
    });

    $('.modal-close').on('click', function(){
      CloseModal();
    });

    $(document).on('keydown', function(e){
      if(e.keyCode == 27) {
        CloseModal();
      }
    });



    new Swiper(".modal-window", {
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
        el: '.modal-thumbnail-container',
        clickable: true,
        type: 'custom',
        bulletClass: 'modal-thumbnail-item',

    }
  });
});


})(jQuery, Drupal);
