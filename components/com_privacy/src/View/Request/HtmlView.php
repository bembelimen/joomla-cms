<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_privacy
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Privacy\Site\View\Request;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\Registry\Registry;

/**
 * Request view class
 *
 * @since  3.9.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The form object
	 *
	 * @var    Form
	 * @since  3.9.0
	 */
	protected $form;

	/**
	 * The CSS class suffix to append to the view container
	 *
	 * @var    string
	 * @since  3.9.0
	 */
	protected $pageclass_sfx;

	/**
	 * The view parameters
	 *
	 * @var    Registry
	 * @since  3.9.0
	 */
	protected $params;

	/**
	 * Flag indicating the site supports sending email
	 *
	 * @var    boolean
	 * @since  3.9.0
	 */
	protected $sendMailEnabled;

	/**
	 * The state information
	 *
	 * @var    CMSObject
	 * @since  3.9.0
	 */
	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @see     BaseHtmlView::loadTemplate()
	 * @since   3.9.0
	 * @throws  Exception
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->form            = $this->get('Form');
		$this->state           = $this->get('State');
		$this->params          = $this->state->params;
		$this->sendMailEnabled = (bool) Factory::getConfig()->get('mailonline', 1);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \JViewGenericdataexception(implode("\n", $errors), 500);
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'), ENT_COMPAT, 'UTF-8');

		$this->prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepares the document.
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	protected function prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_PRIVACY_VIEW_REQUEST_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
