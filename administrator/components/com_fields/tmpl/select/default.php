<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_fields
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Fields\Administrator\View\Select\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('selectiongallery-search');

$context = $this->escape($this->state->get('filter.context'));

// Map the field types onto the shared selection-gallery layout. Cards navigate to the add task here;
// the same layout is reused (prerendered) by the inline "change type" picker on the field edit form.
$items = [];

foreach ($this->items as $item) {
    $items[] = (object) [
        'value'       => $item->type,
        'title'       => $item->label,
        'description' => $item->description ?? '',
        'url'         => Route::_('index.php?option=com_fields&task=field.add&context=' . $context . '&type=' . $item->type),
    ];
}

echo LayoutHelper::render('joomla.selectiongallery.default', [
    'items'          => $items,
    'header'         => Text::_('COM_FIELDS_TYPE_CHOOSE'),
    'emptyText'      => Text::_('COM_FIELDS_MSG_NO_FIELD_TYPES'),
    'searchKey'      => 'Joomla.com_fields.new.search',
    'selectLabelKey' => 'COM_FIELDS_SELECT_FIELD_TYPE',
    'statusTemplate' => Text::_('COM_FIELDS_SEARCH_RESULTS_COUNT'),
]);
