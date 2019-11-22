<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Workflow;

\defined('JPATH_PLATFORM') or die;

/**
 * Trait for component workflow service.
 *
 * @since  4.0.0
 */
trait WorkflowServiceTrait
{
	/**
	 * Returns an array of possible conditions for the component.
	 *
	 * @param   string  $section  Optional section for the component
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getConditions($section = null): array
	{
		return \defined('self::CONDITION_NAMES') ? self::CONDITION_NAMES : Workflow::CONDITION_NAMES;
	}
}
