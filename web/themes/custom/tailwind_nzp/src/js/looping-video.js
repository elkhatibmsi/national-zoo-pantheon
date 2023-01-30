(function ($, Drupal) { 

    function youtube_parser(url){
        var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
        var match = url.match(regExp);
        return (match&&match[7].length==11)? match[7] : false;
    }

    function vidRescale($vid) {
        let w = $vid.parent().width(),
          h = $vid.parent().parent().height();
          console.log(w);
        if (w / h > 16 / 9) {
          $vid.attr("width", w);
          $vid.attr("height", h + 200);
          $vid.css({ "margin-left": "0px" });
        } else {
          $vid.attr("width", (h / 9) * 16);
          $vid.attr("height", h);
          $vid.css({ left: -($vid.outerWidth() - w) / 2 });
        }
      }

    $('document').ready(function(){
        const LoopingVideo = $('.looping-video iframe');
        vidRescale(LoopingVideo);
        let YTid = youtube_parser($('.looping-video').attr('data-source'));
        console.log(YTid);
        LoopingVideo.attr('src', `https://www.youtube.com/embed/${YTid}?controls=0&wmode=opaque&controls=0&loop=1&mute=1&autoplay=1&showinfo=0&enablejsapi=1&playlist=${YTid}&rel=0&modestbranding=1`);
    
    });
    
    })(jQuery, Drupal);