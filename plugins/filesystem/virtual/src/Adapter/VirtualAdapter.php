<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Filesystem.Virtual
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Filesystem\Virtual\Adapter;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use Joomla\Component\Media\Administrator\Exception\InvalidPathException;
use Joomla\Utilities\ArrayHelper;

/**
 * Virtual file adapter.
 *
 * @since  4.0.0
 */
class VirtualAdapter implements AdapterInterface
{
	protected $folderpath = 'media/plg_filesystem_virtual/files';

	/**
	 * Returns the requested file or folder. The returned object
	 * has the following properties available:
	 * - type:          The type can be file or dir
	 * - name:          The name of the file
	 * - path:          The relative path to the root
	 * - extension:     The file extension
	 * - size:          The size of the file
	 * - create_date:   The date created
	 * - modified_date: The date modified
	 * - mime_type:     The mime type
	 * - width:         The width, when available
	 * - height:        The height, when available
	 *
	 * If the path doesn't exist a FileNotFoundException is thrown.
	 *
	 * @param   string  $path  The path to the file or folder
	 *
	 * @return  \stdClass
	 *
	 * @since   4.0.0
	 */
	public function getFile(string $path = '/'): \stdClass
	{
		$parts = explode('/', Path::clean($path, '/'));
		$parts = \array_map(['\\Joomla\\CMS\\Filter\\OutputFilter', 'stringUrlSafe'], $parts);

		$fullpath = ltrim(implode('/', $parts), '/');

		$file = array_pop($parts);
		$cleanpath = implode('/', $parts);

		if (empty($cleanpath))
		{
			$cleanpath = '/';
		}

		$category = $this->loadCategory($cleanpath);

		$children = $category->getChildren();

		foreach ($children as $child)
		{
			if ($file && $child->path == $fullpath)
			{
				return $this->getFolderInformation($child);
			}
		}

		// Check if file
		return $this->getFileInformation($path);
	}

	/**
	 * Returns the folders and files for the given path. The returned objects
	 * have the following properties available:
	 * - type:          The type can be file or dir
	 * - name:          The name of the file
	 * - path:          The relative path to the root
	 * - extension:     The file extension
	 * - size:          The size of the file
	 * - create_date:   The date created
	 * - modified_date: The date modified
	 * - mime_type:     The mime type
	 * - width:         The width, when available
	 * - height:        The height, when available
	 *
	 * If the path doesn't exist a FileNotFoundException is thrown.
	 *
	 * @param   string  $path  The folder
	 *
	 * @return  \stdClass[]
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function getFiles(string $path = '/'): array
	{
		// The data to return
		$data = [];

		// If this is a file, we're good
		try
		{
			$file = $this->getFileInformation($path);

			if ($file)
			{
				return [$file];
			}
		}
		catch (FileNotFoundException $e)
		{
			// Everything ok
		}

		$folders = $this->loadCategories($path);

		// Read the folders
		foreach ($folders as $folder)
		{
			$data[] = $this->getFolderInformation($folder);
		}

		$files = $this->loadFiles($path);

		// Read the files
		foreach ($files as $file)
		{
			$data[] = $this->getFileInformation($file->path);
		}

		// Return the data
		return $data;
	}

	/**
	 * Loads the categories base on a given path
	 *
	 * @param   string $path
	 *
	 * @return \Joomla\Component\Media\Site\Service\Category
	 */
	protected function loadCategories($path)
	{
		$category = $this->loadCategory($path);

		return $category->getChildren();
	}

	/**
	 * Load the category node object based on a path
	 *
	 * @param   string $path
	 *
	 * @return CategoryNode
	 */
	protected function loadCategory($path)
	{
		$id = 'root';

		if (!empty($path) && $path !== '/')
		{
			$categoryTable = $this->loadCategoryTable($path);

			if (empty($categoryTable->id))
			{
				throw new FileNotFoundException;
			}

			$id = (int) $categoryTable->id;
		}

		return Categories::getInstance('Media')->get($id, true);
	}

	/**
	 * Load the file object based on a path
	 *
	 * @param   string $path
	 *
	 * @return stdClass
	 */
	protected function loadFile($path)
	{
		$fileTable = $this->loadFileTable($path);

		if (empty($fileTable->id))
		{
			throw new FileNotFoundException;
		}

		return (object) $fileTable->getProperties();
	}

	protected function loadFiles($path)
	{
		$category = $this->loadCategory($path);

		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('title'),
				$db->quoteName('alias'),
				$db->quoteName('extension'),
				$db->quoteName('access'),
				$db->quoteName('catid'),
				$db->quoteName('filepath'),
				$db->quoteName('created'),
				$db->quoteName('modified'),
			]
		)
			->from($db->quoteName('#__media_files'))
			->where($db->quoteName('catid') . ' = ' . (int) $category->id);

		$files = $db->setQuery($query)->loadObjectList();

		foreach ($files as $file)
		{
			$file->path = $category->path . '/' . $file->alias . '.' . $file->extension;
		}

		return $files;
	}

	protected function loadCategoryTable(string $path)
	{
		$categoryTable = Factory::getApplication()->bootComponent('Categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true])->getTable('Category');

		$cleanpath = ltrim($path, '/');

		$categoryTable->load([
			'extension' => 'com_media',
			'path' => $cleanpath
			]
		);

		return $categoryTable;
	}

	protected function loadFileTable($path)
	{
		$catpath = \dirname($path);

		$category = $this->loadCategory($catpath);

		$alias = OutputFilter::stringUrlSafe(File::stripExt(\basename($path)));

		$fileTable = Factory::getApplication()->bootComponent('Media')->getMVCFactory()->createModel('File', 'Administrator', ['ignore_request' => true])->getTable('File');

		$fileTable->load([
			'catid' => (int) $category->id,
			'alias' => $alias
			]
		);

		return $fileTable;
	}

	/**
	 * Returns a resource to download the path.
	 *
	 * @param   string  $path  The path to download
	 *
	 * @return  resource
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function getResource(string $path)
	{
		$file = $this->loadFile($path);

		return fopen(JPATH_ROOT . '/' . $file->filepath, 'r');
	}

	/**
	 * Creates a folder with the given name in the given path.
	 *
	 * It returns the new folder name. This allows the implementation
	 * classes to normalise the file name.
	 *
	 * @param   string  $name  The name
	 * @param   string  $path  The folder
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function createFolder(string $name, string $path): string
	{
		$category = $this->loadCategory($path);

		$data = [
			'title' => $name,
			'published' => 1,
			'parent_id' => $category->id,
			'extension' => 'com_media'
		];

		CategoriesHelper::createCategory($data);

		return $name;
	}

	/**
	 * Creates a file with the given name in the given path with the data.
	 *
	 * It returns the new file name. This allows the implementation
	 * classes to normalise the file name.
	 *
	 * @param   string  $name  The name
	 * @param   string  $path  The folder
	 * @param   binary  $data  The data
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function createFile(string $name, string $path, $data): string
	{
		$name = $this->getSafeName($name);

		$category = $this->loadCategory($path);

		$filepath = substr(sha1($data . \uniqid()), 0, 2) . '/' . substr(sha1($data), 0, 2);

		$filename = sha1(\uniqid());

		$localPath = JPATH_ROOT . '/' . $this->folderpath . '/' . $filepath . '/';

		while (\file_exists($localPath . $filename))
		{
			$filename = sha1(\uniqid());
		}

		$this->checkContent($name, $localPath . $filename, $data);

		File::write($localPath . $filename, $data);

		$fileTable = Factory::getApplication()->bootComponent('Media')->getMVCFactory()->createModel('File', 'Administrator', ['ignore_request' => true])->getTable('File');

		$file = new \stdClass;

		$file->title = File::stripExt($name);
		$file->extension = File::getExt($name);
		$file->mime = MediaHelper::getMimeType($localPath . $filename, MediaHelper::isImage($file->title . '.' . $file->extension));
		$file->catid = (int) $category->id;
		$file->filepath = $this->folderpath . '/' . $filepath . '/' . $filename;
		$file->filesize = filesize($localPath . $filename);

		$result = $fileTable->save($file);

		if (!$result)
		{
			throw new \Exception($fileTable->getError(), 500);
		}

		return $name;
	}

	/**
	 * Updates the file with the given name in the given path with the data.
	 *
	 * @param   string  $name  The name
	 * @param   string  $path  The folder
	 * @param   binary  $data  The data
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	public function updateFile(string $name, string $path, $data)
	{
		$localPath = $this->getLocalPath($path . '/' . $name);

		if (!File::exists($localPath))
		{
			throw new FileNotFoundException;
		}

		$this->checkContent($name, $localPath, $data);

		File::write($localPath, $data);
	}


	/**
	 * Deletes the folder or file of the given path.
	 *
	 * @param   string  $path  The path to the file or folder
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function delete(string $path)
	{
		try
		{
			$categoryTable = $this->loadCategoryTable($path);

			if (empty($categoryTable->id))
			{
				throw new FileNotFoundException;
			}

			// Delete files
			$db = Factory::getDbo();

			$tree = $categoryTable->getTree();

			$catids = ArrayHelper::getColumn($tree, 'id');
			$catids = ArrayHelper::toInteger($catids);

			$query = $db->getQuery(true);

			$query	->select($db->quoteName('filepath'))
				->from($db->quoteName('#__media_files'))
				->where($db->quoteName('catid') . ' IN(' . implode(',', $catids) . ')');

			$filepaths = $db->setQuery($query)->loadColumn();

			$query->clear('select')->delete();

			$db->setQuery($query)->execute();

			foreach ($filepaths as $filepath)
			{
				File::delete(JPATH_ROOT . '/' . $filepath);
			}

			// Delete categories
			$categoryTable->delete();

			return;
		}
		catch (FileNotFoundException $e)
		{
			// Do nothing
		}

		// No folder, assume it's a file
		$file = $this->loadFileTable($path);

		File::delete(JPATH_ROOT . '/' . $file->filepath);

		$file->delete();
	}

	/**
	 * Load the folder information. The returned objects
	 * have the following properties available:
	 * - type:          The type can be file or dir
	 * - name:          The name of the file
	 * - path:          The relative path to the root
	 * - extension:     The file extension
	 * - size:          The size of the file
	 * - create_date:   The date created
	 * - modified_date: The date modified
	 * - mime_type:     The mime type
	 * - width:         The width, when available
	 * - height:        The height, when available
	 * - permission     The permissions set in this item, when core.admin
	 *
	 * @param   CategoryNode $folder
	 *
	 * @return stdClass
	 */
	private function getFolderInformation(CategoryNode $folder)
	{
		$createDate   = $this->getDate($folder->created_time);
		$modifiedDate = $this->getDate($folder->modified_time);

		// Set the values
		$obj            = new \stdClass;
		$obj->type      = 'dir';
		$obj->name      = $folder->title;
		$obj->path      = '/' . $folder->path;
		$obj->extension = '';
		$obj->size      = '';
		$obj->mime_type = 'directory';
		$obj->width     = 0;
		$obj->height    = 0;

		// Dates
		$obj->create_date             = $createDate->format('c', true);
		$obj->create_date_formatted   = HTMLHelper::_('date', $createDate, Text::_('DATE_FORMAT_LC5'));
		$obj->modified_date           = $modifiedDate->format('c', true);
		$obj->modified_date_formatted = HTMLHelper::_('date', $modifiedDate, Text::_('DATE_FORMAT_LC5'));

		$obj->rules = [];

		if ($folder->asset_id > 0 && Factory::getUser()->authorise('core.admin', 'com_media'))
		{
			$rules = Access::getAssetRules($folder->asset_id, false, false)->getData();

			foreach ($rules as $name => $rule)
			{
				$obj->rules[$name] = $rule->getData();
			}
		}

		$obj->permissions = ContentHelper::getActions('com_media', 'category', $folder->id)->getProperties();

		return $obj;
	}

	/**
	 * Returns the folder or file information for the given path. The returned object
	 * has the following properties:
	 * - type:          The type can be file or dir
	 * - name:          The name of the file
	 * - path:          The relative path to the root
	 * - extension:     The file extension
	 * - size:          The size of the file
	 * - create_date:   The date created
	 * - modified_date: The date modified
	 * - mime_type:     The mime type
	 * - width:         The width, when available
	 * - height:        The height, when available
	 * - thumb_path     The thumbnail path of file, when available
	 * - permission     The permissions set in this item, when core.admin
	 *
	 * @param   string  $path  The folder
	 *
	 * @return  \stdClass
	 *
	 * @since   4.0.0
	 */
	private function getFileInformation(string $path): \stdClass
	{
		$file = $this->loadFile($path);

		$createDate   = $this->getDate($file->created);
		$modifiedDate = $this->getDate($file->modified);

		// Set the values
		$obj            = new \stdClass;
		$obj->id		= (int) $file->id;
		$obj->type      = 'file';
		$obj->name      = $file->title . '.' . $file->extension;
		$obj->path      = '/' . ltrim($path, '/');
		$obj->extension = $file->extension;
		$obj->size      = (int) $file->filesize;
		$obj->mime_type = $file->mime;
		$obj->width     = 0;
		$obj->height    = 0;

		// Dates
		$obj->create_date             = $createDate->format('c', true);
		$obj->create_date_formatted   = HTMLHelper::_('date', $createDate, Text::_('DATE_FORMAT_LC5'));
		$obj->modified_date           = $modifiedDate->format('c', true);
		$obj->modified_date_formatted = HTMLHelper::_('date', $modifiedDate, Text::_('DATE_FORMAT_LC5'));

		if (MediaHelper::isImage($file->title . '.' . $file->extension))
		{
			// Get the image properties
			$props       = Image::getImageFileProperties(JPATH_ROOT . '/' . $file->filepath);
			$obj->width  = $props->width;
			$obj->height = $props->height;

			// Todo : Change this path to an actual thumbnail path
			$obj->thumb_path = $this->getUrl($path);
		}

		$obj->rules = [];

		if ($file->asset_id > 0 && Factory::getUser()->authorise('core.admin', 'com_media'))
		{
			$rules = Access::getAssetRules($file->asset_id, false, false)->getData();

			foreach ($rules as $name => $rule)
			{
				$obj->rules[$name] = $rule->getData();
			}
		}

		$obj->permissions = ContentHelper::getActions('com_media', 'file', $file->id)->getProperties();

		return $obj;
	}

	/**
	 * Returns a Date with the correct Joomla timezone for the given date.
	 *
	 * @param   string  $date  The date to create a Date from
	 *
	 * @return  Date
	 *
	 * @since   4.0.0
	 */
	private function getDate($date = null): Date
	{
		$dateObj = Factory::getDate($date);

		$timezone = Factory::getApplication()->get('offset');
		$user     = Factory::getUser();

		if ($user->id)
		{
			$userTimezone = $user->getParam('timezone');

			if (!empty($userTimezone))
			{
				$timezone = $userTimezone;
			}
		}

		if ($timezone)
		{
			$dateObj->setTimezone(new \DateTimeZone($timezone));
		}

		return $dateObj;
	}

	/**
	 * Copies a file or folder from source to destination.
	 *
	 * It returns the new destination path. This allows the implementation
	 * classes to normalise the file name.
	 *
	 * @param   string  $sourcePath       The source path
	 * @param   string  $destinationPath  The destination path
	 * @param   bool    $force            Force to overwrite
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		// Get absolute paths from relative paths
		$sourcePath      = Path::clean($this->getLocalPath($sourcePath), '/');
		$destinationPath = Path::clean($this->getLocalPath($destinationPath), '/');

		if (!file_exists($sourcePath))
		{
			throw new FileNotFoundException;
		}

		$name     = $this->getFileName($destinationPath);
		$safeName = $this->getSafeName($name);

		// If the safe name is different normalise the file name
		if ($safeName != $name)
		{
			$destinationPath = substr($destinationPath, 0, -strlen($name)) . '/' . $safeName;
		}

		// Check for existence of the file in destination
		// if it does not exists simply copy source to destination
		if (is_dir($sourcePath))
		{
			$this->copyFolder($sourcePath, $destinationPath, $force);
		}
		else
		{
			$this->copyFile($sourcePath, $destinationPath, $force);
		}

		// Get the relative path
		$destinationPath = str_replace($this->rootPath, '', $destinationPath);

		return $destinationPath;
	}

	/**
	 * Copies a file
	 *
	 * @param   string  $sourcePath       Source path of the file or directory
	 * @param   string  $destinationPath  Destination path of the file or directory
	 * @param   bool    $force            Set true to overwrite files or directories
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	private function copyFile(string $sourcePath, string $destinationPath, bool $force = false)
	{
		if (is_dir($destinationPath))
		{
			// If the destination is a folder we create a file with the same name as the source
			$destinationPath = $destinationPath . '/' . $this->getFileName($sourcePath);
		}

		if (file_exists($destinationPath) && !$force)
		{
			throw new \Exception('Copy file is not possible as destination file already exists');
		}

		if (!File::copy($sourcePath, $destinationPath))
		{
			throw new \Exception('Copy file is not possible');
		}
	}

	/**
	 * Copies a folder
	 *
	 * @param   string  $sourcePath       Source path of the file or directory
	 * @param   string  $destinationPath  Destination path of the file or directory
	 * @param   bool    $force            Set true to overwrite files or directories
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @Todo Implement
	 */
	private function copyFolder(string $sourcePath, string $destinationPath, bool $force = false)
	{
		if (file_exists($destinationPath) && !$force)
		{
			throw new \Exception('Copy folder is not possible as destination folder already exists');
		}

		if (is_file($destinationPath) && !File::delete($destinationPath))
		{
			throw new \Exception('Copy folder is not possible as destination folder is a file and can not be deleted');
		}

		if (!Folder::copy($sourcePath, $destinationPath, '', $force))
		{
			throw new \Exception('Copy folder is not possible');
		}
	}

	/**
	 * Moves a file or folder from source to destination.
	 *
	 * It returns the new destination path. This allows the implementation
	 * classes to normalise the file name.
	 *
	 * @param   string  $sourcePath       The source path
	 * @param   string  $destinationPath  The destination path
	 * @param   bool    $force            Force to overwrite
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	public function move(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		// Get absolute paths from relative paths
		$sourcePath      = Path::clean($this->getLocalPath($sourcePath), '/');
		$destinationPath = Path::clean($this->getLocalPath($destinationPath), '/');

		if (!file_exists($sourcePath))
		{
			throw new FileNotFoundException;
		}

		$name     = $this->getFileName($destinationPath);
		$safeName = $this->getSafeName($name);

		// If the safe name is different normalise the file name
		if ($safeName != $name)
		{
			$destinationPath = substr($destinationPath, 0, -strlen($name)) . '/' . $safeName;
		}

		if (is_dir($sourcePath))
		{
			$this->moveFolder($sourcePath, $destinationPath, $force);
		}
		else
		{
			$this->moveFile($sourcePath, $destinationPath, $force);
		}

		// Get the relative path
		$destinationPath = str_replace($this->rootPath, '', $destinationPath);

		return $destinationPath;
	}

	/**
	 * Moves a file
	 *
	 * @param   string  $sourcePath       Absolute path of source
	 * @param   string  $destinationPath  Absolute path of destination
	 * @param   bool    $force            Set true to overwrite file if exists
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	private function moveFile(string $sourcePath, string $destinationPath, bool $force = false)
	{
		if (is_dir($destinationPath))
		{
			// If the destination is a folder we create a file with the same name as the source
			$destinationPath = $destinationPath . '/' . $this->getFileName($sourcePath);
		}

		if (file_exists($destinationPath) && !$force)
		{
			throw new \Exception('Move file is not possible as destination file already exists');
		}

		if (!File::move($sourcePath, $destinationPath))
		{
			throw new \Exception('Move file is not possible');
		}
	}

	/**
	 * Moves a folder from source to destination
	 *
	 * @param   string  $sourcePath       Source path of the file or directory
	 * @param   string  $destinationPath  Destination path of the file or directory
	 * @param   bool    $force            Set true to overwrite files or directories
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Implement
	 */
	private function moveFolder(string $sourcePath, string $destinationPath, bool $force = false)
	{
		try
		{
			$destinationCategory = $this->loadCategory($destinationPath);
		}
		catch (FileNotFoundException $e)
		{
			// Everything ok
		}

		if (!empty($destinationCategory->id) && !$force)
		{
			throw new \Exception('Move folder is not possible as destination folder already exists');
		}

		// TODO check file

		if (is_file($destinationPath) && !File::delete($destinationPath))
		{
			throw new \Exception('Move folder is not possible as destination folder is a file and can not be deleted');
		}

		if (is_dir($destinationPath))
		{
			// We need to bypass exception thrown in JFolder when destination exists
			// So we only copy it in forced condition, then delete the source to simulate a move
			if (!Folder::copy($sourcePath, $destinationPath, '', true))
			{
				throw new \Exception('Move folder to an existing destination failed');
			}

			// Delete the source
			Folder::delete($sourcePath);

			return;
		}

		// Perform usual moves
		$value = Folder::move($sourcePath, $destinationPath);

		if ($value !== true)
		{
			throw new \Exception($value);
		}
	}

	/**
	 * Returns a url which can be used to display an image from within the "images" directory.
	 *
	 * @param   string  $path  Path of the file relative to adapter
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 */
	public function getUrl(string $path): string
	{
		$file = $this->loadFile($path);

		// @TODO use clean method to route to frontend
		return 'index.php?option=com_ajax&plugin=virtual&group=filesystem&format=raw&id=' . (int) $file->id . ':' . $file->alias . '.' . $file->extension;
	}

	/**
	 * Returns the name of this adapter.
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 */
	public function getAdapterName(): string
	{
		return 'media';
	}

	/**
	 * Search for a pattern in a given path
	 *
	 * @param   string  $path       The base path for the search
	 * @param   string  $needle     The path to file
	 * @param   bool    $recursive  Do a recursive search
	 *
	 * @return  \stdClass[]
	 *
	 * @since   4.0.0
	 *
	 * @todo Implement
	 */
	public function search(string $path, string $needle, bool $recursive = false): array
	{
		$pattern = Path::clean($this->getLocalPath($path) . '/*' . $needle . '*');

		if ($recursive)
		{
			$results = $this->rglob($pattern);
		}
		else
		{
			$results = glob($pattern);
		}

		$searchResults = [];

		foreach ($results as $result)
		{
			$searchResults[] = $this->getPathInformation($result);
		}

		return $searchResults;
	}

	/**
	 * Do a recursive search on a given path
	 *
	 * @param   string  $pattern  The pattern for search
	 * @param   int     $flags    Flags for search
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	private function rglob(string $pattern, int $flags = 0): array
	{
		$files = glob($pattern, $flags);

		foreach (glob(\dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
		{
			$files = array_merge($files, $this->rglob($dir . '/' . $this->getFileName($pattern), $flags));
		}

		return $files;
	}

	/**
	 * Returns a temporary url for the given path.
	 * This is used internally in media manager
	 *
	 * @param   string  $path  The path to file
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  FileNotFoundException
	 */
	public function getTemporaryUrl(string $path): string
	{
		return $this->getUrl($path);
	}

	/**
	 * Creates a safe file name for the given name.
	 *
	 * @param   string  $name  The filename
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	private function getSafeName(string $name): string
	{
		// Make the filename safe
		$name = File::makeSafe($name);

		// Transform filename to punycode
		$name = PunycodeHelper::toPunycode($name);

		// Get the extension
		$extension = File::getExt($name);

		// Normalise extension, always lower case
		if ($extension)
		{
			$extension = '.' . strtolower($extension);
		}

		$nameWithoutExtension = substr($name, 0, \strlen($name) - \strlen($extension));

		return $nameWithoutExtension . $extension;
	}

	/**
	 * Performs various check if it is allowed to save the content with the given name.
	 *
	 * @param   string  $localPath     The local path
	 * @param   string  $mediaContent  The media content
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	private function checkContent(string $name, string $localPath, string $mediaContent)
	{
		// The helper
		$helper = new MediaHelper;

		// @todo find a better way to check the input, by not writing the file to the disk
		$tmpFile = Path::clean($localPath . '_temp.' . File::getExt($name));

		if (!File::write($tmpFile, $mediaContent))
		{
			throw new \Exception(Text::_('JLIB_MEDIA_ERROR_UPLOAD_INPUT'), 500);
		}

		$can = $helper->canUpload(['name' => $name, 'size' => \strlen($mediaContent), 'tmp_name' => $tmpFile], 'com_media');

		File::delete($tmpFile);

		if (!$can)
		{
			throw new \Exception(Text::_('JLIB_MEDIA_ERROR_UPLOAD_INPUT'), 403);
		}
	}

	/**
	 * Returns the file name of the given path.
	 *
	 * @param   string  $path  The path
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 *
	 * @todo Remove
	 */
	private function getFileName(string $path): string
	{
		$path = Path::clean($path);

		// Basename does not work here as it strips out certain characters like upper case umlaut u
		$path = explode(DIRECTORY_SEPARATOR, $path);

		// Return the last element
		return array_pop($path);
	}

	/**
	 * Returns the local filesystem path for the given path.
	 *
	 * Throws an InvalidPathException if the path is invalid.
	 *
	 * @param   string  $path  The path
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 * @throws  InvalidPathException
	 *
	 * @todo Remove
	 */
	private function getLocalPath(string $path): string
	{
		try
		{
			return Path::check($this->rootPath . '/' . $path);
		}
		catch (\Exception $e)
		{
			throw new InvalidPathException($e->getMessage());
		}
	}
}
