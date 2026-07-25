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

extract($displayData);

/**
 * Layout for the com_fields "type" picker (rendered by TypeField).
 *
 * Layout variables
 * -----------------
 * @var   string      $id          DOM id of the field.
 * @var   string      $name        Name of the input field.
 * @var   string      $value       Current field type (value).
 * @var   boolean     $readonly    Is the field read only?
 * @var   boolean     $disabled    Is the field disabled?
 * @var   array|null  $fieldType   Definition of the current type (label, description) or null if unknown.
 * @var   \stdClass[] $items       Available types for the gallery (value, title, description).
 */

$galleryId  = $id . '_gallery';
$labelId    = $id . '-lbl';
$changeable = empty($readonly) && empty($disabled);

// The current type, rendered as a value (not a heading) inside a group labelled "Field Type" so
// assistive tech announces the field/value relationship the original <select> label used to carry.
$typeLabel = $fieldType
    ? '<span class="h2 m-0">' . $fieldType['label'] . '</span>'
    : '<span class="h2 m-0 text-danger">' . Text::sprintf('COM_FIELDS_FIELD_TYPE_NOT_FOUND', $this->escape($value)) . '</span>';
?>
<?php if ($changeable) : ?>
    <joomla-field-modal-select data-field="type"
        content="#<?php echo $this->escape($galleryId); ?>"
        modal-title="<?php echo $this->escape(Text::_('COM_FIELDS_TYPE_CHOOSE')); ?>">
        <div role="group" aria-labelledby="<?php echo $this->escape($labelId); ?>">
            <span id="<?php echo $this->escape($labelId); ?>" class="control-label d-block"><?php echo Text::_('COM_FIELDS_FIELD_TYPE_LABEL'); ?></span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php echo $typeLabel; ?>
                <button type="button" data-button-action="select" class="btn btn-primary">
                    <span class="icon-search" aria-hidden="true"></span>
                    <?php echo Text::_('COM_FIELDS_CHANGE_TYPE'); ?>
                </button>
            </div>
            <?php if ($fieldType && !empty($fieldType['description'])) : ?>
                <p class="mt-2"><?php echo $fieldType['description']; ?></p>
            <?php endif; ?>
        </div>
        <?php // The type change reload is wired by com_fields.admin-field-typehaschanged (no inline handler). ?>
        <input type="hidden" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $this->escape($value); ?>">
    </joomla-field-modal-select>

    <?php // Field-type gallery, prerendered once and cloned into an inline dialog (no iframe). ?>
    <template id="<?php echo $this->escape($galleryId); ?>">
        <?php echo LayoutHelper::render('joomla.selectiongallery.default', [
            'items'          => $items,
            'header'         => Text::_('COM_FIELDS_TYPE_CHOOSE'),
            'emptyText'      => Text::_('COM_FIELDS_MSG_NO_FIELD_TYPES'),
            'searchKey'      => 'Joomla.com_fields.change.search',
            'selectLabelKey' => 'COM_FIELDS_SELECT_FIELD_TYPE',
            'statusTemplate' => Text::_('COM_FIELDS_SEARCH_RESULTS_COUNT'),
            'selectMode'     => true,
            'showHeader'     => false,
        ]); ?>
    </template>
<?php else : ?>
    <?php // Existing record: the type is locked (FieldModel marks it readonly). Show it, no picker. ?>
    <div role="group" aria-labelledby="<?php echo $this->escape($labelId); ?>">
        <span id="<?php echo $this->escape($labelId); ?>" class="control-label d-block"><?php echo Text::_('COM_FIELDS_FIELD_TYPE_LABEL'); ?></span>
        <?php echo $typeLabel; ?>
        <?php if ($fieldType && !empty($fieldType['description'])) : ?>
            <p class="mt-2"><?php echo $fieldType['description']; ?></p>
        <?php endif; ?>
    </div>
    <input type="hidden" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $this->escape($value); ?>">
<?php endif; ?>
