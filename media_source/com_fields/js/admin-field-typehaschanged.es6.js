/**
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
((Joomla, document) => {
  'use strict';

  /**
   * Reload the field edit form so the parameters of the newly selected type are rendered.
   * @param {HTMLFormElement} form
   */
  const reload = (form) => {
    if (!form) {
      return;
    }

    // Display the loading indication
    document.body.appendChild(document.createElement('joomla-core-loader'));

    const task = form.querySelector('input[name=task]');

    if (task) {
      task.value = 'field.reload';
    }

    form.submit();
  };

  const onReady = () => {
    document.querySelectorAll('joomla-field-modal-select[data-field="type"]').forEach((picker) => {
      picker.addEventListener('change', (event) => {
        // React only to the component's own selection event, not the inner field's bubbled change
        // (which shares the same event name but has the input as its target).
        if (event.target !== picker) {
          return;
        }

        reload(picker.closest('form'));
      });
    });
  };

  // Backward compatibility: keep the global for any override still invoking it from markup.
  Joomla.typeHasChanged = (element) => reload(element.form);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})(Joomla, document);
