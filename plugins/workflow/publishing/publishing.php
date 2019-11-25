<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Workflow.Publishing
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Joomla\CMS\Workflow\WorkflowServiceInterface;

/**
 * Publishing handling for workflow items
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgWorkflowPublishing extends CMSPlugin
{
	/**
	 * The Application Object
	 *
	 * @var    CMSApplication
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		Form::addFieldPath(__DIR__ . '/field');
	}

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

	/**
	 * Updates the content state of the content item after a workflow is transitioned.
	 *
	 * @param   integer[]  $pks         The primary keys.
	 * @param   string     $extension   The extension being altered.
	 * @param   User       $user        The user making the transition.
	 * @param   object     $transition  The extension being altered.
	 * @param   \stdClass  $options     The options for the plugin event.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onWorkflowAfterTransition($pks, $extension, $user, $transition, $options)
	{
		$extensionOptions = json_decode($transition->options, true);

		if (isset($extensionOptions['publishing']))
		{
			$extensionInterface = $this->app->bootComponent($extension);

			if ($extensionInterface instanceof WorkflowServiceInterface)
			{
				$extensionInterface->updateContentState($pks, $extensionOptions['publishing']);

				if (isset($options['publishing']['changeStateEvent']))
				{
					// Trigger the change stage event.
					$this->app->triggerEvent(
						$options['publishing']['changeStateEvent'],
						[$options['publishing']['context'], $pks, $extensionOptions['publishing']]
					);
				}
			}
		}
	}
}
