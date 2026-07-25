/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

import JoomlaDialog from 'joomla.dialog';

/**
 * Generic web component for a modal-select field.
 *
 * Wraps field elements like joomla-field-fancy-select, select or input and a browse button.
 * On button click it opens a JoomlaDialog and writes the picked value into the inner field.
 *
 * Two modes:
 *   - inline (preferred for bounded lists): set `content` to a selector for an in-page element
 *     (typically a <template> rendered by the shared selectiongallery layout). The dialog clones that
 *     content (popupType="inline", no iframe); a card carrying data-select-value / data-select-title is
 *     picked same-document — no postMessage.
 *   - iframe (fallback for large/paginated lists): set `modal-url`. Selection arrives via the
 *     `joomla:content-select` postMessage protocol.
 *
 * Attributes (all optional):
 *   content      – selector for prerendered in-page content -> inline dialog
 *   modal-url    – iframe src for the picker modal (used when `content` is absent)
 *   modal-title  – dialog header text
 *
 * Dispatched events:
 *   joomla-field-modal-select:open  – before dialog opens; cancelable
 *   change                          – on the component after selection; detail: { value, title }
 */
class JoomlaFieldModalSelect extends HTMLElement {
  constructor() {
    super();
    this.onSelectClick = this.onSelectClick.bind(this);
  }

  connectedCallback() {
    this.buttonSelect = this.querySelector('[data-button-action="select"]');
    // Support fancy-select, plain <select>, or plain <input>
    this.fieldEl = this.querySelector('joomla-field-fancy-select')
      || this.querySelector('select')
      || this.querySelector('input');

    if (this.buttonSelect) {
      this.buttonSelect.addEventListener('click', this.onSelectClick);
    }
  }

  disconnectedCallback() {
    if (this.buttonSelect) {
      this.buttonSelect.removeEventListener('click', this.onSelectClick);
    }
    if (this.dialog) {
      this.dialog.close();
      this.dialog = null;
    }
  }

  get content() { return this.getAttribute('content') || ''; }

  get modalUrl() { return this.getAttribute('modal-url') || ''; }

  get modalTitle() { return this.getAttribute('modal-title') || ''; }

  /**
   * Write the picked value into the inner field and notify listeners.
   * @param {string} value
   * @param {string} title
   */
  applySelection(value, title) {
    const val = String(value || '');

    if (this.fieldEl) {
      this.fieldEl.value = val;
      // fancy-select (Choices.js) does not fire native change on programmatic set;
      // dispatch on the inner <select> so the framework stays reactive.
      const eventTarget = this.fieldEl.tagName === 'JOOMLA-FIELD-FANCY-SELECT'
        ? (this.fieldEl.querySelector('select') ?? this.fieldEl)
        : this.fieldEl;
      eventTarget.dispatchEvent(new CustomEvent('change', { bubbles: true, cancelable: true }));
    }

    this.dispatchEvent(new CustomEvent('change', {
      detail: { value: val, title: title || '' },
      bubbles: true,
    }));
  }

  onSelectClick() {
    if (this.dialog) return;
    if (!this.content && !this.modalUrl) return;

    // Allow consumers to cancel before the dialog opens
    const openEvent = new CustomEvent('joomla-field-modal-select:open', {
      bubbles: true,
      cancelable: true,
    });
    if (!this.dispatchEvent(openEvent)) return;

    const dialog = this.content
      ? new JoomlaDialog({ popupType: 'inline', src: this.content, textHeader: this.modalTitle })
      : new JoomlaDialog({ popupType: 'iframe', src: this.modalUrl, textHeader: this.modalTitle });
    dialog.show();

    let cleanup;

    if (this.content) {
      // Inline mode: the picker content lives in this document, react to card clicks directly.
      const onPick = (event) => {
        const card = event.target.closest('[data-select-value]');
        if (!card) return;
        event.preventDefault();
        this.applySelection(
          card.getAttribute('data-select-value'),
          card.getAttribute('data-select-title') || '',
        );
        dialog.close();
      };
      dialog.getBody().addEventListener('click', onPick);
      cleanup = () => dialog.getBody().removeEventListener('click', onPick);
    } else {
      // Iframe mode: selection is delivered by the picker page via postMessage.
      const msgListener = (event) => {
        if (event.origin !== window.location.origin) return;
        const { messageType, id, title } = event.data;

        if (messageType === 'joomla:content-select') {
          this.applySelection(id, title);
          dialog.close();
        } else if (messageType === 'joomla:cancel') {
          dialog.close();
        }
      };
      window.addEventListener('message', msgListener);
      cleanup = () => window.removeEventListener('message', msgListener);
    }

    dialog.addEventListener('joomla-dialog:close', () => {
      if (cleanup) cleanup();
      dialog.destroy();
      this.dialog = null;
      if (this.buttonSelect) this.buttonSelect.focus();
    });

    this.dialog = dialog;
  }
}

customElements.define('joomla-field-modal-select', JoomlaFieldModalSelect);
