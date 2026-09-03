/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

import openLinkPicker from 'link-picker';

// Register as the 'file' picker (the link dialog) in the shared editor file-picker registry
Joomla.editorFilePickers = Joomla.editorFilePickers || {};
Joomla.editorFilePickers.file = openLinkPicker;
