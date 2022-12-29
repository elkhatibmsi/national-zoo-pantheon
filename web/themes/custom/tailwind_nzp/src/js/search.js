(function ($, Drupal) {
    const toggleOnClick = (trigger, block) => {
        $(trigger).toggleClass('active');
        if ($(trigger).hasClass('active')) {
            $(block).addClass("slidein").removeClass('slideout');
            $(block + ' input[type="text"]').focus();
        } else {
            $(block).addClass('slideout').removeClass('slidein');
        }
        if($(block).hasClass("hidden")) {
            $(block).removeClass("hidden");
        }
    }

    Drupal.behaviors.search = {
        attach: function (context, settings) {
            $(document).ready(function () {
                //Move Search Toggle and Search Block to Menu Nav on responsive screens

                if ($(window).width() <= 960) {
                    $('.search--toggle-block').hide();
                    $('.search--block').prependTo(".nav-collapse");
                };
                if ($(window).width() > 960) {
                    $(".search--block").addClass("hidden");
                    $(".icon-search").on("click", function (e) {
                        toggleOnClick('.icon-search', '.search--block');
                        e.preventDefault();
                    });

                    $(document).keyup(function (e) {
                        if (e.key === "Escape") {
                            console.log(e);
                            toggleOnClick('.icon-search', '.search--block');
                        }
                    });
                }

            });
        }, // Drupal behaviors
    };
})(jQuery, Drupal);