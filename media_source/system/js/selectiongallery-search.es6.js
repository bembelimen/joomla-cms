/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Generic live-search for the shared selection gallery (layouts/joomla/selectiongallery).
 *
 * Replaces the per-consumer copies (com_modules admin-module-search, com_fields admin-field-select-search).
 * It filters `.js-selectiongallery-card` elements by their title/description and preserves group headers
 * by hiding a whole `.js-selectiongallery-group` only when all of its cards are hidden.
 *
 * It initialises every `.js-selectiongallery` container on DOMContentLoaded (full-page galleries) AND on
 * `joomla:updated` (galleries cloned into a JoomlaDialog with popupType="inline"), so the same code powers
 * the standalone select views and the inline modal picker. Search box is hidden until this runs
 * (progressive enhancement) and the remembered search is restored from sessionStorage per container.
 *
 * Accessibility: while typing, the visible/hidden card set changes silently, so a polite live region
 * (`.js-selectiongallery-status`) announces the running result count (or the "no results" text) to screen
 * readers. The input is only auto-focused inside the dialog (focusInput), never on full-page load.
 */

const HIDDEN = 'd-none';

const initGallery = (container, focusInput) => {
  if (!container || container.dataset.sgInit) {
    return;
  }
  container.dataset.sgInit = '1';

  const input = container.querySelector('.js-selectiongallery-input');
  const searchBox = container.querySelector('.js-selectiongallery-search');
  const header = container.querySelector('.js-selectiongallery-header');
  const results = container.querySelector('.js-selectiongallery-results');
  const alertEl = container.querySelector('.js-selectiongallery-alert');
  const statusEl = container.querySelector('.js-selectiongallery-status');
  const groups = Array.from(container.querySelectorAll('.js-selectiongallery-group'));
  const cards = Array.from(container.querySelectorAll('.js-selectiongallery-card'));
  const searchKey = container.dataset.searchKey || '';
  const statusTpl = container.dataset.statusTemplate || '';

  // Nothing to search (search box removed by an override) — leave the gallery as static markup.
  if (!input || !searchBox) {
    return;
  }

  // Announce the result count (or the "no results" text) to screen readers via the polite live region.
  const announce = (visibleCount) => {
    if (!statusEl) {
      return;
    }

    if (visibleCount === 0) {
      statusEl.textContent = statusEl.dataset.emptyText || '';
    } else if (statusTpl) {
      statusEl.textContent = statusTpl.replace('%d', visibleCount);
    } else {
      statusEl.textContent = String(visibleCount);
    }
  };

  const filter = (term, doAnnounce) => {
    const needle = (term || '').toLowerCase();
    let visibleCount = 0;

    cards.forEach((card) => {
      card.classList.remove(HIDDEN);

      if (!needle) {
        visibleCount += 1;
        return;
      }

      const title = (card.querySelector('.js-selectiongallery-card-title')?.textContent || '').toLowerCase();
      const desc = (card.querySelector('.js-selectiongallery-card-desc')?.textContent || '').toLowerCase();

      if (title.includes(needle) || desc.includes(needle)) {
        visibleCount += 1;
      } else {
        card.classList.add(HIDDEN);
      }
    });

    const hasResults = visibleCount > 0;

    // Hide a group header when every card in it is hidden.
    groups.forEach((group) => {
      const visible = group.querySelector('.js-selectiongallery-card:not(.d-none)');
      group.classList.toggle(HIDDEN, !visible);
    });

    if (alertEl) {
      alertEl.classList.toggle(HIDDEN, hasResults);
    }
    if (header) {
      header.classList.toggle(HIDDEN, !hasResults);
    }
    if (results) {
      results.classList.toggle(HIDDEN, !hasResults);
    }

    // Only announce in response to a user action, never for the initial (restored) render.
    if (doAnnounce) {
      announce(visibleCount);
    }
  };

  input.addEventListener('input', (event) => {
    const term = event.target.value;

    if (searchKey && typeof sessionStorage !== 'undefined') {
      try {
        sessionStorage.setItem(searchKey, term);
      } catch (e) {
        // sessionStorage unavailable — search still works, it just isn't remembered.
      }
    }

    filter(term, true);
  });

  // Progressive enhancement: reveal the search box now that JS is running.
  searchBox.classList.remove(HIDDEN);

  // Focus the search only inside the dialog, where it is the expected entry point; on a full page it
  // would steal focus and scroll unexpectedly on load.
  if (focusInput) {
    input.focus();
  }

  // Restore any remembered search string and apply it.
  if (searchKey && typeof sessionStorage !== 'undefined') {
    try {
      input.value = sessionStorage.getItem(searchKey) || '';
    } catch (e) {
      // Ignore storage read errors.
    }
  }

  filter(input.value, false);
};

const initAll = (root, focusInput) => {
  (root || document).querySelectorAll('.js-selectiongallery').forEach((container) => initGallery(container, focusInput));
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initAll(document, false));
} else {
  initAll(document, false);
}

// Galleries cloned into an inline JoomlaDialog announce themselves with joomla:updated; focus the search.
document.addEventListener('joomla:updated', (event) => initAll(event.target, true));
