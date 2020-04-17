<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MVC\Model;

use Joomla\CMS\Form\Form;

\defined('JPATH_PLATFORM') or die;

/**
 * Interface for a workflow model.
 *
 * @since  4.0.0
 */
interface WorkflowModelInterface
{
	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   Form    $form   A Form object.
	 * @param   mixed   $data   The data expected for the form.
	 *
	 * @return  void
	 *
	 * @see     FormField
	 * @since   4.0.0
	 * @throws  \Exception if there is an error in the form event.
	 */
	public function preProcessFormWorkflow(Form $form, $data);


	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  Table  A Table object
	 *
	 * @since   3.0
	 * @throws  \Exception
	 */
	public function getTable($name = '', $prefix = '', $options = array());

}
