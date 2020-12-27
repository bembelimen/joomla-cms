<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  FileSystem.Local
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Helper\UserGroupsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Image\Filter\Backgroundfill;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Event\MediaProviderEvent;
use Joomla\Component\Media\Administrator\Provider\ProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Plugin\Filesystem\Virtual\Adapter\VirtualAdapter;

/**
 * FileSystem Local plugin.
 *
 * The plugin to deal with the local filesystem in Media Manager.
 *
 * @since  4.0.0
 */
class PlgFileSystemVirtual extends CMSPlugin implements ProviderInterface
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Setup Providers for Local Adapter
	 *
	 * @param   MediaProviderEvent  $event  Event for ProviderManager
	 *
	 * @return   void
	 *
	 * @since    4.0.0
	 */
	public function onSetupProviders(MediaProviderEvent $event)
	{
		if (Factory::getUser()->authorise('core.admin', 'com_media'))
		{
			$fileactions = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_media/access.xml',
				"/access/section[@name='file']/"
			);

			$categoryactions = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_media/access.xml',
				"/access/section[@name='category']/"
			);

			$accesslevels = HTMLHelper::_('access.assetgroups');

			$usergroups = UserGroupsHelper::getInstance()->getAll();

			$config = [
				'filepermissionactions' => $fileactions,
				'categorypermissionactions' => $categoryactions,
				'accesslevels' => $accesslevels,
				'usergroups' => array_values($usergroups)
			];

			Factory::getDocument()->addScriptOptions('com_media', $config);
		}

		$event->getProviderManager()->registerProvider($this);
	}

	/**
	 * Returns the ID of the provider
	 *
	 * @return  string
	 *
	 * @since  4.0.0
	 */
	public function getID()
	{
		return $this->_name;
	}

	/**
	 * Returns the display name of the provider
	 *
	 * @return string
	 *
	 * @since  4.0.0
	 */
	public function getDisplayName()
	{
		return Text::_('PLG_FILESYSTEM_VIRTUAL_DEFAULT_NAME');
	}

	/**
	 * Returns and array of adapters
	 *
	 * @return  \Joomla\Component\Media\Administrator\Adapter\AdapterInterface[]
	 *
	 * @since  4.0.0
	 */
	public function getAdapters()
	{
		$adapter = new VirtualAdapter;

		return [$adapter];
	}

	/**
	 * Render a media file
	 *
	 * @return void
	 */
	public static function onAjaxVirtual()
	{
		$app = Factory::getApplication();

		$id = (int) $app->input->getInt('id');

		$fileTable = $app->bootComponent('Media')->getMVCFactory()->createModel('File', 'Administrator', ['ignore_request' => true])->getTable('File');

		$fileTable->load($id);

		// Flag for deleting the image after loading it
		$delete = false;

		$abspath = JPATH_SITE . '/' . $fileTable->filepath;

		if (empty($fileTable->id) || !is_file($abspath) || !in_array($fileTable->access, Factory::getUser()->getAuthorisedViewLevels()))
		{
			// Special handling for images (we show an error image)
			// Can be enhanced for other files if we want (default: throw an exception)
			$filename = $fileTable->title . '.' . $fileTable->extension;

			if (!MediaHelper::isImage($filename))
			{
				throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$imagepath = HTMLHelper::_('image', 'plg_filesystem_virtual/error.jpg', '', null, true, 1);

			try
			{
				$errorFile = Path::check(JPATH_ROOT . substr($imagepath, strlen(Uri::root(true))));

				if (!is_file($errorFile))
				{
					throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				$imageInfo = getImageSize($abspath);

				if ($imageInfo === false)
				{
					throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				$errorImage = new Image($errorFile);

				$errorImage->resize($imageInfo[0], $imageInfo[1], false, Image::SCALE_FIT);

				// Joomla default for leftover spaces (when resizing) is black, so we set it to white
				$filter = new Backgroundfill($errorImage->getHandle());

				$filter->execute(['color' => ['red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0]]);

				$abspath = Factory::getApplication()->get('tmp_path') . '/' . uniqid();

				$errorImage->toFile($abspath);

				$fileTable->mime = 'image/jpeg';
				$fileTable->extension = '.jpg';
				$fileTable->filesize = filesize($abspath);

				$delete = true;
			}
			catch (\Exception $e)
			{
				throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		$app->setHeader('Content-Type', $fileTable->mime);
		$app->setHeader('Content-Transfer-Encoding', 'Binary');
		$app->setHeader('Expires', '0');
		$app->setHeader('Cache-Control', 'must-revalidate');
		$app->setHeader('Pragma', 'public');
		$app->setHeader('Content-Length', $fileTable->filesize);
		$app->setHeader('Content-disposition', 'attachment; filename="' . $fileTable->title . '.' . $fileTable->extension . '"');

		$app->sendHeaders();

		$filepath = Path::check($abspath);

		// Prevent output buffering for readfile timeouts
		while (ob_get_level())
		{
			ob_end_clean();
		}

		readfile($filepath);

		if ($delete)
		{
			File::delete($filepath);
		}

		exit;
	}
}
