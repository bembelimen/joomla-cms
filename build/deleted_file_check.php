<?php

/**
 * This file is used to build the list of deleted files between a Joomla package and the current working tree.
 *
 * The working tree (including uncommitted and untracked files) is built into an Update package by build.php.
 *
 * This script has the following optional parameters:
 *
 * --from    - The starting point for the comparison, defaults to the last stable version (git tag) before the working tree version
 * --dry-run - Don't add the result to administrator/components/com_admin/script.php
 *
 * `--from` may be:
 *  - a released version (e.g. `6.1.4` or `6.2.0-rc1`), its Update package gets downloaded from GitHub
 *  - the path to an Update package zip
 *  - the path to a directory containing an extracted Update package
 *
 * A Full package can't be used, otherwise files only contained in the Full package are reported as deleted.
 *
 * @package    Joomla.Build
 *
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/*
 * Constants
 */
const PHP_TAB = "\t";

/**
 * Prints the usage information.
 *
 * @param   string  $command  The called script
 *
 * @return  void
 */
function usage(string $command): void
{
    echo PHP_EOL;
    echo 'Usage: php ' . $command . ' [options]' . PHP_EOL;
    echo PHP_TAB . '--from <version|zip|dir>:' . PHP_TAB . 'Starting point (released version, Update package zip or extracted directory) [optional, defaults to the last stable version before the working tree version]' . PHP_EOL;
    echo PHP_TAB . '--dry-run:' . PHP_TAB . PHP_TAB . 'Don\'t add the result to administrator/components/com_admin/script.php [optional]' . PHP_EOL;
    echo PHP_EOL;
}

/**
 * Builds the GitHub download URL of the Update package of a released version.
 *
 * @param   string  $version  The version, e.g. `6.1.4` or `6.2.0-beta3`
 *
 * @return  string
 *
 * @throws  InvalidArgumentException  If the version can't be parsed
 */
function getPackageUrl(string $version): string
{
    if (!preg_match('/^\d+\.\d+\.\d+(?:-(alpha|beta|rc)\d+)?$/i', $version, $matches)) {
        throw new InvalidArgumentException(\sprintf('"%s" is neither a version, a zip file nor a directory', $version));
    }

    $stability = match (strtolower($matches[1] ?? '')) {
        'alpha' => 'Alpha',
        'beta'  => 'Beta',
        'rc'    => 'Release_Candidate',
        default => 'Stable',
    };

    return 'https://github.com/joomla/joomla-cms/releases/download/' . $version . '/Joomla_' . $version . '-' . $stability . '-Update_Package.zip';
}

/**
 * Resolves a version, zip file or directory to a directory with the extracted package.
 *
 * Downloads and extractions are cached in build/tmp/deleted_file_check.
 *
 * @param   string  $source  A released version, the path to a package zip or a directory
 *
 * @return  string
 *
 * @throws  RuntimeException  If the package can't be downloaded or extracted
 */
function resolvePackage(string $source): string
{
    if (is_dir($source)) {
        return rtrim(realpath($source), '/');
    }

    $cacheDir = __DIR__ . '/tmp/deleted_file_check';

    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true)) {
        throw new RuntimeException(\sprintf('Unable to create %s', $cacheDir));
    }

    $zipFile = $source;

    if (!is_file($zipFile)) {
        $url     = getPackageUrl($source);
        $zipFile = $cacheDir . '/' . basename($url);

        if (!is_file($zipFile)) {
            echo 'Downloading ' . $url . PHP_EOL;

            if (!copy($url, $zipFile)) {
                throw new RuntimeException(\sprintf('Unable to download %s', $url));
            }
        }
    }

    $targetDir = $cacheDir . '/' . basename($zipFile, '.zip');

    if (is_dir($targetDir)) {
        return $targetDir;
    }

    $zip = new ZipArchive();

    if ($zip->open($zipFile) !== true || !$zip->extractTo($targetDir)) {
        throw new RuntimeException(\sprintf('Unable to extract %s', $zipFile));
    }

    $zip->close();

    return $targetDir;
}

/**
 * Reads the short version (e.g. `6.2.1-rc1`) of an extracted package.
 *
 * @param   string  $directory  The package directory
 *
 * @return  string
 *
 * @throws  RuntimeException  If the version file can't be read
 */
function getPackageVersion(string $directory): string
{
    $versionFile = file_get_contents($directory . '/libraries/src/Version.php');

    if ($versionFile === false) {
        throw new RuntimeException(\sprintf('Unable to read the version of %s', $directory));
    }

    $parts = [];

    foreach (['MAJOR', 'MINOR', 'PATCH', 'EXTRA'] as $part) {
        preg_match('/const ' . $part . '_VERSION\s*=\s*\'?([^\';]*)\'?;/', $versionFile, $matches);
        $parts[$part] = $matches[1] ?? '';
    }

    return $parts['MAJOR'] . '.' . $parts['MINOR'] . '.' . $parts['PATCH'] . ($parts['EXTRA'] !== '' ? '-' . $parts['EXTRA'] : '');
}

/**
 * Builds an Update package of the current working tree (including uncommitted and untracked files) with build.php.
 *
 * @return  string  The path to the Update package zip
 *
 * @throws  RuntimeException  If the working tree can't be read or the build fails
 */
function buildLocalPackage(): string
{
    $indexFile = sys_get_temp_dir() . '/joomla_deleted_file_check_' . getmypid() . '.index';
    $git       = 'GIT_INDEX_FILE=' . escapeshellarg($indexFile) . ' git -C ' . escapeshellarg(\dirname(__DIR__));

    exec($git . ' read-tree HEAD && ' . $git . ' add -A && ' . $git . ' write-tree', $output, $resultCode);

    if (is_file($indexFile)) {
        unlink($indexFile);
    }

    if ($resultCode !== 0) {
        throw new RuntimeException('Unable to read the working tree');
    }

    passthru(
        escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/build.php') . ' --remote=' . escapeshellarg(end($output))
        . ' --exclude-gzip --exclude-zstd --disable-patch-packages',
        $resultCode
    );

    $packages = glob(__DIR__ . '/tmp/packages/*-Update_Package.zip');

    if ($resultCode !== 0 || \count($packages) !== 1) {
        throw new RuntimeException('Unable to build the Update package of the working tree');
    }

    return $packages[0];
}

/**
 * Finds the last stable version (git tag) before the given version.
 *
 * @param   string  $version  The version to compare with, e.g. `6.2.1-rc2-dev`
 *
 * @return  string
 *
 * @throws  RuntimeException  If the git tags can't be read or no previous stable version exists
 */
function getPreviousStableVersion(string $version): string
{
    exec('git -C ' . escapeshellarg(\dirname(__DIR__)) . ' tag --list', $tags, $resultCode);

    if ($resultCode !== 0) {
        throw new RuntimeException('Unable to read the git tags');
    }

    $stableTags = array_filter(
        $tags,
        fn (string $tag): bool => preg_match('/^\d+\.\d+\.\d+$/', $tag) && version_compare($tag, $version, '<')
    );

    if (!$stableTags) {
        throw new RuntimeException(\sprintf('No stable version before %s found, run "git fetch --tags"', $version));
    }

    usort($stableTags, 'version_compare');

    return end($stableTags);
}

/**
 * Adds entries to the end of an array definition in script.php, skipping entries which are already listed.
 *
 * @param   string    $script   The content of script.php
 * @param   string    $method   The method signature containing the array, e.g. `public function deleteUnexistingFiles(`
 * @param   string    $array    The array variable, e.g. `$files`
 * @param   string    $label    The label of the new section, e.g. `From 6.2.0 to 6.2.1`
 * @param   string[]  $entries  The entries to add, already formatted as PHP array items
 *
 * @return  string
 *
 * @throws  RuntimeException  If the array can't be found
 */
function addToScriptArray(string $script, string $method, string $array, string $label, array $entries): string
{
    $methodPos = strpos($script, $method);
    $startPos  = $methodPos === false ? false : strpos($script, "\n        " . $array . " = [\n", $methodPos);
    $endPos    = $startPos === false ? false : strpos($script, "\n        ];\n", $startPos);

    if ($endPos === false) {
        throw new RuntimeException(\sprintf('Unable to find %s in %s', $array, $method));
    }

    $existing = array_map('trim', explode("\n", substr($script, $startPos, $endPos - $startPos)));
    $entries  = array_diff($entries, $existing);

    if (!$entries) {
        return $script;
    }

    $insert = "\n            // " . $label . "\n            " . implode("\n            ", $entries);

    return substr_replace($script, $insert, $endPos, 0);
}

/*
 * This is where the magic happens
 */

$options = getopt('', ['help', 'from:', 'dry-run']);

if (isset($options['help'])) {
    usage($argv[0]);

    exit(0);
}

try {
    $toDir   = resolvePackage(buildLocalPackage());
    $fromDir = resolvePackage($options['from'] ?? getPreviousStableVersion(getPackageVersion($toDir)));
} catch (Exception $e) {
    echo PHP_EOL . $e->getMessage() . PHP_EOL;

    exit(1);
}

// Directories to skip for the check (needs to include anything from previous versions which we want to keep)
$previousReleaseExclude = [
    $fromDir . '/images/sampledata',
    $fromDir . '/installation',
    $fromDir . '/media/plg_captcha_recaptcha',
    $fromDir . '/media/plg_captcha_recaptcha_invisible',
    $fromDir . '/media/plg_behaviour_compat',
    $fromDir . '/plugins/behaviour/compat',
];

/**
 * @param   SplFileInfo                      $file      The file being checked
 * @param   mixed                            $key       ?
 * @param   RecursiveCallbackFilterIterator  $iterator  The iterator being processed
 *
 * @return bool True if you need to recurse or if the item is acceptable
 */
$previousReleaseFilter = function ($file, $key, $iterator) use ($previousReleaseExclude) {
    if ($iterator->hasChildren() && !\in_array($file->getPathname(), $previousReleaseExclude)) {
        return true;
    }

    return $file->isFile();
};

// Directories to skip for the check
$newReleaseExclude = [
    $toDir . '/installation',
];

/**
 * @param   SplFileInfo                      $file      The file being checked
 * @param   mixed                            $key       ?
 * @param   RecursiveCallbackFilterIterator  $iterator  The iterator being processed
 *
 * @return bool True if you need to recurse or if the item is acceptable
 */
$newReleaseFilter = function ($file, $key, $iterator) use ($newReleaseExclude) {
    if ($iterator->hasChildren() && !\in_array($file->getPathname(), $newReleaseExclude)) {
        return true;
    }

    return $file->isFile();
};

$previousReleaseDirIterator = new RecursiveDirectoryIterator($fromDir, RecursiveDirectoryIterator::SKIP_DOTS);
$previousReleaseIterator    = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator($previousReleaseDirIterator, $previousReleaseFilter),
    RecursiveIteratorIterator::SELF_FIRST
);
$previousReleaseFiles   = [];
$previousReleaseFolders = [];

foreach ($previousReleaseIterator as $info) {
    if ($info->isDir()) {
        $previousReleaseFolders[] = "'" . str_replace($fromDir, '', $info->getPathname()) . "',";
        continue;
    }

    $previousReleaseFiles[] = "'" . str_replace($fromDir, '', $info->getPathname()) . "',";
}

$newReleaseDirIterator = new RecursiveDirectoryIterator($toDir, RecursiveDirectoryIterator::SKIP_DOTS);
$newReleaseIterator    = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator($newReleaseDirIterator, $newReleaseFilter),
    RecursiveIteratorIterator::SELF_FIRST
);
$newReleaseFiles   = [];
$newReleaseFolders = [];

foreach ($newReleaseIterator as $info) {
    if ($info->isDir()) {
        $newReleaseFolders[] = "'" . str_replace($toDir, '', $info->getPathname()) . "',";
        continue;
    }

    $newReleaseFiles[] = "'" . str_replace($toDir, '', $info->getPathname()) . "',";
}

$filesDifference = array_diff($previousReleaseFiles, $newReleaseFiles);

$foldersDifference = array_diff($previousReleaseFolders, $newReleaseFolders);

// Specific files (e.g. language files) that we want to keep on upgrade
$filesToKeep = [
    // Example: "'/administrator/language/en-GB/en-GB.com_search.ini',",
    "'/administrator/language/en-GB/plg_behaviour_compat.ini',",
    "'/administrator/language/en-GB/plg_behaviour_compat.sys.ini',",
];

// Specific folders that we want to keep on upgrade
$foldersToKeep = [
    // Example: "'/bin',",
];

// Remove folders from the results which we want to keep on upgrade
foreach ($foldersToKeep as $folder) {
    if (($key = array_search($folder, $foldersDifference)) !== false) {
        unset($foldersDifference[$key]);
    }
}

asort($filesDifference);
rsort($foldersDifference);

$deletedFiles = [];
$renamedFiles = [];

foreach ($filesDifference as $file) {
    // Don't remove any specific files (e.g. language files) that we want to keep on upgrade
    if (array_search($file, $filesToKeep) !== false) {
        continue;
    }

    // Check for files which might have been renamed only
    $matches = preg_grep('/^' . preg_quote($file, '/') . '$/i', $newReleaseFiles);

    if ($matches !== false) {
        foreach ($matches as $match) {
            if (\dirname($match) === \dirname($file) && strtolower(basename($match)) === strtolower(basename($file))) {
                // File has been renamed only: Add to renamed files list
                $renamedFiles[] = substr($file, 0, -1) . ' => ' . $match;

                // Go on with the next file in $filesDifference
                continue 2;
            }
        }
    }

    // File has been really deleted and not just renamed
    $deletedFiles[] = $file;
}

// Write the lists to files for later reference
file_put_contents(__DIR__ . '/deleted_files.txt', implode("\n", $deletedFiles));
file_put_contents(__DIR__ . '/deleted_folders.txt', implode("\n", $foldersDifference));
file_put_contents(__DIR__ . '/renamed_files.txt', implode("\n", $renamedFiles));

$label = 'From ' . getPackageVersion($fromDir) . ' to ' . getPackageVersion($toDir);

echo PHP_EOL;
echo 'There are ' . \count($deletedFiles) . ' deleted files, ' . \count($foldersDifference) .  ' deleted folders and ' . \count($renamedFiles) .  ' renamed files ' . lcfirst($label) . PHP_EOL;

if (!isset($options['dry-run'])) {
    $scriptFile = \dirname(__DIR__) . '/administrator/components/com_admin/script.php';

    try {
        $script = file_get_contents($scriptFile);
        $script = addToScriptArray($script, 'public function deleteUnexistingFiles(', '$files', $label, $deletedFiles);
        $script = addToScriptArray($script, 'public function deleteUnexistingFiles(', '$folders', $label, $foldersDifference);
        $script = addToScriptArray($script, 'protected function fixFilenameCasing(', '$files', $label, $renamedFiles);
    } catch (RuntimeException $e) {
        echo PHP_EOL . $e->getMessage() . PHP_EOL;

        exit(1);
    }

    file_put_contents($scriptFile, $script);

    echo 'Added the result to ' . $scriptFile . PHP_EOL;
}
