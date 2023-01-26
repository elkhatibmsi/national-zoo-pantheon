(function ($, Drupal, drupalSettings) {

  'use strict';

  // Default plugin properties.
  var defaults = {
    // The URL path for the OpenSeadragon buttons. May either be relative or
    // absolute, local or external.
    buttonPrefixUrl: "//openseadragon.github.io/openseadragon/images/",
    // If the full page control is shown.
    fullPageControl: true,
    // Where the navigation controls are positioned.
    // @see https://openseadragon.github.io/docs/OpenSeadragon.html#.ControlAnchor
    navigationControlPosition: "BOTTOM_RIGHT",
    // Where the navigator is positioned.
    // @see https://openseadragon.github.io/docs/OpenSeadragon.html#.Options
    navigatorPosition: "BOTTOM_LEFT",
    // If the rotation control is shown.
    rotationControl: true,
    // A callback to run after the OpenSeadragon container has been initialized.
    onComplete: false,
  },
    viewer = {},
    viewerSettings = {};

  function getJson(idsId) {
    return $.ajax({
      type: "GET",
      url: "https://ids.si.edu/ids/dynamic?openseadragon&id=" + idsId,
      dataType: "json",
      global: false,
      async: false,
      success: function (data) {
        return  data;
      }
    });
  }
  function loadMedia(media, context) {
    const container = $(".edan-viewer-container", context),
      winHeight =  Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
      mediaHeight = Math.round(winHeight * .75);
    let output = "";
    //     use_image = $(window).width() <= 680;
    // console.log( $(window).width(), 'width');
    // console.log(use_image, 'image');
    container.removeClass("has-3d");
    viewerSettings.tileSources = {};
    $("#edan-viewer").html("");
    $("#viewer-other", context).html("");
    if (media.type === "3d_package") {
      output += "<iframe width=\"100%\" height=\""+ mediaHeight +"\" frameborder=\"0\" style=\"border:0\" allowfullscreen title=\"3d model for " + media.alt_text +"\" src=\"" + media.content +"\"></iframe>";
      output +=  media.caption.length > 0 ? "<div class=\"caption\">" + media.caption +"</div>" : "";
      $("#viewer-other", context).html(output);
      container.addClass("has-3d");
    }
    else {
     // if(use_image === false) {
        const data = getJson(media.idsId);
        if (data.status === 200) {
          const image = data.responseJSON;
          if (image.Image.Size.Width > 500) {
            viewerSettings.tileSources = image;
            viewer = OpenSeadragon(viewerSettings);
          }
          //else {
          //   use_image = true;
          // }
        }
    //  }
      // console.log(use_image, 'use image');
      //
      // if (use_image === true) {
      //   output = "<div><img typeof=\"foaf:Image\" src=\"" + media.content + "\" alt=\"" + media.alt_text + "\" /></div>";
      //   console.log(output);
      //   $("#edan-viewer", context).html(output);
      // }
    }
  }

  /**
   * Function that updates OpenSeaDragon
   */
  function processViewerPage( idsId,context, media = {}  ) {

    if (drupalSettings.edanViewer['hasSlideshow'] === true) {
      const image = $('img[data-idsid="' + idsId +'"]',context),
            idx = image.parents(".slide").data("slick-index"),
            slideClass = idx === 0 ? 'first' : '';
      $(".edan-viewer-container", context).removeClass('first').addClass(slideClass);
      slickActive(idx, context);
      if ($.isEmptyObject(media) === true) {
        const toolbar = $(".media-metadata", context);
        toolbar.attr('data-idsid', idsId).attr('data-type', image.data("type"));
        edanMetadata(toolbar);
        media = {
          idsId: idsId,
          type: image.data("type"),
          content: image.data("content"),
          caption: image.data("caption"),
          alt_text: image.attr("alt")
        };
      }
    }
    loadMedia(media, context);


  }; // end function processViewerPage


  /**
   * Attaches slick behavior to HTML element identified by CSS selector .slick.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.edanViewer = {
    attach: function (context) {
      $(".edan-viewer-container",context).parents('body').addClass('page--edan-viewer');
      $(".edan-viewer-container",context).once("edanViewer").each(function () {
        viewerSettings = typeof drupalSettings.edanViewer !== 'undefined' ? $.extend(defaults, drupalSettings.edanViewer['settings']) : defaults;
        // resizeViewer(context);
        if (typeof drupalSettings.edanViewer !== 'undefined') {
          const winHeight =  Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
          mediaHeight = Math.round(winHeight * .75);

          $("#edan-viewer", context).height(mediaHeight);
          $("#viewer-other", context).height(mediaHeight);

          const media = drupalSettings.edanViewer['media'],
            idsId = media['idsId'];
          if (drupalSettings.edanViewer['hasSlideshow'] === true) {
            const lastSlide = $(".slide", context).last().data("slick-index"),
              mcAnchors = $(".media-slider .slide", context);
            processViewerPage(idsId, context, media);

            mcAnchors.click(function (e) {
              const image = $(this, context).find("img"),
                idsId = image.data('idsid');

              processViewerPage(idsId, context);
            });
            mcAnchors.keypress(function (event) {
              if (event.key === "Enter") {
                const image = $(this, context).find("img"),
                  idsId = image.data('idsid');

                processViewerPage(idsId, context);
              }
            });
            $("#zoom-previous", context).click(function (e) {
              const index = parseInt($(".slide.active", context).data("slick-index"));
              if (index !== 0) {
                const newIndex = index - 1,
                  image = $('div[data-slick-index="' + newIndex + '"]', context).find('img'),
                  idsId = image.data('idsid');
                processViewerPage(idsId, context);
              }

            });
            $("#zoom-next", context).click(function (e) {
              const index = parseInt($(".slide.active", context).data("slick-index")),
                newIndex = index === lastSlide ? 0 : index + 1,
                image = $('div[data-slick-index="' + newIndex + '"]', context).find('img'),
                idsId = image.data('idsid');

              processViewerPage(idsId, context);
            });

          } else {
            loadMedia(media, context);
          }
          // reset orientation when home is pressed
          $(".icon--home", context).click(function (e) {
            viewer.viewport.setRotation(0);
          });
        }

      });

      // // Runs function once on window resize.
      // var TO = false;
      // $(window).resize(function () {
      //   if (TO !== false) {
      //     clearTimeout(TO);
      //   }
      //
      //   // 200 is time in miliseconds.
      //   TO = setTimeout(resizeViewer, 100);
      // }).resize();
    }
  };

})(jQuery, Drupal, drupalSettings);

