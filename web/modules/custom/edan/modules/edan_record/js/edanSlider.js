(function ($, Drupal) {

  /**
   * Function that updates OpenSeaDragon
   */
   slickActive = function (idx, context ) {
    // Get the current image from the slider
    const divSwap = $( ".media-slider", context ).find("[data-slick-index=\"" + idx +"\"]");
      // const usage = edanInfo.usage;
    // If we"re already on this slide, stop here; otherwise, remove the "selected" class from the old current slide
    if ( $( divSwap ).hasClass( "active" ) ) {
      return;
    } else {
      $(".media-slider .slide", context).removeClass("active");
    }

    divSwap.click();

    // Make sure the slider matches what"s in OpenSeaDragon
    $( ".media-slider .slick > .slick__slider" ).slick( "slickGoTo", idx );

  }; // end function processViewerPage

  edanSlick = function (slide) {
    if (slide.hasClass('active')) {
      return '';
    } else {
      slide.siblings().removeClass('active');

      const mediaParent = slide.parents('.media-container'),
        image = slide.find('img'),
        media = image.length ? image : slide.find('.slide-data'),
        idsId = media.data('idsid'),
        mediaSrc = media.data('url'),
        viewer = media.data('viewer'),
        mediaType = media.data('type'),
        slideCaption = slide.find('.slide__caption');
        caption = mediaType === '3d_package' ? media.data('caption') : slideCaption.html();
        viewerMode = media.data('view-mode'),
        altText = media.attr('alt'),
        edanToolbar = $('.media-metadata', mediaParent),
        mediaContainer = mediaParent.children('.edan-media-item');

        let mediaClass = 'media-container type--' + mediaType,
        mediaText = '';

      if (typeof idsId !== undefined && idsId.length) {
        edanToolbar.attr('data-idsid', idsId).attr('data-type', mediaType);
        mediaClass += mediaType === '3d_package' ? ' no-ids' : ' has-ids';
      } else {
        mediaClass += ' no-ids';
      }
      edanToolbar.removeData();
      $('.btn-viewer', mediaParent).attr('data-source', viewer);

      mediaContainer.attr('class','b-lazy edan-media-item');
      if (mediaType === 'images') {
        mediaText +=  '<figure itemtype="http://schema.org/ImageObject">';
        mediaText += viewerMode === 'embed' ? '' : '<button class="modal-trigger image" data-toggle="modal" data-target="#viewerModal" data-source="' + viewer + '">';
        mediaText +=  '<div class="media-inner">';
        mediaText += '<img src="' + mediaSrc + '" alt="' + altText + '"   data-ids="' + idsId +'" />';
        mediaText +=  viewerMode === 'embed' ? '</span>' : '</div></button>';
        mediaText += typeof caption !== 'undefined' ? '<figcaption class="caption">' + caption + '</figcaption></figure>': '</figure>';
        mediaText += viewerMode === 'embed' ? '<div id="viewerContainer"><div class="media-inner"><iframe width="100%" height="100%" src="" id="viewerContainer"></iframe></div></div>' : '';
      }
      else {
        mediaText = '<figure class="wrapper--iframe"><iframe width="600" height="450" frameborder="0" style="border:0" allowfullscreen title="' + image.data('title') +'"';
        mediaText += 'src="'+ mediaSrc +'"></iframe>';
        mediaText += typeof caption !== 'undefined' ? '<figcaption>' + caption + '</figcaption>' : '';
        mediaText += '</figure>';
      }

      slide.addClass('active');
      mediaContainer.html(mediaText);
      $(".modal-trigger", mediaParent).each(function () {
        modalInit($(this));
      });
      setTimeout(function(){
        mediaContainer.addClass('b-loaded');
        mediaParent.attr('class', mediaClass);
      }, 300);
     // mediaParent.attr('class', mediaClass)
      Drupal.attachBehaviors( mediaParent);
    }
  }
  Drupal.behaviors.edanSlider = {
    attach: function attach(context) {
        $(".media-slider", context).once("edanSlider").each(function (){
          const slides = $(".slide", context).length,
            lastSlide = slides > 0 ? $(".slide", context).last().data("slick-index") : 0,
            mcAnchors = $('.edan-record .media-slider .slide', context);
          // set first slide as active
          if ($('.slick-list', context).length) {
            $('.slick-list', context).attr('aria-live', 'polite');
            $('.edan-record .media-slider .slick-active', context).first().addClass('active');
          } else {
            $('.edan-record .media-slider .slide', context).first().addClass('active');
          }

          // Add slide information before thumbnail in edanSlider.
          mcAnchors.each(function (index) {
            const slideInfoMessage = Drupal.t('Click to view slide @slide of @count', {'@slide': index + 1, '@count': slides});
            const slideInfo = $(`<span class="slide__info visually-hidden">${slideInfoMessage}</span>`);
            $(this).prepend(slideInfo);
          });
          mcAnchors.click(function (e) {
            edanSlick($(this), context);
          });
          mcAnchors.keypress(function(event) {
            if (event.key === 'Enter') {
              edanSlick($(this), context);
            }
          });

          $(".edan-media-item").on('swipe', function(event, slick, direction){
            if (direction === 'right') {
              const index = parseInt($(".slide.active").data("slick-index")),
                newIndex = index === 0 ? lastSlide : index - 1;
              slickActive( newIndex, context );
            }
            else {
              const index = parseInt($(".slide.active").data("slick-index")),
                newIndex = index === lastSlide ? 0 : index + 1;
              slickActive( newIndex, context );
            }
          });
        });
      } // attach
  }; // Drupal.behaviors.edanSlider
})(jQuery, Drupal);
