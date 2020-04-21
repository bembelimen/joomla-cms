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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\DatabaseModelInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\Workflow\WorkflowServiceInterface;

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

		if (!$this->isSupported($context))
		{
			return true;
		}

		$parts = explode('.', $context);

		$table = $this->app->bootComponent($parts[0])
					->getMVCFactory()->createModel($parts[1], $this->app->getName(), ['ignore_request' => true])
					->getTable();

		$form->setFieldAttribute($table->getColumnAlias('published'), 'disabled', 'true');

		return true;
	}

	/**
	 * The save event.
	 *
	 * @param   string   $context  The context
	 * @param   object   $table    The item
	 * @param   boolean  $isNew    Is new item
	 * @param   array    $data     The validated data
	 *
	 * @return  boolean
	 *
	 * @since   4.0.0
	 */
	public function onContentBeforeSave($context, TableInterface $table, $isNew, $data)
	{
		if (!$this->isSupported($context))
		{
			return true;
		}

		$keyName = $table->getColumnAlias('published');

		// Check for the old value
		$article = clone $table;

		$article->load($table->id);

		// We don't allow the change of the state when we use the workflow
		// As we're setting the field to disabled, no value should be there at all
		if (isset($data[$keyName]))
		{
			$table->setError(Text::_('PLG_WORKFLOW_PUBLISHING_SAVE_NOT_ALLOWED'));

			return false;
		}

		return true;
	}

	/**
	 * Check if the current plugin should execute workflow related activities
	 *
	 * @param type $context
	 * @return boolean
	 */
	protected function isSupported($context)
	{
		$parts = explode('.', $context);

		// We need at least the extension + view for loading the table fields
		if (count($parts) < 2)
		{
			return false;
		}

		$component = $this->app->bootComponent($parts[0]);

		if (!$component instanceof WorkflowServiceInterface || !$component->supportFunctionality('joomla.state'))
		{
			return false;
		}

		$model = $component->getMVCFactory()->createModel($parts[1], $this->app->getName(), ['ignore_request' => true]);

		if (!$model instanceof DatabaseModelInterface)
		{
			return false;
		}

		$table = $model->getTable();

		if (!$table instanceof TableInterface || !$table->hasField('published'))
		{
			return false;
		}

		return true;
	}
}
