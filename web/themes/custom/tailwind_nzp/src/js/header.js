(function ($, Drupal) { 
    $(window).scroll(function () {
        const header = $('.page--header');
        const megaMenu = $('.mega-menu');
        const logo = $('.site-logo--inner');
        const alert = $('.topbar .site-alert');
        if(alert) {
            $('.topbar .site-alert').hide();
        }
        if($(document).scrollTop() > 100) {
            header.addClass('scrolled');
            logo.addClass("lg:relative lg:top-[2.2em] lg:z-10");
        } else {
            if(alert) {
                $('.topbar .site-alert').show();
            }
            header.removeClass('scrolled');
            logo.removeClass("lg:relative lg:top-[2.2em] lg:z-10");
        }


    });
})(jQuery, Drupal);