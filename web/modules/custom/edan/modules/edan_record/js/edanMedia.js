(function ($, Drupal, drupalSettings) {

  modalInit = function (trigger) {
    var winH = Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
      viewH = Math.floor(winH);

    trigger.click(function (e) {
      // onMobile(function(){
      //   e.stopPropagation();
      //   return
      // });
      if ($(window).width() < 600) {
        e.stopPropagation();
        return;
      }
      else {
        const $this = $(this);
        $this.removeData();

        const  theModal = $this.data("target"),
          viewerSRC = $this.data("source");
        $("#viewerModal .modal-dialog").height(viewH);
        $(theModal + " iframe").attr("src", viewerSRC).attr("height", viewH + "px");
      }

    });
  }
  edanMetadata = function (toolbar) {
    toolbar.removeData();
    const idsId = toolbar.data('idsid'),
      type = toolbar.data('type');
    toolbar.removeClass('has-iiif').removeClass('media--no-openaccess').removeClass('has-download');

    // if (typeof idsId !== undefined) {
    if (typeof idsId !== undefined && idsId.length) {
      $.getJSON('https://ids.si.edu/ids/media_view', {
        id: idsId,
        format: "json"
      })
        .done(function (data) {
          let usage = '';
          $.each(data, function (index, value) {
            switch (index) {
              case 'downloads':
                if (value.length > 0) {
                  let text = '<ul>';
                  $.each(value, function(i, element) {
                    text += '<li><a href="' + element.url +'">' + element.label + '</a></li>';
                  });
                  text +='</ul>';
                  $('[id^="tab-download-"]', toolbar).html(text);
                  toolbar.addClass('has-download');
                }
                break;
              case 'iiif':
                $('.iiif-data .manifest', toolbar).attr("href", value.manifest);
                $('.iiif-data .miradorViewer', toolbar).attr('href', value.miradorViewer);
                toolbar.addClass('has-iiif');
                break;
              case 'usage':
                usage += '<div class="usage--conditions">' + value.description + '</div>';

                if (type === '3d_package') {
                  usage += '<div class="extra--3d">We also suggest that users:<ul><li>Give attribution to the Smithsonian.</li><li>Contribute back any modifications or improvements.</li><li>Do not mislead others or misrepresent the datasets or its sources. </li><li>Be responsible.</li> </ul></div>';
                }
                if (typeof  value.restrictions !== 'undefined' ) {
                  usage += '<div class="extra">' + value.restrictions + '</div>';
                }
                if (value.id !== 'cc0') {
                  toolbar.addClass('media--no-openaccess');
                }
                break;
            }
          });
          $('.usage', toolbar).html(usage);
        });
    }
  };
  edanPopover = function (context) {
    $('.btn-popover', context).each(function() {
      var popoverElement = $(this).parent().data('popover-element');
      var popoverTarget = $(this).data('target');
      $(this).popover({
        // trigger: 'focus',
        content: function () {
          return $(popoverTarget).html();
        },
        container: '[data-popover-element=' + popoverElement +']',
        html: true
      });
    });
    $('body').on('click', function (e) {
      $('.btn-popover', context).each(function () {
        //the 'is' for buttons that trigger popups
        //the 'has' for icons within a button that triggers a popup
        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
          $(this).popover('hide');
        }
      });
    });
  };

  Drupal.behaviors.edanMedia = {
    attach: function (context) {
      const trigger = $(".modal-trigger", context);
      // Look for on shown.bs.modal
      $("#viewerModal", context).on("hidden.bs.modal", function (e) {
        $("#viewerModal" + " iframe").attr("src", "");
      });

//      /* always keep at least 1 tab open by preventing the current to close itself */
//      $('[data-toggle="collapse"]', context).on('click',function(e){
//        if ( $(this).parents('.tabWrapper').find('.collapse.show') ){
//          var idx = $(this).data('target');
//          if ($(idx).hasClass('show')) {
//            // prevent collapse
//            e.stopPropagation();
//          }
//        }
//      });
      if (typeof drupalSettings.edanMediaelement !== undefined) {
        $.each(drupalSettings.edanMediaelement, function (key, options) {
          $(key, context).mediaelementplayer(options);
        });
      }
      $('.media-metadata', context).each(function () {
        edanMetadata($(this));
      });

      edanPopover(context);

      trigger.each(function () {
        modalInit($(this));
      });
    } // attach
  }; // Drupal.behaviors.edanMedia
})(jQuery, Drupal, drupalSettings);
