<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Workflow.Publishing
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;

/**
 * Publishing handling for workflow items
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
	 * Add additional fields to the supported forms
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		// Check we are manipulating the workflow form.
		if ($form->getName() === 'com_workflow.transition')
		{
			$this->loadPublishingFormForTransitionView($form);
		}

		return true;
	}

	/**
	 * Adds the publishing field to the transition view
	 *
	 * @param   Form   $form  The form to be altered.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function loadPublishingFormForTransitionView($form)
	{
		Form::addFormPath(__DIR__ . '/forms');

		$form->loadFile('publishing');
	}
}
