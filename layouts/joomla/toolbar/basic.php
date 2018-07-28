<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.core');
HTMLHelper::_('webcomponent', 'system/webcomponents/joomla-toolbar-button.min.js', ['relative' => true, 'version' => 'auto', 'detectDebug' => true]);

/**
 * @var  int     $id
 * @var  string  $onclick
 * @var  string  $class
 * @var  string  $text
 * @var  string  $btnClass
 * @var  string  $tagName
 * @var  string  $htmlAttributes
 * @var  string  $task             The task which should be executed
 * @var  bool    $listCheck        Boolean, whether selection from a list is needed
 * @var  string  $form             CSS selector for a target form
 * @var  bool    $formValidation   Whether the form need to be validated before run the task
 */
extract($displayData, EXTR_OVERWRITE);

$tagName = $tagName ?? 'button';

$taskAttr = '';
$idAttr   = !empty($id)             ? ' id="' . $id . '"' : '';
$listAttr = !empty($listCheck)      ? ' list-selection' : '';
$formAttr = !empty($form)           ? ' form="' . $this->escape($form) . '"' : '';
$validate = !empty($formValidation) ? ' form-validation' : '';

if (!empty($task))
{
	$taskAttr = ' task="' . $task . '"';
}
elseif (!empty($onclick))
{
	$htmlAttributes .= ' onclick="' . $onclick . '"';
}

?>
<joomla-toolbar-button <?php echo $idAttr.$taskAttr.$listAttr.$formAttr.$validate.$msgAttr; ?>>
<<?php echo $tagName; ?>
	class="<?php echo $btnClass ?? ''; ?>"
	<?php echo $htmlAttributes ?? ''; ?>
	>
	<span class="<?php echo trim($class ?? ''); ?>" aria-hidden="true"></span>
	<?php echo $text ?? ''; ?>
</<?php echo $tagName; ?>>
</joomla-toolbar-button>
