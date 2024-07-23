<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mails
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Mails\Administrator\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Form Field to display a list of the layouts for a field from
 * the extension or template overrides.
 *
 * @since  __DEPLOY_VERSION__
 */
class MailtemplateLayoutField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'MailtemplateLayout';

    /**
     * Method to get the field input for a field layout field.
     *
     * @return  string   The field input.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getInput()
    {
        // $extension = explode('.', $this->form->getValue('context'));
        // $extension = $extension[0];

        // Get the database object and a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Build the query.
        $query->select('element, name')
            ->from('#__extensions')
            ->where('client_id = 0')
            ->where('type = ' . $db->quote('template'))
            ->where('enabled = 1');

        // Set the query and load the templates.
        $db->setQuery($query);
        $templates = $db->loadObjectList('element');

        // Prepare the grouped list
        $groups = [];

        // Add "Use Default"
        $groups[]['items'][] = HTMLHelper::_('select.option', '', Text::_('JOPTION_USE_DEFAULT'));

        // Loop on all templates
        if ($templates) {
            foreach ($templates as $template) {
                $files          = [];
                $template_paths = [
                    Path::clean(JPATH_SITE . '/templates/' . $template->element . '/html/layouts/joomla/mail'),
                    Path::clean(JPATH_SITE . '/templates/' . $template->element . '/html/layouts/com_mails/joomla/mail'),
                ];

                // Add the layout options from the template paths.
                foreach ($template_paths as $template_path) {
                    if (is_dir($template_path)) {
                        $files = array_merge($files, Folder::files($template_path, '^[^_]*\.php$', false, true));
                    }
                }

                if (\count($files)) {
                    // Create the group for the template
                    $groups[$template->name]          = [];
                    $groups[$template->name]['id']    = $this->id . '_' . $template->element;
                    $groups[$template->name]['text']  = Text::sprintf('JOPTION_FROM_TEMPLATE', $template->name);
                    $groups[$template->name]['items'] = [];

                    foreach ($files as $file) {
                        // Add an option to the template group
                        $value                              = basename($file, '.php');
                        $groups[$template->name]['items'][] = HTMLHelper::_('select.option', $value, $value);
                    }
                }
            }
        }

        // Compute attributes for the grouped list
        $attr = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
        $attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';

        // Prepare HTML code
        $html = [];

        // Compute the current selected values
        $selected = [$this->value];

        // Add a grouped list
        $html[] = HTMLHelper::_(
            'select.groupedlist',
            $groups,
            $this->name,
            ['id' => $this->id, 'group.id' => 'id', 'list.attr' => $attr, 'list.select' => $selected]
        );

        return implode($html);
    }
}
