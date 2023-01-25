(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.edanTimeline = {
    attach: function (context) {
      once('edanTimeline', 'html', context).forEach( function (element) {
        if (typeof drupalSettings.timeline !== undefined) {
          const timelines = drupalSettings.timeline;
          const timelinesKeys = Object.keys(timelines);
          timelinesKeys.forEach((item) => {
            const timeline = timelines[item];
            if (typeof timeline.id !== undefined && typeof timeline.json !== undefined) {
              // Get and target id for each timeline on a page.
              const timelineId = `timeline-${timeline.id}`;
              window.timeline = new TL.Timeline(timelineId, timeline.json)
            }
          })
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
