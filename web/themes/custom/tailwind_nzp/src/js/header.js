(function ($, Drupal) { 
    $(window).scroll(function () {
        const header = $('header');
        const megaMenu = $('.mega-menu');
        const logo = $('.site-logo--inner');

        if($(document).scrollTop() > 500) {
            header.addClass('scrolled');
            logo.addClass("lg:relative lg:top-[2.2em] lg:z-10");
        } else {
            header.removeClass('scrolled');
            logo.removeClass("lg:relative lg:top-[2.2em] lg:z-10");
        }


    });
})(jQuery, Drupal);