<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       4.0.0
 */

namespace Joomla\Component\Workflow\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\String\StringHelper;

/**
 * Model class for stage
 *
 * @since  4.0.0
 */
class StageModel extends AdminModel
{
	/**
	 * Constructor.
	 *
	 * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   MVCFactoryInterface   $factory      The factory.
	 * @param   FormFactoryInterface  $formFactory  The form factory.
	 *
	 * @since   1.6
	 * @throws  \Exception
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);
		$this->extension     = Factory::getApplication()->input->get('extension', 'com_content', 'cmd');
		$this->context = $this->option . '.' . $this->name . '.' . $this->extension;
	}

	/**
	 * Method to change the title
	 *
	 * @param integer $category_id The id of the category.
	 * @param string  $alias       The alias.
	 * @param string  $title       The title.
	 *
	 * @return    array  Contains the modified title and alias.
	 *
	 * @since    4.0.0
	 */
	protected function generateNewTitle($category_id, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('title' => $title))) {
			$title = StringHelper::increment($title);
		}

		return array($title, $alias);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param array $data The form data.
	 *
	 * @return   boolean  True on success.
	 *
	 * @since  4.0.0
	 */
	public function save($data)
	{
		$context    = $this->option . '.' . $this->name;
		$app        = Factory::getApplication();
		$input      = $app->input;
		$workflowID = $app->getUserStateFromRequest($context . '.filter.workflow_id', 'workflow_id', 0, 'int');

		if (empty($data['workflow_id'])) {
			$data['workflow_id'] = $workflowID;
		}

		if ($input->get('task') == 'save2copy') {
			$origTable = clone $this->getTable();

			// Alter the title for save as copy
			if ($origTable->load(['title' => $data['title']])) {
				list($title) = $this->generateNewTitle(0, '', $data['title']);
				$data['title'] = $title;
			}

			$data['published'] = 0;
			$data['default']   = 0;
		}

		return parent::save($data);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since  4.0.0
	 */
	protected function canDelete($record)
	{
		$table = $this->getTable('Workflow', 'Administrator');

		$table->load($record->workflow_id);

		if (empty($record->id) || $record->published != -2 || $table->core) {
			return false;
		}

		$app       = Factory::getApplication();
		$extension = $app->getUserStateFromRequest('com_workflow.stage.filter.extension', 'extension', null, 'cmd');

		$parts = explode('.', $extension);

		$component = reset($parts);

		if (!Factory::getUser()->authorise('core.delete',
				$component . '.state.' . (int)$record->id) || $record->default) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));

			return false;
		}

		return true;
	}

	/**
	 * Method to test whether a record can have its state changed.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   4.0.0
	 */
	protected function canEditState($record)
	{
		$user      = Factory::getUser();
		$app       = Factory::getApplication();
		$extension = $app->getUserStateFromRequest('com_workflow.state.filter.extension', 'extension', null, 'cmd');

		$table = $this->getTable('Workflow', 'Administrator');

		$table->load($record->workflow_id);

		if ($table->core) {
			return false;
		}

		// Check for existing workflow.
		if (!empty($record->id)) {
			return $user->authorise('core.edit.state', $extension . '.state.' . (int)$record->id);
		}

		// Default to component settings if workflow isn't known.
		return $user->authorise('core.edit.state', $extension);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param array   $data     Data for the form.
	 * @param boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return \JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since  4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			$this->context,
			'stage',
			array(
				'control'   => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form)) {
			return false;
		}

		if ($loadData) {
			$data = $this->loadFormData();
		}

		$item = $this->getItem($form->getValue('id'));

		// Deactivate switcher if default
		// Use $item, otherwise we'll be locked when we get the data from the request
		if (!empty($item->default)) {
			$form->setValue('default', null, 1);
			$form->setFieldAttribute('default', 'readonly', 'true');
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object)$data)) {
			// Disable fields for display.
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed  The data for the form.
	 *
	 * @since  4.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(
			'com_workflow.edit.state.data',
			array()
		);

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to change the home state of one or more items.
	 *
	 * @param array   $pk    A list of the primary keys to change.
	 * @param integer $value The value of the home state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  4.0.0
	 */
	public function setDefault($pk, $value = 1)
	{
		$table = $this->getTable();

		if ($table->load(array('id' => $pk))) {
			if (!$table->published) {
				$this->setError(Text::_("COM_WORKFLOW_ITEM_MUST_PUBLISHED"));

				return false;
			}
		}

		if ($value) {
			// Verify that the home page for this language is unique per client id
			if ($table->load(array('default' => '1', 'workflow_id' => $table->workflow_id))) {
				$table->default = 0;
				$table->store();
			}
		}

		if ($table->load(array('id' => $pk))) {
			$table->default = $value;
			$table->store();
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Stock method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState()
	{
		parent::populateState();
		$this->setState('filter.extension', $this->extension);
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param array    &$pks   A list of the primary keys to change.
	 * @param integer   $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  4.0.0
	 */
	public function publish(&$pks, $value = 1)
	{
		$table     = $this->getTable();
		$pks       = (array)$pks;
		$app       = Factory::getApplication();
		$extension = $app->getUserStateFromRequest('com_workflow.state.filter.extension', 'extension', null, 'cmd');

		// Default item existence checks.
		if ($value != 1) {
			foreach ($pks as $i => $pk) {
				if ($table->load($pk) && $table->default) {
					// Prune items that you can't change.
					$app->enqueueMessage(Text::_('COM_WORKFLOW_MSG_DELETE_DEFAULT'), 'error');
					unset($pks[$i]);
				}
			}
		}

		return parent::publish($pks, $value);
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param Form   $form  A Form object.
	 * @param mixed  $data  The data expected for the form.
	 * @param string $group The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @throws  \Exception if there is an error in the form event.
	 * @since   4.0.0
	 * @see     FormField
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		PluginHelper::importPlugin('workflow');

		parent::preprocessForm($form, $data, $group);
	}
}
