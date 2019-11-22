<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Workflow\Administrator\View\Transition;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Workflow\Administrator\Helper\StageHelper;

/**
 * View class to add or edit Workflow
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The model state
	 *
	 * @var     object
	 * @since   4.0.0
	 */
	protected $state;

	/**
	 * From object to generate fields
	 *
	 * @var    Form
	 * @since  4.0.0
	 */
	protected $form;

	/**
	 * Items array
	 *
	 * @var    object
	 * @since  4.0.0
	 */
	protected $item;

	/**
	 * The extension that the workflow will be used on
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $extension;

	/**
	 * The ID of current workflow
	 *
	 * @var    integer
	 * @since  4.0.0
	 */
	protected $workflowID;

	/**
	 * Use core ui in different layouts
	 *
	 * @var   integer
	 * @since 4.0.0
	 */
	protected $useCoreUI = true;

	/**
	 * Display item view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$app   = Factory::getApplication();

		// Get the Data
		$this->state      = $this->get('State');
		$this->form       = $this->get('Form');
		$this->item       = $this->get('Item');
		$this->extension  = $this->state->get('filter.extension');

		// Get the ID of workflow
		$this->workflowID = $app->input->getCmd("workflow_id");

		// Set the toolbar
		$this->addToolBar($app);

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @param   CMSApplicationInterface  $app  The Application object
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	protected function addToolbar($app)
	{
		$app->input->set('hidemainmenu', true);

		$user       = $app->getIdentity();
		$userId     = $user->id;
		$isNew      = empty($this->item->id);

		$canDo = StageHelper::getActions($this->extension, 'transition', $this->item->id);

		ToolbarHelper::title(empty($this->item->id) ? Text::_('COM_WORKFLOW_TRANSITION_ADD') : Text::_('COM_WORKFLOW_TRANSITION_EDIT'), 'address');

		$toolbarButtons = [];

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.edit'))
			{
				ToolbarHelper::apply('transition.apply');
				$toolbarButtons = [['save', 'transition.save'], ['save2new', 'transition.save2new']];
			}

			ToolbarHelper::saveGroup(
				$toolbarButtons,
				'btn-success'
			);
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			if ($itemEditable)
			{
				ToolbarHelper::apply('transition.apply');
				$toolbarButtons = [['save', 'transition.save']];

				// We can save this record, but check the create permission to see if we can return to make a new one.
				if ($canDo->get('core.create'))
				{
					$toolbarButtons[] = ['save2new', 'transition.save2new'];
					$toolbarButtons[] = ['save2copy', 'transition.save2copy'];
				}
			}

			ToolbarHelper::saveGroup(
				$toolbarButtons,
				'btn-success'
			);
		}

		ToolbarHelper::cancel('transition.cancel');
		ToolbarHelper::divider();
	}
}
