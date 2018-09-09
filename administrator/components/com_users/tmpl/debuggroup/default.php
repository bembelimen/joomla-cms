<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_users&view=debuggroup&group_id=' . (int) $this->state->get('group_id')); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container" class="j-main-container">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_USERS_HEADING_ASSET_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_USERS_HEADING_ASSET_NAME', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<?php foreach ($this->actions as $key => $action) : ?>
						<th style="width:6%" class="text-center">
							<span class="hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', $key, $action[1]); ?>"><?php echo Text::_($key); ?></span>
						</th>
						<?php endforeach; ?>
						<th style="width:6%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_USERS_HEADING_LFT', 'a.lft', $listDirn, $listOrder); ?>
						</th>
						<th style="width:3%">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<tr class="row0">
							<td>
								<?php echo $this->escape($item->title); ?>
							</td>
							<td>
								<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level + 1)) . $this->escape($item->name); ?>
							</td>
							<?php foreach ($this->actions as $action) : ?>
								<?php
								$name  = $action[0];
								$check = $item->checks[$name];
								if ($check === true) :
									$class  = 'text-success icon-ok';
									$button = 'btn-success';
								elseif ($check === false) :
									$class  = 'icon-remove';
									$button = 'btn-danger';
								elseif ($check === null) :
									$class  = 'text-danger icon-ban-circle';
									$button = 'btn-warning';
								else :
									$class  = '';
									$button = '';
								endif;
								?>
							<td class="text-center">
								<span class="<?php echo $class; ?>"></span>
							</td>
							<?php endforeach; ?>
							<td>
								<?php echo (int) $item->lft; ?>
								- <?php echo (int) $item->rgt; ?>
							</td>
							<td>
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="legend">
				<?php echo Text::_('COM_USERS_DEBUG_LEGEND'); ?>
				<span class="text-danger icon-ban-circle"></span><?php echo Text::_('COM_USERS_DEBUG_IMPLICIT_DENY'); ?>&nbsp;
				<span class="text-success icon-ok"></span><?php echo Text::_('COM_USERS_DEBUG_EXPLICIT_ALLOW'); ?>&nbsp;
				<span class="icon-remove"></span><?php echo Text::_('COM_USERS_DEBUG_EXPLICIT_DENY'); ?>
			</div>

			<?php // load the pagination. ?>
			<?php echo $this->pagination->getListFooter(); ?>

		</div>
		<input type="hidden" name="task" value="">
		<input type="hidden" name="boxchecked" value="0">
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
