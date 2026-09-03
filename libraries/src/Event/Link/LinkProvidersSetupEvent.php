<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Link;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Event\AbstractImmutableEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Link providers setup event.
 *
 * Listeners add their link sources to the "link-providers" script options of the given document:
 *
 * $event->getDocument()->addScriptOptions('link-providers', [
 *     'article' => ['title' => '…', 'icon' => '…', 'src' => '…', 'select' => 'content'],
 * ], true);
 *
 * @since  __DEPLOY_VERSION__
 */
final class LinkProvidersSetupEvent extends AbstractImmutableEvent
{
    /**
     * Constructor.
     *
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @throws  \BadMethodCallException
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($name, array $arguments = [])
    {
        if (!\array_key_exists('subject', $arguments)) {
            throw new \BadMethodCallException("Argument 'subject' of event {$name} is required but has not been provided");
        }

        parent::__construct($name, $arguments);
    }

    /**
     * Setter for the subject argument
     *
     * @param   Document  $value  The value to set
     *
     * @return  Document
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function onSetSubject(Document $value): Document
    {
        return $value;
    }

    /**
     * Returns the document the link sources should be added to.
     *
     * @return  Document
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getDocument(): Document
    {
        return $this->getArgument('subject');
    }
}
