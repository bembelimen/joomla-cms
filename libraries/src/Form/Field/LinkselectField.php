<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Form\Field;

use Joomla\CMS\Event\Link\LinkProvidersSetupEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Form field supporting a freely typeable URL with a button that opens the link picker.
 *
 * @since  __DEPLOY_VERSION__
 */
class LinkselectField extends TextField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Linkselect';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $layout = 'joomla.form.field.linkselect';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getInput()
    {
        // Nothing to pick into: render a plain text field
        if ($this->readonly || $this->disabled) {
            $this->layout = 'joomla.form.field.text';

            return parent::getInput();
        }

        $app = Factory::getApplication();
        $doc = $app->getDocument();

        $doc->getWebAssetManager()
            ->useStyle('link-picker')
            ->useScript('link-picker')
            ->useScript('field.linkselect');

        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);

        PluginHelper::importPlugin('editors-xtd', null, true, $dispatcher);

        $dispatcher->dispatch(
            'onLinkProvidersSetup',
            new LinkProvidersSetupEvent('onLinkProvidersSetup', ['subject' => $doc])
        );

        Text::script('JLIB_LINK_PICKER_TITLE');
        Text::script('JLIB_LINK_PICKER_MEDIA');
        Text::script('JSELECT');
        Text::script('JCLOSE');

        return parent::getInput();
    }
}
