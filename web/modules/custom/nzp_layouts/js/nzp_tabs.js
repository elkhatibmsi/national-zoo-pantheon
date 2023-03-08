(function ($, Drupal) {

      //grab all Tab instances from the Dom. This will create an array of elements which we will loop over and generate Tabs
      const $ul = document.querySelectorAll(".tab-wrapper > ul");
      //The Forloop we use to loop over and convert all ul els into tabs.
      [].forEach.call($ul, ($el) => {
        $tabsId = $el.dataset.tabs;
        let $tabs = new Tabby(`[data-tabs="${$tabsId}"]`, {
          default: "[data-tabby-default]",
        });
      });

})(jQuery, Drupal);
