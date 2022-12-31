<?php

/**
 * @package     Joomla.Plugin
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt

 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

namespace Joomla\Plugin\Schemaorg\Blogposting\Extension;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Schemaorg\SchemaorgPluginTrait;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Registry\Registry;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Schemaorg Plugin
 *
 * @since  4.0.0
 */
final class Blogposting extends CMSPlugin implements SubscriberInterface
{
    use SchemaorgPluginTrait;

    /**
     * @var    \Joomla\Database\DatabaseDriver
     *
     */
    protected $db;

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Loads the CMS Application for direct access
     *
     * @var   CMSApplicationInterface
     * @since 4.0.0
     */
    protected $app;

    /**
     * The name of the schema form
     *
     * @var   string
     * @since 4.0.0
     */
    protected $pluginName = 'BlogPosting';

    /**
     *  Saves the schema to the database
     *
     *  @param   AbstractEvent $event
     *
     *  @return  boolean
     */
    public function onSchemaAfterSave(AbstractEvent $event)
    {
        $data = $event->getArgument('data')->toArray();
        $form = $data['schema']['schemaType'];

        if ($form != $this->pluginName) {
            return false;
        }
        $this->storeSchemaToStandardLocation($event);
        return true;
    }

    /**
     *  To add plugin specific functions
     *
     *  @param   Registry $schema Schema form
     *
     *  @return  Registry $schema Updated schema form
     */
    public function cleanupIndividualSchema(Registry $schema)
    {
        if (is_object($schema)) {
            $schema = $this->cleanupDate($schema, ['datePublished','dateModified']);
        }
        return $schema;
    }
}
