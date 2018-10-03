<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

$app       = Factory::getApplication();
$form      = $displayData->getForm();
$fieldSets = $form->getFieldsets();

if (empty($fieldSets))
{
	return;
}

$ignoreFieldsets = $displayData->get('ignore_fieldsets') ?: array();
$ignoreFields    = $displayData->get('ignore_fields') ?: array();
$extraFields     = $displayData->get('extra_fields') ?: array();
$tabName         = $displayData->get('tab_name') ?: 'myTab';

if (!empty($displayData->hiddenFieldsets))
{
	// These are required to preserve data on save when fields are not displayed.
	$hiddenFieldsets = $displayData->hiddenFieldsets ?: array();
}

if (!empty($displayData->configFieldsets))
{
	// These are required to configure showing and hiding fields in the editor.
	$configFieldsets = $displayData->configFieldsets ?: array();
}

// Handle the hidden fieldsets when show_options is set false
if (!$displayData->get('show_options', 1))
{
	// The HTML buffer
	$html   = array();

	// Loop over the fieldsets
	foreach ($fieldSets as $name => $fieldSet)
	{
		// Check if the fieldset should be ignored
		if (in_array($name, $ignoreFieldsets, true))
		{
			continue;
		}

		// If it is a hidden fieldset, render the inputs
		if (in_array($name, $hiddenFieldsets))
		{
			// Loop over the fields
			foreach ($form->getFieldset($name) as $field)
			{
				// Add only the input on the buffer
				$html[] = $field->input;
			}

			// Make sure the fieldset is not rendered twice
			$ignoreFieldsets[] = $name;
		}

		// Check if it is the correct fieldset to ignore
		if (strpos($name, 'basic') === 0)
		{
			// Ignore only the fieldsets which are defined by the options not the custom fields ones
			$ignoreFieldsets[] = $name;
		}
	}

	// Echo the hidden fieldsets
	echo implode('', $html);
}

// Loop again over the fieldsets
foreach ($fieldSets as $name => $fieldSet)
{
	// Ensure any fieldsets we don't want to show are skipped (including repeating formfield fieldsets)
	if ((isset($fieldSet->repeat) && $fieldSet->repeat === true)
		|| in_array($name, $ignoreFieldsets)
		|| (!empty($configFieldsets) && in_array($name, $configFieldsets, true))
		|| (!empty($hiddenFieldsets) && in_array($name, $hiddenFieldsets, true))
	)
	{
		continue;
	}

	// Determine the label
	if (!empty($fieldSet->label))
	{
		$label = Text::_($fieldSet->label);
	}
	else
	{
		$label = strtoupper('JGLOBAL_FIELDSET_' . $name);
		if (Text::_($label) === $label)
		{
			$label = strtoupper($app->input->get('option') . '_' . $name . '_FIELDSET_LABEL');
		}
		$label = Text::_($label);
	}

	$helper = $displayData->get('useCoreUI', false) ? 'uitab' : 'bootstrap';

	// Start the tab
	echo HTMLHelper::_($helper . '.addTab', $tabName, 'attrib-' . $name, $label);

	// Include the description when available
	if (isset($fieldSet->description) && trim($fieldSet->description))
	{
		echo '<div class="alert alert-info">' . $this->escape(Text::_($fieldSet->description)) . '</div>';
	}

	// The name of the fieldset to render
	$displayData->fieldset = $name;

	// Force to show the options
	$displayData->showOptions = true;

	// Render the fieldset
	echo LayoutHelper::render('joomla.edit.fieldset', $displayData);

	// End the tab
	echo HTMLHelper::_($helper . '.endTab');
}
