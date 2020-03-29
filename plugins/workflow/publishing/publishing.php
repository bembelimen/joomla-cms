<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Workflow.Publishing
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\DatabaseModelInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\Workflow\Workflow;
use Joomla\CMS\Workflow\WorkflowServiceInterface;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

/**
 * Workflow Publishing Plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgWorkflowPublishing extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the CMS Application for direct access
	 *
	 * @var   CMSApplicationInterface
	 * @since __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * The form event.
	 *
	 * @param   Form      $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		$context = $form->getName();

		// Extend the transition form
		if ($context == 'com_workflow.transition')
		{
			return $this->enhanceTransitionForm($form, $data);
		}

		return $this->enhanceItemForm($form, $data);
	}

	/**
	 * Add different parameter options to the transition view, we need when executing the transition
	 *
	 * @param   Form      $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function enhanceTransitionForm(Form $form, $data)
	{
		Form::addFormPath(__DIR__ . '/forms');

		$form->loadFile('workflow_publishing');

		$model = $this->app->bootComponent('com_workflow')
				->getMVCFactory()->createModel('Workflow', 'Administrator', ['ignore_request' => true]);

		$workflow_id = !empty($data->workflow_id) ? (int) $data->workflow_id : (int) $form->getValue('workflow_id');

		if (empty($workflow_id))
		{
			$workflow_id = $this->app->input->getInt('workflow_id');
		}

		if ($workflow_id)
		{
			$workflow = $model->getItem($workflow_id);

			$form->setFieldAttribute('publishing', 'extension', $workflow->extension, 'options');
		}
		else
		{
			$form->setFieldAttribute('publishing', 'disabled', 'true', 'options');
		}

		return true;
	}

	/**
	 * Disable certain fields in the item  form view, when we want to take over this function in the transition
	 * Check also for the workflow implementation and if the field exists
	 *
	 * @param   Form      $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function enhanceItemForm(Form $form, $data)
	{
		$context = $form->getName();

		$parts = explode('.', $context);

		// We need at least the extension + view for loading the fields
		if (count($parts) < 2)
		{
			return true;
		}

		$component = $this->app->bootComponent($parts[0]);

		if (!$component instanceof WorkflowServiceInterface)
		{
			return true;
		}

		$model = $component->getMVCFactory()->createModel($parts[1], $this->app->getName(), ['ignore_request' => true]);

		if (!$model instanceof DatabaseModelInterface)
		{
			return true;
		}

		$table = $model->getTable();

		if (!$table instanceof TableInterface || !$table->hasField('published'))
		{
			return true;
		}

		$form->setFieldAttribute($table->getColumnAlias('published'), 'disabled', 'true');

		return true;
	}
}
