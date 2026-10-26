<?php
if (!defined('SMF'))
	die('No direct access.');

global $boarddir, $smcFunc, $modSettings;

// This file is a <database> uninstall action. SMF 2.1.7 executes it only when
// the administrator checks "Remove all data associated with this modification".
$storage_root = !empty($modSettings['bg_storage_path'])
	? rtrim((string) $modSettings['bg_storage_path'], '/\\')
	: rtrim((string) $boarddir, '/\\') . '/gallery_storage';
$default_storage_root = rtrim((string) $boarddir, '/\\') . '/gallery_storage';

// Never recursively delete an arbitrary custom storage root. Only Better
// Media's known subdirectories are removed there. The complete root is
// removable only when it is the dedicated default gallery_storage directory.
$remove_tree = function ($path) use (&$remove_tree)
{
	if (!is_dir($path) || is_link($path))
		return;

	$items = @scandir($path);
	if (!is_array($items))
		return;

	foreach ($items as $item)
	{
		if ($item === '.' || $item === '..')
			continue;

		$child = $path . DIRECTORY_SEPARATOR . $item;

		if (is_dir($child) && !is_link($child))
			$remove_tree($child);
		else
			@unlink($child);
	}

	@rmdir($path);
};

foreach (array('originals', 'thumbs', 'videos', 'posters', 'files', 'downloads') as $subdir)
	$remove_tree($storage_root . DIRECTORY_SEPARATOR . $subdir);

foreach (array('.htaccess', 'index.php') as $scaffold)
{
	$path = $storage_root . DIRECTORY_SEPARATOR . $scaffold;

	if (is_file($path) || is_link($path))
		@unlink($path);
}

if (rtrim($storage_root, '/\\') === rtrim($default_storage_root, '/\\'))
	@rmdir($storage_root);

foreach (array(
	'bg_reports',
	'bg_unique_views',
	'bg_album_likes',
	'bg_album_favorites',
	'bg_media_favorites',
	'bg_media_likes',
	'bg_media_comments',
	'bg_media_tags',
	'bg_tags',
	'bg_access_requests',
	'bg_album_access',
	'bg_media',
	'bg_albums',
	'bg_categories',
) as $table)
	$smcFunc['db_query']('', 'DROP TABLE IF EXISTS {db_prefix}' . $table, array());

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable LIKE {string:prefix}',
	array('prefix' => 'bg_%')
);

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}permissions
	WHERE permission IN ({array_string:bg_permissions})',
	array(
		'bg_permissions' => array(
			'bg_view_gallery',
			'bg_create_album',
			'bg_upload_media',
			'bg_request_access',
			'bg_moderate_gallery',
		),
	)
);

updateSettings(array('settings_updated' => time()));
