/**
 * @file
 * Attaches uniqueness behaviors to the entity form.
 */
(($, Drupal) => {

  "use strict";

  Drupal.behaviors.uniquenessFieldsetSummaries = {
    attach: (context) => {
      $(context).find('.uniqueness-fieldset').drupalSetSummary((context) => {
        let summary = '';
        let enabledConfig = [];

        const checkboxes = $(context).find('input:checkbox:checked[data-uniqueness-label][value="1"]');
        // console.log(checkboxes);
        checkboxes.each((_i, checkbox) => {
          enabledConfig.push(checkbox.getAttribute('data-uniqueness-label'));
        });

        if (enabledConfig.length > 0) {
          summary = Drupal.t('Check for uniqueness when @op', {
            '@op': enabledConfig.join(Drupal.t(' and ')),
          });
        } else {
          summary = Drupal.t('No checks for uniqueness');
        }

        return summary;
      });
    }
  };

})(jQuery, Drupal);
