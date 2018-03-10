<?php
# cleans the titles of anime files from the high seas

const animeFormats = ['mp4', 'mkv'];
const fileFormats = ['mp4', 'mkv', 'mka', 'flac', 'jpg', 'png'];

# returns the fixed title of an anime file (read: NOT the whole filename & path)
function fixTitle(string $title): string {
	return trim(
		str_replace('_', ' ',
			# \[[^\]]+\] => strip away every [] and everything inside it
			# \([^)]*(\d(p|bit)|\dx\d)[^)]*\) => strip any () that contains format information,
			# matched by resolution (eg. 720p, 1920x1080) and bitrate (eg. 8bit, 24bits)
			preg_replace('/\[[^\]]+\]|\([^)]*(\dp|\dx\d|\dbit)[^)]*\)/i', '', $title)
		)
	);
}

# returns the fixed pathname of a given anime file
function fixFile(string $oldPath): string {
	$path = pathinfo($oldPath);
	return $path['dirname'].'/'.fixTitle($path['filename']).'.'.$path['extension'];
}

# returns a list of files and subdirectories in a directory
function getFiles(string $dir): array {
	$files = array_diff(scandir($dir), ['.', '..', '.DS_Store']);
	foreach ($files as &$file)
		$file = $dir.'/'.$file;
	return $files;
}

# returns ['file to fix' => 'fixed path'], ['files to be deleted']
function fix(string $path): array {
	$toFix = [];
	$toDelete = [];
	
	if (is_file($path)) {
		# queue to delete anything that isn't the right format
		if (!in_array($extension = pathinfo($path, PATHINFO_EXTENSION), fileFormats))
			$toDelete[] = $path;
		else if (in_array($extension, animeFormats) && ($newPath = fixFile($path)) != $path)
			$toFix[$path] = $newPath;
	} else
		# recursively check all of the subfolders & files
		foreach (getFiles($path) as $subPath) {
			list($toFix2, $toDelete2) = fix($subPath);
			$toFix = array_merge($toFix, $toFix2);
			$toDelete = array_merge($toDelete, $toDelete2);
		}
	
	return [$toFix, $toDelete];
}

function numFilesInDir(string $dir): int {
	return count(array_filter(getFiles($dir), 'is_file'));
}

function getFirstFile(string $dir): string {
	foreach (getFiles($dir) as $path)
		if (is_file($path)) return $path;
	
	return null;
}

function upOneDirectory(string $file): string {
	return dirname(dirname($file)).'/'.basename($file);
}

# for any folders with only a single file in it,
# returns ['oldPath' => 'file up one level']
function fixSingleFolders(string $path): array {
	if (is_file($path)) return [];
	$result = [];
	
	if (numFilesInDir($path) == 1) {
		$file = getFirstFile($path);
		$result[$file] = upOneDirectory($file);
	} else
		foreach (getFiles($path) as $subPath)
			if ($subResult = fixSingleFolders($subPath))
				$result = array_merge($result, $subResult);
	
	return $result;
}

# fixes a single directory
function fixDirectory(string $dir): string {
	return implode('/', array_map('fixTitle', explode('/', $dir)));
}

# makes a final pass to correct the folder names
# returns ['folder' => 'fixed']
function fixFolders(string $dir) {
	$toFix = [];
	if (($newDir = fixDirectory($dir)) != $dir)
		$toFix[$dir] = $newDir;
	
	foreach (getFiles($dir) as $path)
		if (is_dir($path))
			$toFix = array_merge($toFix, fixFolders($path));
	
	return $toFix;
}

# -------------------- main --------------------
# TODO: accept command line arguments
if (!debug_backtrace()) {
	$error = '';
	do {
		$base = readline($error.'Directory or File to rename: ');
		$error = 'Not a valid file/directory path! ';
	} while (!is_dir($base) && !is_file($base));
	
	$start = microtime(true);
	list($toFix, $toDelete) = fix($base);
	
	foreach ($toFix as $oldFile => $newFile)
		rename($oldFile, $newFile);
	foreach ($toDelete as $file)
		unlink($file);
	if (is_dir($base)) {
		foreach (fixSingleFolders($base) as $oldFile => $newFile) {
			rename($oldFile, $newFile);
		}
		# TODO: fix 'No such file or directory' error (it still works, but will throw the error for some reason)
		foreach (fixFolders($base) as $oldDir => $newDir)
			rename($oldDir, $newDir);
		# TODO: clear out any empty directories (I guess I'll have to make one more pass, huh)
	}
	echo 'Took '.(microtime(true) - $start)." seconds.\n";
}