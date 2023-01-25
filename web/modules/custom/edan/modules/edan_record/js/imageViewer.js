(function ($) {
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

  };

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
  loadMedia = function(media, viewerSettings, context) {
    const container = $(".edan-viewer-container", context);
    let output = "",
      has_ids = media.has_ids;
//    console.log(media);
    container.removeClass("no-ids").removeClass("has-ids").addClass("no-ids").addClass("type--"+media.type);
    $('.media-metadata',context).addClass(media.usage.class);

    viewerSettings.tileSources = {};
    $("#edan-viewer").html("");
    // $(".viewer-other", context).html("");
    if (media.type === "3d_package") {
      let winHeight =  Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
      winHeight = Math.round(winHeight * .7);
      output = "<iframe width=\"100%\" height=\""+ winHeight +"\" frameborder=\"0\" style=\"border:0\" allowfullscreen title=\"3d model for " + media.title +"\" src=\"" + media.content +"\"></iframe>";
      output +=  media.caption.length > 0 ? "<div class=\"caption\">" + media.caption +"</div>" : "";
      $(".viewer-other", context).html(output);
    }
    else {
      if(has_ids === true) {
        const data = getJson(media.idsId);
        if (data.status === 200) {
          const image = data.responseJSON;
          if (image.Image.Size.Width > 500) {
            container.removeClass("no-ids");
            viewerSettings.tileSources = image;
            viewer = OpenSeadragon(viewerSettings);
            container.removeClass("no-ids").addClass("has-ids");
          }else {
            has_ids = false;
          }
        }
      }

      if (has_ids === false) {
        output = "<div><img typeof=\"foaf:Image\" src=\"" + media.content + "\" alt=\"" + media.alt_text + "\" /></div>";
        $(".viewer-other", context).html(output);

      }
    }
   // Drupal.attachBehaviors( context);
  };

  /**
   * Function that updates OpenSeaDragon
   */
  processViewerPage = function( idx, viewerSettings, context ) {

    // Get the current image from the slider
    const divSwap = $( ".media-slider", context ).find("[data-slick-index=\"" + idx +"\"]"),
      image = $("img", divSwap),
      edanInfo = image.data("edan"),
      usage = edanInfo.usage;
    // If we"re already on this slide, stop here; otherwise, remove the "selected" class from the old current slide
    if ( $( divSwap ).hasClass( "active" ) ) {
      return;
    } else {
      $(".media-slider .slide", context).removeClass("active");
    }

    divSwap.addClass("active");
    $(".media-metadata", context).attr("class", "media-metadata " + usage);
    // /* Change to current image; reset orientation */
    //viewer.viewport.setRotation( 0 );
    loadMedia(edanInfo, viewerSettings, context);
    edanMetadata(edanInfo, context);
    // Update meta information
    //
    //
    // // Make sure the slider matches what"s in OpenSeaDragon
    $( ".media-slider .slick > .slick__slider" ).slick( "slickGoTo", idx );

  }; // end function processViewerPage



  Drupal.behaviors.imageViewer = {
    attach: function (context, settings) {
      const mediaContainer = $("#edan-viewer", context),
        edanInfo = mediaContainer.data("edan");
      if (mediaContainer.length && typeof edanInfo !== "undefined") {
        edanMetadata(edanInfo, context);
        mediaContainer.attr("data-edan","");
        edanPopover(context);
      }
      if (typeof settings["edanViewer"] !== "undefined") {
        // imageViewer = function () {
        const initSlide = $("img[data-index=\"" + settings["edanViewer"]["delta"] +"\"]").first().parents(".slide"),
          winHeight =  Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
          winWidth =   $(window).width(),
          slides = $(".slide", context).length,
         // slideH = slides > 0 ? $(".media-slider", context).outerHeight() : 0,
          lastSlide = slides > 0 ? $(".slide", context).last().data("slick-index") : 0,
          //mediaHeight = winHeight - (slideH + 120),
          mediaHeight = Math.round(winHeight * .8),
          mcAnchors = $(".media-slider .slide", context),
        initImage = settings["edanViewer"]["initImage"];
        let viewerSettings = typeof settings["edanViewer"].viewer !== "undefined" ? settings["edanViewer"].viewer : {},
          mediaText = "";
        viewerSettings = $.extend(defaults, viewerSettings);
        if ($(".slick-list", context).length) {
          $(".media-slider .slick-list", context).attr("aria-live", "polite");
          initSlide.addClass("active");
          $( ".media-slider .slick > .slick__slider" ).slick( "slickGoTo", settings["edanViewer"]["delta"]);
        }
        $(".media-slider .slick-list", context).attr("aria-live", "polite");

        if (winWidth < 680) {
          $(".media-metadata", context).removeClass("mobile").addClass("mobile");
          mediaText = "<img typeof=\"foaf:Image\" src=\"" + $("#edan-viewer", context).data("url") + "\" alt=\"" + $("#edan-viewer", context).data("alt") + "\" />";
          if ($(".media-slider", context).length !== 0) {
              mcAnchors.click(function (e) {
              let $this = $(this, context),
                image = $this.find("img"),
                edanInfo = image.data("edan"),
                usage = edanInfo.usage,
                idsId = edanInfo.idsid,
                mediaSrc = edanInfo.url,
                viewer = edanInfo.expand,
                mediaType = edanInfo.type,
                caption = edanInfo.text,
                altText = image.attr("alt");
              mediaContainer = $("#edan-viewer", context);
              if ($this.hasClass("active")) {
                return "";
              } else {
                mcAnchors.removeClass("active");
                mediaContainer.fadeTo(300, 0);
                mediaText = "<img typeof=\"foaf:Image\" src=\"" + edanInfo.url + "\" alt=\"" + altText + "\" />";
                $("#edan-viewer", context).html(mediaText);
                //console.log(mediaText);
                edanMetadata(edanInfo, context);
                $(".media-metadata", context).attr("class", "media-metadata " + usage);
                $this.addClass("active");
                mediaContainer.fadeTo(300, 1);

              }
            });
          }

          // $("#edan-viewer", context).html(mediaText);

        }
        else {
          $(".edan-viewer-container",context).once("edanViewer", function () {
            $("#edan-viewer", context).height(mediaHeight);
            $(".viewer-other", context).height(mediaHeight);

            loadMedia(initImage, viewerSettings, context);

//           }
            if ($(".media-slider", context).length !== 0) {

              mcAnchors.click(function (e) {
                let $this = $(this, context),
                  image = $this.find("img"),
                  newIndex = parseInt(image.attr("data-index"));
                $(".edan-viewer-container", context).data("index", newIndex);
                processViewerPage( newIndex, viewerSettings, context );
                //edanMetadata(image, context);
              });
              mcAnchors.keypress(function(event) {
                if (event.key === "Enter") {
                  let $this = $(this, context),
                    image = $this.find("img"),
                    newIndex = parseInt($this.data("slick-index"));

                  processViewerPage( newIndex, viewerSettings, context );
                }
              });
            }
            // reset orientation when home is pressed
            $(".icon--home", context).click(function (e) {
              viewer.viewport.setRotation( 0 );
            });
            $("#zoom-previous", context).click(function (e) {
                const index = parseInt($(".slide.active", context).data("slick-index")),
                  newIndex = index === 0 ? lastSlide : index - 1;
                 processViewerPage( newIndex, viewerSettings, context );
            });
            $("#zoom-next", context).click(function (e) {
              const index = parseInt($(".slide.active", context).data("slick-index")),
                  newIndex = index === lastSlide ? 0 : index + 1;
                processViewerPage( newIndex, viewerSettings, context );
            });
          });
        }
      }




    }
  };
})(jQuery);





