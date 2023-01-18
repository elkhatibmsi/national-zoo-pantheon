(function ($, Drupal) { 

    function youtube_parser(url){
        var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
        var match = url.match(regExp);
        return (match&&match[7].length==11)? match[7] : false;
    }
    $('document').ready(function(){
        const LoopingVideo = $('.looping-video iframe');
        let YTid = youtube_parser($('.looping-video').attr('data-source'));
        console.log(YTid);
        LoopingVideo.attr('src', `https://www.youtube.com/embed/${YTid}?controls=0&wmode=opaque&controls=0&loop=1&mute=1&autoplay=1&showinfo=0&enablejsapi=1&playlist=${YTid}&rel=0`);
    
    });
    
    })(jQuery, Drupal);