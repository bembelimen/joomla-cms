<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_privacy
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var PrivacyViewConsent $this */

HTMLHelper::_('behavior.multiselect');

$user       = Factory::getUser();
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$now        = Factory::getDate();
$stateIcons = array(-1 => 'delete', 0 => 'archive', 1 => 'publish');
$stateMsgs  = array(
	-1 => Text::_('COM_PRIVACY_CONSENTS_STATE_INVALIDATED'),
	0 => Text::_('COM_PRIVACY_CONSENTS_STATE_OBSOLETE'),
	1 => Text::_('COM_PRIVACY_CONSENTS_STATE_VALID')
);

?>
<form action="<?php echo Route::_('index.php?option=com_privacy&view=consents'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-info">
				<span class="fas fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table" id="consentList">
				<caption id="captionTable" class="sr-only">
					<?php echo Text::_('COM_PRIVACY_TABLE_CONSENTS_CAPTION'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
				</caption>
				<thead>
					<tr>
						<td class="w-1 text-center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</td>
						<th scope="col" class="w-5 text-center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th scope="col" width="10%">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_USERNAME', 'u.username', $listDirn, $listOrder); ?>
						</th>
						<th scope="col" width="10%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_PRIVACY_HEADING_NAME', 'u.name', $listDirn, $listOrder); ?>
						</th>
						<th scope="col" width="1%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_PRIVACY_HEADING_USERID', 'a.user_id', $listDirn, $listOrder); ?>
						</th>
						<th scope="col" width="10%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_PRIVACY_HEADING_CONSENTS_SUBJECT', 'a.subject', $listDirn, $listOrder); ?>
						</th>
						<th scope="col">
							<?php echo Text::_('COM_PRIVACY_HEADING_CONSENTS_BODY'); ?>
						</th>
						<th scope="col" width="15%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_PRIVACY_HEADING_CONSENTS_CREATED', 'a.created', $listDirn, $listOrder); ?>
						</th>
						<th scope="col" width="1%">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<tr>
							<td class="center">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="tbody-icon">
								<span class="icon-<?php echo $stateIcons[$item->state]; ?>" aria-hidden="true" title="<?php echo $stateMsgs[$item->state]; ?>"></span>
								<span class="sr-only"><?php echo $stateMsgs[$item->state]; ?>"></span>
							</td>
							<th scope="row">
								<?php echo $item->username; ?>
							</th>
							<td>
								<?php echo $item->name; ?>
							</td>
							<td>
								<?php echo $item->user_id; ?>
							</td>
							<td>
								<?php echo Text::_($item->subject); ?>
							</td>
							<td>
								<?php echo $item->body; ?>
							</td>
							<td class="break-word">
								<?php echo HTMLHelper::_('date.relative', new Date($item->created), null, $now); ?>
								<div class="small">
									<?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC6')); ?>
								</div>
							</td>
							<td>
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
