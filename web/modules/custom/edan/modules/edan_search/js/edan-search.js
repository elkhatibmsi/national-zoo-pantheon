(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.edan_search = {
    attach: function attach(context) {

      $( ".facets.accordion" ).accordion({
          active: false,    // collapse all by default
          animate: false,   // disable animation
          collapsible: true
      });

      // if (history.pushState) {
      //   var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?myNewUrlQuery=1';
      //   window.history.pushState({path:newurl},'',newurl);
      // }

      // var currentUserID = parseInt(drupalSettings.user.uid, 10);
      // $('[data-comment-user-id]').filter(function () {
      //   return parseInt(this.getAttribute('data-comment-user-id'), 10) === currentUserID;
      // }).addClass('by-viewer');

    } // attach
  }; // Drupal.behaviors.edan_search
})(jQuery, Drupal, drupalSettings);
