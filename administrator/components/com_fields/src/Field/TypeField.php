<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_fields
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Fields\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Fields Type
 *
 * Renders the field-type picker: a joomla-field-modal-select web component wrapping the (hidden) type
 * value, with a browse button that opens the shared selection gallery inline (no iframe). For an
 * existing record the type is readonly (see FieldModel::preprocessForm()), so the picker is replaced
 * by a static heading and the value can no longer be changed.
 *
 * @since  3.7.0
 */
class TypeField extends ListField
{
    /**
     * @var    string
     */
    public $type = 'Type';

    /**
     * Cached list of available field types (getFieldTypes() dispatches a plugin event, so call it once).
     *
     * @var    array|null
     *
     * @since  __DEPLOY_VERSION__
     */
    private $fieldTypesData;

    /**
     * Get the available field types, cached for the lifetime of the field.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getFieldTypesData(): array
    {
        if ($this->fieldTypesData === null) {
            $this->fieldTypesData = FieldsHelper::getFieldTypes();
        }

        return $this->fieldTypesData;
    }

    /**
     * Method to get the field options (kept for backward compatibility).
     *
     * @return  array  The field option objects.
     *
     * @since   3.7.0
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        foreach ($this->getFieldTypesData() as $fieldType) {
            $options[] = HTMLHelper::_('select.option', $fieldType['type'], $fieldType['label']);
        }

        // Sorting the fields based on the text which is displayed
        usort(
            $options,
            function ($a, $b) {
                return strcmp($a->text, $b->text);
            }
        );

        return $options;
    }

    /**
     * Method to get the field input markup: the inline modal-select picker.
     *
     * @return  string  The field input markup.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getInput()
    {
        // Only load the picker behaviour when the type can actually be changed (a new record).
        if (!$this->readonly && !$this->disabled) {
            Factory::getApplication()->getDocument()->getWebAssetManager()
                ->useScript('com_fields.admin-field-typehaschanged')
                ->useScript('webcomponent.core-loader')
                ->useScript('webcomponent.field-modal-select')
                ->useScript('selectiongallery-search');
        }

        return LayoutHelper::render(
            'field.fieldtypeselect',
            $this->getLayoutData(),
            JPATH_ADMINISTRATOR . '/components/com_fields/layouts'
        );
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getLayoutData()
    {
        $data  = parent::getLayoutData();
        $types = $this->getFieldTypesData();

        // The current type definition (label/description) for the heading.
        $data['fieldType'] = $types[$this->value] ?? null;

        // Normalised items for the shared selection-gallery layout.
        $items = [];

        foreach ($types as $type) {
            $items[] = (object) [
                'value'       => $type['type'],
                'title'       => $type['label'],
                'description' => $type['description'] ?? '',
            ];
        }

        $data['items'] = $items;

        return $data;
    }
}
