<?php

if (!defined('SMF'))
	die('No direct access.');

global $sourcedir, $smcFunc;

require_once($sourcedir . '/Subs.php');

// Load SMF's database package helpers (db_create_table, db_add_column, etc.).
db_extend('packages');

foreach (array(
	'integrate_actions' => 'bg_integrate_actions',
	'integrate_menu_buttons' => 'bg_integrate_menu_buttons',
	'integrate_admin_areas' => 'bg_integrate_admin_areas',
	'integrate_load_theme' => 'bg_integrate_load_theme',
	'integrate_load_permissions' => 'bg_integrate_load_permissions',
	'integrate_load_illegal_guest_permissions' => 'bg_integrate_load_illegal_guest_permissions',
) as $hook => $function)
	add_integration_function($hook, $function, true, '$sourcedir/BetterGallery.php');

$tables = array(
	'bg_categories' => array(
		array('name'=>'id_category','type'=>'mediumint','size'=>8,'unsigned'=>true,'auto'=>true),
		array('name'=>'id_parent','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'name','type'=>'varchar','size'=>160,'default'=>''),
		array('name'=>'description','type'=>'text'),
		array('name'=>'slug','type'=>'varchar','size'=>180,'default'=>''),
		array('name'=>'sort_order','type'=>'smallint','size'=>5,'unsigned'=>true,'default'=>0),
		array('name'=>'enabled','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>1),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_albums' => array(
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true,'auto'=>true),
		array('name'=>'id_owner','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'id_category','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'id_cover_media','type'=>'bigint','size'=>20,'unsigned'=>true,'default'=>0),
		array('name'=>'title','type'=>'varchar','size'=>180,'default'=>''),
		array('name'=>'description','type'=>'text'),
		array('name'=>'slug','type'=>'varchar','size'=>200,'default'=>''),
		array('name'=>'discovery_visibility','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>2),
		array('name'=>'access_mode','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>0),
		array('name'=>'password_hash','type'=>'varchar','size'=>255,'default'=>''),
		array('name'=>'allow_comments','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>1),
		array('name'=>'allow_ratings','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>1),
		array('name'=>'allow_downloads','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>0),
		array('name'=>'is_featured','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>0),
		array('name'=>'approved','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>1),
		array('name'=>'num_items','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'num_views','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'updated_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_media' => array(
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true,'auto'=>true),
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'id_owner','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'media_type','type'=>'varchar','size'=>16,'default'=>'image'),
		array('name'=>'title','type'=>'varchar','size'=>200,'default'=>''),
		array('name'=>'description','type'=>'text'),
		array('name'=>'filename','type'=>'varchar','size'=>255,'default'=>''),
		array('name'=>'storage_key','type'=>'varchar','size'=>255,'default'=>''),
		array('name'=>'storage_backend','type'=>'varchar','size'=>16,'default'=>'local'),
		array('name'=>'mime_type','type'=>'varchar','size'=>100,'default'=>''),
		array('name'=>'file_size','type'=>'bigint','size'=>20,'unsigned'=>true,'default'=>0),
		array('name'=>'width','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'height','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'duration_ms','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'poster_key','type'=>'varchar','size'=>255,'default'=>''),
		array('name'=>'thumb_key','type'=>'varchar','size'=>255,'default'=>''),
		array('name'=>'status','type'=>'varchar','size'=>24,'default'=>'ready'),
		array('name'=>'approved','type'=>'tinyint','size'=>1,'unsigned'=>true,'default'=>1),
		array('name'=>'visibility_override','type'=>'tinyint','size'=>1,'default'=>-1),
		array('name'=>'num_views','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'num_downloads','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'updated_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_album_access' => array(
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'role','type'=>'varchar','size'=>24,'default'=>'viewer'),
		array('name'=>'granted_by','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'granted_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'revoked_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_access_requests' => array(
		array('name'=>'id_request','type'=>'bigint','size'=>20,'unsigned'=>true,'auto'=>true),
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'message','type'=>'text'),
		array('name'=>'status','type'=>'varchar','size'=>16,'default'=>'pending'),
		array('name'=>'requested_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'decided_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'decided_by','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
	),
	'bg_tags' => array(
		array('name'=>'id_tag','type'=>'int','size'=>10,'unsigned'=>true,'auto'=>true),
		array('name'=>'name','type'=>'varchar','size'=>80,'default'=>''),
		array('name'=>'slug','type'=>'varchar','size'=>100,'default'=>''),
	),
	'bg_media_tags' => array(
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true),
		array('name'=>'id_tag','type'=>'int','size'=>10,'unsigned'=>true),
	),
	'bg_media_comments' => array(
		array('name'=>'id_comment','type'=>'bigint','size'=>20,'unsigned'=>true,'auto'=>true),
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true,'default'=>0),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'body','type'=>'text'),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'edited_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_media_likes' => array(
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_media_favorites' => array(
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_album_favorites' => array(
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_album_likes' => array(
		array('name'=>'id_album','type'=>'int','size'=>10,'unsigned'=>true),
		array('name'=>'id_member','type'=>'mediumint','size'=>8,'unsigned'=>true),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_unique_views' => array(
		array('name'=>'target_type','type'=>'varchar','size'=>16,'default'=>'album'),
		array('name'=>'id_target','type'=>'bigint','size'=>20,'unsigned'=>true),
		array('name'=>'viewer_key','type'=>'varchar','size'=>64,'default'=>''),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
	),
	'bg_reports' => array(
		array('name'=>'id_report','type'=>'bigint','size'=>20,'unsigned'=>true,'auto'=>true),
		array('name'=>'target_type','type'=>'varchar','size'=>16,'default'=>'media'),
		array('name'=>'id_media','type'=>'bigint','size'=>20,'unsigned'=>true,'default'=>0),
		array('name'=>'id_comment','type'=>'bigint','size'=>20,'unsigned'=>true,'default'=>0),
		array('name'=>'id_reporter','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
		array('name'=>'reason','type'=>'varchar','size'=>32,'default'=>'other'),
		array('name'=>'note','type'=>'text'),
		array('name'=>'status','type'=>'varchar','size'=>16,'default'=>'open'),
		array('name'=>'created_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'decided_at','type'=>'int','size'=>10,'unsigned'=>true,'default'=>0),
		array('name'=>'decided_by','type'=>'mediumint','size'=>8,'unsigned'=>true,'default'=>0),
	),
);

$indexes = array(
	'bg_categories' => array(
		array('type'=>'primary','columns'=>array('id_category')),
		array('type'=>'index','columns'=>array('id_parent','sort_order')),
		array('type'=>'unique','columns'=>array('slug')),
	),
	'bg_albums' => array(
		array('type'=>'primary','columns'=>array('id_album')),
		array('type'=>'index','columns'=>array('id_owner','created_at')),
		array('type'=>'index','columns'=>array('id_category','approved','discovery_visibility')),
		array('type'=>'index','columns'=>array('discovery_visibility','approved','updated_at')),
		array('type'=>'unique','columns'=>array('slug')),
	),
	'bg_media' => array(
		array('type'=>'primary','columns'=>array('id_media')),
		array('type'=>'index','columns'=>array('id_album','approved','created_at')),
		array('type'=>'index','columns'=>array('id_owner','created_at')),
		array('type'=>'index','columns'=>array('media_type','approved','created_at')),
		array('type'=>'unique','columns'=>array('storage_key')),
	),
	'bg_album_access' => array(
		array('type'=>'primary','columns'=>array('id_album','id_member')),
		array('type'=>'index','columns'=>array('id_member','revoked_at')),
	),
	'bg_access_requests' => array(
		array('type'=>'primary','columns'=>array('id_request')),
		array('type'=>'index','columns'=>array('id_album','status','requested_at')),
		array('type'=>'index','columns'=>array('id_member','status')),
	),
	'bg_tags' => array(
		array('type'=>'primary','columns'=>array('id_tag')),
		array('type'=>'unique','columns'=>array('slug')),
		array('type'=>'index','columns'=>array('name')),
	),
	'bg_media_tags' => array(
		array('type'=>'primary','columns'=>array('id_media','id_tag')),
		array('type'=>'index','columns'=>array('id_tag','id_media')),
	),
	'bg_media_comments' => array(
		array('type'=>'primary','columns'=>array('id_comment')),
		array('type'=>'index','columns'=>array('id_media','created_at')),
		array('type'=>'index','columns'=>array('id_member','created_at')),
	),
	'bg_media_likes' => array(
		array('type'=>'primary','columns'=>array('id_media','id_member')),
		array('type'=>'index','columns'=>array('id_member','created_at')),
	),
	'bg_media_favorites' => array(
		array('type'=>'primary','columns'=>array('id_media','id_member')),
		array('type'=>'index','columns'=>array('id_member','created_at')),
	),
	'bg_album_favorites' => array(
		array('type'=>'primary','columns'=>array('id_album','id_member')),
		array('type'=>'index','columns'=>array('id_member','created_at')),
	),
	'bg_album_likes' => array(
		array('type'=>'primary','columns'=>array('id_album','id_member')),
		array('type'=>'index','columns'=>array('id_member','created_at')),
	),
	'bg_unique_views' => array(
		array('type'=>'primary','columns'=>array('target_type','id_target','viewer_key')),
		array('type'=>'index','columns'=>array('viewer_key','created_at')),
	),
	'bg_reports' => array(
		array('type'=>'primary','columns'=>array('id_report')),
		array('type'=>'index','columns'=>array('status','created_at')),
		array('type'=>'index','columns'=>array('id_media','status')),
		array('type'=>'index','columns'=>array('id_comment','status')),
		array('type'=>'index','columns'=>array('id_reporter','status')),
	),
);

foreach ($tables as $name => $columns)
	$smcFunc['db_create_table'](
		'{db_prefix}' . $name,
		$columns,
		$indexes[$name],
		array(),
		$name === 'bg_albums' ? 'update' : 'ignore'
	);

// Phase 1B: remember which backend owns each media object's bytes. Existing rows remain local.
$smcFunc['db_add_column'](
	'{db_prefix}bg_media',
	array('name'=>'storage_backend','type'=>'varchar','size'=>16,'default'=>'local'),
	array(),
	'ignore'
);

// Upgrade-safe settings defaults.
//
// install.php runs for both fresh installs and Package Manager upgrades.
// Never write defaults over an existing Better Gallery setting: administrators'
// live choices must survive every update. We query the settings table directly
// so this does not depend on the currently loaded $modSettings cache.
$setting_defaults = array(
	'bg_enabled' => 1,
	'bg_storage_path' => '',
	'bg_storage_url' => '',
	'bg_storage_backend' => 'local',
	'bg_s3_region' => 'us-east-1',
	'bg_s3_bucket' => '',
	'bg_s3_prefix' => 'better-gallery',
	'bg_s3_access_key' => '',
	'bg_s3_secret_key' => '',
	'bg_s3_endpoint' => '',
	'bg_s3_private' => 1,
	'bg_images_enabled' => 1,
	'bg_videos_enabled' => 1,
	'bg_require_approval' => 0,
	'bg_default_album_discovery' => 2,
	'bg_default_album_access' => 0,
	'bg_max_upload_files' => 10,
	'bg_upload_progress_mode' => 'compact',
	'bg_max_image_kb' => 10240,
	'bg_max_video_mb' => 250,
	'bg_thumb_width' => 480,
	'bg_thumb_height' => 360,
	'bg_comments_enabled' => 1,
	'bg_likes_enabled' => 1,
	'bg_album_download_chunk_mb' => 500,
	'bg_media_per_page' => 24,
	'bg_member_storage_quota_mb' => 0,
	'bg_nav_label' => 'Better Gallery',
	'bg_show_main_menu' => 1,
	'bg_nav_icon' => 'gallery',
	'bg_content_profile' => 'gallery',
	'bg_allowed_extensions' => '',
	'bg_max_file_mb' => 250,
	'bg_portfolio_mode' => 0,
	'bg_portfolio_owner' => 0,
	'bg_portfolio_owner_name' => '',
	'bg_portfolio_title' => '',
	'bg_portfolio_intro' => '',
);

$existing_bg_settings = array();

$request = $smcFunc['db_query']('', '
	SELECT variable
	FROM {db_prefix}settings
	WHERE variable LIKE {string:bg_prefix}',
	array(
		'bg_prefix' => 'bg_%',
	)
);

while ($row = $smcFunc['db_fetch_assoc']($request))
	$existing_bg_settings[(string) $row['variable']] = true;

$smcFunc['db_free_result']($request);

$is_fresh_install = empty($existing_bg_settings);
$missing_settings = array();

foreach ($setting_defaults as $setting_name => $default_value)
	if (!isset($existing_bg_settings[$setting_name]))
		$missing_settings[$setting_name] = $default_value;

if (!empty($missing_settings))
	updateSettings($missing_settings);

// Better Gallery 1.0.0-dev2b identity reset. Retire the old multi-purpose
// content profiles and Portfolio Mode without deleting any existing media.
// Legacy non-gallery rows remain in the database and can still be served, but
// all new uploads are constrained to the gallery image/video profile.
$identity_reset = array(
	'bg_content_profile' => 'gallery',
	'bg_allowed_extensions' => '',
	'bg_nav_icon' => 'gallery',
	'bg_portfolio_mode' => 0,
);

$existing_nav_label = '';
if (isset($existing_bg_settings['bg_nav_label']))
{
	$request = $smcFunc['db_query']('', '
		SELECT value
		FROM {db_prefix}settings
		WHERE variable = {string:variable}
		LIMIT 1',
		array('variable' => 'bg_nav_label')
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	if ($row)
		$existing_nav_label = trim((string) $row['value']);
}

if ($existing_nav_label === '' || strtolower($existing_nav_label) === 'media')
	$identity_reset['bg_nav_label'] = 'Better Gallery';

updateSettings($identity_reset);

// dev50/dev50a used temporary pre-armed uninstall settings. The native SMF
// Package Manager checkbox supersedes them, so remove only those obsolete keys.
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable IN ({array_string:obsolete_uninstall_settings})',
	array(
		'obsolete_uninstall_settings' => array(
			'bg_uninstall_mode',
			'bg_uninstall_purge_armed_at',
		),
	)
);


// Permission defaults belong to a true first install only.
// An upgrade must never recreate or alter a permission that an administrator
// intentionally changed or removed.
if ($is_fresh_install)
{
	$default_permissions = array(
		array(-1, 'bg_view_gallery', 1),
		array(0, 'bg_view_gallery', 1),
		array(0, 'bg_create_album', 1),
		array(0, 'bg_upload_media', 1),
		array(0, 'bg_request_access', 1),
	);

	$smcFunc['db_insert']('ignore',
		'{db_prefix}permissions',
		array(
			'id_group' => 'int',
			'permission' => 'string',
			'add_deny' => 'int',
		),
		$default_permissions,
		array('id_group', 'permission')
	);
}

updateSettings(array('settings_updated' => time()));

?>