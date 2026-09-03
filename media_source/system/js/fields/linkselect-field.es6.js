/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

import openLinkPicker from 'link-picker';

// Delegated, so it also covers markup that is not rendered by the field layout
document.addEventListener('click', async (event) => {
  const button = event.target.closest('.js-linkselect-field-select');

  if (!button) {
    return;
  }

  event.preventDefault();

  const field = button.closest('.js-linkselect-field');
  const input = field ? field.querySelector('.js-linkselect-field-input') : null;

  if (!input) {
    return;
  }

  const result = await openLinkPicker();

  if (!result || !result.url) {
    return;
  }

  input.value = result.url;
  input.dispatchEvent(new CustomEvent('change', { bubbles: true, cancelable: true }));
  input.focus();
});
