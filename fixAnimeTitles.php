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
function fixFile(string $oldPath, $anime = true): string {
	$path = pathinfo($oldPath);
	$fixed = $anime ? fixTitle($path['filename']) : fix_youtube_dl($path['filename']);
	return $path['dirname'].'/'.$fixed.'.'.$path['extension'];
}

# returns a list of files and subdirectories in a directory
function getFiles(string $dir): array {
	$files = array_diff(scandir($dir), ['.', '..', '.DS_Store']);
	foreach ($files as &$file)
		$file = $dir.'/'.$file;
	return $files;
}

# returns ['file to fix' => 'fixed path'], ['files to be deleted']
function fix(string $path, $anime = true): array {
	$toFix = [];
	$toDelete = [];
	
	if (is_file($path)) {
		# queue to delete anything that isn't the right format
		if (!in_array($extension = pathinfo($path, PATHINFO_EXTENSION), fileFormats) && $anime)
			$toDelete[] = $path;
		else if (in_array($extension, animeFormats) && ($newPath = fixFile($path, $anime)) != $path)
			$toFix[$path] = $newPath;
	} else
		# recursively check all of the subfolders & files
		foreach (getFiles($path) as $subPath) {
			list($toFix2, $toDelete2) = fix($subPath, $anime);
			$toFix = array_merge($toFix, $toFix2);
			if ($anime) $toDelete = array_merge($toDelete, $toDelete2);
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
function fixFolders(string $dir): array {
	$toFix = [];
	if (($newDir = fixDirectory($dir)) != $dir)
		$toFix[$dir] = $newDir;
	
	foreach (getFiles($dir) as $path)
		if (is_dir($path))
			$toFix = array_merge($toFix, fixFolders($path));
	
	return $toFix;
}

# use the fact that youtube_dl appends some 11 characters to the end of filename
function fix_youtube_dl(string $filename): string {
	return preg_replace('/-\S{11}$/', '', $filename);
}

function absolutePath(string $path): string {
	return $path[0] !== '~' ? $path : posix_getpwuid(posix_getuid())['dir'].'/'.substr($path, 2);
}

# -------------------- main --------------------
# TODO: accept command line arguments
if (!debug_backtrace()) {
	$error = '';
	do {
		$base = absolutePath(readline($error.'Directory or File to rename: '));
		$error = 'Not a valid file/directory path! ';
	} while (!is_dir($base) && !is_file($base));
	
	$error = '';
	do {
		$anime = readline($error.'Fix anime (t)orrents or (y)outube_dl downloads? ');
		$error = 'Not a valid option! ';
	} while ($anime != 't' && $anime != 'y');
	
	$start = microtime(true);
	list($toFix, $toDelete) = fix($base, $anime == 't');
	
	if ($anime == 't') {
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
	} else
		foreach ($toFix as $oldFile => $newFile)
			rename($oldFile, $newFile);
	
	echo 'Took '.round((microtime(true) - $start), 2)." seconds.\n";
}