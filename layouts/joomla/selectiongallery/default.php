<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Reusable "selection gallery": a searchable grid of choosable cards.
 *
 * Shared by consumers that let the user pick one item from a bounded, server-known list
 * (com_modules module types, com_fields field types, template/module positions, ...). It renders the
 * same markup whether shown as a full page or cloned into a JoomlaDialog (popupType="inline") by the
 * joomla-field-modal-select web component. Cards carry data-select-value / data-select-title so an
 * inline picker can read the selection same-document (no iframe, no postMessage); the optional url
 * keeps them as real navigation links when shown full page.
 *
 * Layout variables ($displayData):
 * -----------------
 * @var   \stdClass[]|array[] $items          Each: value, title, description, url?, linkAttribs?, group?
 * @var   string             $header          Heading text ("Select a Field Type").
 * @var   string             $emptyText       Message shown when the search matches nothing.
 * @var   string             $searchKey       sessionStorage key for the remembered search string.
 * @var   string             $selectLabelKey  Language key (expects %s) for each card's aria-label.
 * @var   boolean            $showSearch      Whether to render the search box (default true).
 * @var   boolean            $showHeader      Whether to render the visible <h2> header (default true).
 * @var   boolean            $selectMode      Render cards as <button> pickers instead of <a> links (default false).
 * @var   string             $statusTemplate  Translated "%d results" template for the search live region.
 */

$items         = $displayData['items'] ?? [];
$header        = $displayData['header'] ?? Text::_('JSELECT');
$emptyText     = $displayData['emptyText'] ?? Text::_('JGLOBAL_NO_MATCHING_RESULTS');
$searchKey     = $displayData['searchKey'] ?? 'Joomla.selectiongallery.search';
$selectLabelKey = $displayData['selectLabelKey'] ?? 'JSELECT';
$showSearch    = $displayData['showSearch'] ?? true;
$showHeader    = $displayData['showHeader'] ?? true;
$selectMode    = $displayData['selectMode'] ?? false;
$statusTemplate = $displayData['statusTemplate'] ?? '';

// Group the items when at least one carries a "group" key; otherwise render a single flat group.
$grouped = [];

foreach ($items as $item) {
    $item  = (object) $item;
    $group = $item->group ?? '';
    $grouped[$group][] = $item;
}

$hasGroups = \count($grouped) > 1 || !isset($grouped['']);
?>

<div class="js-selectiongallery" data-search-key="<?php echo $this->escape($searchKey); ?>" data-status-template="<?php echo $this->escape($statusTemplate); ?>">
    <?php if ($showSearch) : ?>
        <div class="js-selectiongallery-search d-none">
            <div class="d-flex mt-2">
                <div class="m-auto">
                    <label class="visually-hidden" for="<?php echo $this->escape($searchKey); ?>-input">
                        <?php echo $header; ?>
                    </label>
                    <div class="input-group mb-3 me-sm-2">
                        <input type="text" value="" class="form-control js-selectiongallery-input"
                            id="<?php echo $this->escape($searchKey); ?>-input"
                            placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>">
                        <div class="input-group-text">
                            <span class="icon-search" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php // Screen-reader announcements for live search results (count / no results). ?>
    <div class="js-selectiongallery-status visually-hidden" role="status" aria-live="polite" data-empty-text="<?php echo $this->escape($emptyText); ?>"></div>

    <div class="js-selectiongallery-alert alert alert-info d-none">
        <span class="icon-info-circle" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
        <?php echo $emptyText; ?>
    </div>

    <?php if ($showHeader) : ?>
        <h2 class="pb-3 ms-3 js-selectiongallery-header"><?php echo $header; ?></h2>
    <?php endif; ?>

    <div class="js-selectiongallery-results">
        <?php foreach ($grouped as $groupTitle => $groupItems) : ?>
            <div class="js-selectiongallery-group">
                <?php if ($hasGroups && $groupTitle !== '') : ?>
                    <h3 class="ms-3 js-selectiongallery-group-title"><?php echo $this->escape($groupTitle); ?></h3>
                <?php endif; ?>
                <div class="main-card card-columns p-4">
                    <?php foreach ($groupItems as $item) : ?>
                        <?php
                        $value = $this->escape((string) ($item->value ?? ''));
                        $name  = $this->escape((string) ($item->title ?? ''));
                        $desc  = HTMLHelper::_('string.truncate', $this->escape(strip_tags((string) ($item->description ?? ''))), 200);
                        $url   = $item->url ?? '#';
                        ?>
                        <?php if ($selectMode) : ?>
                            <?php // Inline picker: a real button (native Enter/Space, no form submit) that the web component reads. ?>
                            <button type="button" class="new-module mb-3 p-0 text-start js-selectiongallery-card"
                                data-select-value="<?php echo $value; ?>"
                                data-select-title="<?php echo $name; ?>"
                                aria-label="<?php echo Text::sprintf($selectLabelKey, $name); ?>">
                        <?php else : ?>
                            <a href="<?php echo $url; ?>" class="new-module mb-3 js-selectiongallery-card"
                                data-select-value="<?php echo $value; ?>"
                                data-select-title="<?php echo $name; ?>"
                                <?php echo $item->linkAttribs ?? ''; ?>
                                aria-label="<?php echo Text::sprintf($selectLabelKey, $name); ?>">
                        <?php endif; ?>
                            <span class="new-module-details d-block">
                                <span class="new-module-title js-selectiongallery-card-title d-block"><?php echo $name; ?></span>
                                <span class="new-module-caption js-selectiongallery-card-desc p-0">
                                    <?php echo $desc; ?>
                                </span>
                            </span>
                            <span class="new-module-link">
                                <span class="icon-plus" aria-hidden="true"></span>
                            </span>
                        <?php echo $selectMode ? '</button>' : '</a>'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
