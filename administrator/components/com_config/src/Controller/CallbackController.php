<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Config\Administrator\Controller;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\MultiFactor\Callback;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Input\Input;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * OAuth2 AJAX callback controller for mailer
 *
 * @since __DEPLOY_VERSION__
 */
class CallbackController extends BaseController
{
    /**
     * Public constructor
     *
     * @param   array                 $config   Plugin configuration
     * @param   ?MVCFactoryInterface  $factory  MVC Factory for the com_users component
     * @param   ?CMSApplication       $app      CMS application object
     * @param   ?Input                $input    Joomla CMS input object
     *
     * @since __DEPLOY_VERSION__
     */
    public function __construct(array $config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $this->registerDefaultTask('mailer');
    }

    /**
     * Handles an OAuth Callback request for OAuth2
     *
     * @param   bool         $cachable   Can this view be cached
     * @param   array|bool   $urlparams  An array of safe url parameters and their variable types.
     *                       @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  void
     * @since __DEPLOY_VERSION__
     */
    protected function callback($cachable = false, $urlparams = false): void
    {
        /**
         * If we are still here and no method  handled the request successfully. Show an error.
         */
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    /**
     * Handles an OAuth Callback request for phpmailer
     *
     * URLs containing [sitename]/administrator/index.php?option=com_config&task=callback.mailer
     *
     * @param   bool         $cachable   Can this view be cached
     * @param   array|bool   $urlparams  An array of safe url parameters and their variable types.
     *                       @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  void
     * @since __DEPLOY_VERSION__
     */
    public function mailer($cachable = false, $urlparams = false): void
    {
        // Redirect
        $this->redirect();
    }
}
