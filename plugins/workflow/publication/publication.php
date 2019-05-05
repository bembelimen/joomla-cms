<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.profile
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * An example custom profile plugin.
 *
 * @since  1.6
 */
class PlgWorkflowPublication extends CMSPlugin
{

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Adds additional fields to the user editing form
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		// Check we are manipulating a valid form.
		$name = $form->getName();
		$contexts = ['com_workflow.stage.com_content'];

		if (!in_array($name, $contexts))
		{
			return true;
		}

		// Add the registration fields to the form.
		Form::addFormPath(__DIR__ . '/forms');
		$form->loadFile('publication');

		return true;
	}
}
