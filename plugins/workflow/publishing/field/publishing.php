<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.profile
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Workflow\WorkflowServiceInterface;

/**
 * Provides input for "Publishing" field from the active extension
 *
 * @package     Joomla.Plugin
 * @subpackage  Workflow.publishing
 * @since       __DEPLOY_VERSION__
 */
class WorkflowFormFieldPublishing extends \Joomla\CMS\Form\Field\ListField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'publishing';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.5
	 */
	public function getOptions()
	{
		$app = Factory::getApplication();

		$extension = $app->input->getCmd('extension');
		$extensionInterface = $app->bootComponent($extension);

		$publishList = parent::getOptions();

		if ($extensionInterface instanceof WorkflowServiceInterface)
		{
			// TODO: Concept of sections
			$conditions = $extensionInterface->getConditions();

			foreach ($conditions as $value => $text)
			{
				$conditions[$value] = Text::_($text);
			}

			return array_merge($publishList, $conditions);
		}

		return $publishList;
	}
}
