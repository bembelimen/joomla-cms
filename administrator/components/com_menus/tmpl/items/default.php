<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getUser();
$app       = Factory::getApplication();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$ordering  = ($listOrder == 'a.lft');
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');
$menuType  = (string) $app->getUserState('com_menus.items.menutype', '', 'string');

if ($saveOrder && $menuType && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_menus&task=items.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$assoc   = Associations::isEnabled() && $this->state->get('filter.client_id') == 0;

?>
<?php // Set up the filter bar. ?>
<form action="<?php echo Route::_('index.php?option=com_menus&view=items'); ?>" method="post" name="adminForm"
      id="adminForm">
	<div class="row">
		<?php if (!empty($this->sidebar)) : ?>
        <div id="j-sidebar-container" class="col-md-2">
            <?php echo $this->sidebar; ?>
        </div>
		<?php endif; ?>
        <div class="<?php if (!empty($this->sidebar)) {echo 'col-md-10'; } else { echo 'col-md-12'; } ?>">
			<div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('selectorFieldName' => 'menutype'))); ?>
				<?php if (empty($this->items)) : ?>
					<joomla-alert type="warning"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></joomla-alert>
				<?php else : ?>
					<table class="table" id="itemList">
						<thead>
						<tr>
							<?php if ($menuType) : ?>
								<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
								</th>
							<?php endif; ?>
							<td style="width:1%" class="text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" style="width:1%" class="text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" style="width:10%" class="d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_MENUS_HEADING_MENU', 'menutype_title', $listDirn, $listOrder); ?>
							</th>
							<?php if ($this->state->get('filter.client_id') == 0) : ?>
								<th scope="col" style="width:10%" class="text-center d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_MENUS_HEADING_HOME', 'a.home', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<?php if ($this->state->get('filter.client_id') == 0) : ?>
								<th scope="col" style="width:10%" class="d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<?php if ($assoc) : ?>
								<th scope="col" style="width:10%" class="d-none d-md-table-cell text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_MENUS_HEADING_ASSOCIATION', 'association', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<?php if (($this->state->get('filter.client_id') == 0) && (Multilanguage::isEnabled())) : ?>
								<th scope="col" style="width:10%" class="d-none d-md-table-cell text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<th scope="col" style="width:5%" class="d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody <?php if ($saveOrder && $menuType) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
						<?php
						foreach ($this->items as $i => $item) :
							$orderkey = array_search($item->id, $this->ordering[$item->parent_id]);
							$canCreate = $user->authorise('core.create', 'com_menus.menu.' . $item->menutype_id);
							$canEdit = $user->authorise('core.edit', 'com_menus.menu.' . $item->menutype_id);
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
							$canChange = $user->authorise('core.edit.state', 'com_menus.menu.' . $item->menutype_id) && $canCheckin;

							// Get the parents of item for sorting
							if ($item->level > 1)
							{
								$parentsStr       = '';
								$_currentParentId = $item->parent_id;
								$parentsStr       = ' ' . $_currentParentId;

								for ($j = 0; $j < $item->level; $j++)
								{
									foreach ($this->ordering as $k => $v)
									{
										$v = implode('-', $v);
										$v = '-' . $v . '-';

										if (strpos($v, '-' . $_currentParentId . '-') !== false)
										{
											$parentsStr .= ' ' . $k;
											$_currentParentId = $k;
											break;
										}
									}
								}
							}
							else
							{
								$parentsStr = '';
							}
							?>
							<tr class="row<?php echo $i % 2; ?>" data-dragable-group="<?php echo $item->parent_id; ?>"
							    item-id="<?php echo $item->id; ?>" parents="<?php echo $parentsStr; ?>"
							    level="<?php echo $item->level; ?>">
								<?php if ($menuType) : ?>
									<td class="order text-center d-none d-md-table-cell">
										<?php
										$iconClass = '';

										if (!$canChange)
										{
											$iconClass = ' inactive';
										}
										elseif (!$saveOrder)
										{
											$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
										}
										?>
										<span class="sortable-handler<?php echo $iconClass ?>">
											<span class="icon-menu" aria-hidden="true"></span>
										</span>
										<?php if ($canChange && $saveOrder) : ?>
											<input type="text" style="display:none" name="order[]" size="5"
											       value="<?php echo $orderkey + 1; ?>">
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="text-center">
									<?php
									// Show protected items as published always. We don't allow state change for them. Show/Hide is the module's job.
									$published = $item->protected ? 3 : $item->published;
									echo HTMLHelper::_('menus.state', $published, $i, $canChange && !$item->protected, 'cb'); ?>
								</td>
								<th scope="row">
									<?php $prefix = LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
									<?php echo $prefix; ?>
									<?php if ($item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'items.', $canCheckin); ?>
									<?php endif; ?>
									<?php if ($canEdit && !$item->protected) : ?>
										<?php $editIcon = $item->checked_out ? '' : '<span class="fa fa-pencil-square mr-2" aria-hidden="true"></span>'; ?>
										<a class="hasTooltip"
										   href="<?php echo Route::_('index.php?option=com_menus&task=item.edit&id=' . (int) $item->id); ?>"
										   title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape(addslashes($item->title)); ?>">
											<?php echo $editIcon; ?><?php echo $this->escape($item->title); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->title); ?>
									<?php endif; ?>
									<span class="small">
									<?php if ($item->type != 'url') : ?>
										<?php if (empty($item->note)) : ?>
											<?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
										<?php else : ?>
											<?php echo Text::sprintf('JGLOBAL_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->note)); ?>
										<?php endif; ?>
									<?php elseif ($item->type == 'url' && $item->note) : ?>
										<?php echo Text::sprintf('JGLOBAL_LIST_NOTE', $this->escape($item->note)); ?>
									<?php endif; ?>
									</span>
									<?php echo HTMLHelper::_('menus.visibility', $item->params); ?>
									<div title="<?php echo $this->escape($item->path); ?>">
										<?php echo $prefix; ?>
										<span class="small"
										      title="<?php echo isset($item->item_type_desc) ? htmlspecialchars($this->escape($item->item_type_desc), ENT_COMPAT, 'UTF-8') : ''; ?>">
											<?php echo $this->escape($item->item_type); ?></span>
									</div>
								</th>
								<td class="small d-none d-md-table-cell">
									<?php echo $this->escape($item->menutype_title ?: ucwords($item->menutype)); ?>
								</td>
								<?php if ($this->state->get('filter.client_id') == 0) : ?>
									<td class="text-center d-none d-md-table-cell">
										<?php if ($item->type == 'component') : ?>
											<?php if ($item->language == '*' || $item->home == '0') : ?>
												<?php echo HTMLHelper::_('jgrid.isdefault', $item->home, $i, 'items.', ($item->language != '*' || !$item->home) && $canChange && !$item->protected); ?>
											<?php elseif ($canChange) : ?>
												<a href="<?php echo Route::_('index.php?option=com_menus&task=items.unsetDefault&cid[]=' . $item->id . '&' . Session::getFormToken() . '=1'); ?>">
													<?php if ($item->language_image) : ?>
														<?php echo HTMLHelper::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => Text::sprintf('COM_MENUS_GRID_UNSET_LANGUAGE', $item->language_title)), true); ?>
													<?php else : ?>
														<span class="badge badge-secondary"
														      title="<?php echo Text::sprintf('COM_MENUS_GRID_UNSET_LANGUAGE', $item->language_title); ?>"><?php echo $item->language_sef; ?></span>
													<?php endif; ?>
												</a>
											<?php else : ?>
												<?php if ($item->language_image) : ?>
													<?php echo HTMLHelper::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => $item->language_title), true); ?>
												<?php else : ?>
													<span class="badge badge-secondary"
													      title="<?php echo $item->language_title; ?>"><?php echo $item->language_sef; ?></span>
												<?php endif; ?>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<?php if ($this->state->get('filter.client_id') == 0) : ?>
									<td class="small d-none d-md-table-cell">
										<?php echo $this->escape($item->access_level); ?>
									</td>
								<?php endif; ?>
								<?php if ($assoc) : ?>
									<td class="small d-none d-md-table-cell text-center">
										<?php if ($item->association) : ?>
											<?php echo HTMLHelper::_('menus.association', $item->id); ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<?php if ($this->state->get('filter.client_id') == 0 && Multilanguage::isEnabled()) : ?>
									<td class="small d-none d-md-table-cell text-center">
										<?php echo LayoutHelper::render('joomla.content.language', $item); ?>
									</td>
								<?php endif; ?>
								<td class="d-none d-md-table-cell">
									<?php echo (int) $item->id; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // load the pagination. ?>
					<?php echo $this->pagination->getListFooter(); ?>

					<?php // Load the batch processing form if user is allowed ?>
					<?php if ($user->authorise('core.create', 'com_menus') || $user->authorise('core.edit', 'com_menus')) : ?>
						<?php echo HTMLHelper::_(
							'bootstrap.renderModal',
							'collapseModal',
							array(
								'title'  => Text::_('COM_MENUS_BATCH_OPTIONS'),
								'footer' => $this->loadTemplate('batch_footer')
							),
							$this->loadTemplate('batch_body')
						); ?>
					<?php endif; ?>
				<?php endif; ?>

				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
