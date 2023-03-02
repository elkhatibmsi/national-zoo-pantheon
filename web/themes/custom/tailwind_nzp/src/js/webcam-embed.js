window.onload = () => {

  const container = document.querySelectorAll(".hls-container");  
        //for each container instantiated...  
for (item of container) {

  let config = {
    autoStartLoad: true
    // backBufferLength: 3000,
    // maxBufferSize: 60 * 1000 * 1000,
    // maxBufferHole: 0.5,
    // maxLoadingDelay: 5,
    // initialLiveManifestSize: 3,
    // fragLoadingMaxRetry: 10,
    // manifestLoadingMaxRetry: 10,
    // levelLoadingMaxRetry: 10,
    // lowLatencyMode: true
  };

  let source = item.getAttribute('data-src');
  let vid = item.getAttribute('id');
  console.log(vid);

  const webcam = new Hls(config);
  webcam.loadSource(source);
  webcam.attachMedia(item);

  let timeout = item.getAttribute("data-timeout") * 6000;
  const modal = document.querySelector('.timeout-msg');
  const close_modal = document.querySelector(`.timeout-close`);

  // load timeout message when timeout is reached.
  item.onplay = function () {
    let $this = this;
    let timedout = setTimeout(async function () {
      $this.pause();
      modal.style.display='flex';
    }, timeout);

    item.onpause = function () {
      clearTimeout(timedout);
    };
      close_modal.addEventListener("click", () => {
        modal.style.display='none';
      });
    
  };
}

        // //pause video inside inactive tab
        // $('a[data-toggle="tab"]').on("click", function () {
        //   let video = $(".tab-pane.active video");
        //   if (video && !video.paused) {
        //     video.get(0).pause();
        //   }
        // });
};



  