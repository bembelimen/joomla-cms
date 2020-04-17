<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\MVC\Model;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Workflow\Workflow;
use Joomla\CMS\Table\Category;
use Joomla\Registry\Registry;

/**
 * Trait which supports state behavior
 *
 * @since  4.0.0
 */
trait WorkflowBehaviorTrait
{
	/**
	 * The  for the component.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $extension = null;

	protected $section = '';

	protected $workflowEnabled = false;

	/**
	 * Set Up the workflow
	 *
	 * @param   string  $extension  The option and section separated by .
	 */
	public function setUpWorkflow($extension)
	{
		$parts = explode('.', $extension);

		$this->extension = array_shift($parts);

		if (count($parts))
		{
			$this->section = array_shift($parts);
		}

		$params = ComponentHelper::getParams($this->extension);

		$this->workflowEnabled = $params->get('workflows_enable', 1);
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   Form   $form  A Form object.
	 * @param   mixed  $data  The data expected for the form.
	 *
	 * @return  void
	 *
	 * @throws  \Exception if there is an error in the form event.
	 * @since   4.0.0
	 * @see     FormField
	 */
	public function preprocessFormWorkflow(Form $form, $data)
	{
		$this->addTransitionField($form, $data);

		if (!$this->workflowEnabled)
		{
			return;
		}

		// Import the workflow plugin group to allow form manipulation.
		PluginHelper::importPlugin('workflow');
	}

	/**
	 * Adds a transition field to the form. Can be overwritten by the child class if not needed
	 *
	 * @param   Form   $form  A Form object.
	 * @param   mixed  $data  The data expected for the form.
	 *
	 * @return  void
	 * @since   4.0.0
	 */
	protected function addTransitionField(Form $form, $data)
	{
		$extension = $this->extension . ($this->section ? '.' . $this->section : '');

		$field = new \SimpleXMLElement('<field></field>');

		$field->addAttribute('name', 'transition');
		$field->addAttribute('type', $this->workflowEnabled ? 'transition' : 'hidden');
		$field->addAttribute('label', 'COM_CONTENT_TRANSITION');
		$field->addAttribute('extension', $extension);

		$form->setField($field);

		$table = $this->getTable();

		$key = $table->getKeyName();

		$id = isset($data->$key) ? $data->$key : $form->getValue($key);

		if ($id)
		{
			$workflow = new Workflow(['extension' => $extension]);

			// Transition field
			$assoc = $workflow->getAssociation($id);

			$form->setFieldAttribute('transition', 'workflow_stage', (int) $assoc->stage_id);
		}
		else
		{
			$stage_id = $this->getStageForNewItem($form, $data);

			if (!empty($stage_id))
			{
				$form->setFieldAttribute('transition', 'workflow_stage', (int) $stage_id);
			}
		}
	}

	/**
	 * Try to load a workflow object for newly created items
	 * which does not have a workflow assinged yet. If the category is not the
	 * carrier, overwrite it on your model and deliver your own carrier.
	 *
	 * @param   Form   $form  A Form object.
	 * @param   mixed  $data  The data expected for the form.
	 *
	 * @return  boolean|object  A object containing workflow information or false
	 * @since   4.0.0
	 */
	protected function getStageForNewItem(Form $form, $data)
	{
		$table = $this->getTable();

		$hasKey = $table->hasField('catid');

		if (!$hasKey)
		{
			return false;
		}

		$catKey = $table->getColumnAlias('catid');

		$field = $form->getField($catKey);

		if (!$field)
		{
			return false;
		}

		$catId = isset($data->$catKey) ? $data->$catKey : $form->getValue($catKey);

		// Try to get the category from the html code of the field
		if (empty($catId))
		{
			$catId = $field->getAttribute('default', null);

			// Choose the first category available
			$xml = new \DOMDocument;
			libxml_use_internal_errors(true);
			$xml->loadHTML($field->__get('input'));
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			$options = $xml->getElementsByTagName('option');

			if (!$catId && $firstChoice = $options->item(0))
			{
				$catId = $firstChoice->getAttribute('value');
			}
		}

		if (empty($catId))
		{
			return false;
		}

		$db = Factory::getContainer()->get('DatabaseDriver');

		// Let's check if a workflow ID is assigned to a category
		$category = new Category($db);

		$categories = array_reverse($category->getPath($catId));

		$workflow_id = 0;

		foreach ($categories as $cat)
		{
			$cat->params = new Registry($cat->params);

			$workflow_id = $cat->params->get('workflow_id');

			if ($workflow_id == 'inherit')
			{
				$workflow_id = 0;

				continue;
			}
			elseif ($workflow_id == 'use_default')
			{
				$workflow_id = 0;

				break;
			}
			elseif ($workflow_id > 0)
			{
				break;
			}
		}

		// Check if the workflow exists
		if ($workflow_id = (int) $workflow_id)
		{
			$query = $db->getQuery(true);

			$query->select(
				[
					$db->quoteName('ws.id')
				]
			)
				->from(
					[
						$db->quoteName('#__workflow_stages', 'ws'),
						$db->quoteName('#__workflows', 'w'),
					]
				)
				->where(
					[
						$db->quoteName('ws.workflow_id') . ' = ' . $db->quoteName('w.id'),
						$db->quoteName('ws.default') . ' = 1',
						$db->quoteName('w.published') . ' = 1',
						$db->quoteName('ws.published') . ' = 1',
						$db->quoteName('w.id') . ' = :workflowId',
					]
				)
				->bind(':workflowId', $workflow_id, ParameterType::INTEGER);

			$stage_id = (int) $db->setQuery($query)->loadResult();

			if (!empty($stage_id))
			{
				return $stage_id;
			}
		}

		// Use default workflow
		$query  = $db->getQuery(true);

		$query->select(
			[
				$db->quoteName('ws.id')
			]
		)
			->from(
				[
					$db->quoteName('#__workflow_stages', 'ws'),
					$db->quoteName('#__workflows', 'w'),
				]
			)
			->where(
				[
					$db->quoteName('ws.default') . ' = 1',
					$db->quoteName('ws.workflow_id') . ' = ' . $db->quoteName('w.id'),
					$db->quoteName('w.published') . ' = 1',
					$db->quoteName('ws.published') . ' = 1',
					$db->quoteName('w.default') . ' = 1',
				]
			);

		$stage_id = (int) $db->setQuery($query)->loadResult();

		// Last check if we have a workflow ID
		if (!empty($stage_id))
		{
			return $stage_id;
		}

		return false;
	}

	/**
	 * Batch change workflow stage or current.
	 *
	 * @param   integer  $value     The workflow stage ID.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since   4.0.0
	 */
	public function batchWorkflowStage(int $value, array $pks, array $contexts) {

		$user = Factory::getApplication()->getIdentity();
		/** @var  $workflow */
		$workflow = Factory::getApplication()->bootComponent('com_workflow');

		if (!$user->authorise('core.admin', $this->option))
		{
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EXECUTE_TRANSITION'));
		}

		// Get workflow stage information
		$stage = $workflow->createTable('Stage', 'Administrator');

		if (empty($value) || !$stage->load($value))
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_BATCH_WORKFLOW_STAGE_ROW_NOT_FOUND'), 'error');

			return false;
		}

		if (empty($pks))
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_BATCH_WORKFLOW_STAGE_ROW_NOT_FOUND'), 'error');

			return false;
		}

		$workflow = new Workflow(['extension' => $this->option]);

		// Update workflow associations
		return $workflow->updateAssociations($pks, $value);
	}

}
