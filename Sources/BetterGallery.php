<?php

if (!defined('SMF'))
	die('No direct access.');

function bg_media_identity_label()
{
	global $modSettings;

	$label = isset($modSettings['bg_nav_label'])
		? trim((string) $modSettings['bg_nav_label'])
		: '';

	if ($label === '')
		$label = 'Better Gallery';

	if (strlen($label) > 40)
		$label = substr($label, 0, 40);

	return $label;
}

function bg_media_nav_icons()
{
	return array(
		'gallery' => 'gallery_menu.png',
	);
}

function bg_media_nav_icon_markup($icon = '')
{
	global $modSettings, $settings;

	$icons = bg_media_nav_icons();
	$icon = $icon !== ''
		? (string) $icon
		: (isset($modSettings['bg_nav_icon']) ? (string) $modSettings['bg_nav_icon'] : 'gallery');

	if (!isset($icons[$icon]))
		$icon = 'gallery';

	// Package Manager can build menu buttons before the default theme URL has
	// been populated. The icon is cosmetic, so do not emit an undefined-setting
	// notice just to draw it on those transient admin requests.
	if (empty($settings['default_theme_url']))
		return '';

	$src = $settings['default_theme_url'] . '/images/BetterGallery/' . rawurlencode($icons[$icon]) . '?bg=1.0.2';

	return '<span class="bg_menu_icon"><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="" aria-hidden="true"></span>';
}

function bg_content_type_catalog()
{
	return array(
		'jpg' => array('type'=>'image','mimes'=>array('image/jpeg')),
		'jpeg' => array('type'=>'image','mimes'=>array('image/jpeg')),
		'png' => array('type'=>'image','mimes'=>array('image/png')),
		'gif' => array('type'=>'image','mimes'=>array('image/gif')),
		'webp' => array('type'=>'image','mimes'=>array('image/webp')),
		'mp4' => array('type'=>'video','mimes'=>array('video/mp4','application/mp4')),
		'webm' => array('type'=>'video','mimes'=>array('video/webm')),
		'mov' => array('type'=>'video','mimes'=>array('video/quicktime','application/octet-stream')),
		'mkv' => array('type'=>'video','mimes'=>array('video/x-matroska','application/octet-stream')),
		'mp3' => array('type'=>'audio','mimes'=>array('audio/mpeg','audio/mp3','application/octet-stream')),
		'wav' => array('type'=>'audio','mimes'=>array('audio/wav','audio/x-wav','audio/wave')),
		'ogg' => array('type'=>'audio','mimes'=>array('audio/ogg','application/ogg')),
		'flac' => array('type'=>'audio','mimes'=>array('audio/flac','audio/x-flac','application/octet-stream')),
		'pdf' => array('type'=>'document','mimes'=>array('application/pdf')),
		'txt' => array('type'=>'document','mimes'=>array('text/plain')),
		'md' => array('type'=>'document','mimes'=>array('text/plain','text/markdown')),
		'csv' => array('type'=>'document','mimes'=>array('text/plain','text/csv','application/csv')),
		'json' => array('type'=>'document','mimes'=>array('application/json','text/plain')),
		'xml' => array('type'=>'document','mimes'=>array('application/xml','text/xml','text/plain')),
		'doc' => array('type'=>'document','mimes'=>array('application/msword','application/CDFV2','application/octet-stream')),
		'docx' => array('type'=>'document','mimes'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip')),
		'xls' => array('type'=>'document','mimes'=>array('application/vnd.ms-excel','application/CDFV2','application/octet-stream')),
		'xlsx' => array('type'=>'document','mimes'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip')),
		'ppt' => array('type'=>'document','mimes'=>array('application/vnd.ms-powerpoint','application/CDFV2','application/octet-stream')),
		'pptx' => array('type'=>'document','mimes'=>array('application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip')),
		'zip' => array('type'=>'archive','mimes'=>array('application/zip','application/x-zip-compressed')),
		'rar' => array('type'=>'archive','mimes'=>array('application/vnd.rar','application/x-rar','application/x-rar-compressed','application/octet-stream')),
		'7z' => array('type'=>'archive','mimes'=>array('application/x-7z-compressed','application/octet-stream')),
	);
}

function bg_content_profiles()
{
	// Better Gallery is intentionally gallery-first. The old Downloads,
	// Resources, Media Hub and Custom product profiles were retired in dev1.
	return array(
		'gallery' => array('jpg','jpeg','png','gif','webp','mp4','webm','mov','mkv'),
	);
}

function bg_allowed_extensions()
{
	// New uploads are always image/video gallery media. Keep the broader
	// catalog elsewhere so legacy rows created by older Better Media builds
	// can remain readable during an upgrade.
	return bg_content_profiles()['gallery'];
}

function bg_allowed_extensions_csv()
{
	return implode(',', bg_allowed_extensions());
}

function bg_extension_profile($ext)
{
	$catalog = bg_content_type_catalog();
	$ext = strtolower(ltrim((string) $ext, '.'));

	return isset($catalog[$ext]) ? $catalog[$ext] : false;
}

function bg_extension_mime_valid($ext, $mime)
{
	$profile = bg_extension_profile($ext);

	if (!$profile)
		return false;

	$mime = strtolower(trim((string) $mime));

	return in_array($mime, array_map('strtolower', $profile['mimes']), true);
}

/* -------------------------------------------------------
 * Integration hooks
 * ----------------------------------------------------- */

/**
 * Generic external content slot for Better Gallery.
 *
 * Better Gallery does not depend on Better Ads; any mod may fill these slots.
 */
function bg_integration_slot($slot, $meta = array())
{
	$html = '';

	if (!function_exists('call_integration_hook'))
		return $html;

	call_integration_hook(
		'integrate_bettergallery_ad_slot',
		array((string) $slot, &$html, is_array($meta) ? $meta : array())
	);

	return (string) $html;
}

function bg_integrate_actions(&$actions)
{
	$actions['gallery'] = array('BetterGallery.php', 'bg_gallery_dispatch');
}

function bg_integrate_menu_buttons(&$buttons)
{
	global $scripturl, $txt, $user_info, $modSettings;

	if (empty($modSettings['bg_enabled']) || !bg_allowed('bg_view_gallery'))
		return;

	// Allow administrators to keep Better Gallery available through Portal,
	// custom navigation, or direct links without forcing a header-menu button.
	// Existing installations default to visible for backward compatibility.
	if (isset($modSettings['bg_show_main_menu']) && empty($modSettings['bg_show_main_menu']))
		return;

	bg_load_better_media_language();

	$label = bg_media_identity_label();

	$buttons['gallery'] = array(
		'title' => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
		'href' => $scripturl . '?action=gallery',
		// Use SMF's native main-menu icon slot so the Gallery link inherits
		// the exact same baseline/line-height as every other menu button.
		'icon' => 'bg_gallery_nav',
		'show' => true,
		'sub_buttons' => array(),
	);
}

function bg_integrate_admin_areas(&$admin_areas)
{
	global $txt;

	bg_load_better_media_language();

	$admin_areas['config']['areas']['bettergallery'] = array(
		'label' => $txt['bg_admin_title'],
		'file' => 'BetterGallery.php',
		'function' => 'bg_admin_dispatch',
		'permission' => array('admin_forum'),
		'icon' => 'better_gallery.png',
	);
}

function bg_integrate_load_theme()
{
	global $context, $settings;

	// Better Gallery CSS is installed in the default theme so every custom
	// theme can share one stable stylesheet. Do not ask the active theme to
	// resolve BetterGallery.css, because custom themes normally do not contain it.
	if (empty($settings['default_theme_url']))
		return;

	$css_url = $settings['default_theme_url'] . '/css/BetterGallery.css?bg=1.0.2';

	if (!isset($context['html_headers']))
		$context['html_headers'] = '';

	// Avoid duplicate injection if integrate_load_theme is called more than once.
	if (strpos($context['html_headers'], 'BetterGallery.css') === false)
		$context['html_headers'] .= "\n<link rel=\"stylesheet\" href=\"" . htmlspecialchars($css_url, ENT_QUOTES, 'UTF-8') . '">';

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'gallery')
	{
		if (strpos($context['html_headers'], 'BetterGalleryUploader.js') === false)
		{
			$js_url = $settings['default_theme_url'] . '/scripts/BetterGalleryUploader.js?bg=1.0.2';
			$context['html_headers'] .= "\n<script defer src=\"" . htmlspecialchars($js_url, ENT_QUOTES, 'UTF-8') . '\"></script>';
		}

		if (strpos($context['html_headers'], 'BetterGalleryViewer.js') === false)
		{
			$viewer_url = $settings['default_theme_url'] . '/scripts/BetterGalleryViewer.js?bg=1.0.2';
			$context['html_headers'] .= "\n<script defer src=\"" . htmlspecialchars($viewer_url, ENT_QUOTES, 'UTF-8') . '\"></script>';
		}

		if (strpos($context['html_headers'], 'BetterGalleryBulk.js') === false)
		{
			$bulk_url = $settings['default_theme_url'] . '/scripts/BetterGalleryBulk.js?bg=1.0.2';
			$context['html_headers'] .= "\n<script defer src=\"" . htmlspecialchars($bulk_url, ENT_QUOTES, 'UTF-8') . '\"></script>';
		}
	}
}

function bg_integrate_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	global $txt;

	bg_load_better_media_language();

	if (!isset($permissionGroups['membergroup']['better_gallery']))
		$permissionGroups['membergroup'][] = 'better_gallery';

	$permissionList['membergroup']['bg_view_gallery'] = array(false, 'better_gallery');
	$permissionList['membergroup']['bg_create_album'] = array(false, 'better_gallery');
	$permissionList['membergroup']['bg_upload_media'] = array(false, 'better_gallery');
	$permissionList['membergroup']['bg_request_access'] = array(false, 'better_gallery');
	$permissionList['membergroup']['bg_moderate_gallery'] = array(false, 'better_gallery');
}

function bg_integrate_load_illegal_guest_permissions()
{
	global $context;

	foreach (array('bg_create_album', 'bg_upload_media', 'bg_request_access', 'bg_moderate_gallery') as $permission)
		if (!in_array($permission, $context['non_guest_permissions']))
			$context['non_guest_permissions'][] = $permission;
}

/* -------------------------------------------------------
 * Permission helpers
 * ----------------------------------------------------- */

function bg_allowed($permission)
{
	global $user_info;

	if (!empty($user_info['is_admin']))
		return true;

	return allowedTo($permission);
}

function bg_require($permission)
{
	global $user_info;

	if (!empty($user_info['is_admin']))
		return;

	isAllowedTo($permission);
}

/* -------------------------------------------------------
 * Front controller
 * ----------------------------------------------------- */

function bg_breadcrumb_add($name, $url = '')
{
	global $context;

	if (!isset($context['linktree']) || !is_array($context['linktree']))
		$context['linktree'] = array();

	$crumb = array(
		'name' => (string) $name,
	);

	if ($url !== '')
		$crumb['url'] = (string) $url;

	// Do not append the exact same Gallery breadcrumb twice.
	foreach ($context['linktree'] as $existing)
		if (
			isset($existing['name'])
			&& (string) $existing['name'] === $crumb['name']
			&& (string) ($existing['url'] ?? '') === (string) ($crumb['url'] ?? '')
		)
			return;

	$context['linktree'][] = $crumb;
}

function bg_breadcrumb_category($id_category, $category_name)
{
	global $scripturl;

	$id_category = (int) $id_category;
	$category_name = trim((string) $category_name);

	if ($id_category <= 0 || $category_name === '')
		return;

	bg_breadcrumb_add(
		$category_name,
		$scripturl . '?action=gallery;category=' . $id_category
	);
}

function bg_breadcrumb_album($album, $include_album = true)
{
	global $scripturl;

	if (!$album || !is_array($album))
		return;

	if (!empty($album['id_category']) && !empty($album['category_name']))
		bg_breadcrumb_category((int) $album['id_category'], $album['category_name']);

	if ($include_album && !empty($album['id_album']) && !empty($album['title']))
		bg_breadcrumb_add(
			$album['title'],
			$scripturl . '?action=gallery;sa=album;album=' . (int) $album['id_album']
		);
}

function bg_load_better_media_language()
{
	global $settings, $txt;

	// Let SMF/custom themes provide translations first.
	loadLanguage('BetterGallery');

	// Then fill only missing keys from Better Gallery's canonical default-theme
	// English file. This prevents stale custom-theme language copies from
	// blanking newly added strings while preserving any custom translations
	// that are actually present.
	$default_dir = !empty($settings['default_theme_dir'])
		? rtrim($settings['default_theme_dir'], '/\\')
		: '';

	if ($default_dir === '')
		return;

	$file = $default_dir . '/languages/BetterGallery.english.php';

	if (!is_file($file))
		return;

	$canonical = (static function ($language_file)
	{
		$txt = array();
		include $language_file;
		return $txt;
	})($file);

	if (!is_array($canonical))
		return;

	foreach ($canonical as $key => $value)
		if (!isset($txt[$key]))
			$txt[$key] = $value;

	// dev2a: Theme Lab and other custom themes can retain an older copy of
	// BetterGallery.english.php.  That is useful for genuine translations, but
	// it also means stale Better Media admin branding can override the current
	// package strings.  In Admin, the canonical package owns product identity
	// and maintenance terminology so the control panel cannot regress to the
	// retired Better Media wording.
	if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'admin')
	{
		$force_keys = array(
			'bg_admin_title', 'bg_admin_desc', 'bg_admin_dashboard', 'bg_admin_categories',
			'bg_admin_overview', 'bg_admin_health_heading', 'bg_admin_data_heading',
			'bg_admin_passed', 'bg_admin_warnings', 'bg_admin_failed', 'bg_admin_status',
			'bg_admin_check', 'bg_admin_details', 'bg_admin_gallery_content',
			'bg_admin_gallery_content_help', 'bg_admin_gallery_content_value',
			'bg_admin_settings', 'bg_admin_maintenance', 'bg_identity_settings_heading',
			'bg_upload_settings_heading', 'bg_settings_saved', 'bg_setting_nav_label',
			'bg_setting_nav_label_help', 'bg_setting_max_files', 'bg_setting_max_files_help',
			'bg_setting_upload_progress_mode', 'bg_setting_upload_progress_mode_help',
			'bg_upload_progress_compact', 'bg_upload_progress_per_file',
			'bg_setting_member_storage_quota', 'bg_setting_member_storage_quota_help',
			'bg_setting_max_image_mb', 'bg_setting_max_image_mb_help',
			'bg_setting_max_video_mb', 'bg_setting_max_video_mb_help',
			'bg_setting_thumb_width', 'bg_setting_thumb_width_help',
			'bg_setting_thumb_height', 'bg_setting_thumb_height_help',
			'bg_setting_comments_enabled', 'bg_setting_comments_enabled_help',
			'bg_setting_likes_enabled', 'bg_setting_likes_enabled_help',
			'bg_setting_album_chunk_mb', 'bg_setting_album_chunk_mb_help',
			'bg_setting_media_per_page', 'bg_setting_media_per_page_help',
			'bg_php_upload_limits', 'bg_php_upload_max_help', 'bg_php_post_max_help',
			'bg_php_max_file_uploads_help', 'bg_php_effective_limit',
			'bg_php_effective_limit_help',
		);

		foreach ($canonical as $key => $value)
			if (strpos($key, 'bg_maintenance_') === 0 || in_array($key, $force_keys, true))
				$txt[$key] = $value;
	}
}

function bg_load_better_media_template()
{
	global $settings;

	// Better Gallery ships one canonical template in Themes/default.
	// SMF normally searches the active theme first, which can cause an older
	// copied BetterGallery.template.php in a custom theme to shadow upgrades.
	$original_dirs = !empty($settings['template_dirs']) && is_array($settings['template_dirs'])
		? $settings['template_dirs']
		: array();

	$default_dir = !empty($settings['default_theme_dir'])
		? rtrim($settings['default_theme_dir'], '/\\')
		: '';

	if ($default_dir !== '')
	{
		$settings['template_dirs'] = array_values(array_unique(array_merge(
			array($default_dir),
			$original_dirs
		)));
	}

	loadTemplate('BetterGallery');

	$settings['template_dirs'] = $original_dirs;
}

function bg_gallery_dispatch()
{
	global $context, $txt, $modSettings, $scripturl, $user_info;

	if (empty($modSettings['bg_enabled']))
		fatal_lang_error('bg_disabled', false);

	bg_require('bg_view_gallery');

	bg_load_better_media_language();
	bg_load_better_media_template();

	/* dev37: shared public action toolbar authority.
	 * Resolve these once for every Better Gallery page so Create Album and the
	 * common member actions remain reachable regardless of the active subpage. */
	$context['bg_identity_label'] = bg_media_identity_label();
	$context['bg_can_create'] = bg_allowed('bg_create_album')
		&& bg_portfolio_is_manager((int) $user_info['id']);
	$context['bg_can_moderate'] = bg_allowed('bg_moderate_gallery');

	if (empty($context['template_layers']) || !is_array($context['template_layers']))
		$context['template_layers'] = array();

	if (!in_array('bg_media_actions', $context['template_layers'], true))
		$context['template_layers'][] = 'bg_media_actions';

	bg_breadcrumb_add($context['bg_identity_label'], $scripturl . '?action=gallery');

	$sa = !empty($_REQUEST['sa']) ? (string) $_REQUEST['sa'] : 'home';

	$subactions = array(
		'home' => 'bg_gallery_home',
		'myalbums' => 'bg_gallery_home',
		'mygallery' => 'bg_my_gallery_dashboard',
		'favorites' => 'bg_my_favorites',
		'reports' => 'bg_report_queue',
		'reportdecide' => 'bg_report_decide',
		'album' => 'bg_album_view',
		'albumfavorite' => 'bg_album_favorite',
		'albumlike' => 'bg_album_like',
		'albumreaction' => 'bg_album_reaction',
		'albumreactors' => 'bg_album_reactors',
		'albumdeleteconfirm' => 'bg_album_delete_confirm',
		'albumdelete' => 'bg_album_delete',
		'albumdownload' => 'bg_album_download',
		'create' => 'bg_album_edit',
		'edit' => 'bg_album_edit',
		'unlock' => 'bg_album_unlock',
		'request' => 'bg_album_request_access',
		'requests' => 'bg_album_requests',
		'decide' => 'bg_album_decide_request',
		'upload' => 'bg_media_upload',
		'file' => 'bg_media_file',
		'mediaedit' => 'bg_media_edit',
		'mediadelete' => 'bg_media_delete',
		'mediabulkdelete' => 'bg_media_bulk_delete',
		'mediabulkmove' => 'bg_media_bulk_move',
		'setcover' => 'bg_album_set_cover',
		'mediainfo' => 'bg_media_info',
		'medialike' => 'bg_media_like',
		'mediareaction' => 'bg_media_reaction',
		'mediareactors' => 'bg_media_reactors',
		'mediafavorite' => 'bg_media_favorite',
		'mediareport' => 'bg_media_report',
		'mediacomment' => 'bg_media_comment',
		'commentreaction' => 'bg_comment_reaction',
		'commentreactors' => 'bg_comment_reactors',
		'commentdelete' => 'bg_media_comment_delete',
	);

	if (!isset($subactions[$sa]))
		$sa = 'home';

	$context['page_title'] = bg_media_identity_label();
	call_helper($subactions[$sa]);
}

function bg_format_bytes($bytes)
{
	$bytes = max(0, (float) $bytes);
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$unit = 0;

	while ($bytes >= 1024 && $unit < count($units) - 1)
	{
		$bytes /= 1024;
		$unit++;
	}

	$precision = $unit === 0 ? 0 : ($bytes >= 100 ? 0 : ($bytes >= 10 ? 1 : 2));

	return round($bytes, $precision) . ' ' . $units[$unit];
}

function bg_member_storage_used_bytes($id_member)
{
	global $smcFunc;

	$id_member = (int) $id_member;

	if ($id_member <= 0)
		return 0;

	$request = $smcFunc['db_query']('', '
		SELECT COALESCE(SUM(file_size), 0) AS storage_bytes
		FROM {db_prefix}bg_media
		WHERE id_owner = {int:id_member}',
		array('id_member' => $id_member)
	);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return max(0, (int) ($row['storage_bytes'] ?? 0));
}

function bg_member_storage_quota_bytes()
{
	global $modSettings;

	$quota_mb = isset($modSettings['bg_member_storage_quota_mb'])
		? max(0, (int) $modSettings['bg_member_storage_quota_mb'])
		: 0;

	return $quota_mb > 0 ? $quota_mb * 1048576 : 0;
}

function bg_member_storage_quota_status($id_member)
{
	$used = bg_member_storage_used_bytes($id_member);
	$quota = bg_member_storage_quota_bytes();
	$remaining = $quota > 0 ? max(0, $quota - $used) : 0;
	$percent = $quota > 0 ? min(999, round(($used / $quota) * 100, 1)) : 0;

	return array(
		'used_bytes' => $used,
		'quota_bytes' => $quota,
		'remaining_bytes' => $remaining,
		'percent' => $percent,
		'unlimited' => $quota <= 0,
		'at_limit' => $quota > 0 && $used >= $quota,
	);
}

function bg_my_gallery_dashboard()
{
	global $context, $smcFunc, $user_info, $txt;

	if (empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$id_member = (int) $user_info['id'];

	$context['bg_can_create'] = bg_allowed('bg_create_album')
		&& bg_portfolio_is_manager($id_member);
	$context['bg_can_moderate'] = bg_allowed('bg_moderate_gallery');
	$context['bg_dashboard_stats'] = array(
		'albums' => 0,
		'media' => 0,
		'album_views' => 0,
		'media_views' => 0,
		'likes_received' => 0,
		'comments_received' => 0,
		'favorites_received' => 0,
		'storage_bytes' => 0,
		'storage_formatted' => '0 B',
	);
	$context['bg_dashboard_recent_media'] = array();
	$context['bg_dashboard_recent_activity'] = array();
	$context['bg_dashboard_most_viewed'] = array();
	$context['bg_dashboard_most_liked'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS albums, COALESCE(SUM(num_views), 0) AS album_views
		FROM {db_prefix}bg_albums
		WHERE id_owner = {int:id_member}',
		array('id_member' => $id_member)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['bg_dashboard_stats']['albums'] = (int) ($row['albums'] ?? 0);
	$context['bg_dashboard_stats']['album_views'] = (int) ($row['album_views'] ?? 0);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS media, COALESCE(SUM(num_views), 0) AS media_views,
			COALESCE(SUM(file_size), 0) AS storage_bytes
		FROM {db_prefix}bg_media
		WHERE id_owner = {int:id_member}',
		array('id_member' => $id_member)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['bg_dashboard_stats']['media'] = (int) ($row['media'] ?? 0);
	$context['bg_dashboard_stats']['media_views'] = (int) ($row['media_views'] ?? 0);
	$context['bg_dashboard_stats']['storage_bytes'] = (int) ($row['storage_bytes'] ?? 0);
	$context['bg_dashboard_stats']['storage_formatted'] = bg_format_bytes($context['bg_dashboard_stats']['storage_bytes']);

	$quota = bg_member_storage_quota_status($id_member);
	$context['bg_dashboard_storage_quota'] = array(
		'used_bytes' => $quota['used_bytes'],
		'used_formatted' => bg_format_bytes($quota['used_bytes']),
		'quota_bytes' => $quota['quota_bytes'],
		'quota_formatted' => $quota['unlimited'] ? $txt['bg_storage_unlimited'] : bg_format_bytes($quota['quota_bytes']),
		'remaining_bytes' => $quota['remaining_bytes'],
		'remaining_formatted' => $quota['unlimited'] ? $txt['bg_storage_unlimited'] : bg_format_bytes($quota['remaining_bytes']),
		'percent' => $quota['percent'],
		'unlimited' => $quota['unlimited'],
		'at_limit' => $quota['at_limit'],
	);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_likes AS l
			INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = l.id_media)
		WHERE m.id_owner = {int:id_member}',
		array('id_member' => $id_member)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	$context['bg_dashboard_stats']['likes_received'] = (int) ($row['total'] ?? 0);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_comments AS c
			INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = c.id_media)
		WHERE m.id_owner = {int:id_member}
			AND c.id_member != {int:id_member}',
		array('id_member' => $id_member)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	$context['bg_dashboard_stats']['comments_received'] = (int) ($row['total'] ?? 0);

	$request = $smcFunc['db_query']('', '
		SELECT
			(SELECT COUNT(*)
			 FROM {db_prefix}bg_media_favorites AS f
			 INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = f.id_media)
			 WHERE m.id_owner = {int:id_member}) +
			(SELECT COUNT(*)
			 FROM {db_prefix}bg_album_favorites AS af
			 INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = af.id_album)
			 WHERE a.id_owner = {int:id_member}) AS total',
		array('id_member' => $id_member)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	$context['bg_dashboard_stats']['favorites_received'] = (int) ($row['total'] ?? 0);

	$request = $smcFunc['db_query']('', '
		SELECT m.id_media, m.id_album, m.media_type, m.title, m.thumb_key, m.poster_key,
			m.num_views, m.created_at, a.title AS album_title
		FROM {db_prefix}bg_media AS m
			INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
		WHERE m.id_owner = {int:id_member}
			AND m.status = {string:ready}
			AND m.approved = {int:approved}
		ORDER BY m.created_at DESC, m.id_media DESC
		LIMIT 6',
		array(
			'id_member' => $id_member,
			'ready' => 'ready',
			'approved' => 1,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_dashboard_recent_media'][] = $row;
	$smcFunc['db_free_result']($request);

	// Recent engagement on this member's media, newest first.
	$request = $smcFunc['db_query']('', '
		SELECT activity_type, id_media, media_title, actor_name, created_at
		FROM (
			SELECT {string:like_type} AS activity_type, l.id_media,
				m.title AS media_title, mem.real_name AS actor_name, l.created_at
			FROM {db_prefix}bg_media_likes AS l
				INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = l.id_media)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = l.id_member)
			WHERE m.id_owner = {int:id_member}
				AND l.id_member != {int:id_member}
			UNION ALL
			SELECT {string:comment_type} AS activity_type, c.id_media,
				m.title AS media_title, mem.real_name AS actor_name, c.created_at
			FROM {db_prefix}bg_media_comments AS c
				INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = c.id_media)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE m.id_owner = {int:id_member}
				AND c.id_member != {int:id_member}
		) AS activity
		ORDER BY created_at DESC
		LIMIT 8',
		array(
			'id_member' => $id_member,
			'like_type' => 'like',
			'comment_type' => 'comment',
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_dashboard_recent_activity'][] = $row;
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT m.id_media, m.id_album, m.media_type, m.title, m.thumb_key, m.poster_key,
			m.num_views, a.title AS album_title
		FROM {db_prefix}bg_media AS m
			INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
		WHERE m.id_owner = {int:id_member}
			AND m.status = {string:ready}
			AND m.approved = {int:approved}
		ORDER BY m.num_views DESC, m.id_media DESC
		LIMIT 5',
		array(
			'id_member' => $id_member,
			'ready' => 'ready',
			'approved' => 1,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_dashboard_most_viewed'][] = $row;
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT m.id_media, m.id_album, m.media_type, m.title, m.thumb_key, m.poster_key,
			COUNT(l.id_member) AS like_count, a.title AS album_title
		FROM {db_prefix}bg_media AS m
			INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
			LEFT JOIN {db_prefix}bg_media_likes AS l ON (l.id_media = m.id_media)
		WHERE m.id_owner = {int:id_member}
			AND m.status = {string:ready}
			AND m.approved = {int:approved}
		GROUP BY m.id_media, m.id_album, m.media_type, m.title, m.thumb_key, m.poster_key, a.title
		ORDER BY like_count DESC, m.id_media DESC
		LIMIT 5',
		array(
			'id_member' => $id_member,
			'ready' => 'ready',
			'approved' => 1,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_dashboard_most_liked'][] = $row;
	$smcFunc['db_free_result']($request);

	bg_breadcrumb_add($txt['bg_my_gallery']);
	$context['page_title'] = $txt['bg_my_gallery'];
	$context['sub_template'] = 'bg_my_gallery_dashboard';
}

function bg_portfolio_config()
{
	// Portfolio Mode was retired when Better Media became Better Gallery.
	// Keep this compatibility shim so older call sites and stored settings do
	// not break upgrades, but never filter the public gallery by one owner.
	return array(
		'enabled' => false,
		'id_owner' => 0,
		'owner_name' => '',
		'title' => 'Better Gallery',
		'intro' => '',
	);
}

function bg_portfolio_is_manager($id_member = 0)
{
	global $user_info;

	$id_member = $id_member > 0 ? (int) $id_member : (int) $user_info['id'];
	$config = bg_portfolio_config();

	if (empty($config['enabled']))
		return true;

	if (!empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery'))
		return true;

	return $id_member > 0 && $id_member === (int) $config['id_owner'];
}

function bg_portfolio_album_visible($album)
{
	global $user_info;

	$config = bg_portfolio_config();

	if (empty($config['enabled']))
		return true;

	if (!is_array($album) || empty($album['id_owner']))
		return false;

	if ((int) $album['id_owner'] === (int) $config['id_owner'])
		return true;

	// Preserve management access to legacy albums for their owner and moderators,
	// while keeping them out of the public portfolio.
	if (!empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery'))
		return true;

	return !empty($user_info['id']) && (int) $album['id_owner'] === (int) $user_info['id'];
}

function bg_gallery_home()
{
	global $context, $smcFunc, $user_info, $txt, $scripturl;

	$my_albums = !empty($_REQUEST['sa']) && $_REQUEST['sa'] === 'myalbums';

	if ($my_albums && empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$portfolio = bg_portfolio_config();

	$context['bg_is_my_albums'] = $my_albums;
	$context['bg_portfolio'] = $portfolio;
	$context['bg_identity_label'] = bg_media_identity_label();
	$context['bg_can_create'] = bg_allowed('bg_create_album')
		&& bg_portfolio_is_manager((int) $user_info['id']);
	$context['bg_can_moderate'] = bg_allowed('bg_moderate_gallery');
	$context['bg_albums'] = array();
	$context['bg_categories'] = bg_load_categories();

	$album_sort_map = array(
		'featured' => 'a.is_featured DESC, a.updated_at DESC, a.id_album DESC',
		'newest' => 'a.created_at DESC, a.id_album DESC',
		'oldest' => 'a.created_at ASC, a.id_album ASC',
		'title_asc' => 'a.title ASC, a.id_album ASC',
		'title_desc' => 'a.title DESC, a.id_album DESC',
	);

	$album_sort = isset($_REQUEST['sort']) ? (string) $_REQUEST['sort'] : 'featured';

	if (!isset($album_sort_map[$album_sort]))
		$album_sort = 'featured';

	$filters = array(
		'q' => isset($_REQUEST['q']) ? trim((string) $_REQUEST['q']) : '',
		'category' => !empty($_REQUEST['category']) ? max(0, (int) $_REQUEST['category']) : 0,
		'accessible_only' => !empty($_REQUEST['accessible_only']),
		'sort' => $album_sort,
	);

	$context['bg_search_filters'] = $filters;

	$per_page = 20;
	$start = isset($_REQUEST['start']) ? max(0, (int) $_REQUEST['start']) : 0;
	$context['bg_album_per_page'] = $per_page;
	$context['bg_album_start'] = $start;
	$context['bg_album_total'] = 0;
	$context['bg_album_page_index'] = '';

	if ($my_albums)
		bg_breadcrumb_add($txt['bg_my_albums']);
	elseif ($filters['category'] > 0)
	{
		foreach ($context['bg_categories'] as $category)
			if ((int) $category['id_category'] === (int) $filters['category'])
			{
				bg_breadcrumb_category((int) $category['id_category'], $category['name']);
				break;
			}
	}

	$where = $my_albums
		? array(
			'a.id_owner = {int:member}',
		)
		: array(
			'a.approved = {int:approved}',
			'(a.discovery_visibility >= {int:discoverable} OR a.id_owner = {int:member})',
		);
	if (!$my_albums && !empty($portfolio['enabled']))
		$where[] = 'a.id_owner = {int:portfolio_owner}';

	$params = array(
		'approved' => 1,
		'discoverable' => 1,
		'member' => (int) $user_info['id'],
		'portfolio_owner' => !empty($portfolio['id_owner']) ? (int) $portfolio['id_owner'] : 0,
		'image_type' => 'image',
		'ready_status' => 'ready',
		'approved_media' => 1,
	);

	if ($filters['q'] !== '')
	{
		$where[] = '(a.title LIKE {string:search}
			OR a.description LIKE {string:search}
			OR m.real_name LIKE {string:search}
			OR c.name LIKE {string:search})';
		$params['search'] = '%' . $filters['q'] . '%';
	}

	if ($filters['category'] > 0)
	{
		$where[] = 'a.id_category = {int:category}';
		$params['category'] = $filters['category'];
	}

	$select_sql = '
		SELECT a.id_album, a.id_owner, a.id_category, a.id_cover_media,
			a.title, a.description, a.discovery_visibility, a.access_mode,
			a.password_hash, a.num_items, a.num_views,
			a.created_at, a.updated_at, m.real_name AS owner_name,
			c.name AS category_name,
			(
				SELECT mi.id_media
				FROM {db_prefix}bg_media AS mi
				WHERE mi.id_album = a.id_album
					AND mi.media_type = {string:image_type}
					AND mi.status = {string:ready_status}
					AND mi.approved = {int:approved_media}
				ORDER BY mi.created_at ASC, mi.id_media ASC
				LIMIT 1
			) AS auto_cover_media,
			(
				SELECT mf.media_type
				FROM {db_prefix}bg_media AS mf
				WHERE mf.id_album = a.id_album
					AND mf.status = {string:ready_status}
					AND mf.approved = {int:approved_media}
				ORDER BY mf.created_at ASC, mf.id_media ASC
				LIMIT 1
			) AS first_media_type,
			(
				SELECT mm.mime_type
				FROM {db_prefix}bg_media AS mm
				WHERE mm.id_album = a.id_album
					AND mm.status = {string:ready_status}
					AND mm.approved = {int:approved_media}
				ORDER BY mm.created_at ASC, mm.id_media ASC
				LIMIT 1
			) AS first_media_mime
		FROM {db_prefix}bg_albums AS a
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_owner)
			LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
		WHERE ' . implode("\n\t\t\tAND ", $where) . '
		ORDER BY ' . $album_sort_map[$album_sort];

	if (!$filters['accessible_only'])
	{
		$count_request = $smcFunc['db_query']('', '
			SELECT COUNT(*) AS total
			FROM {db_prefix}bg_albums AS a
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_owner)
				LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
			WHERE ' . implode("\n\t\t\t\tAND ", $where),
			$params
		);

		$count_row = $smcFunc['db_fetch_assoc']($count_request);
		$context['bg_album_total'] = !empty($count_row['total']) ? (int) $count_row['total'] : 0;
		$smcFunc['db_free_result']($count_request);

		if ($context['bg_album_total'] > 0 && $start >= $context['bg_album_total'])
			$start = max(0, (int) (floor(($context['bg_album_total'] - 1) / $per_page) * $per_page));

		$context['bg_album_start'] = $start;
		$page_params = $params;
		$page_params['start'] = $start;
		$page_params['limit'] = $per_page;

		$request = $smcFunc['db_query']('', $select_sql . '
			LIMIT {int:start}, {int:limit}',
			$page_params
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$row['accessible'] = bg_album_content_accessible($row, (int) $user_info['id'], !empty($user_info['is_admin']));
			$row['locked'] = !$row['accessible'];

			if (empty($row['id_cover_media']) && !empty($row['auto_cover_media']))
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}bg_albums
					SET id_cover_media = {int:id_media}
					WHERE id_album = {int:id_album}
						AND id_cover_media = {int:no_cover}',
					array(
						'id_media' => (int) $row['auto_cover_media'],
						'id_album' => (int) $row['id_album'],
						'no_cover' => 0,
					)
				);
				$row['id_cover_media'] = (int) $row['auto_cover_media'];
			}

			$context['bg_albums'][] = $row;
		}

		$smcFunc['db_free_result']($request);
	}
	else
	{
		$accessible_rows = array();
		$request = $smcFunc['db_query']('', $select_sql, $params);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$row['accessible'] = bg_album_content_accessible($row, (int) $user_info['id'], !empty($user_info['is_admin']));

			if (!$row['accessible'])
				continue;

			$row['locked'] = false;
			$accessible_rows[] = $row;
		}

		$smcFunc['db_free_result']($request);
		$context['bg_album_total'] = count($accessible_rows);

		if ($context['bg_album_total'] > 0 && $start >= $context['bg_album_total'])
			$start = max(0, (int) (floor(($context['bg_album_total'] - 1) / $per_page) * $per_page));

		$context['bg_album_start'] = $start;
		$context['bg_albums'] = array_slice($accessible_rows, $start, $per_page);

		foreach ($context['bg_albums'] as &$row)
		{
			if (empty($row['id_cover_media']) && !empty($row['auto_cover_media']))
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}bg_albums
					SET id_cover_media = {int:id_media}
					WHERE id_album = {int:id_album}
						AND id_cover_media = {int:no_cover}',
					array(
						'id_media' => (int) $row['auto_cover_media'],
						'id_album' => (int) $row['id_album'],
						'no_cover' => 0,
					)
				);
				$row['id_cover_media'] = (int) $row['auto_cover_media'];
			}
		}
		unset($row);
	}

	$page_url = $scripturl . '?action=gallery';

	if ($my_albums)
		$page_url .= ';sa=myalbums';
	if ($filters['q'] !== '')
		$page_url .= ';q=' . urlencode($filters['q']);
	if ($filters['category'] > 0)
		$page_url .= ';category=' . (int) $filters['category'];
	if ($filters['accessible_only'])
		$page_url .= ';accessible_only=1';
	if ($filters['sort'] !== 'featured')
		$page_url .= ';sort=' . urlencode($filters['sort']);

	$context['bg_album_page_index'] = constructPageIndex(
		$page_url,
		$start,
		$context['bg_album_total'],
		$per_page
	);
	if ($my_albums)
		$context['page_title'] = $txt['bg_my_albums'];
	elseif (!empty($portfolio['enabled']))
		$context['page_title'] = $portfolio['title'];

	$context['sub_template'] = 'bg_home';
}

function bg_unique_viewer_key()
{
	global $context, $user_info;

	if (!empty($user_info['id']))
		return hash('sha256', 'member:' . (int) $user_info['id']);

	$session = '';

	if (!empty($context['session_id']))
		$session = (string) $context['session_id'];
	elseif (function_exists('session_id'))
		$session = (string) session_id();

	if ($session === '')
		return '';

	return hash('sha256', 'guest-session:' . $session);
}

function bg_record_unique_view($target_type, $id_target, $id_owner = 0)
{
	global $smcFunc, $user_info;

	$target_type = $target_type === 'media' ? 'media' : 'album';
	$id_target = (int) $id_target;
	$id_owner = (int) $id_owner;

	if ($id_target <= 0)
		return false;

	// Owners do not inflate their own album/media view counts.
	if ($id_owner > 0 && !empty($user_info['id']) && (int) $user_info['id'] === $id_owner)
		return false;

	$viewer_key = bg_unique_viewer_key();

	if ($viewer_key === '')
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT viewer_key
		FROM {db_prefix}bg_unique_views
		WHERE target_type = {string:target_type}
			AND id_target = {int:id_target}
			AND viewer_key = {string:viewer_key}
		LIMIT 1',
		array(
			'target_type' => $target_type,
			'id_target' => $id_target,
			'viewer_key' => $viewer_key,
		)
	);

	$exists = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($exists)
		return false;

	$smcFunc['db_insert']('ignore',
		'{db_prefix}bg_unique_views',
		array(
			'target_type' => 'string',
			'id_target' => 'int',
			'viewer_key' => 'string',
			'created_at' => 'int',
		),
		array(
			$target_type,
			$id_target,
			$viewer_key,
			time(),
		),
		array('target_type', 'id_target', 'viewer_key')
	);

	$table = $target_type === 'media' ? 'bg_media' : 'bg_albums';
	$id_column = $target_type === 'media' ? 'id_media' : 'id_album';

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}' . $table . '
		SET num_views = num_views + 1
		WHERE ' . $id_column . ' = {int:id_target}',
		array('id_target' => $id_target)
	);

	return true;
}

function bg_my_favorites()
{
	global $context, $smcFunc, $user_info, $txt;

	if (empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$type = !empty($_REQUEST['type']) && $_REQUEST['type'] === 'albums' ? 'albums' : 'media';

	$context['bg_favorite_type'] = $type;
	$context['bg_favorites'] = array();
	$context['bg_favorite_albums'] = array();
	$context['bg_can_moderate'] = bg_allowed('bg_moderate_gallery');

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if ($type === 'albums')
	{
		$request = $smcFunc['db_query']('', '
			SELECT f.id_album, f.created_at AS favorited_at,
				a.id_owner, a.id_category, a.id_cover_media,
				a.title, a.description, a.discovery_visibility, a.access_mode,
				a.password_hash, a.num_items, a.num_views,
				m.real_name AS owner_name, c.name AS category_name
			FROM {db_prefix}bg_album_favorites AS f
				INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = f.id_album)
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_owner)
				LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
			WHERE f.id_member = {int:id_member}
				AND a.approved = {int:approved}
			ORDER BY f.created_at DESC, f.id_album DESC',
			array(
				'id_member' => (int) $user_info['id'],
				'approved' => 1,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!bg_album_is_discoverable($row, (int) $user_info['id'], $is_admin)
				&& (int) $row['id_owner'] !== (int) $user_info['id'])
				continue;

			if (!bg_album_content_accessible($row, (int) $user_info['id'], $is_admin))
				continue;

			$context['bg_favorite_albums'][] = $row;
		}

		$smcFunc['db_free_result']($request);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT f.id_media, f.created_at AS favorited_at,
				m.id_album, m.media_type, m.title, m.description, m.mime_type,
				m.file_size, m.width, m.height, m.poster_key, m.thumb_key, m.num_views, m.num_downloads,
				a.title AS album_title, a.id_owner AS album_owner,
				a.discovery_visibility, a.access_mode, a.password_hash,
				a.approved AS album_approved
			FROM {db_prefix}bg_media_favorites AS f
				INNER JOIN {db_prefix}bg_media AS m ON (m.id_media = f.id_media)
				INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
			WHERE f.id_member = {int:id_member}
				AND m.status = {string:ready}
				AND m.approved = {int:approved}
				AND a.approved = {int:album_approved}
			ORDER BY f.created_at DESC, f.id_media DESC',
			array(
				'id_member' => (int) $user_info['id'],
				'ready' => 'ready',
				'approved' => 1,
				'album_approved' => 1,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$album = array(
				'id_album' => (int) $row['id_album'],
				'id_owner' => (int) $row['album_owner'],
				'discovery_visibility' => (int) $row['discovery_visibility'],
				'access_mode' => (int) $row['access_mode'],
				'password_hash' => $row['password_hash'],
			);

			if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
				continue;

			$context['bg_favorites'][] = $row;
		}

		$smcFunc['db_free_result']($request);
	}

	bg_breadcrumb_add($txt['bg_my_favorites']);
	$context['page_title'] = $txt['bg_my_favorites'];
	$context['sub_template'] = 'bg_my_favorites';
}

function bg_report_queue()
{
	global $context, $smcFunc, $txt;

	bg_require('bg_moderate_gallery');

	$status = isset($_REQUEST['status']) ? (string) $_REQUEST['status'] : 'open';
	if (!in_array($status, array('open', 'resolved', 'dismissed', 'all'), true))
		$status = 'open';

	$where = '';
	$params = array();

	if ($status !== 'all')
	{
		$where = 'WHERE r.status = {string:status}';
		$params['status'] = $status;
	}

	$context['bg_report_status'] = $status;
	$context['bg_reports'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT r.id_report, r.target_type, r.id_media, r.id_comment,
			r.id_reporter, r.reason, r.note, r.status, r.created_at,
			r.decided_at, r.decided_by,
			reporter.real_name AS reporter_name,
			media.title AS media_title, media.id_album,
			album.title AS album_title,
			comment.body AS comment_body
		FROM {db_prefix}bg_reports AS r
			LEFT JOIN {db_prefix}members AS reporter ON (reporter.id_member = r.id_reporter)
			LEFT JOIN {db_prefix}bg_media AS media ON (media.id_media = r.id_media)
			LEFT JOIN {db_prefix}bg_albums AS album ON (album.id_album = media.id_album)
			LEFT JOIN {db_prefix}bg_media_comments AS comment ON (comment.id_comment = r.id_comment)
		' . $where . '
		ORDER BY r.status = {string:open_status} DESC, r.created_at DESC, r.id_report DESC',
		array_merge($params, array('open_status' => 'open'))
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_reports'][] = $row;

	$smcFunc['db_free_result']($request);

	bg_breadcrumb_add($txt['bg_report_queue']);
	$context['page_title'] = $txt['bg_report_queue'];
	$context['sub_template'] = 'bg_report_queue';
}

function bg_report_decide()
{
	global $smcFunc, $user_info;

	bg_require('bg_moderate_gallery');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$id_report = !empty($_POST['report']) ? (int) $_POST['report'] : 0;
	$status = !empty($_POST['decision']) && $_POST['decision'] === 'dismissed'
		? 'dismissed'
		: 'resolved';

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_reports
		SET status = {string:status},
			decided_at = {int:decided_at},
			decided_by = {int:decided_by}
		WHERE id_report = {int:id_report}
			AND status = {string:open_status}',
		array(
			'status' => $status,
			'decided_at' => time(),
			'decided_by' => (int) $user_info['id'],
			'id_report' => $id_report,
			'open_status' => 'open',
		)
	);

	redirectexit('action=gallery;sa=reports');
}

/* -------------------------------------------------------
 * Album CRUD
 * ----------------------------------------------------- */

function bg_album_load($id_album)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT a.*, m.real_name AS owner_name, c.name AS category_name
		FROM {db_prefix}bg_albums AS a
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_owner)
			LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
		WHERE a.id_album = {int:id_album}
		LIMIT 1',
		array('id_album' => (int) $id_album)
	);

	$album = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return $album ?: false;
}

function bg_album_favorite()
{
	global $smcFunc, $user_info;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	if (empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$id_album = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_portfolio_album_visible($album))
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner)
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		fatal_lang_error('no_access', false);

	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}bg_album_favorites
		WHERE id_album = {int:id_album}
			AND id_member = {int:id_member}
		LIMIT 1',
		array(
			'id_album' => $id_album,
			'id_member' => (int) $user_info['id'],
		)
	);

	$favorited = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($favorited)
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_album_favorites
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}',
			array(
				'id_album' => $id_album,
				'id_member' => (int) $user_info['id'],
			)
		);
	else
		$smcFunc['db_insert']('ignore',
			'{db_prefix}bg_album_favorites',
			array(
				'id_album' => 'int',
				'id_member' => 'int',
				'created_at' => 'int',
			),
			array(
				$id_album,
				(int) $user_info['id'],
				time(),
			),
			array('id_album', 'id_member')
		);

	redirectexit('action=gallery;sa=album;album=' . $id_album);
}


function bg_album_like()
{
	global $smcFunc, $user_info, $modSettings;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	if (empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$id_album = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_portfolio_album_visible($album))
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner)
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		fatal_lang_error('no_access', false);

	/* Album Likes share the existing Better Gallery like switch and each
	 * album's Allow Likes choice, but use their own album-level table. */
	if (empty($modSettings['bg_likes_enabled']) || empty($album['allow_ratings']))
		fatal_lang_error('bg_album_likes_disabled', false);

	// A direct legacy album Like still replaces any custom album reaction.
	if (bg_better_reactions_available())
		BetterReactions_ClearObjectReaction('media_album', $id_album, (int) $user_info['id']);

	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}bg_album_likes
		WHERE id_album = {int:id_album}
			AND id_member = {int:id_member}
		LIMIT 1',
		array(
			'id_album' => $id_album,
			'id_member' => (int) $user_info['id'],
		)
	);

	$liked = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($liked)
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_album_likes
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}',
			array(
				'id_album' => $id_album,
				'id_member' => (int) $user_info['id'],
			)
		);
	else
		$smcFunc['db_insert']('ignore',
			'{db_prefix}bg_album_likes',
			array(
				'id_album' => 'int',
				'id_member' => 'int',
				'created_at' => 'int',
			),
			array(
				$id_album,
				(int) $user_info['id'],
				time(),
			),
			array('id_album', 'id_member')
		);

	redirectexit('action=gallery;sa=album;album=' . $id_album);
}

function bg_album_reaction()
{
	global $smcFunc, $user_info, $modSettings;

	checkSession('post');

	if (empty($user_info['id']))
		bg_upload_json_response(false, 'You must be logged in to react to albums.');

	if (!bg_better_reactions_available())
		bg_upload_json_response(false, 'Better Reactions is not available.');

	$id_album = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$reaction = isset($_POST['reaction']) ? strtolower(trim((string) $_POST['reaction'])) : '';
	$album = bg_album_load($id_album);

	if (!$album)
		bg_upload_json_response(false, 'Album not found.');

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_portfolio_album_visible($album)
		|| (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner))
		bg_upload_json_response(false, 'Album not found.');

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		bg_upload_json_response(false, 'You do not have access to this album.');

	if (empty($modSettings['bg_likes_enabled']) || empty($album['allow_ratings']))
		bg_upload_json_response(false, 'Reactions are disabled for this album.');

	$liked = false;

	if ($reaction === 'like')
	{
		BetterReactions_ClearObjectReaction('media_album', $id_album, (int) $user_info['id']);
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}bg_album_likes
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}
			LIMIT 1',
			array('id_album' => $id_album, 'id_member' => (int) $user_info['id'])
		);
		$liked = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);

		if ($liked)
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}bg_album_likes
				WHERE id_album = {int:id_album}
					AND id_member = {int:id_member}',
				array('id_album' => $id_album, 'id_member' => (int) $user_info['id'])
			);
		else
			$smcFunc['db_insert']('ignore',
				'{db_prefix}bg_album_likes',
				array('id_album' => 'int', 'id_member' => 'int', 'created_at' => 'int'),
				array($id_album, (int) $user_info['id'], time()),
				array('id_album', 'id_member')
			);

		$liked = !$liked;
	}
	else
	{
		// Custom reactions replace the native Better Gallery album Like.
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_album_likes
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}',
			array('id_album' => $id_album, 'id_member' => (int) $user_info['id'])
		);

		$result = BetterReactions_SetObjectReaction('media_album', $id_album, $reaction, (int) $user_info['id']);
		if (empty($result['ok']))
			bg_upload_json_response(false, 'That reaction is not available.');
	}

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS likes
		FROM {db_prefix}bg_album_likes
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	$like_count = !empty($row['likes']) ? (int) $row['likes'] : 0;

	bg_upload_json_response(true, 'OK', array(
		'better_reactions' => bg_album_reaction_payload($id_album, $like_count, $liked),
		'liked' => $liked,
		'like_count' => $like_count,
	));
}

function bg_album_reactors()
{
	global $smcFunc, $user_info, $scripturl, $modSettings;

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$reaction = isset($_REQUEST['reaction']) ? strtolower(trim((string) $_REQUEST['reaction'])) : '';
	$album = bg_album_load($id_album);

	if (!$album)
		bg_upload_json_response(false, 'Album not found.');

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_portfolio_album_visible($album)
		|| (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner))
		bg_upload_json_response(false, 'Album not found.');

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		bg_upload_json_response(false, 'You do not have access to this album.');

	if ($reaction === 'like')
	{
		$people = array();
		$request = $smcFunc['db_query']('', '
			SELECT l.id_member,
				COALESCE(NULLIF(m.real_name, {string:empty}), NULLIF(m.member_name, {string:empty}), {string:former}) AS reactor_name
			FROM {db_prefix}bg_album_likes AS l
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = l.id_member)
			WHERE l.id_album = {int:id_album}
			ORDER BY l.created_at ASC
			LIMIT 50',
			array('empty' => '', 'former' => 'Former member', 'id_album' => $id_album)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$people[] = array(
				'id' => (int) $row['id_member'],
				'name' => (string) $row['reactor_name'],
				'url' => (!isset($modSettings['better_reactions_profile_links']) || !empty($modSettings['better_reactions_profile_links']))
					? $scripturl . '?action=profile;u=' . (int) $row['id_member'] : '',
			);
		$smcFunc['db_free_result']($request);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*) AS total
			FROM {db_prefix}bg_album_likes
			WHERE id_album = {int:id_album}',
			array('id_album' => $id_album)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		bg_upload_json_response(true, 'OK', array('people' => $people, 'total' => (int) ($row['total'] ?? 0)));
	}

	if (!bg_better_reactions_available())
		bg_upload_json_response(false, 'Better Reactions is not available.');

	bg_upload_json_response(true, 'OK', BetterReactions_GetObjectReactors('media_album', $id_album, $reaction, 50));
}

function bg_album_delete_confirm()
{
	global $context, $user_info, $txt, $smcFunc;

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!$is_owner && !$is_admin)
		fatal_lang_error('no_access', false);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$album['actual_media_count'] = !empty($row['total']) ? (int) $row['total'] : 0;

	bg_breadcrumb_album($album);
	bg_breadcrumb_add($txt['bg_delete_album']);

	$context['bg_delete_album'] = $album;
	$context['page_title'] = $txt['bg_delete_album'];
	$context['sub_template'] = 'bg_album_delete_confirm';
}

function bg_album_delete()
{
	global $smcFunc, $user_info;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$id_album = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!$is_owner && !$is_admin)
		fatal_lang_error('no_access', false);

	$confirmed = !empty($_POST['confirm_permanent']);
	$typed_title = isset($_POST['confirm_title']) ? trim((string) $_POST['confirm_title']) : '';

	if (!$confirmed || $typed_title !== (string) $album['title'])
		redirectexit('action=gallery;sa=albumdeleteconfirm;album=' . $id_album . ';confirmerror=1');

	// Load every media row first so the proven storage cleanup helper can remove
	// originals, thumbnails and posters before database records disappear.
	$request = $smcFunc['db_query']('', '
		SELECT id_media, id_album, storage_backend, storage_key, thumb_key, poster_key
		FROM {db_prefix}bg_media
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	$media_rows = array();
	$media_ids = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$media_rows[] = $row;
		$media_ids[] = (int) $row['id_media'];
	}
	$smcFunc['db_free_result']($request);

	foreach ($media_rows as $media)
		bg_media_delete_files($media);

	if (!empty($media_ids))
	{
		$params = array('media_ids' => $media_ids);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_reports
			WHERE id_media IN ({array_int:media_ids})',
			$params
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_tags
			WHERE id_media IN ({array_int:media_ids})',
			$params
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_comments
			WHERE id_media IN ({array_int:media_ids})',
			$params
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_likes
			WHERE id_media IN ({array_int:media_ids})',
			$params
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_favorites
			WHERE id_media IN ({array_int:media_ids})',
			$params
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_unique_views
			WHERE target_type = {string:target_type}
				AND id_target IN ({array_int:media_ids})',
			array(
				'target_type' => 'media',
				'media_ids' => $media_ids,
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media
			WHERE id_album = {int:id_album}
				AND id_media IN ({array_int:media_ids})',
			array(
				'id_album' => $id_album,
				'media_ids' => $media_ids,
			)
		);
	}

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_album_access
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_access_requests
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_album_favorites
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_album_likes
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_unique_views
		WHERE target_type = {string:target_type}
			AND id_target = {int:id_album}',
		array(
			'target_type' => 'album',
			'id_album' => $id_album,
		)
	);

	// Remove any abandoned/generated album ZIP parts for this album.
	$download_dir = bg_storage_root() . '/downloads';
	foreach ((array) glob($download_dir . '/album_' . $id_album . '_*.zip') as $zip)
		if (is_file($zip))
			@unlink($zip);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_albums
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);

	unset($GLOBALS['better_tags_gallery_album_deleted_ran'][(int) $id_album]);

	if (function_exists('call_integration_hook'))
		call_integration_hook('integrate_bettergallery_album_deleted', array($id_album, $media_ids));

	if (empty($GLOBALS['better_tags_gallery_album_deleted_ran'][(int) $id_album]))
		bg_optional_better_tags_bridge('better_tags_gallery_album_deleted', array($id_album, $media_ids));

	redirectexit('action=gallery;sa=myalbums;albumdeleted=1');
}

function bg_album_view()
{
	global $context, $txt, $user_info, $smcFunc, $modSettings, $scripturl;

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner)
		fatal_lang_error('bg_album_not_found', false);

	$accessible = bg_album_content_accessible($album, (int) $user_info['id'], $is_admin);

	if (bg_record_unique_view('album', $id_album, (int) $album['id_owner']))
		$album['num_views'] = (int) $album['num_views'] + 1;

	$album_like_count = 0;
	$album_liked = false;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS likes
		FROM {db_prefix}bg_album_likes
		WHERE id_album = {int:id_album}',
		array('id_album' => $id_album)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$album_like_count = !empty($row['likes']) ? (int) $row['likes'] : 0;
	$smcFunc['db_free_result']($request);

	if (!empty($user_info['id']) && $accessible && !empty($modSettings['bg_likes_enabled']) && !empty($album['allow_ratings']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}bg_album_likes
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}
			LIMIT 1',
			array(
				'id_album' => $id_album,
				'id_member' => (int) $user_info['id'],
			)
		);
		$album_liked = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);
	}

	$album_favorited = false;
	if (!empty($user_info['id']) && $accessible)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}bg_album_favorites
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}
			LIMIT 1',
			array(
				'id_album' => $id_album,
				'id_member' => (int) $user_info['id'],
			)
		);
		$album_favorited = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);
	}

	bg_breadcrumb_album($album);

	$context['bg_album'] = $album;
	$context['bg_album']['can_favorite_album'] = !empty($user_info['id']) && $accessible;
	$context['bg_album']['album_favorited'] = $album_favorited;
	$context['bg_album']['can_like_album'] =
		!empty($user_info['id'])
		&& $accessible
		&& !empty($modSettings['bg_likes_enabled'])
		&& !empty($album['allow_ratings']);
	$context['bg_album']['album_liked'] = $album_liked;
	$context['bg_album']['album_like_count'] = $album_like_count;
	$context['bg_album']['better_reactions'] = $context['bg_album']['can_like_album']
		? bg_album_reaction_payload($id_album, $album_like_count, $album_liked)
		: array('enabled' => false);
	$context['bg_album']['accessible'] = $accessible;
	$context['bg_album']['is_owner'] = $is_owner;
	$context['bg_album']['can_edit'] = $is_owner || $is_admin;
	$context['bg_album']['can_delete_album'] = $is_owner || $is_admin;
	$context['bg_album']['can_request'] =
		!$accessible
		&& (int) $user_info['id'] > 0
		&& bg_allowed('bg_request_access')
		&& in_array((int) $album['access_mode'], array(2, 4, 5), true);

	$context['bg_album']['request_status'] = '';
	if ((int) $user_info['id'] > 0)
	{
		$request = $smcFunc['db_query']('', '
			SELECT status
			FROM {db_prefix}bg_access_requests
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}
			ORDER BY id_request DESC
			LIMIT 1',
			array(
				'id_album' => $id_album,
				'id_member' => (int) $user_info['id'],
			)
		);

		if ($row = $smcFunc['db_fetch_assoc']($request))
			$context['bg_album']['request_status'] = $row['status'];

		$smcFunc['db_free_result']($request);
	}

	$context['bg_album']['can_upload'] =
		$accessible
		&& ($is_owner || $is_admin)
		&& bg_allowed('bg_upload_media')
		&& bg_portfolio_is_manager((int) $album['id_owner']);

	$context['bg_album']['can_manage_media'] =
		$accessible
		&& ($is_owner || $is_admin);

	$context['bg_move_destinations'] = array();

	if (!empty($context['bg_album']['can_manage_media']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_album, title, discovery_visibility, access_mode
			FROM {db_prefix}bg_albums
			WHERE id_owner = {int:id_owner}
				AND id_album != {int:source_album}
				AND approved = {int:approved}
			ORDER BY title ASC, id_album ASC',
			array(
				'id_owner' => (int) $album['id_owner'],
				'source_album' => $id_album,
				'approved' => 1,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['bg_move_destinations'][] = $row;

		$smcFunc['db_free_result']($request);
	}

	$context['bg_album']['comments_enabled'] =
		!empty($modSettings['bg_comments_enabled'])
		&& !empty($album['allow_comments']);

	$context['bg_album']['likes_enabled'] =
		!empty($modSettings['bg_likes_enabled'])
		&& !empty($album['allow_ratings']);

	$context['bg_album']['downloads_enabled'] = !empty($album['allow_downloads']);
	$context['bg_album']['album_download_available'] =
		$accessible
		&& !empty($album['allow_downloads'])
		&& bg_album_zip_available();

	$php_upload_bytes = bg_ini_bytes(ini_get('upload_max_filesize'));
	$php_post_bytes = bg_ini_bytes(ini_get('post_max_size'));
	$php_file_ceiling = 0;

	if ($php_upload_bytes > 0 && $php_post_bytes > 0)
		$php_file_ceiling = min($php_upload_bytes, $php_post_bytes);
	elseif ($php_upload_bytes > 0)
		$php_file_ceiling = $php_upload_bytes;
	elseif ($php_post_bytes > 0)
		$php_file_ceiling = $php_post_bytes;

	$member_quota = bg_member_storage_quota_status((int) $user_info['id']);

	$context['bg_upload_config'] = array(
		'max_files' => max(1, (int) $modSettings['bg_max_upload_files']),
		'progress_mode' => isset($modSettings['bg_upload_progress_mode']) && $modSettings['bg_upload_progress_mode'] === 'per_file' ? 'per_file' : 'compact',
		'max_image_bytes' => (!empty($modSettings['bg_max_image_kb']) ? max(1, (int) $modSettings['bg_max_image_kb']) : 10240) * 1024,
		'max_video_bytes' => (!empty($modSettings['bg_max_video_mb']) ? max(1, (int) $modSettings['bg_max_video_mb']) : 250) * 1024 * 1024,
		'max_file_bytes' => (!empty($modSettings['bg_max_file_mb']) ? max(1, (int) $modSettings['bg_max_file_mb']) : 250) * 1024 * 1024,
		'allowed_extensions' => bg_allowed_extensions(),
		'allowed_extensions_csv' => bg_allowed_extensions_csv(),
		'php_file_ceiling' => $php_file_ceiling,
		'storage_quota_bytes' => $member_quota['quota_bytes'],
		'storage_used_bytes' => $member_quota['used_bytes'],
		'storage_remaining_bytes' => $member_quota['remaining_bytes'],
		'storage_unlimited' => $member_quota['unlimited'],
		'storage_at_limit' => $member_quota['at_limit'],
	);

	$context['bg_media'] = array();

	$per_page = !empty($modSettings['bg_media_per_page'])
		? max(6, min(100, (int) $modSettings['bg_media_per_page']))
		: 24;

	$sort = isset($_REQUEST['sort']) ? (string) $_REQUEST['sort'] : 'newest';
	$sort_map = array(
		'newest' => 'm.created_at DESC, m.id_media DESC',
		'oldest' => 'm.created_at ASC, m.id_media ASC',
		'title_asc' => 'm.title ASC, m.id_media ASC',
		'title_desc' => 'm.title DESC, m.id_media DESC',
	);

	if (!isset($sort_map[$sort]))
		$sort = 'newest';

	$start = !empty($_REQUEST['start']) ? max(0, (int) $_REQUEST['start']) : 0;

	$context['bg_media_sort'] = $sort;
	$context['bg_media_per_page'] = $per_page;
	$context['bg_media_total'] = 0;

	if ($accessible)
	{
		$count_request = $smcFunc['db_query']('', '
			SELECT COUNT(*) AS total
			FROM {db_prefix}bg_media
			WHERE id_album = {int:id_album}
				AND approved = {int:approved}
				AND status = {string:ready}',
			array(
				'id_album' => $id_album,
				'approved' => 1,
				'ready' => 'ready',
			)
		);

		$count_row = $smcFunc['db_fetch_assoc']($count_request);
		$context['bg_media_total'] = !empty($count_row['total']) ? (int) $count_row['total'] : 0;
		$smcFunc['db_free_result']($count_request);

		if ($context['bg_media_total'] > 0 && $start >= $context['bg_media_total'])
			$start = max(0, (int) (floor(($context['bg_media_total'] - 1) / $per_page) * $per_page));

		$context['bg_media_start'] = $start;

		$request = $smcFunc['db_query']('', '
			SELECT m.id_media, m.id_album, m.id_owner, m.media_type, m.title, m.description,
				m.filename, m.mime_type, m.file_size, m.width, m.height, m.duration_ms,
				m.poster_key, m.thumb_key, m.num_views, m.num_downloads, m.created_at,
				(SELECT COUNT(*) FROM {db_prefix}bg_media_likes AS ml WHERE ml.id_media = m.id_media) AS like_count,
				(SELECT COUNT(*) FROM {db_prefix}bg_media_comments AS mc WHERE mc.id_media = m.id_media) AS comment_count
			FROM {db_prefix}bg_media AS m
			WHERE m.id_album = {int:id_album}
				AND m.approved = {int:approved}
				AND m.status = {string:ready}
			ORDER BY ' . $sort_map[$sort] . '
			LIMIT {int:start}, {int:limit}',
			array(
				'id_album' => $id_album,
				'approved' => 1,
				'ready' => 'ready',
				'start' => $start,
				'limit' => $per_page,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$row['is_cover'] = (int) $album['id_cover_media'] === (int) $row['id_media'];
			$context['bg_media'][] = $row;
		}

		$smcFunc['db_free_result']($request);

		$sort_suffix = $sort !== 'newest' ? ';sort=' . urlencode($sort) : '';

		$context['page_index'] = constructPageIndex(
			$scripturl . '?action=gallery;sa=album;album=' . $id_album . $sort_suffix,
			$start,
			$context['bg_media_total'],
			$per_page
		);
	}

	$context['bg_album_integrations_html'] = '';
	unset($context['better_tags_gallery_album_view_ran']);

	if (function_exists('call_integration_hook'))
		call_integration_hook(
			'integrate_bettergallery_album_view',
			array(&$context['bg_album_integrations_html'], $context['bg_album'])
		);

	if (empty($context['better_tags_gallery_album_view_ran']))
	{
		$args = array(&$context['bg_album_integrations_html'], $context['bg_album']);
		bg_optional_better_tags_bridge('better_tags_gallery_album_view', $args);
	}

	$context['page_title'] = $album['title'];
	$context['sub_template'] = 'bg_album';
}

function bg_album_zip_available()
{
	return class_exists('ZipArchive');
}

function bg_album_download_cleanup($max_age = 7200)
{
	$dir = bg_storage_root() . '/downloads';

	if (!is_dir($dir))
		return;

	$cutoff = time() - max(300, (int) $max_age);

	foreach ((array) glob($dir . '/album_*.zip') as $file)
		if (is_file($file) && (int) @filemtime($file) < $cutoff)
			@unlink($file);
}

function bg_album_download_files($id_album)
{
	global $smcFunc;

	$files = array();
	$root = bg_storage_root();

	$request = $smcFunc['db_query']('', '
		SELECT id_media, media_type, filename, storage_backend, storage_key, mime_type, file_size
		FROM {db_prefix}bg_media
		WHERE id_album = {int:id_album}
			AND approved = {int:approved}
			AND status = {string:ready}
		ORDER BY created_at ASC, id_media ASC',
		array(
			'id_album' => (int) $id_album,
			'approved' => 1,
			'ready' => 'ready',
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (empty($row['storage_key']))
			continue;

		if (!empty($row['storage_backend']) && $row['storage_backend'] === 's3')
		{
			$row['path'] = '';
			$row['file_size'] = max(0, (int) $row['file_size']);
			$files[] = $row;
			continue;
		}

		$path = $root . '/' . ltrim((string) $row['storage_key'], '/');
		if (!is_file($path)) continue;
		$actual_size = (int) @filesize($path);
		$row['path'] = $path;
		$row['file_size'] = $actual_size > 0 ? $actual_size : max(0, (int) $row['file_size']);
		$files[] = $row;
	}

	$smcFunc['db_free_result']($request);

	return $files;
}

function bg_album_download_chunks($files, $target_bytes)
{
	$target_bytes = max(1, (int) $target_bytes);
	$chunks = array();
	$current = array();
	$current_size = 0;

	foreach ($files as $file)
	{
		$size = max(0, (int) $file['file_size']);

		if (!empty($current) && $current_size + $size > $target_bytes)
		{
			$chunks[] = array(
				'files' => $current,
				'size' => $current_size,
			);
			$current = array();
			$current_size = 0;
		}

		$current[] = $file;
		$current_size += $size;

		// Never split a media file. An oversized file becomes one ZIP part.
		if ($size > $target_bytes)
		{
			$chunks[] = array(
				'files' => $current,
				'size' => $current_size,
			);
			$current = array();
			$current_size = 0;
		}
	}

	if (!empty($current))
		$chunks[] = array(
			'files' => $current,
			'size' => $current_size,
		);

	return $chunks;
}

function bg_album_download_safe_name($name)
{
	$name = trim((string) $name);
	$name = preg_replace('~[\\\\/:*?"<>|]+~', '_', $name);
	$name = preg_replace('~\s+~', ' ', $name);
	$name = trim($name, " .\t\n\r\0\x0B");

	return $name !== '' ? $name : 'album';
}

function bg_album_download_unique_entry($filename, &$used)
{
	$filename = bg_album_download_safe_name(basename((string) $filename));
	$base = pathinfo($filename, PATHINFO_FILENAME);
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	$candidate = $filename;
	$counter = 2;

	while (isset($used[strtolower($candidate)]))
	{
		$candidate = $base . '-' . $counter . ($ext !== '' ? '.' . $ext : '');
		$counter++;
	}

	$used[strtolower($candidate)] = true;

	return $candidate;
}

function bg_album_download()
{
	global $context, $txt, $user_info, $modSettings, $smcFunc;

	bg_require('bg_view_gallery');

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$is_owner = (int) $album['id_owner'] === (int) $user_info['id'];

	if (!bg_portfolio_album_visible($album))
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_is_discoverable($album, (int) $user_info['id'], $is_admin) && !$is_owner)
		fatal_lang_error('bg_album_not_found', false);

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		fatal_lang_error('no_access', false);

	if (empty($album['allow_downloads']))
		fatal_lang_error('no_access', false);

	bg_breadcrumb_album($album);
	bg_breadcrumb_add($txt['bg_download_album']);

	if (!bg_album_zip_available())
		fatal_lang_error('bg_album_zip_unavailable', false);

	if (!bg_ensure_storage())
		fatal_lang_error('bg_storage_unavailable', false);

	bg_album_download_cleanup();

	$chunk_mb = !empty($modSettings['bg_album_download_chunk_mb'])
		? max(50, min(4096, (int) $modSettings['bg_album_download_chunk_mb']))
		: 500;

	$files = bg_album_download_files($id_album);
	$chunks = bg_album_download_chunks($files, $chunk_mb * 1048576);

	if (empty($_REQUEST['part']))
	{
		$total_size = 0;

		foreach ($files as $file)
			$total_size += (int) $file['file_size'];

		$context['bg_album_download'] = array(
			'album' => $album,
			'chunk_mb' => $chunk_mb,
			'total_files' => count($files),
			'total_size' => $total_size,
			'chunks' => array(),
		);

		foreach ($chunks as $index => $chunk)
			$context['bg_album_download']['chunks'][] = array(
				'part' => $index + 1,
				'files' => count($chunk['files']),
				'size' => (int) $chunk['size'],
			);

		$context['page_title'] = $txt['bg_download_album'];
		$context['sub_template'] = 'bg_album_download';
		return;
	}

	$part = max(1, (int) $_REQUEST['part']);

	if (!isset($chunks[$part - 1]))
		fatal_lang_error('bg_album_download_part_missing', false);

	$chunk = $chunks[$part - 1];
	$download_dir = bg_storage_root() . '/downloads';

	if (!is_dir($download_dir) || !is_writable($download_dir))
		fatal_lang_error('bg_storage_unavailable', false);

	// Each request builds only the selected part.
	@set_time_limit(0);
	$token = bin2hex(random_bytes(16));
	$temp_path = $download_dir . '/album_' . $id_album . '_' . $token . '.zip';

	$zip = new ZipArchive();
	$opened = $zip->open($temp_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	if ($opened !== true)
		fatal_lang_error('bg_album_zip_failed', false);

	$used = array();

	$s3_temp_files = array();
	foreach ($chunk['files'] as $file)
	{
		$file_path = $file['path'];
		if (!empty($file['storage_backend']) && $file['storage_backend'] === 's3')
		{
			global $sourcedir;
			bg_load_storage_helpers();
			$file_path = $download_dir . '/s3_' . bin2hex(random_bytes(12)) . '.tmp';
			if (!betterstorage_s3_download_to_file(bg_s3_config(), $file['storage_key'], $file_path))
				continue;
			$s3_temp_files[] = $file_path;
		}
		if (!is_file($file_path)) continue;

		$entry = bg_album_download_unique_entry($file['filename'], $used);

		if (!$zip->addFile($file_path, $entry))
		{
			$zip->close();
			@unlink($temp_path);
			fatal_lang_error('bg_album_zip_failed', false);
		}

		// Gallery media is already compressed; storing avoids wasteful recompression.
		if (method_exists($zip, 'setCompressionName') && defined('ZipArchive::CM_STORE'))
			@$zip->setCompressionName($entry, ZipArchive::CM_STORE);
	}

	$zip_closed = $zip->close();
	foreach ($s3_temp_files as $s3_temp_file) @unlink($s3_temp_file);
	if (!$zip_closed || !is_file($temp_path))
	{
		@unlink($temp_path);
		fatal_lang_error('bg_album_zip_failed', false);
	}

	$size = (int) @filesize($temp_path);

	if ($size <= 0)
	{
		@unlink($temp_path);
		fatal_lang_error('bg_album_zip_failed', false);
	}

	// Count each file in this archive as a download only after the ZIP was built.
	foreach ($chunk['files'] as $file)
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}bg_media
			SET num_downloads = num_downloads + 1
			WHERE id_media = {int:id_media}',
			array('id_media' => (int) $file['id_media'])
		);

	$album_name = bg_album_download_safe_name($album['title']);
	$total_parts = count($chunks);
	$archive_name = $album_name
		. '-Part-' . str_pad((string) $part, 2, '0', STR_PAD_LEFT)
		. '-of-' . str_pad((string) $total_parts, 2, '0', STR_PAD_LEFT)
		. '.zip';

	while (ob_get_level())
		@ob_end_clean();

	header('Content-Type: application/zip');
	header('X-Content-Type-Options: nosniff');
	header('Content-Disposition: attachment; filename="' . addcslashes($archive_name, "\"\\") . '"');
	header('Content-Length: ' . $size);
	header('Cache-Control: private, no-store, max-age=0');

	$handle = @fopen($temp_path, 'rb');

	if ($handle === false)
	{
		@unlink($temp_path);
		fatal_lang_error('bg_album_zip_failed', false);
	}

	// Stream the completed ZIP from disk without loading it into PHP memory.
	while (!feof($handle))
	{
		$buffer = fread($handle, 1024 * 1024);

		if ($buffer === false)
			break;

		echo $buffer;

		if (function_exists('flush'))
			@flush();
	}

	fclose($handle);
	@unlink($temp_path);
	exit;
}


function bg_optional_better_tags_bridge($function, array $args = array())
{
	global $sourcedir;

	if (!is_file($sourcedir . '/BetterTags.php'))
		return false;

	require_once($sourcedir . '/BetterTags.php');

	if (!function_exists($function))
		return false;

	call_user_func_array($function, $args);
	return true;
}


function bg_album_edit()
{
	global $context, $txt, $user_info, $smcFunc, $scripturl, $modSettings;

	bg_require('bg_create_album');

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$is_edit = $id_album > 0;
	$album = $is_edit ? bg_album_load($id_album) : false;

	if (!$is_edit && !bg_portfolio_is_manager((int) $user_info['id']))
		fatal_lang_error('no_access', false);

	if ($is_edit && !$album)
		fatal_lang_error('bg_album_not_found', false);

	if ($is_edit && (int) $album['id_owner'] !== (int) $user_info['id'] && !bg_allowed('bg_moderate_gallery'))
		fatal_lang_error('no_access', false);

	if (!empty($_POST['bg_save_album']))
	{
		checkSession('post');

		$title = trim((string) ($_POST['title'] ?? ''));
		$description = trim((string) ($_POST['description'] ?? ''));
		$id_category = max(0, (int) ($_POST['id_category'] ?? 0));
		$discovery = max(0, min(2, (int) ($_POST['discovery_visibility'] ?? 2)));
		$access_mode = max(0, min(5, (int) ($_POST['access_mode'] ?? 0)));
		$password = (string) ($_POST['album_password'] ?? '');
		$allow_comments = !empty($modSettings['bg_comments_enabled']) && !empty($_POST['allow_comments']) ? 1 : 0;
		$allow_likes = !empty($modSettings['bg_likes_enabled']) && !empty($_POST['allow_likes']) ? 1 : 0;
		$allow_downloads = !empty($_POST['allow_downloads']) ? 1 : 0;

		$context['bg_form_error'] = '';

		if ($title === '')
			$context['bg_form_error'] = $txt['bg_error_title_required'];
		elseif (strlen($title) > 180)
			$context['bg_form_error'] = $txt['bg_error_title_too_long'];
		elseif (in_array($access_mode, array(3, 4), true) && !$is_edit && $password === '')
			$context['bg_form_error'] = $txt['bg_error_password_required'];

		if ($context['bg_form_error'] === '')
		{
			$password_hash = $is_edit ? $album['password_hash'] : '';

			if ($password !== '')
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
			elseif (!in_array($access_mode, array(3, 4), true))
				$password_hash = '';

			$now = time();

			if ($is_edit)
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}bg_albums
					SET id_category = {int:id_category},
						title = {string:title},
						description = {string:description},
						discovery_visibility = {int:discovery},
						access_mode = {int:access_mode},
						password_hash = {string:password_hash},
						allow_comments = {int:allow_comments},
						allow_ratings = {int:allow_likes},
						allow_downloads = {int:allow_downloads},
						updated_at = {int:updated_at}
					WHERE id_album = {int:id_album}',
					array(
						'id_category' => $id_category,
						'title' => $title,
						'description' => $description,
						'discovery' => $discovery,
						'access_mode' => $access_mode,
						'password_hash' => $password_hash,
						'allow_comments' => $allow_comments,
						'allow_likes' => $allow_likes,
						'allow_downloads' => $allow_downloads,
						'updated_at' => $now,
						'id_album' => $id_album,
					)
				);
			}
			else
			{
				$slug = bg_unique_album_slug($title);

				$smcFunc['db_insert']('insert',
					'{db_prefix}bg_albums',
					array(
						'id_owner' => 'int',
						'id_category' => 'int',
						'title' => 'string',
						'description' => 'string',
						'slug' => 'string',
						'discovery_visibility' => 'int',
						'access_mode' => 'int',
						'password_hash' => 'string',
						'allow_comments' => 'int',
						'allow_ratings' => 'int',
						'allow_downloads' => 'int',
						'created_at' => 'int',
						'updated_at' => 'int',
					),
					array(
						(int) $user_info['id'],
						$id_category,
						$title,
						$description,
						$slug,
						$discovery,
						$access_mode,
						$password_hash,
						$allow_comments,
						$allow_likes,
						$allow_downloads,
						$now,
						$now,
					),
					array('id_album')
				);

				$id_album = (int) $smcFunc['db_insert_id']('{db_prefix}bg_albums', 'id_album');
			}

			unset($GLOBALS['better_tags_gallery_album_saved_ran'][(int) $id_album]);

			if (function_exists('call_integration_hook'))
				call_integration_hook('integrate_bettergallery_album_saved', array($id_album, $is_edit));

			if (empty($GLOBALS['better_tags_gallery_album_saved_ran'][(int) $id_album]))
				bg_optional_better_tags_bridge('better_tags_gallery_album_saved', array($id_album, $is_edit));

			redirectexit('action=gallery;sa=album;album=' . $id_album);
		}
	}

	$context['bg_album_form'] = $album ?: array(
		'id_album' => 0,
		'id_category' => 0,
		'title' => '',
		'description' => '',
		'discovery_visibility' => 2,
		'access_mode' => 0,
		'password_hash' => '',
		'allow_comments' => !empty($modSettings['bg_comments_enabled']) ? 1 : 0,
		'allow_ratings' => !empty($modSettings['bg_likes_enabled']) ? 1 : 0,
		'allow_downloads' => 0,
	);

	$context['bg_categories'] = bg_load_categories();
	$context['bg_global_comments_enabled'] = !empty($modSettings['bg_comments_enabled']);
	$context['bg_global_likes_enabled'] = !empty($modSettings['bg_likes_enabled']);

	$context['bg_album_form_integrations_html'] = '';
	unset($context['better_tags_gallery_album_form_ran']);

	if (function_exists('call_integration_hook'))
		call_integration_hook(
			'integrate_bettergallery_album_form',
			array(&$context['bg_album_form_integrations_html'], $context['bg_album_form'], $is_edit)
		);

	if (empty($context['better_tags_gallery_album_form_ran']))
	{
		$args = array(&$context['bg_album_form_integrations_html'], $context['bg_album_form'], $is_edit);
		bg_optional_better_tags_bridge('better_tags_gallery_album_form', $args);
	}

	if ($is_edit)
	{
		bg_breadcrumb_album($album);
		bg_breadcrumb_add($txt['bg_edit_album']);
	}
	else
		bg_breadcrumb_add($txt['bg_create_album']);

	$context['page_title'] = $is_edit ? $txt['bg_edit_album'] : $txt['bg_create_album'];
	$context['sub_template'] = 'bg_album_edit';
}

function bg_slugify($text)
{
	$text = strtolower(trim((string) $text));
	$text = preg_replace('~[^a-z0-9]+~', '-', $text);
	$text = trim($text, '-');

	return $text !== '' ? $text : 'album';
}

function bg_unique_album_slug($title)
{
	global $smcFunc;

	$base = bg_slugify($title);
	$slug = $base;
	$i = 2;

	while (true)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_album
			FROM {db_prefix}bg_albums
			WHERE slug = {string:slug}
			LIMIT 1',
			array('slug' => $slug)
		);

		$exists = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);

		if (!$exists)
			return $slug;

		$slug = $base . '-' . $i++;
	}
}

/* -------------------------------------------------------
 * Password unlock
 * ----------------------------------------------------- */

function bg_album_unlock()
{
	global $user_info, $txt;

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	if (!in_array((int) $album['access_mode'], array(3, 4), true) || empty($album['password_hash']))
		redirectexit('action=gallery;sa=album;album=' . $id_album);

	if (!empty($_POST['bg_unlock_album']))
	{
		checkSession('post');

		$password = (string) ($_POST['album_password'] ?? '');

		if (password_verify($password, $album['password_hash']))
		{
			if (empty($_SESSION['bg_album_unlocks']) || !is_array($_SESSION['bg_album_unlocks']))
				$_SESSION['bg_album_unlocks'] = array();

			$_SESSION['bg_album_unlocks'][$id_album] = time();
			redirectexit('action=gallery;sa=album;album=' . $id_album);
		}

		$_SESSION['bg_unlock_error'] = $txt['bg_password_incorrect'];
	}

	redirectexit('action=gallery;sa=album;album=' . $id_album);
}

/* -------------------------------------------------------
 * Request access
 * ----------------------------------------------------- */

function bg_album_request_access()
{
	global $user_info, $smcFunc;

	bg_require('bg_request_access');

	if (empty($user_info['id']))
		fatal_lang_error('no_access', false);

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	if (!in_array((int) $album['access_mode'], array(2, 4, 5), true))
		redirectexit('action=gallery;sa=album;album=' . $id_album);

	checkSession('post');

	$message = trim((string) ($_POST['request_message'] ?? ''));

	// Avoid duplicate pending requests.
	$request = $smcFunc['db_query']('', '
		SELECT id_request
		FROM {db_prefix}bg_access_requests
		WHERE id_album = {int:id_album}
			AND id_member = {int:id_member}
			AND status = {string:pending}
		LIMIT 1',
		array(
			'id_album' => $id_album,
			'id_member' => (int) $user_info['id'],
			'pending' => 'pending',
		)
	);

	$exists = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if (!$exists)
		$smcFunc['db_insert']('insert',
			'{db_prefix}bg_access_requests',
			array(
				'id_album' => 'int',
				'id_member' => 'int',
				'message' => 'string',
				'status' => 'string',
				'requested_at' => 'int',
			),
			array(
				$id_album,
				(int) $user_info['id'],
				$message,
				'pending',
				time(),
			),
			array('id_request')
		);

	redirectexit('action=gallery;sa=album;album=' . $id_album);
}

function bg_album_requests()
{
	global $context, $user_info, $smcFunc, $txt;

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	if ((int) $album['id_owner'] !== (int) $user_info['id'] && !bg_allowed('bg_moderate_gallery'))
		fatal_lang_error('no_access', false);

	bg_breadcrumb_album($album);
	bg_breadcrumb_add($txt['bg_access_requests']);

	$context['bg_album'] = $album;
	$context['bg_requests'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT r.id_request, r.id_member, r.message, r.status, r.requested_at,
			m.real_name
		FROM {db_prefix}bg_access_requests AS r
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = r.id_member)
		WHERE r.id_album = {int:id_album}
		ORDER BY r.status = {string:pending} DESC, r.requested_at DESC',
		array(
			'id_album' => $id_album,
			'pending' => 'pending',
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['bg_requests'][] = $row;

	$smcFunc['db_free_result']($request);

	$context['page_title'] = sprintf($txt['bg_access_requests_for'], $album['title']);
	$context['sub_template'] = 'bg_album_requests';
}

function bg_album_decide_request()
{
	global $user_info, $smcFunc;

	checkSession('post');

	$id_request = !empty($_POST['request']) ? (int) $_POST['request'] : 0;
	$decision = !empty($_POST['decision']) && $_POST['decision'] === 'approve' ? 'approved' : 'denied';

	$request = $smcFunc['db_query']('', '
		SELECT r.id_request, r.id_album, r.id_member, a.id_owner
		FROM {db_prefix}bg_access_requests AS r
			INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = r.id_album)
		WHERE r.id_request = {int:id_request}
		LIMIT 1',
		array('id_request' => $id_request)
	);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!$row)
		fatal_lang_error('no_access', false);

	if ((int) $row['id_owner'] !== (int) $user_info['id'] && !bg_allowed('bg_moderate_gallery'))
		fatal_lang_error('no_access', false);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_access_requests
		SET status = {string:status},
			decided_at = {int:decided_at},
			decided_by = {int:decided_by}
		WHERE id_request = {int:id_request}',
		array(
			'status' => $decision,
			'decided_at' => time(),
			'decided_by' => (int) $user_info['id'],
			'id_request' => $id_request,
		)
	);

	if ($decision === 'approved')
		$smcFunc['db_insert']('replace',
			'{db_prefix}bg_album_access',
			array(
				'id_album' => 'int',
				'id_member' => 'int',
				'role' => 'string',
				'granted_by' => 'int',
				'granted_at' => 'int',
				'revoked_at' => 'int',
			),
			array(
				(int) $row['id_album'],
				(int) $row['id_member'],
				'viewer',
				(int) $user_info['id'],
				time(),
				0,
			),
			array('id_album', 'id_member')
		);

	redirectexit('action=gallery;sa=requests;album=' . (int) $row['id_album']);
}


/* -------------------------------------------------------
 * Media storage / image upload pipeline
 * ----------------------------------------------------- */

function bg_betterstorage_available()
{
    global $sourcedir;

    $storage_file = $sourcedir . '/BetterStorage.php';
    if (!is_file($storage_file))
        return false;

    require_once($storage_file);

    return function_exists('betterstorage_config')
        && function_exists('betterstorage_source_config')
        && function_exists('betterstorage_register_object')
        && function_exists('betterstorage_unregister_object');
}

function bg_load_storage_helpers()
{
    global $sourcedir;

    if (bg_betterstorage_available())
        return true;

    require_once($sourcedir . '/BetterGalleryStorage.php');
    return false;
}

function bg_storage_backend()
{
    global $modSettings;
    if (bg_betterstorage_available())
    {
        $config = betterstorage_config();
        return !empty($config['backend']) && $config['backend'] === 's3' ? 's3' : 'local';
    }
    // Better Gallery no longer owns a cloud backend. Without Better Storage, new uploads stay local.
    return 'local';
}

function bg_s3_config()
{
    global $modSettings;
    if (bg_betterstorage_available())
        return betterstorage_source_config('gallery');
    return array(
        'region' => (string) ($modSettings['bg_s3_region'] ?? 'us-east-1'),
        'bucket' => (string) ($modSettings['bg_s3_bucket'] ?? ''),
        'prefix' => (string) ($modSettings['bg_s3_prefix'] ?? 'better-gallery'),
        'access_key' => (string) ($modSettings['bg_s3_access_key'] ?? ''),
        'secret_key' => (string) ($modSettings['bg_s3_secret_key'] ?? ''),
        'endpoint' => (string) ($modSettings['bg_s3_endpoint'] ?? ''),
        'private' => !isset($modSettings['bg_s3_private']) || !empty($modSettings['bg_s3_private']),
    );
}

function bg_store_local_file_to_backend($local_path, $relative_key, $mime)
{
    if (bg_storage_backend() !== 's3')
        return array('ok'=>true, 'backend'=>'local', 'key'=>$relative_key);

    bg_load_storage_helpers();
    $result = betterstorage_s3_put_file(bg_s3_config(), $relative_key, $local_path, $mime ?: 'application/octet-stream');
    if (empty($result['ok']))
        return array('ok'=>false, 'backend'=>'s3', 'key'=>$relative_key, 'error'=>(string)($result['error'] ?? 'Amazon S3 upload failed.'));

    @unlink($local_path);
    return array('ok'=>true, 'backend'=>'s3', 'key'=>$relative_key);
}

function bg_s3_media_url($relative_key, $download_name = '')
{
    bg_load_storage_helpers();
    return betterstorage_s3_presign_get(bg_s3_config(), $relative_key, 300, $download_name);
}

function bg_betterstorage_register($backend, $key, $size, $mime, $owner_id)
{
    if (!bg_betterstorage_available() || $key === '')
        return;
    betterstorage_register_object('gallery', $backend, $key, max(0, (int) $size), (string) $mime, (int) $owner_id);
}

function bg_betterstorage_unregister($backend, $key)
{
    if (!bg_betterstorage_available() || $key === '')
        return;
    betterstorage_unregister_object('gallery', $backend, $key);
}

function bg_betterstorage_sync_registry()
{
    global $modSettings, $smcFunc;
    if (!bg_betterstorage_available())
        return;

    if (!empty($modSettings['bg_betterstorage_registry_synced_v1']))
    {
        $check = $smcFunc['db_query']('', 'SELECT COUNT(*) AS total FROM {db_prefix}better_storage_objects WHERE source={string:source}', array('source'=>'gallery'));
        $row = $smcFunc['db_fetch_assoc']($check);
        $smcFunc['db_free_result']($check);
        if (!empty($row['total']))
        {
            if (empty($modSettings['betterstorage_integration_gallery'])) updateSettings(array('betterstorage_integration_gallery'=>1));
            return;
        }
    }

    $config = bg_s3_config();
    $request = $smcFunc['db_query']('', 'SELECT id_owner, storage_backend, storage_key, thumb_key, poster_key, file_size, mime_type FROM {db_prefix}bg_media', array());
    while ($row = $smcFunc['db_fetch_assoc']($request))
    {
        $backend = !empty($row['storage_backend']) && $row['storage_backend'] === 's3' ? 's3' : 'local';
        $objects = array();
        if (!empty($row['storage_key'])) $objects[$row['storage_key']] = array((int)$row['file_size'], (string)$row['mime_type']);
        if (!empty($row['thumb_key'])) $objects[$row['thumb_key']] = array(-1, 'image/jpeg');
        if (!empty($row['poster_key'])) $objects[$row['poster_key']] = array(-1, 'image/jpeg');
        foreach ($objects as $key => $meta)
        {
            $size = (int) $meta[0];
            if ($size < 0)
            {
                if ($backend === 's3' && function_exists('betterstorage_s3_object_size'))
                    $size = betterstorage_s3_object_size($config, $key);
                else
                {
                    $path = bg_storage_root() . '/' . ltrim($key, '/');
                    $size = is_file($path) ? (int) @filesize($path) : 0;
                }
            }
            bg_betterstorage_register($backend, $key, max(0, $size), $meta[1], (int)$row['id_owner']);
        }
    }
    $smcFunc['db_free_result']($request);
    updateSettings(array('bg_betterstorage_registry_synced_v1' => 1, 'betterstorage_integration_gallery' => 1));
}

function bg_storage_root()
{
	global $boarddir, $modSettings;

	if (!empty($modSettings['bg_storage_path']))
		return rtrim($modSettings['bg_storage_path'], '/\\');

	return $boarddir . '/gallery_storage';
}

function bg_ensure_storage()
{
	$root = bg_storage_root();
	$dirs = array($root, $root . '/originals', $root . '/thumbs', $root . '/videos', $root . '/posters', $root . '/files', $root . '/downloads');

	foreach ($dirs as $dir)
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir))
			return false;

	// Apache protection for installations where this directory sits under webroot.
	$htaccess = $root . '/.htaccess';
	if (!is_file($htaccess))
		@file_put_contents($htaccess, "Require all denied\nDeny from all\n");

	$index = $root . '/index.php';
	if (!is_file($index))
		@file_put_contents($index, "<?php http_response_code(403); exit;");

	return is_writable($root) && is_writable($root . '/originals') && is_writable($root . '/thumbs');
}

function bg_media_allowed_types()
{
	return array(
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/webp' => 'webp',
	);
}

function bg_video_allowed_types()
{
	return array(
		'video/mp4' => 'mp4',
		'video/webm' => 'webm',
		'video/quicktime' => 'mov',
		'video/x-matroska' => 'mkv',
	);
}

function bg_video_browser_native_types()
{
	return array('video/mp4', 'video/webm');
}

function bg_run_command($command, &$exit_code = null)
{
	$exit_code = 1;

	if (function_exists('exec'))
	{
		$output = array();
		@exec($command . ' 2>&1', $output, $exit_code);
		return implode("\n", $output);
	}

	if (function_exists('shell_exec'))
	{
		$output = @shell_exec($command . ' 2>&1');
		$exit_code = $output === null ? 1 : 0;
		return (string) $output;
	}

	return '';
}

function bg_probe_video($filename)
{
	$ffprobe = bg_find_binary('ffprobe');

	if (!$ffprobe)
		return false;

	$command = escapeshellarg($ffprobe)
		. ' -v error -select_streams v:0'
		. ' -show_entries stream=width,height'
		. ' -show_entries format=duration'
		. ' -of json '
		. escapeshellarg($filename);

	$exit = 1;
	$output = bg_run_command($command, $exit);

	if ($exit !== 0 || $output === '')
		return false;

	$data = json_decode($output, true);

	if (!is_array($data))
		return false;

	$stream = !empty($data['streams'][0]) ? $data['streams'][0] : array();
	$duration = !empty($data['format']['duration']) ? (float) $data['format']['duration'] : 0;

	return array(
		'width' => !empty($stream['width']) ? (int) $stream['width'] : 0,
		'height' => !empty($stream['height']) ? (int) $stream['height'] : 0,
		'duration_ms' => max(0, (int) round($duration * 1000)),
	);
}

function bg_transcode_video_to_mp4($source, $dest)
{
	$ffmpeg = bg_find_binary('ffmpeg');

	if (!$ffmpeg)
		return false;

	$command = escapeshellarg($ffmpeg)
		. ' -y -i ' . escapeshellarg($source)
		. ' -map 0:v:0 -map 0:a?'
		. ' -c:v libx264 -preset medium -crf 23'
		. ' -pix_fmt yuv420p -movflags +faststart'
		. ' -c:a aac -b:a 160k '
		. escapeshellarg($dest);

	$exit = 1;
	bg_run_command($command, $exit);

	return $exit === 0 && is_file($dest) && filesize($dest) > 0;
}

function bg_generate_video_poster($source, $dest, $duration_ms = 0)
{
	$ffmpeg = bg_find_binary('ffmpeg');

	if (!$ffmpeg)
		return false;

	$seconds = $duration_ms > 0 ? min(5, max(0.25, ($duration_ms / 1000) * 0.10)) : 1;
	$seek = number_format($seconds, 2, '.', '');

	$command = escapeshellarg($ffmpeg)
		. ' -y -ss ' . escapeshellarg($seek)
		. ' -i ' . escapeshellarg($source)
		. ' -frames:v 1 -vf ' . escapeshellarg('scale=960:-2:force_original_aspect_ratio=decrease')
		. ' -q:v 3 '
		. escapeshellarg($dest);

	$exit = 1;
	bg_run_command($command, $exit);

	return $exit === 0 && is_file($dest) && filesize($dest) > 0;
}

function bg_detect_mime($filename)
{
	if (function_exists('finfo_open'))
	{
		$finfo = @finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo)
		{
			$mime = @finfo_file($finfo, $filename);
			finfo_close($finfo);
			if (!empty($mime))
				return strtolower($mime);
		}
	}

	if (function_exists('getimagesize'))
	{
		$info = @getimagesize($filename);
		if (!empty($info['mime']))
			return strtolower($info['mime']);
	}

	return '';
}

function bg_read_jpeg_orientation($filename)
{
	$fp = @fopen($filename, 'rb');
	if (!$fp)
		return 1;

	if (fread($fp, 2) !== "\xFF\xD8")
	{
		fclose($fp);
		return 1;
	}

	while (!feof($fp))
	{
		$b = fread($fp, 1);
		if ($b !== "\xFF")
			continue;

		do {
			$mb = fread($fp, 1);
			if ($mb === '' || $mb === false)
			{
				fclose($fp);
				return 1;
			}
		} while ($mb === "\xFF");

		$marker = ord($mb);
		if ($marker === 0xDA || $marker === 0xD9)
			break;
		if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7))
			continue;

		$len = fread($fp, 2);
		if (strlen($len) !== 2)
			break;

		$seglen = unpack('n', $len)[1];
		if ($seglen < 2)
			break;

		$data = fread($fp, $seglen - 2);
		if ($marker !== 0xE1 || strlen($data) < 14 || substr($data, 0, 6) !== "Exif\x00\x00")
			continue;

		$tiff = substr($data, 6);
		$order = substr($tiff, 0, 2);

		if ($order === 'II')
		{
			$u16 = function ($s) { return unpack('v', $s)[1]; };
			$u32 = function ($s) { return unpack('V', $s)[1]; };
		}
		elseif ($order === 'MM')
		{
			$u16 = function ($s) { return unpack('n', $s)[1]; };
			$u32 = function ($s) { return unpack('N', $s)[1]; };
		}
		else
			continue;

		if (strlen($tiff) < 8 || $u16(substr($tiff, 2, 2)) !== 42)
			continue;

		$ifd = $u32(substr($tiff, 4, 4));
		if ($ifd < 8 || $ifd + 2 > strlen($tiff))
			continue;

		$count = $u16(substr($tiff, $ifd, 2));
		for ($i = 0; $i < $count; $i++)
		{
			$entry = $ifd + 2 + ($i * 12);
			if ($entry + 12 > strlen($tiff))
				break;

			if ($u16(substr($tiff, $entry, 2)) !== 0x0112)
				continue;

			$value = $u16(substr($tiff, $entry + 8, 2));
			fclose($fp);
			return ($value >= 1 && $value <= 8) ? $value : 1;
		}
	}

	fclose($fp);
	return 1;
}

function bg_image_load($filename, $mime)
{
	switch ($mime)
	{
		case 'image/jpeg':
			return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($filename) : false;
		case 'image/png':
			return function_exists('imagecreatefrompng') ? @imagecreatefrompng($filename) : false;
		case 'image/gif':
			return function_exists('imagecreatefromgif') ? @imagecreatefromgif($filename) : false;
		case 'image/webp':
			return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filename) : false;
	}
	return false;
}

function bg_image_save($image, $filename, $mime)
{
	switch ($mime)
	{
		case 'image/jpeg':
			return @imagejpeg($image, $filename, 92);
		case 'image/png':
			return @imagepng($image, $filename, 6);
		case 'image/gif':
			return @imagegif($image, $filename);
		case 'image/webp':
			return function_exists('imagewebp') ? @imagewebp($image, $filename, 88) : false;
	}
	return false;
}

function bg_normalize_jpeg_orientation($filename)
{
	if (!function_exists('imagecreatefromjpeg') || !function_exists('imagerotate'))
		return true;

	$orientation = bg_read_jpeg_orientation($filename);
	if ($orientation < 2 || $orientation > 8)
		return true;

	$image = @imagecreatefromjpeg($filename);
	if (!$image)
		return false;

	$hflip = defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1;
	$vflip = defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2;

	switch ($orientation)
	{
		case 2:
			if (function_exists('imageflip')) imageflip($image, $hflip);
			break;
		case 3:
			$image = imagerotate($image, 180, 0);
			break;
		case 4:
			if (function_exists('imageflip')) imageflip($image, $vflip);
			break;
		case 5:
			if (function_exists('imageflip')) imageflip($image, $hflip);
			$image = imagerotate($image, 270, 0);
			break;
		case 6:
			$image = imagerotate($image, 270, 0);
			break;
		case 7:
			if (function_exists('imageflip')) imageflip($image, $hflip);
			$image = imagerotate($image, 90, 0);
			break;
		case 8:
			$image = imagerotate($image, 90, 0);
			break;
	}

	if (!$image)
		return false;

	$result = @imagejpeg($image, $filename, 92);
	imagedestroy($image);
	return $result;
}

function bg_create_thumbnail($source, $dest, $mime, $max_w, $max_h)
{
	$info = @getimagesize($source);
	if (empty($info[0]) || empty($info[1]))
		return false;

	$src_w = (int) $info[0];
	$src_h = (int) $info[1];

	$ratio = min($max_w / $src_w, $max_h / $src_h, 1);
	$dst_w = max(1, (int) round($src_w * $ratio));
	$dst_h = max(1, (int) round($src_h * $ratio));

	$image = bg_image_load($source, $mime);
	if (!$image)
		return false;

	$thumb = imagecreatetruecolor($dst_w, $dst_h);
	if (!$thumb)
	{
		imagedestroy($image);
		return false;
	}

	if (in_array($mime, array('image/png', 'image/gif', 'image/webp'), true))
	{
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		$transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
		imagefilledrectangle($thumb, 0, 0, $dst_w, $dst_h, $transparent);
	}

	imagecopyresampled($thumb, $image, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
	$result = bg_image_save($thumb, $dest, $mime);

	imagedestroy($thumb);
	imagedestroy($image);

	return $result;
}

function bg_upload_json_response($success, $message, $extra = array())
{
	while (ob_get_level())
		@ob_end_clean();

	header('Content-Type: application/json; charset=UTF-8');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: no-store, no-cache, must-revalidate');

	echo json_encode(array_merge(array(
		'success' => (bool) $success,
		'message' => (string) $message,
	), $extra));

	exit;
}

function bg_upload_error_message($code)
{
	switch ((int) $code)
	{
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return 'The file exceeds the server upload limit.';
		case UPLOAD_ERR_PARTIAL:
			return 'The file upload was interrupted before it completed.';
		case UPLOAD_ERR_NO_FILE:
			return 'No file was received.';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'The server temporary upload directory is unavailable.';
		case UPLOAD_ERR_CANT_WRITE:
			return 'The server could not write the uploaded file.';
		case UPLOAD_ERR_EXTENSION:
			return 'A PHP extension stopped the upload.';
		default:
			return 'The upload could not be completed.';
	}
}

function bg_media_upload()
{
	global $user_info, $smcFunc, $modSettings;

	$is_ajax = !empty($_REQUEST['ajax']);

	bg_require('bg_upload_media');
	checkSession('post');

	$id_album = !empty($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
	{
		if ($is_ajax)
			bg_upload_json_response(false, 'The album could not be found.');
		fatal_lang_error('bg_album_not_found', false);
	}

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if ((int) $album['id_owner'] !== (int) $user_info['id'] && !$is_admin)
	{
		if ($is_ajax)
			bg_upload_json_response(false, 'You do not have permission to upload to this album.');
		fatal_lang_error('no_access', false);
	}

	if (!bg_portfolio_is_manager((int) $album['id_owner']))
	{
		if ($is_ajax)
			bg_upload_json_response(false, 'Portfolio Mode only allows uploads by the selected portfolio owner.');
		fatal_lang_error('no_access', false);
	}

	if (!bg_ensure_storage())
	{
		if ($is_ajax)
			bg_upload_json_response(false, 'Better Gallery storage is unavailable or not writable.');
		fatal_lang_error('bg_storage_unavailable', false);
	}

	if (!empty($_FILES['bg_media_files']) && is_array($_FILES['bg_media_files']['name']))
		$files = $_FILES['bg_media_files'];
	elseif (!empty($_FILES['bg_images']) && is_array($_FILES['bg_images']['name']))
		$files = $_FILES['bg_images'];
	else
	{
		if ($is_ajax)
			bg_upload_json_response(false, 'No media file was received.');
		redirectexit('action=gallery;sa=album;album=' . $id_album);
	}

	$max_files = max(1, (int) $modSettings['bg_max_upload_files']);
	$max_image_bytes = (!empty($modSettings['bg_max_image_kb']) ? max(1, (int) $modSettings['bg_max_image_kb']) : 10240) * 1024;
	$max_video_bytes = (!empty($modSettings['bg_max_video_mb']) ? max(1, (int) $modSettings['bg_max_video_mb']) : 250) * 1024 * 1024;
	$max_file_bytes = (!empty($modSettings['bg_max_file_mb']) ? max(1, (int) $modSettings['bg_max_file_mb']) : 250) * 1024 * 1024;
	$allowed_extensions = array_flip(bg_allowed_extensions());
	$thumb_w = !empty($modSettings['bg_thumb_width']) ? max(50, (int) $modSettings['bg_thumb_width']) : 480;
	$thumb_h = !empty($modSettings['bg_thumb_height']) ? max(50, (int) $modSettings['bg_thumb_height']) : 360;
	$storage_quota_bytes = bg_member_storage_quota_bytes();
	$images_enabled = !isset($modSettings['bg_images_enabled']) || !empty($modSettings['bg_images_enabled']);
	$videos_enabled = !isset($modSettings['bg_videos_enabled']) || !empty($modSettings['bg_videos_enabled']);
	$image_types = bg_media_allowed_types();
	$video_types = bg_video_allowed_types();
	$native_video = bg_video_browser_native_types();
	$root = bg_storage_root();
	$created = 0;
	$errors = array();
	$created_media = array();

	$count = min(count($files['name']), $max_files);

	for ($i = 0; $i < $count; $i++)
	{
		$original_name = basename((string) $files['name'][$i]);

		if ((int) $files['error'][$i] !== UPLOAD_ERR_OK)
		{
			$errors[] = $original_name . ': ' . bg_upload_error_message($files['error'][$i]);
			continue;
		}

		$tmp = $files['tmp_name'][$i];
		$size = (int) $files['size'][$i];
		$original_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

		if ($original_ext === '' || !isset($allowed_extensions[$original_ext]))
		{
			$errors[] = $original_name . ': This file type is not supported by Better Gallery. Images and video only.';
			continue;
		}

		if (!is_uploaded_file($tmp) || $size <= 0)
		{
			$errors[] = $original_name . ': The uploaded file could not be verified.';
			continue;
		}

		if ($storage_quota_bytes > 0)
		{
			$current_used = bg_member_storage_used_bytes((int) $user_info['id']);

			if ($current_used >= $storage_quota_bytes || $size > ($storage_quota_bytes - $current_used))
			{
				$errors[] = $original_name . ': Your Better Gallery storage quota does not have enough remaining space for this file.';
				continue;
			}
		}

		$mime = bg_detect_mime($tmp);

		if (!bg_extension_mime_valid($original_ext, $mime))
		{
			$errors[] = $original_name . ': The detected file type does not match the enabled extension.';
			continue;
		}

		$file_profile = bg_extension_profile($original_ext);

		// IMAGE PIPELINE
		if ($file_profile['type'] === 'image' && $images_enabled && isset($image_types[$mime]))
		{
			if ($size > $max_image_bytes)
			{
				$errors[] = $original_name . ': The image exceeds the Better Gallery image-size limit.';
				continue;
			}

			$info = @getimagesize($tmp);

			if (empty($info[0]) || empty($info[1]))
			{
				$errors[] = $original_name . ': The image dimensions could not be read.';
				continue;
			}

			$key = bin2hex(random_bytes(20));
			$ext = $image_types[$mime];
			$original_rel = 'originals/' . $key . '.' . $ext;
			$thumb_rel = 'thumbs/' . $key . '.' . $ext;
			$original_path = $root . '/' . $original_rel;
			$thumb_path = $root . '/' . $thumb_rel;

			if (!@move_uploaded_file($tmp, $original_path))
			{
				$errors[] = $original_name . ': Better Gallery could not move the uploaded image into storage.';
				continue;
			}

			if ($mime === 'image/jpeg')
				bg_normalize_jpeg_orientation($original_path);

			clearstatcache(true, $original_path);
			$info = @getimagesize($original_path);

			if (empty($info[0]) || empty($info[1]))
			{
				@unlink($original_path);
				$errors[] = $original_name . ': The stored image could not be validated.';
				continue;
			}

			$stored_size = (int) @filesize($original_path);

			if ($storage_quota_bytes > 0)
			{
				$current_used = bg_member_storage_used_bytes((int) $user_info['id']);

				if ($current_used >= $storage_quota_bytes || $stored_size > ($storage_quota_bytes - $current_used))
				{
					@unlink($original_path);
					$errors[] = $original_name . ': The stored image would exceed your Better Gallery storage quota.';
					continue;
				}
			}

			if (!bg_create_thumbnail($original_path, $thumb_path, $mime, $thumb_w, $thumb_h))
			{
				@unlink($original_path);
				$errors[] = $original_name . ': Thumbnail generation failed.';
				continue;
			}

			$media_backend = bg_storage_backend();
			if ($media_backend === 's3')
			{
				$original_upload = bg_store_local_file_to_backend($original_path, $original_rel, $mime);
				$thumb_upload = bg_store_local_file_to_backend($thumb_path, $thumb_rel, $mime);
				if (empty($original_upload['ok']) || empty($thumb_upload['ok']))
				{
					@unlink($original_path); @unlink($thumb_path);
					$errors[] = $original_name . ': S3 upload failed: ' . (!empty($original_upload['error']) ? $original_upload['error'] : ($thumb_upload['error'] ?? 'Unknown S3 error.'));
					continue;
				}
			}

			$smcFunc['db_insert']('insert',
				'{db_prefix}bg_media',
				array(
					'id_album' => 'int', 'id_owner' => 'int', 'media_type' => 'string',
					'title' => 'string', 'description' => 'string', 'filename' => 'string',
					'storage_key' => 'string', 'storage_backend' => 'string', 'mime_type' => 'string', 'file_size' => 'int',
					'width' => 'int', 'height' => 'int', 'duration_ms' => 'int',
					'poster_key' => 'string', 'thumb_key' => 'string', 'status' => 'string',
					'approved' => 'int', 'created_at' => 'int', 'updated_at' => 'int',
				),
				array(
					$id_album, (int) $user_info['id'], 'image',
					pathinfo($original_name, PATHINFO_FILENAME), '', $original_name,
					$original_rel, $media_backend, $mime, $stored_size,
					(int) $info[0], (int) $info[1], 0,
					'', $thumb_rel, 'ready',
					1, time(), time(),
				),
				array('id_media')
			);

			$new_media_id = (int) $smcFunc['db_insert_id']('{db_prefix}bg_media', 'id_media');

			if ($new_media_id > 0)
			{
				bg_betterstorage_register($media_backend, $original_rel, $stored_size, $mime, (int)$user_info['id']);
				$thumb_size = $media_backend === 'local' && is_file($thumb_path) ? (int)@filesize($thumb_path) : ($media_backend === 's3' && function_exists('betterstorage_s3_object_size') ? max(0, betterstorage_s3_object_size(bg_s3_config(), $thumb_rel)) : 0);
				bg_betterstorage_register($media_backend, $thumb_rel, $thumb_size, $mime, (int)$user_info['id']);
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}bg_albums
					SET id_cover_media = {int:id_media}
					WHERE id_album = {int:id_album}
						AND id_cover_media = {int:no_cover}',
					array(
						'id_media' => $new_media_id,
						'id_album' => $id_album,
						'no_cover' => 0,
					)
				);

				$created_media[] = $new_media_id;
			}

			$created++;
			continue;
		}

		// VIDEO PIPELINE
		if ($file_profile['type'] === 'video' && (!$videos_enabled || !isset($video_types[$mime])))
		{
			$errors[] = $original_name . ': This video type is not supported by Better Gallery.';
			continue;
		}

		if ($file_profile['type'] !== 'video')
		{
			if ($size > $max_file_bytes)
			{
				$errors[] = $original_name . ': The file exceeds the Better Gallery general-file size limit.';
				continue;
			}

			$key = bin2hex(random_bytes(20));
			$stored_rel = 'files/' . $key . '.' . $original_ext;
			$stored_path = $root . '/' . $stored_rel;

			if (!@move_uploaded_file($tmp, $stored_path))
			{
				$errors[] = $original_name . ': Better Gallery could not move the uploaded file into storage.';
				continue;
			}

			$stored_size = (int) @filesize($stored_path);

			if ($storage_quota_bytes > 0)
			{
				$current_used = bg_member_storage_used_bytes((int) $user_info['id']);

				if ($current_used >= $storage_quota_bytes || $stored_size > ($storage_quota_bytes - $current_used))
				{
					@unlink($stored_path);
					$errors[] = $original_name . ': The stored file would exceed your Better Gallery storage quota.';
					continue;
				}
			}

			$media_backend = bg_storage_backend();
			if ($media_backend === 's3')
			{
				$object_upload = bg_store_local_file_to_backend($stored_path, $stored_rel, $mime);
				if (empty($object_upload['ok']))
				{
					@unlink($stored_path);
					$errors[] = $original_name . ': S3 upload failed: ' . ($object_upload['error'] ?? 'Unknown S3 error.');
					continue;
				}
			}

			$smcFunc['db_insert']('insert',
				'{db_prefix}bg_media',
				array(
					'id_album' => 'int', 'id_owner' => 'int', 'media_type' => 'string',
					'title' => 'string', 'description' => 'string', 'filename' => 'string',
					'storage_key' => 'string', 'storage_backend' => 'string', 'mime_type' => 'string', 'file_size' => 'int',
					'width' => 'int', 'height' => 'int', 'duration_ms' => 'int',
					'poster_key' => 'string', 'thumb_key' => 'string', 'status' => 'string',
					'approved' => 'int', 'created_at' => 'int', 'updated_at' => 'int',
				),
				array(
					$id_album, (int) $user_info['id'], (string) $file_profile['type'],
					pathinfo($original_name, PATHINFO_FILENAME), '', $original_name,
					$stored_rel, $media_backend, $mime, $stored_size,
					0, 0, 0,
					'', '', 'ready',
					1, time(), time(),
				),
				array('id_media')
			);

			$new_media_id = (int) $smcFunc['db_insert_id']('{db_prefix}bg_media', 'id_media');

			if ($new_media_id > 0)
			{
				bg_betterstorage_register($media_backend, $stored_rel, $stored_size, $mime, (int)$user_info['id']);
				$created_media[] = $new_media_id;
			}

			$created++;
			continue;
		}

		if ($size > $max_video_bytes)
		{
			$errors[] = $original_name . ': The video exceeds the Better Gallery video-size limit.';
			continue;
		}

		$key = bin2hex(random_bytes(20));
		$source_ext = $video_types[$mime];
		$temp_rel = 'videos/' . $key . '.source.' . $source_ext;
		$temp_path = $root . '/' . $temp_rel;

		if (!@move_uploaded_file($tmp, $temp_path))
		{
			$errors[] = $original_name . ': Better Gallery could not move the uploaded video into storage.';
			continue;
		}

		$stored_rel = '';
		$stored_path = '';
		$stored_mime = $mime;

		if (in_array($mime, $native_video, true))
		{
			$stored_rel = 'videos/' . $key . '.' . $source_ext;
			$stored_path = $root . '/' . $stored_rel;

			if (!@rename($temp_path, $stored_path))
			{
				@unlink($temp_path);
				$errors[] = $original_name . ': Better Gallery could not finalize the uploaded video.';
				continue;
			}
		}
		else
		{
			$stored_rel = 'videos/' . $key . '.mp4';
			$stored_path = $root . '/' . $stored_rel;

			if (!bg_transcode_video_to_mp4($temp_path, $stored_path))
			{
				@unlink($temp_path);
				@unlink($stored_path);
				$errors[] = $original_name . ': FFmpeg could not normalize this video to MP4.';
				continue;
			}

			@unlink($temp_path);
			$stored_mime = 'video/mp4';
		}

		$stored_size = (int) @filesize($stored_path);

		if ($storage_quota_bytes > 0)
		{
			$current_used = bg_member_storage_used_bytes((int) $user_info['id']);

			if ($current_used >= $storage_quota_bytes || $stored_size > ($storage_quota_bytes - $current_used))
			{
				@unlink($stored_path);
				$errors[] = $original_name . ': The stored video would exceed your Better Gallery storage quota.';
				continue;
			}
		}

		$probe = bg_probe_video($stored_path);

		if (!$probe)
			$probe = array('width' => 0, 'height' => 0, 'duration_ms' => 0);

		$poster_rel = '';
		$poster_path = $root . '/posters/' . $key . '.jpg';

		if (bg_generate_video_poster($stored_path, $poster_path, (int) $probe['duration_ms']))
			$poster_rel = 'posters/' . $key . '.jpg';

		$media_backend = bg_storage_backend();
		if ($media_backend === 's3')
		{
			$video_upload = bg_store_local_file_to_backend($stored_path, $stored_rel, $stored_mime);
			$poster_upload = array('ok'=>true);
			if ($poster_rel !== '' && is_file($poster_path))
				$poster_upload = bg_store_local_file_to_backend($poster_path, $poster_rel, 'image/jpeg');
			if (empty($video_upload['ok']) || empty($poster_upload['ok']))
			{
				@unlink($stored_path); @unlink($poster_path);
				$errors[] = $original_name . ': S3 upload failed: ' . (!empty($video_upload['error']) ? $video_upload['error'] : ($poster_upload['error'] ?? 'Unknown S3 error.'));
				continue;
			}
		}

		$smcFunc['db_insert']('insert',
			'{db_prefix}bg_media',
			array(
				'id_album' => 'int', 'id_owner' => 'int', 'media_type' => 'string',
				'title' => 'string', 'description' => 'string', 'filename' => 'string',
				'storage_key' => 'string', 'storage_backend' => 'string', 'mime_type' => 'string', 'file_size' => 'int',
				'width' => 'int', 'height' => 'int', 'duration_ms' => 'int',
				'poster_key' => 'string', 'thumb_key' => 'string', 'status' => 'string',
				'approved' => 'int', 'created_at' => 'int', 'updated_at' => 'int',
			),
			array(
				$id_album, (int) $user_info['id'], 'video',
				pathinfo($original_name, PATHINFO_FILENAME), '', $original_name,
				$stored_rel, $media_backend, $stored_mime, $stored_size,
				(int) $probe['width'], (int) $probe['height'], (int) $probe['duration_ms'],
				$poster_rel, $poster_rel, 'ready',
				1, time(), time(),
			),
			array('id_media')
		);

		$new_media_id = (int) $smcFunc['db_insert_id']('{db_prefix}bg_media', 'id_media');

		if ($new_media_id > 0)
		{
			bg_betterstorage_register($media_backend, $stored_rel, $stored_size, $stored_mime, (int)$user_info['id']);
			if ($poster_rel !== '')
			{
				$poster_size = $media_backend === 'local' && is_file($poster_path) ? (int)@filesize($poster_path) : ($media_backend === 's3' && function_exists('betterstorage_s3_object_size') ? max(0, betterstorage_s3_object_size(bg_s3_config(), $poster_rel)) : 0);
				bg_betterstorage_register($media_backend, $poster_rel, $poster_size, 'image/jpeg', (int)$user_info['id']);
			}
			$created_media[] = $new_media_id;
		}

		$created++;
	}

	if ($created > 0)
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}bg_albums
			SET num_items = num_items + {int:created},
				updated_at = {int:updated}
			WHERE id_album = {int:id_album}',
			array(
				'created' => $created,
				'updated' => time(),
				'id_album' => $id_album,
			)
		);

	if ($is_ajax)
	{
		if ($created > 0)
		{
			$quota_status = bg_member_storage_quota_status((int) $user_info['id']);

			bg_upload_json_response(true, 'Upload complete.', array(
				'created' => $created,
				'media_ids' => $created_media,
				'errors' => $errors,
				'storage' => array(
					'used_bytes' => $quota_status['used_bytes'],
					'quota_bytes' => $quota_status['quota_bytes'],
					'remaining_bytes' => $quota_status['remaining_bytes'],
					'unlimited' => $quota_status['unlimited'],
				),
			));
		}

		bg_upload_json_response(false, !empty($errors) ? implode(' ', $errors) : 'The media file was not accepted.');
	}

	redirectexit('action=gallery;sa=album;album=' . $id_album);
}

function bg_media_load($id_media)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT m.*, a.id_owner AS album_owner, a.discovery_visibility, a.access_mode,
			a.password_hash, a.approved AS album_approved, a.allow_comments, a.allow_ratings,
			a.allow_downloads
		FROM {db_prefix}bg_media AS m
			INNER JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
		WHERE m.id_media = {int:id_media}
			AND m.status = {string:ready}
			AND m.approved = {int:approved}
		LIMIT 1',
		array(
			'id_media' => (int) $id_media,
			'ready' => 'ready',
			'approved' => 1,
		)
	);

	$media = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return $media ?: false;
}

function bg_media_assert_access($media, $json = false)
{
	global $user_info;

	if (!$media)
	{
		if ($json)
			bg_upload_json_response(false, 'The media item could not be found.');
		fatal_lang_error('bg_media_not_found', false);
	}

	$album = array(
		'id_album' => (int) $media['id_album'],
		'id_owner' => (int) $media['album_owner'],
		'discovery_visibility' => (int) $media['discovery_visibility'],
		'access_mode' => (int) $media['access_mode'],
		'password_hash' => $media['password_hash'],
	);

	if (!bg_portfolio_album_visible($album))
	{
		if ($json)
			bg_upload_json_response(false, 'The media item could not be found.');
		fatal_lang_error('bg_media_not_found', false);
	}

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
	{
		if ($json)
			bg_upload_json_response(false, 'You do not have access to this media item.');
		fatal_lang_error('no_access', false);
	}

	return true;
}


function bg_better_reactions_available()
{
    global $sourcedir, $modSettings;

    if (empty($modSettings['better_reactions_enabled']))
        return false;

    if (!function_exists('BetterReactions_GetObjectSummary'))
    {
        $file = $sourcedir . '/BetterReactions.php';
        if (!is_file($file))
            return false;
        require_once($file);
    }

    return function_exists('BetterReactions_GetObjectSummary')
        && function_exists('BetterReactions_SetObjectReaction')
        && function_exists('BetterReactions_ClearObjectReaction')
        && function_exists('BetterReactions_GetClientRegistry');
}

function bg_reaction_summary_with_native_like($content_type, $content_id, $native_count = 0, $native_mine = false)
{
    $summary = array('counts' => array(), 'mine' => '');
    if (bg_better_reactions_available())
    {
        $all = BetterReactions_GetObjectSummary($content_type, array((int) $content_id));
        if (isset($all[(int) $content_id]))
            $summary = $all[(int) $content_id];
    }
    if ((int) $native_count > 0)
        $summary['counts']['like'] = (int) $native_count;
    if ($native_mine)
        $summary['mine'] = 'like';
    return $summary;
}

function bg_media_reaction_payload($id_media, $like_count, $user_liked)
{
    if (!bg_better_reactions_available())
        return array('enabled' => false);
    $registry = BetterReactions_GetClientRegistry();
    return array(
        'enabled' => true,
        'types' => $registry['types'],
        'display' => $registry['display'],
        'summary' => bg_reaction_summary_with_native_like('media_item', (int) $id_media, (int) $like_count, (bool) $user_liked),
    );
}

function bg_album_reaction_payload($id_album, $like_count, $user_liked)
{
    if (!bg_better_reactions_available())
        return array('enabled' => false);

    $registry = BetterReactions_GetClientRegistry();

    return array(
        'enabled' => true,
        'types' => $registry['types'],
        'display' => $registry['display'],
        'summary' => bg_reaction_summary_with_native_like('media_album', (int) $id_album, (int) $like_count, (bool) $user_liked),
    );
}

function bg_comment_reaction_summaries($comments)
{
    if (!bg_better_reactions_available() || empty($comments))
        return array();
    $ids = array();
    foreach ($comments as $comment)
        $ids[] = (int) $comment['id_comment'];
    return BetterReactions_GetObjectSummary('media_comment', $ids);
}

function bg_media_interaction_flags($media)
{
	global $modSettings, $user_info;

	$comments = !empty($modSettings['bg_comments_enabled']) && !empty($media['allow_comments']);
	$likes = !empty($modSettings['bg_likes_enabled']) && !empty($media['allow_ratings']);

	return array(
		'comments_enabled' => $comments,
		'likes_enabled' => $likes,
		'can_comment' => $comments && !empty($user_info['id']),
		'can_like' => $likes && !empty($user_info['id']),
	);
}

function bg_media_info()
{
	global $smcFunc, $user_info, $scripturl;

	$id_media = !empty($_REQUEST['media']) ? (int) $_REQUEST['media'] : 0;
	$media = bg_media_load($id_media);
	bg_media_assert_access($media, true);

	if (bg_record_unique_view('media', $id_media, (int) $media['id_owner']))
		$media['num_views'] = (int) $media['num_views'] + 1;

	$flags = bg_media_interaction_flags($media);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS likes
		FROM {db_prefix}bg_media_likes
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$like_count = !empty($row['likes']) ? (int) $row['likes'] : 0;
	$smcFunc['db_free_result']($request);

	$user_favorited = false;
	if (!empty($user_info['id']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}bg_media_favorites
			WHERE id_media = {int:id_media}
				AND id_member = {int:id_member}
			LIMIT 1',
			array(
				'id_media' => $id_media,
				'id_member' => (int) $user_info['id'],
			)
		);
		$user_favorited = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);
	}

	$user_liked = false;
	if (!empty($user_info['id']) && $flags['likes_enabled'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}bg_media_likes
			WHERE id_media = {int:id_media}
				AND id_member = {int:id_member}
			LIMIT 1',
			array(
				'id_media' => $id_media,
				'id_member' => (int) $user_info['id'],
			)
		);
		$user_liked = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);
	}

	$comments = array();
	if ($flags['comments_enabled'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.id_member, c.body, c.created_at,
				m.real_name
			FROM {db_prefix}bg_media_comments AS c
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = c.id_member)
			WHERE c.id_media = {int:id_media}
			ORDER BY c.created_at ASC, c.id_comment ASC',
			array('id_media' => $id_media)
		);

		$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
		while ($comment = $smcFunc['db_fetch_assoc']($request))
		{
			$comment['can_delete'] =
				$is_admin
				|| (int) $media['album_owner'] === (int) $user_info['id']
				|| (int) $comment['id_member'] === (int) $user_info['id'];
			$comment['can_report'] =
				!empty($user_info['id'])
				&& (int) $comment['id_member'] !== (int) $user_info['id'];
			$comments[] = $comment;
		}
		$smcFunc['db_free_result']($request);
	}

	$comment_reactions = bg_comment_reaction_summaries($comments);
	if (!empty($comment_reactions))
	{
		foreach ($comments as &$comment)
			$comment['reactions'] = isset($comment_reactions[(int) $comment['id_comment']]) ? $comment_reactions[(int) $comment['id_comment']] : array('counts'=>array(), 'mine'=>'');
		unset($comment);
	}

	$integration_html = '';
	unset($GLOBALS['better_tags_gallery_media_info_ran'][(int) $id_media]);

	if (function_exists('call_integration_hook'))
		call_integration_hook(
			'integrate_bettergallery_media_info',
			array(&$integration_html, $media)
		);

	if (empty($GLOBALS['better_tags_gallery_media_info_ran'][(int) $id_media]))
	{
		$args = array(&$integration_html, $media);
		bg_optional_better_tags_bridge('better_tags_gallery_media_info', $args);
	}

	bg_upload_json_response(true, 'OK', array(
		'media' => array(
			'id_media' => (int) $media['id_media'],
			'media_type' => $media['media_type'],
			'title' => $media['title'],
			'description' => $media['description'],
			'integration_html' => $integration_html,
			'mime_type' => $media['mime_type'],
			'width' => (int) $media['width'],
			'height' => (int) $media['height'],
			'view_count' => (int) $media['num_views'],
			'downloads_enabled' => !empty($media['allow_downloads']),
			'file_url' => $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'],
			'download_url' => !empty($media['allow_downloads'])
				? $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';download=1'
				: '',
			'poster_url' => !empty($media['poster_key'])
				? $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';thumb=1'
				: '',
		),
		'interactions' => array_merge($flags, array(
			'like_count' => $like_count,
			'user_liked' => $user_liked,
			'better_reactions' => bg_media_reaction_payload($id_media, $like_count, $user_liked),
			'can_favorite' => !empty($user_info['id']),
			'user_favorited' => $user_favorited,
			'can_report_media' => !empty($user_info['id']) && (int) $media['id_owner'] !== (int) $user_info['id'],
		)),
		'comments' => $comments,
	));
}

function bg_media_like()
{
	global $smcFunc, $user_info;

	checkSession('post');

	if (empty($user_info['id']))
		bg_upload_json_response(false, 'You must be logged in to like media.');

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$media = bg_media_load($id_media);
	bg_media_assert_access($media, true);

	$flags = bg_media_interaction_flags($media);
	if (!$flags['likes_enabled'])
		bg_upload_json_response(false, 'Likes are disabled for this album.');

	// When Better Reactions is present, a direct legacy Like click must still
	// replace any custom reaction instead of creating a double state.
	if (bg_better_reactions_available())
		BetterReactions_ClearObjectReaction('media_item', $id_media, (int) $user_info['id']);

	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}bg_media_likes
		WHERE id_media = {int:id_media}
			AND id_member = {int:id_member}
		LIMIT 1',
		array(
			'id_media' => $id_media,
			'id_member' => (int) $user_info['id'],
		)
	);

	$liked = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($liked)
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_likes
			WHERE id_media = {int:id_media}
				AND id_member = {int:id_member}',
			array(
				'id_media' => $id_media,
				'id_member' => (int) $user_info['id'],
			)
		);
		$liked = false;
	}
	else
	{
		$smcFunc['db_insert']('ignore',
			'{db_prefix}bg_media_likes',
			array(
				'id_media' => 'int',
				'id_member' => 'int',
				'created_at' => 'int',
			),
			array(
				$id_media,
				(int) $user_info['id'],
				time(),
			),
			array('id_media', 'id_member')
		);
		$liked = true;
	}

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS likes
		FROM {db_prefix}bg_media_likes
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$count = !empty($row['likes']) ? (int) $row['likes'] : 0;
	$smcFunc['db_free_result']($request);

	bg_upload_json_response(true, 'OK', array(
		'liked' => $liked,
		'like_count' => $count,
	));
}


function bg_media_reaction()
{
    global $smcFunc, $user_info;
    checkSession('post');
    if (empty($user_info['id']))
        bg_upload_json_response(false, 'You must be logged in to react to media.');
    if (!bg_better_reactions_available())
        bg_upload_json_response(false, 'Better Reactions is not available.');

    $id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
    $reaction = isset($_POST['reaction']) ? strtolower(trim((string) $_POST['reaction'])) : '';
    $media = bg_media_load($id_media);
    bg_media_assert_access($media, true);
    $flags = bg_media_interaction_flags($media);
    if (!$flags['likes_enabled'])
        bg_upload_json_response(false, 'Reactions are disabled for this album.');

    if ($reaction === 'like')
    {
        BetterReactions_ClearObjectReaction('media_item', $id_media, (int) $user_info['id']);
        $request = $smcFunc['db_query']('', 'SELECT id_member FROM {db_prefix}bg_media_likes WHERE id_media={int:id_media} AND id_member={int:id_member} LIMIT 1', array('id_media'=>$id_media,'id_member'=>(int)$user_info['id']));
        $liked = $smcFunc['db_num_rows']($request) > 0; $smcFunc['db_free_result']($request);
        if ($liked)
            $smcFunc['db_query']('', 'DELETE FROM {db_prefix}bg_media_likes WHERE id_media={int:id_media} AND id_member={int:id_member}', array('id_media'=>$id_media,'id_member'=>(int)$user_info['id']));
        else
            $smcFunc['db_insert']('ignore','{db_prefix}bg_media_likes',array('id_media'=>'int','id_member'=>'int','created_at'=>'int'),array($id_media,(int)$user_info['id'],time()),array('id_media','id_member'));
        $liked = !$liked;
    }
    else
    {
        // A custom reaction replaces any native Better Gallery Like.
        $smcFunc['db_query']('', 'DELETE FROM {db_prefix}bg_media_likes WHERE id_media={int:id_media} AND id_member={int:id_member}', array('id_media'=>$id_media,'id_member'=>(int)$user_info['id']));
        $result = BetterReactions_SetObjectReaction('media_item', $id_media, $reaction, (int) $user_info['id']);
        if (empty($result['ok']))
            bg_upload_json_response(false, 'That reaction is not available.');
        $liked = false;
    }

    $request = $smcFunc['db_query']('', 'SELECT COUNT(*) AS likes FROM {db_prefix}bg_media_likes WHERE id_media={int:id_media}', array('id_media'=>$id_media));
    $row=$smcFunc['db_fetch_assoc']($request); $smcFunc['db_free_result']($request);
    $like_count=!empty($row['likes'])?(int)$row['likes']:0;
    bg_upload_json_response(true,'OK',array('better_reactions'=>bg_media_reaction_payload($id_media,$like_count,$liked),'liked'=>$liked,'like_count'=>$like_count));
}

function bg_media_reactors()
{
    global $smcFunc, $scripturl, $modSettings;
    $id_media=!empty($_REQUEST['media'])?(int)$_REQUEST['media']:0;
    $reaction=isset($_REQUEST['reaction'])?strtolower(trim((string)$_REQUEST['reaction'])):'';
    $media=bg_media_load($id_media); bg_media_assert_access($media,true);
    if ($reaction === 'like')
    {
        $people=array();
        $request=$smcFunc['db_query']('', 'SELECT l.id_member, COALESCE(NULLIF(m.real_name,{string:empty}),NULLIF(m.member_name,{string:empty}),{string:former}) AS reactor_name FROM {db_prefix}bg_media_likes AS l LEFT JOIN {db_prefix}members AS m ON (m.id_member=l.id_member) WHERE l.id_media={int:id_media} ORDER BY l.created_at ASC LIMIT 50', array('empty'=>'','former'=>'Former member','id_media'=>$id_media));
        while($row=$smcFunc['db_fetch_assoc']($request)) $people[]=array('id'=>(int)$row['id_member'],'name'=>(string)$row['reactor_name'],'url'=>(!isset($modSettings['better_reactions_profile_links'])||!empty($modSettings['better_reactions_profile_links']))?$scripturl.'?action=profile;u='.(int)$row['id_member']:'');
        $smcFunc['db_free_result']($request);
        $request=$smcFunc['db_query']('', 'SELECT COUNT(*) AS total FROM {db_prefix}bg_media_likes WHERE id_media={int:id_media}',array('id_media'=>$id_media)); $row=$smcFunc['db_fetch_assoc']($request); $smcFunc['db_free_result']($request);
        bg_upload_json_response(true,'OK',array('people'=>$people,'total'=>(int)($row['total']??0)));
    }
    if (!bg_better_reactions_available()) bg_upload_json_response(false,'Better Reactions is not available.');
    bg_upload_json_response(true,'OK',BetterReactions_GetObjectReactors('media_item',$id_media,$reaction,50));
}

function bg_comment_reaction()
{
    global $smcFunc, $user_info;
    checkSession('post');
    if (empty($user_info['id'])) bg_upload_json_response(false,'You must be logged in to react to comments.');
    if (!bg_better_reactions_available()) bg_upload_json_response(false,'Better Reactions is not available.');
    $id_comment=!empty($_POST['comment'])?(int)$_POST['comment']:0;
    $reaction=isset($_POST['reaction'])?strtolower(trim((string)$_POST['reaction'])):'';
        $request=$smcFunc['db_query']('', 'SELECT c.id_comment,c.id_media FROM {db_prefix}bg_media_comments AS c WHERE c.id_comment={int:id_comment} LIMIT 1',array('id_comment'=>$id_comment));
    $comment=$smcFunc['db_fetch_assoc']($request); $smcFunc['db_free_result']($request);
    if (empty($comment)) bg_upload_json_response(false,'Comment not found.');
    $media=bg_media_load((int)$comment['id_media']); bg_media_assert_access($media,true);
    $result=BetterReactions_SetObjectReaction('media_comment',$id_comment,$reaction,(int)$user_info['id'], true);
    if (empty($result['ok'])) bg_upload_json_response(false,'That reaction is not available.');
    bg_upload_json_response(true,'OK',array('summary'=>$result['summary']));
}

function bg_comment_reactors()
{
    global $smcFunc;
    $id_comment=!empty($_REQUEST['comment'])?(int)$_REQUEST['comment']:0;
    $reaction=isset($_REQUEST['reaction'])?strtolower(trim((string)$_REQUEST['reaction'])):'';
    if (!bg_better_reactions_available()) bg_upload_json_response(false,'Better Reactions is not available.');
    $request=$smcFunc['db_query']('', 'SELECT id_media FROM {db_prefix}bg_media_comments WHERE id_comment={int:id_comment} LIMIT 1',array('id_comment'=>$id_comment)); $row=$smcFunc['db_fetch_assoc']($request); $smcFunc['db_free_result']($request);
    if (empty($row)) bg_upload_json_response(false,'Comment not found.');
    $media=bg_media_load((int)$row['id_media']); bg_media_assert_access($media,true);
    bg_upload_json_response(true,'OK',BetterReactions_GetObjectReactors('media_comment',$id_comment,$reaction,50));
}

function bg_media_favorite()
{
	global $smcFunc, $user_info;

	checkSession('post');

	if (empty($user_info['id']))
		bg_upload_json_response(false, 'You must be logged in to favorite media.');

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$media = bg_media_load($id_media);
	bg_media_assert_access($media, true);

	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}bg_media_favorites
		WHERE id_media = {int:id_media}
			AND id_member = {int:id_member}
		LIMIT 1',
		array(
			'id_media' => $id_media,
			'id_member' => (int) $user_info['id'],
		)
	);

	$favorited = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($favorited)
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}bg_media_favorites
			WHERE id_media = {int:id_media}
				AND id_member = {int:id_member}',
			array(
				'id_media' => $id_media,
				'id_member' => (int) $user_info['id'],
			)
		);
		$favorited = false;
	}
	else
	{
		$smcFunc['db_insert']('ignore',
			'{db_prefix}bg_media_favorites',
			array(
				'id_media' => 'int',
				'id_member' => 'int',
				'created_at' => 'int',
			),
			array(
				$id_media,
				(int) $user_info['id'],
				time(),
			),
			array('id_media', 'id_member')
		);
		$favorited = true;
	}

	bg_upload_json_response(true, 'OK', array('favorited' => $favorited));
}

function bg_media_report()
{
	global $smcFunc, $user_info;

	checkSession('post');

	if (empty($user_info['id']))
		bg_upload_json_response(false, 'You must be logged in to report content.');

	$target_type = !empty($_POST['target_type']) && $_POST['target_type'] === 'comment'
		? 'comment'
		: 'media';

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$id_comment = !empty($_POST['comment']) ? (int) $_POST['comment'] : 0;
	$media = bg_media_load($id_media);
	bg_media_assert_access($media, true);

	if ($target_type === 'comment')
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_comment, id_member
			FROM {db_prefix}bg_media_comments
			WHERE id_comment = {int:id_comment}
				AND id_media = {int:id_media}
			LIMIT 1',
			array(
				'id_comment' => $id_comment,
				'id_media' => $id_media,
			)
		);
		$comment = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$comment)
			bg_upload_json_response(false, 'Comment not found.');

		if ((int) $comment['id_member'] === (int) $user_info['id'])
			bg_upload_json_response(false, 'You cannot report your own comment.');
	}
	elseif ((int) $media['id_owner'] === (int) $user_info['id'])
		bg_upload_json_response(false, 'You cannot report your own media.');

	$allowed_reasons = array('spam', 'abuse', 'copyright', 'inappropriate', 'other');
	$reason = isset($_POST['reason']) ? (string) $_POST['reason'] : 'other';

	if (!in_array($reason, $allowed_reasons, true))
		$reason = 'other';

	$note = trim((string) ($_POST['note'] ?? ''));
	if (strlen($note) > 2000)
		$note = substr($note, 0, 2000);

	// Prevent repeated open reports by the same member against the same target.
	$request = $smcFunc['db_query']('', '
		SELECT id_report
		FROM {db_prefix}bg_reports
		WHERE id_reporter = {int:id_reporter}
			AND target_type = {string:target_type}
			AND id_media = {int:id_media}
			AND id_comment = {int:id_comment}
			AND status = {string:open_status}
		LIMIT 1',
		array(
			'id_reporter' => (int) $user_info['id'],
			'target_type' => $target_type,
			'id_media' => $id_media,
			'id_comment' => $target_type === 'comment' ? $id_comment : 0,
			'open_status' => 'open',
		)
	);
	$exists = $smcFunc['db_num_rows']($request) > 0;
	$smcFunc['db_free_result']($request);

	if ($exists)
		bg_upload_json_response(false, 'You already have an open report for this item.');

	$smcFunc['db_insert']('insert',
		'{db_prefix}bg_reports',
		array(
			'target_type' => 'string',
			'id_media' => 'int',
			'id_comment' => 'int',
			'id_reporter' => 'int',
			'reason' => 'string',
			'note' => 'string',
			'status' => 'string',
			'created_at' => 'int',
			'decided_at' => 'int',
			'decided_by' => 'int',
		),
		array(
			$target_type,
			$id_media,
			$target_type === 'comment' ? $id_comment : 0,
			(int) $user_info['id'],
			$reason,
			$note,
			'open',
			time(),
			0,
			0,
		),
		array('id_report')
	);

	bg_upload_json_response(true, 'Report submitted.');
}

function bg_media_comment()
{
	global $smcFunc, $user_info;

	checkSession('post');

	if (empty($user_info['id']))
		bg_upload_json_response(false, 'You must be logged in to comment.');

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$media = bg_media_load($id_media);
	bg_media_assert_access($media, true);

	$flags = bg_media_interaction_flags($media);
	if (!$flags['comments_enabled'])
		bg_upload_json_response(false, 'Comments are disabled for this album.');

	$body = trim((string) ($_POST['body'] ?? ''));
	if ($body === '')
		bg_upload_json_response(false, 'Comment cannot be empty.');

	if (strlen($body) > 2000)
		$body = substr($body, 0, 2000);

	$smcFunc['db_insert']('insert',
		'{db_prefix}bg_media_comments',
		array(
			'id_media' => 'int',
			'id_member' => 'int',
			'body' => 'string',
			'created_at' => 'int',
			'edited_at' => 'int',
		),
		array(
			$id_media,
			(int) $user_info['id'],
			$body,
			time(),
			0,
		),
		array('id_comment')
	);

	bg_upload_json_response(true, 'Comment added.');
}

function bg_media_comment_delete()
{
	global $smcFunc, $user_info;

	checkSession('post');

	$id_comment = !empty($_POST['comment']) ? (int) $_POST['comment'] : 0;

	$request = $smcFunc['db_query']('', '
		SELECT c.id_comment, c.id_media, c.id_member
		FROM {db_prefix}bg_media_comments AS c
		WHERE c.id_comment = {int:id_comment}
		LIMIT 1',
		array('id_comment' => $id_comment)
	);
	$comment = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!$comment)
		bg_upload_json_response(false, 'Comment not found.');

	$media = bg_media_load((int) $comment['id_media']);
	bg_media_assert_access($media, true);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');
	$can_delete =
		$is_admin
		|| (int) $media['album_owner'] === (int) $user_info['id']
		|| (int) $comment['id_member'] === (int) $user_info['id'];

	if (!$can_delete)
		bg_upload_json_response(false, 'You cannot delete this comment.');

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_reports
		WHERE target_type = {string:target_type}
			AND id_comment = {int:id_comment}',
		array(
			'target_type' => 'comment',
			'id_comment' => $id_comment,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_comments
		WHERE id_comment = {int:id_comment}',
		array('id_comment' => $id_comment)
	);

	bg_upload_json_response(true, 'Comment deleted.');
}

function bg_media_require_manage($media)
{
	global $user_info;

	if (!$media)
		fatal_lang_error('bg_media_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if ((int) $media['album_owner'] !== (int) $user_info['id'] && !$is_admin)
		fatal_lang_error('no_access', false);

	return true;
}

function bg_media_edit()
{
	global $context, $txt, $smcFunc;

	$id_media = !empty($_REQUEST['media']) ? (int) $_REQUEST['media'] : 0;
	$media = bg_media_load($id_media);

	bg_media_require_manage($media);

	$breadcrumb_album = bg_album_load((int) $media['id_album']);

	if (!empty($_POST['bg_save_media']))
	{
		checkSession('post');

		$title = trim((string) ($_POST['title'] ?? ''));
		$description = trim((string) ($_POST['description'] ?? ''));

		if ($title === '')
			$title = pathinfo($media['filename'], PATHINFO_FILENAME);

		if (strlen($title) > 200)
			$title = substr($title, 0, 200);

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}bg_media
			SET title = {string:title},
				description = {string:description},
				updated_at = {int:updated}
			WHERE id_media = {int:id_media}',
			array(
				'title' => $title,
				'description' => $description,
				'updated' => time(),
				'id_media' => $id_media,
			)
		);

		unset($GLOBALS['better_tags_gallery_media_saved_ran'][(int) $id_media]);

		if (function_exists('call_integration_hook'))
			call_integration_hook('integrate_bettergallery_media_saved', array($id_media));

		if (empty($GLOBALS['better_tags_gallery_media_saved_ran'][(int) $id_media]))
			bg_optional_better_tags_bridge('better_tags_gallery_media_saved', array($id_media));

		redirectexit('action=gallery;sa=album;album=' . (int) $media['id_album']);
	}

	bg_breadcrumb_album($breadcrumb_album);
	bg_breadcrumb_add($txt['bg_edit_media']);

	$context['bg_media_edit'] = $media;
	$context['bg_media_form_integrations_html'] = '';
	unset($context['better_tags_gallery_media_form_ran']);

	if (function_exists('call_integration_hook'))
		call_integration_hook(
			'integrate_bettergallery_media_form',
			array(&$context['bg_media_form_integrations_html'], $context['bg_media_edit'])
		);

	if (empty($context['better_tags_gallery_media_form_ran']))
	{
		$args = array(&$context['bg_media_form_integrations_html'], $context['bg_media_edit']);
		bg_optional_better_tags_bridge('better_tags_gallery_media_form', $args);
	}

	$context['page_title'] = $txt['bg_edit_media'];
	$context['sub_template'] = 'bg_media_edit';
}

function bg_media_delete_files($media)
{
	global $sourcedir;
	if (!$media || !is_array($media))
		return;

	$paths = array_filter(array_unique(array($media['storage_key'] ?? '', $media['thumb_key'] ?? '', $media['poster_key'] ?? '')));
	$backend = !empty($media['storage_backend']) ? (string) $media['storage_backend'] : 'local';

	if ($backend === 's3')
	{
		bg_load_storage_helpers();
		$config = bg_s3_config();
		foreach ($paths as $relative)
		{
			$result = betterstorage_s3_delete($config, $relative);
			if (empty($result['ok']) && function_exists('log_error'))
				log_error('[Better Gallery S3] Failed to delete object ' . $relative . ': ' . (string) ($result['error'] ?? 'Unknown S3 error.'));
			else
				bg_betterstorage_unregister('s3', $relative);
		}
		return;
	}

	foreach ($paths as $relative)
	{
		$relative = str_replace('\\', '/', (string) $relative);
		if ($relative === '' || strpos($relative, '..') !== false || substr($relative, 0, 1) === '/')
			continue;
		$path = bg_storage_root() . '/' . $relative;
		if (is_file($path)) @unlink($path);
		bg_betterstorage_unregister('local', $relative);
	}
}

function bg_media_delete()
{
	global $smcFunc;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$media = bg_media_load($id_media);

	bg_media_require_manage($media);

	bg_media_delete_files($media);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_tags
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_comments
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_likes
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_favorites
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_reports
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_unique_views
		WHERE target_type = {string:target_type}
			AND id_target = {int:id_media}',
		array(
			'target_type' => 'media',
			'id_media' => $id_media,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media
		WHERE id_media = {int:id_media}',
		array('id_media' => $id_media)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_cover_media = CASE WHEN id_cover_media = {int:id_media} THEN 0 ELSE id_cover_media END,
			num_items = GREATEST(0, num_items - 1),
			updated_at = {int:updated}
		WHERE id_album = {int:id_album}',
		array(
			'id_media' => $id_media,
			'updated' => time(),
			'id_album' => (int) $media['id_album'],
		)
	);

	unset($GLOBALS['better_tags_gallery_media_deleted_ran']);

	if (function_exists('call_integration_hook'))
		call_integration_hook('integrate_bettergallery_media_deleted', array(array($id_media)));

	if (empty($GLOBALS['better_tags_gallery_media_deleted_ran']))
		bg_optional_better_tags_bridge('better_tags_gallery_media_deleted', array(array($id_media)));

	redirectexit('action=gallery;sa=album;album=' . (int) $media['id_album']);
}

function bg_media_bulk_move()
{
	global $smcFunc, $user_info;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$source_album_id = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$destination_album_id = !empty($_POST['destination_album']) ? (int) $_POST['destination_album'] : 0;

	if ($source_album_id <= 0 || $destination_album_id <= 0 || $source_album_id === $destination_album_id)
		redirectexit('action=gallery;sa=album;album=' . $source_album_id . ';moveerror=destination');

	$source_album = bg_album_load($source_album_id);
	$destination_album = bg_album_load($destination_album_id);

	if (!$source_album || !$destination_album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if ((int) $source_album['id_owner'] !== (int) $user_info['id'] && !$is_admin)
		fatal_lang_error('no_access', false);

	// Media ownership is intentionally kept stable. A move is therefore only
	// valid between albums owned by the same member, even for administrators.
	if ((int) $destination_album['id_owner'] !== (int) $source_album['id_owner'])
		fatal_lang_error('no_access', false);

	$posted = isset($_POST['media']) && is_array($_POST['media']) ? $_POST['media'] : array();
	$ids = array();

	foreach ($posted as $id_media)
	{
		$id_media = (int) $id_media;

		if ($id_media > 0)
			$ids[$id_media] = $id_media;
	}

	$ids = array_values($ids);

	if (empty($ids))
		redirectexit('action=gallery;sa=album;album=' . $source_album_id);

	// Revalidate all selected IDs against the source album. IDs posted from
	// another album are ignored rather than trusted.
	$request = $smcFunc['db_query']('', '
		SELECT id_media, media_type, thumb_key
		FROM {db_prefix}bg_media
		WHERE id_album = {int:source_album}
			AND id_media IN ({array_int:media_ids})',
		array(
			'source_album' => $source_album_id,
			'media_ids' => $ids,
		)
	);

	$valid_ids = array();
	$cover_candidate = 0;

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$id_media = (int) $row['id_media'];
		$valid_ids[] = $id_media;

		if (
			$cover_candidate === 0
			&& $row['media_type'] === 'image'
			&& !empty($row['thumb_key'])
			&& is_file(bg_storage_root() . '/' . bg_maintenance_normalize_key($row['thumb_key']))
		)
			$cover_candidate = $id_media;
	}

	$smcFunc['db_free_result']($request);

	if (empty($valid_ids))
		redirectexit('action=gallery;sa=album;album=' . $source_album_id);

	$moved_count = count($valid_ids);
	$source_cover_moved = in_array((int) $source_album['id_cover_media'], $valid_ids, true);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_media
		SET id_album = {int:destination_album},
			updated_at = {int:updated}
		WHERE id_album = {int:source_album}
			AND id_media IN ({array_int:media_ids})',
		array(
			'destination_album' => $destination_album_id,
			'updated' => time(),
			'source_album' => $source_album_id,
			'media_ids' => $valid_ids,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_cover_media = CASE
				WHEN {int:cover_moved} = {int:yes} THEN {int:no_cover}
				ELSE id_cover_media
			END,
			num_items = GREATEST(0, num_items - {int:moved_count}),
			updated_at = {int:updated}
		WHERE id_album = {int:source_album}',
		array(
			'cover_moved' => $source_cover_moved ? 1 : 0,
			'yes' => 1,
			'no_cover' => 0,
			'moved_count' => $moved_count,
			'updated' => time(),
			'source_album' => $source_album_id,
		)
	);

	// Preserve the destination's existing cover. If it has none, use the first
	// moved image with a valid thumbnail as a sensible automatic cover.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_cover_media = CASE
				WHEN id_cover_media = {int:no_cover} AND {int:cover_candidate} > {int:no_cover}
					THEN {int:cover_candidate}
				ELSE id_cover_media
			END,
			num_items = num_items + {int:moved_count},
			updated_at = {int:updated}
		WHERE id_album = {int:destination_album}',
		array(
			'no_cover' => 0,
			'cover_candidate' => $cover_candidate,
			'moved_count' => $moved_count,
			'updated' => time(),
			'destination_album' => $destination_album_id,
		)
	);

	redirectexit(
		'action=gallery;sa=album;album=' . $source_album_id
		. ';bulkmoved=' . $moved_count
		. ';movedto=' . $destination_album_id
	);
}

function bg_media_bulk_delete()
{
	global $smcFunc, $user_info;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$id_album = !empty($_POST['album']) ? (int) $_POST['album'] : 0;
	$album = bg_album_load($id_album);

	if (!$album)
		fatal_lang_error('bg_album_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if ((int) $album['id_owner'] !== (int) $user_info['id'] && !$is_admin)
		fatal_lang_error('no_access', false);

	$posted = isset($_POST['media']) && is_array($_POST['media']) ? $_POST['media'] : array();
	$ids = array();

	foreach ($posted as $id_media)
	{
		$id_media = (int) $id_media;

		if ($id_media > 0)
			$ids[$id_media] = $id_media;
	}

	$ids = array_values($ids);

	if (empty($ids))
		redirectexit('action=gallery;sa=album;album=' . $id_album);

	// Validate ownership by album in one query. Posted IDs from another album are ignored.
	$request = $smcFunc['db_query']('', '
		SELECT id_media, id_album, storage_backend, storage_key, thumb_key, poster_key
		FROM {db_prefix}bg_media
		WHERE id_album = {int:id_album}
			AND id_media IN ({array_int:media_ids})',
		array(
			'id_album' => $id_album,
			'media_ids' => $ids,
		)
	);

	$media_rows = array();
	$valid_ids = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$media_rows[] = $row;
		$valid_ids[] = (int) $row['id_media'];
	}

	$smcFunc['db_free_result']($request);

	if (empty($valid_ids))
		redirectexit('action=gallery;sa=album;album=' . $id_album);

	foreach ($media_rows as $media)
		bg_media_delete_files($media);

	$params = array('media_ids' => $valid_ids);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_tags
		WHERE id_media IN ({array_int:media_ids})',
		$params
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_comments
		WHERE id_media IN ({array_int:media_ids})',
		$params
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_likes
		WHERE id_media IN ({array_int:media_ids})',
		$params
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media_favorites
		WHERE id_media IN ({array_int:media_ids})',
		$params
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_reports
		WHERE id_media IN ({array_int:media_ids})',
		$params
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_unique_views
		WHERE target_type = {string:target_type}
			AND id_target IN ({array_int:media_ids})',
		array(
			'target_type' => 'media',
			'media_ids' => $valid_ids,
		)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_media
		WHERE id_album = {int:id_album}
			AND id_media IN ({array_int:media_ids})',
		array(
			'id_album' => $id_album,
			'media_ids' => $valid_ids,
		)
	);

	$deleted_count = count($valid_ids);
	$cover_deleted = in_array((int) $album['id_cover_media'], $valid_ids, true);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_cover_media = CASE
				WHEN {int:cover_deleted} = {int:yes} THEN {int:no_cover}
				ELSE id_cover_media
			END,
			num_items = GREATEST(0, num_items - {int:deleted_count}),
			updated_at = {int:updated}
		WHERE id_album = {int:id_album}',
		array(
			'cover_deleted' => $cover_deleted ? 1 : 0,
			'yes' => 1,
			'no_cover' => 0,
			'deleted_count' => $deleted_count,
			'updated' => time(),
			'id_album' => $id_album,
		)
	);

	unset($GLOBALS['better_tags_gallery_media_deleted_ran']);

	if (function_exists('call_integration_hook'))
		call_integration_hook('integrate_bettergallery_media_deleted', array($valid_ids));

	if (empty($GLOBALS['better_tags_gallery_media_deleted_ran']))
		bg_optional_better_tags_bridge('better_tags_gallery_media_deleted', array($valid_ids));

	redirectexit('action=gallery;sa=album;album=' . $id_album . ';bulkdeleted=' . $deleted_count);
}

function bg_album_set_cover()
{
	global $smcFunc;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$id_media = !empty($_POST['media']) ? (int) $_POST['media'] : 0;
	$media = bg_media_load($id_media);

	bg_media_require_manage($media);

	$return_start = isset($_POST['start']) ? max(0, (int) $_POST['start']) : 0;
	$return_sort = isset($_POST['sort']) ? (string) $_POST['sort'] : 'newest';
	$allowed_sorts = array('newest', 'oldest', 'title_asc', 'title_desc');

	if (!in_array($return_sort, $allowed_sorts, true))
		$return_sort = 'newest';

	if (empty($media['thumb_key']) || ((empty($media['storage_backend']) || $media['storage_backend'] !== 's3') && !is_file(bg_storage_root() . '/' . $media['thumb_key'])))
		fatal_lang_error('bg_cover_unavailable', false);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_cover_media = {int:id_media},
			updated_at = {int:updated}
		WHERE id_album = {int:id_album}',
		array(
			'id_media' => $id_media,
			'updated' => time(),
			'id_album' => (int) $media['id_album'],
		)
	);

	$return_url = 'action=gallery;sa=album;album=' . (int) $media['id_album'];

	if ($return_start > 0)
		$return_url .= ';start=' . $return_start;

	if ($return_sort !== 'newest')
		$return_url .= ';sort=' . urlencode($return_sort);

	redirectexit($return_url);
}

function bg_media_file()
{
	global $user_info, $smcFunc;

	$id_media = !empty($_REQUEST['media']) ? (int) $_REQUEST['media'] : 0;
	$media = bg_media_load($id_media);

	if (!$media)
		fatal_lang_error('bg_media_not_found', false);

	$album = array(
		'id_album' => (int) $media['id_album'],
		'id_owner' => (int) $media['album_owner'],
		'discovery_visibility' => (int) $media['discovery_visibility'],
		'access_mode' => (int) $media['access_mode'],
		'password_hash' => $media['password_hash'],
	);

	if (!bg_portfolio_album_visible($album))
		fatal_lang_error('bg_media_not_found', false);

	$is_admin = !empty($user_info['is_admin']) || bg_allowed('bg_moderate_gallery');

	if (!bg_album_content_accessible($album, (int) $user_info['id'], $is_admin))
		fatal_lang_error('no_access', false);

	$thumb = !empty($_REQUEST['thumb']);
	$force_download = !in_array($media['media_type'], array('image', 'video'), true);
	$download = (!$thumb && (!empty($_REQUEST['download']) || $force_download));

	if ($download && empty($media['allow_downloads']))
		fatal_lang_error('no_access', false);

	$relative = $thumb ? $media['thumb_key'] : $media['storage_key'];

	if ($thumb && empty($relative))
		fatal_lang_error('bg_media_not_found', false);

	if (!empty($media['storage_backend']) && $media['storage_backend'] === 's3')
	{
		$filename = preg_replace('~[^A-Za-z0-9._ -]+~', '_', $media['filename']);
		$url = bg_s3_media_url($relative, $download ? $filename : '');
		if ($url === '')
			fatal_lang_error('bg_media_not_found', false);
		if ($download)
		{
			$smcFunc['db_query']('', 'UPDATE {db_prefix}bg_media SET num_downloads = num_downloads + 1 WHERE id_media = {int:id_media}', array('id_media'=>(int)$media['id_media']));
		}
		header('Location: ' . $url, true, 302);
		exit;
	}

	$path = bg_storage_root() . '/' . $relative;

	if (!is_file($path))
		fatal_lang_error('bg_media_not_found', false);

	while (ob_get_level())
		@ob_end_clean();

	$is_video = $media['media_type'] === 'video' && !$thumb && !$download;
	$mime = $thumb ? 'image/jpeg' : $media['mime_type'];
	$size = (int) filesize($path);
	$start = 0;
	$end = max(0, $size - 1);
	$status = 200;

	header('Content-Type: ' . $mime);
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: private, max-age=86400');

	if ($is_video)
	{
		header('Accept-Ranges: bytes');

		if (!empty($_SERVER['HTTP_RANGE']) && preg_match('~bytes=(\d*)-(\d*)~', $_SERVER['HTTP_RANGE'], $match))
		{
			if ($match[1] !== '')
				$start = (int) $match[1];

			if ($match[2] !== '')
				$end = min($end, (int) $match[2]);

			if ($match[1] === '' && $match[2] !== '')
			{
				$suffix = min($size, (int) $match[2]);
				$start = max(0, $size - $suffix);
				$end = $size - 1;
			}

			if ($start > $end || $start >= $size)
			{
				http_response_code(416);
				header('Content-Range: bytes */' . $size);
				exit;
			}

			$status = 206;
			http_response_code(206);
			header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
		}
	}

	$length = $end - $start + 1;
	header('Content-Length: ' . $length);

	$filename = preg_replace('~[^A-Za-z0-9._ -]+~', '_', $media['filename']);

	if ($download)
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}bg_media
			SET num_downloads = num_downloads + 1
			WHERE id_media = {int:id_media}',
			array('id_media' => (int) $media['id_media'])
		);

		header('Content-Disposition: attachment; filename="' . addcslashes($filename, "\"\\") . '"');
		header('Cache-Control: private, no-store, max-age=0');
	}
	else
		header('Content-Disposition: inline; filename="' . addcslashes($filename, "\"\\") . '"');

	$fp = @fopen($path, 'rb');

	if (!$fp)
		fatal_lang_error('bg_media_not_found', false);

	if ($start > 0)
		fseek($fp, $start);

	$remaining = $length;

	while ($remaining > 0 && !feof($fp))
	{
		$chunk = fread($fp, min(1024 * 1024, $remaining));

		if ($chunk === false || $chunk === '')
			break;

		echo $chunk;
		$remaining -= strlen($chunk);
		@flush();
	}

	fclose($fp);
	exit;
}

/* -------------------------------------------------------
 * Access resolution
 * ----------------------------------------------------- */

function bg_album_is_discoverable(array $album, $member_id, $is_admin = false)
{
	if ($is_admin || (int) $album['id_owner'] === (int) $member_id)
		return true;

	return (int) $album['discovery_visibility'] >= 1;
}

function bg_album_content_accessible(array $album, $member_id, $is_admin = false)
{
	global $smcFunc;

	if ($is_admin || (int) $album['id_owner'] === (int) $member_id)
		return true;

	$mode = (int) $album['access_mode'];

	if ($mode === 0)
		return true;

	if ($mode === 1)
		return (int) $member_id > 0;

	if (in_array($mode, array(3, 4), true))
	{
		if (!empty($_SESSION['bg_album_unlocks'][(int) $album['id_album']]))
			return true;

		if ($mode === 3)
			return false;
	}

	if (in_array($mode, array(2, 4, 5), true))
	{
		if ((int) $member_id <= 0)
			return false;

		$request = $smcFunc['db_query']('', '
			SELECT id_album
			FROM {db_prefix}bg_album_access
			WHERE id_album = {int:id_album}
				AND id_member = {int:id_member}
				AND revoked_at = {int:not_revoked}
			LIMIT 1',
			array(
				'id_album' => (int) $album['id_album'],
				'id_member' => (int) $member_id,
				'not_revoked' => 0,
			)
		);

		$allowed = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);

		return $allowed;
	}

	return false;
}

/* -------------------------------------------------------
 * Categories
 * ----------------------------------------------------- */

function bg_load_categories()
{
	global $smcFunc;

	$categories = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_category, id_parent, name, description, slug, sort_order, enabled
		FROM {db_prefix}bg_categories
		ORDER BY sort_order, name');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$categories[] = $row;

	$smcFunc['db_free_result']($request);

	return $categories;
}

function bg_unique_category_slug($name, $exclude = 0)
{
	global $smcFunc;

	$base = bg_slugify($name);
	$slug = $base;
	$i = 2;

	while (true)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_category
			FROM {db_prefix}bg_categories
			WHERE slug = {string:slug}
				AND id_category != {int:exclude}
			LIMIT 1',
			array(
				'slug' => $slug,
				'exclude' => (int) $exclude,
			)
		);

		$exists = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);

		if (!$exists)
			return $slug;

		$slug = $base . '-' . $i++;
	}
}

/* -------------------------------------------------------
 * Admin
 * ----------------------------------------------------- */

function bg_admin_dispatch()
{
	global $context, $txt;

	isAllowedTo('admin_forum');
	bg_load_better_media_language();
	bg_load_better_media_template();

	$sa = !empty($_REQUEST['sa']) ? (string) $_REQUEST['sa'] : 'dashboard';

	$subactions = array(
		'dashboard' => 'bg_admin_dashboard',
		'categories' => 'bg_admin_categories',
		'settings' => 'bg_admin_settings',
		'settingssave' => 'bg_admin_settings_save',
		'storage_test' => 'bg_admin_storage_test',
		'maintenance' => 'bg_admin_maintenance',
		'maintenanceclean' => 'bg_admin_maintenance_clean',
		'maintenancerecount' => 'bg_admin_maintenance_recount',
		'maintenancezips' => 'bg_admin_maintenance_zips',
		'categorysave' => 'bg_admin_category_save',
		'categorydelete' => 'bg_admin_category_delete',
	);

	if (!isset($subactions[$sa]))
		$sa = 'dashboard';

	$context['page_title'] = $txt['bg_admin_title'];
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['bg_admin_title'],
		'description' => $txt['bg_admin_desc'],
		'tabs' => array(
			'dashboard' => array('description' => $txt['bg_admin_dashboard']),
			'categories' => array('description' => $txt['bg_admin_categories']),
			'settings' => array('description' => $txt['bg_admin_settings']),
			'maintenance' => array('description' => $txt['bg_admin_maintenance']),
		),
	);

	call_helper($subactions[$sa]);
}

function bg_admin_dashboard()
{
	global $context;

	$context['bg_environment'] = bg_environment_checks();
	$context['bg_schema_counts'] = bg_schema_counts();
	$context['sub_template'] = 'bg_admin_dashboard';
}

function bg_ini_bytes($value)
{
	$value = trim((string) $value);

	if ($value === '')
		return 0;

	$last = strtolower(substr($value, -1));
	$number = (float) $value;

	switch ($last)
	{
		case 'g':
			$number *= 1024;
			// no break
		case 'm':
			$number *= 1024;
			// no break
		case 'k':
			$number *= 1024;
			break;
	}

	return max(0, (int) round($number));
}

function bg_admin_settings()
{
	global $context, $modSettings, $smcFunc;

	bg_betterstorage_sync_registry();

	$upload_bytes = bg_ini_bytes(ini_get('upload_max_filesize'));
	$post_bytes = bg_ini_bytes(ini_get('post_max_size'));

	$php_limit = 0;

	if ($upload_bytes > 0 && $post_bytes > 0)
		$php_limit = min($upload_bytes, $post_bytes);
	elseif ($upload_bytes > 0)
		$php_limit = $upload_bytes;
	elseif ($post_bytes > 0)
		$php_limit = $post_bytes;

	$context['bg_storage_counts'] = array('local' => 0, 's3' => 0);
	$request = $smcFunc['db_query']('', '
		SELECT storage_backend, COUNT(*) AS total
		FROM {db_prefix}bg_media
		GROUP BY storage_backend', array());
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$key = !empty($row['storage_backend']) && $row['storage_backend'] === 's3' ? 's3' : 'local';
		$context['bg_storage_counts'][$key] += (int) $row['total'];
	}
	$smcFunc['db_free_result']($request);

	$context['bg_upload_settings'] = array(
		'max_files' => max(1, (int) $modSettings['bg_max_upload_files']),
		'upload_progress_mode' => isset($modSettings['bg_upload_progress_mode']) && $modSettings['bg_upload_progress_mode'] === 'per_file' ? 'per_file' : 'compact',
		'max_image_mb' => !empty($modSettings['bg_max_image_kb']) ? round(((int) $modSettings['bg_max_image_kb']) / 1024, 2) : 10,
		'max_video_mb' => !empty($modSettings['bg_max_video_mb']) ? (int) $modSettings['bg_max_video_mb'] : 250,
		'thumb_width' => !empty($modSettings['bg_thumb_width']) ? (int) $modSettings['bg_thumb_width'] : 480,
		'thumb_height' => !empty($modSettings['bg_thumb_height']) ? (int) $modSettings['bg_thumb_height'] : 360,
		'comments_enabled' => !empty($modSettings['bg_comments_enabled']),
		'likes_enabled' => !empty($modSettings['bg_likes_enabled']),
		'album_download_chunk_mb' => !empty($modSettings['bg_album_download_chunk_mb']) ? (int) $modSettings['bg_album_download_chunk_mb'] : 500,
		'media_per_page' => !empty($modSettings['bg_media_per_page']) ? max(6, min(100, (int) $modSettings['bg_media_per_page'])) : 24,
		'member_storage_quota_mb' => isset($modSettings['bg_member_storage_quota_mb']) ? max(0, (int) $modSettings['bg_member_storage_quota_mb']) : 0,
		'nav_label' => isset($modSettings['bg_nav_label']) && trim((string) $modSettings['bg_nav_label']) !== '' ? (string) $modSettings['bg_nav_label'] : 'Better Gallery',
		'show_main_menu' => !isset($modSettings['bg_show_main_menu']) || !empty($modSettings['bg_show_main_menu']),
		'nav_icon' => 'gallery',
		'nav_icons' => array_keys(bg_media_nav_icons()),
		'zip_available' => bg_album_zip_available(),
		'php_upload_max' => (string) ini_get('upload_max_filesize'),
		'php_post_max' => (string) ini_get('post_max_size'),
		'php_max_file_uploads' => (int) ini_get('max_file_uploads'),
		'php_effective_mb' => $php_limit > 0 ? round($php_limit / 1048576, 2) : 0,
		'betterstorage_integrated' => bg_betterstorage_available(),
		'storage_backend' => bg_storage_backend(),
		's3_region' => isset($modSettings['bg_s3_region']) ? (string) $modSettings['bg_s3_region'] : 'us-east-1',
		's3_bucket' => isset($modSettings['bg_s3_bucket']) ? (string) $modSettings['bg_s3_bucket'] : '',
		's3_prefix' => isset($modSettings['bg_s3_prefix']) ? (string) $modSettings['bg_s3_prefix'] : 'better-gallery',
		's3_access_key' => isset($modSettings['bg_s3_access_key']) ? (string) $modSettings['bg_s3_access_key'] : '',
		's3_has_secret' => !empty($modSettings['bg_s3_secret_key']),
		's3_endpoint' => isset($modSettings['bg_s3_endpoint']) ? (string) $modSettings['bg_s3_endpoint'] : '',
		's3_private' => !isset($modSettings['bg_s3_private']) || !empty($modSettings['bg_s3_private']),
	);

	if (!empty($context['bg_upload_settings']['betterstorage_integrated']))
	{
		$central = bg_s3_config();
		$context['bg_upload_settings']['s3_region'] = (string) ($central['region'] ?? 'us-east-1');
		$context['bg_upload_settings']['s3_bucket'] = (string) ($central['bucket'] ?? '');
		$context['bg_upload_settings']['s3_prefix'] = (string) ($central['prefix'] ?? 'better-gallery');
		$context['bg_upload_settings']['s3_access_key'] = '';
		$context['bg_upload_settings']['s3_has_secret'] = !empty($central['secret_key']);
		$context['bg_upload_settings']['s3_endpoint'] = (string) ($central['endpoint'] ?? '');
		$context['bg_upload_settings']['s3_private'] = !empty($central['private']);
	}

	// If the administrator just tested unsaved storage settings, carry those values back
	// into the form so the test redirect does not silently revert to saved defaults.
	if (!empty($_SESSION['bg_storage_pending']) && is_array($_SESSION['bg_storage_pending']))
	{
		$pending = $_SESSION['bg_storage_pending'];
		foreach (array('storage_backend', 's3_region', 's3_bucket', 's3_prefix', 's3_access_key', 's3_endpoint', 's3_private') as $key)
			if (array_key_exists($key, $pending))
				$context['bg_upload_settings'][$key] = $pending[$key];

		if (!empty($pending['s3_secret_key']))
			$context['bg_upload_settings']['s3_has_secret'] = true;
	}

	$context['sub_template'] = 'bg_admin_settings';
}

function bg_admin_settings_save()
{
	global $modSettings;

	checkSession('post');

	$max_files = max(1, min(1000, (int) ($_POST['max_files'] ?? 1)));
	$upload_progress_mode = (string) ($_POST['upload_progress_mode'] ?? 'compact');
	if (!in_array($upload_progress_mode, array('compact', 'per_file'), true))
		$upload_progress_mode = 'compact';

	$max_image_mb = (float) ($_POST['max_image_mb'] ?? 10);
	if ($max_image_mb < 1)
		$max_image_mb = 1;
	elseif ($max_image_mb > 1024)
		$max_image_mb = 1024;

	$max_video_mb = max(1, min(10240, (int) ($_POST['max_video_mb'] ?? 250)));
	$thumb_width = max(50, min(2400, (int) ($_POST['thumb_width'] ?? 480)));
	$thumb_height = max(50, min(2400, (int) ($_POST['thumb_height'] ?? 360)));
	$comments_enabled = !empty($_POST['comments_enabled']) ? 1 : 0;
	$likes_enabled = !empty($_POST['likes_enabled']) ? 1 : 0;
	$album_download_chunk_mb = max(50, min(4096, (int) ($_POST['album_download_chunk_mb'] ?? 500)));
	$media_per_page = max(6, min(100, (int) ($_POST['media_per_page'] ?? 24)));
	$member_storage_quota_mb = max(0, min(1048576, (int) ($_POST['member_storage_quota_mb'] ?? 0)));
	$show_main_menu = !empty($_POST['show_main_menu']) ? 1 : 0;
	$nav_label = trim((string) ($_POST['nav_label'] ?? 'Better Gallery'));

	if ($nav_label === '')
		$nav_label = 'Better Gallery';
	if (strlen($nav_label) > 40)
		$nav_label = substr($nav_label, 0, 40);

	updateSettings(array(
		'bg_max_upload_files' => $max_files,
		'bg_upload_progress_mode' => $upload_progress_mode,
		'bg_max_image_kb' => max(1024, (int) round($max_image_mb * 1024)),
		'bg_max_video_mb' => $max_video_mb,
		'bg_thumb_width' => $thumb_width,
		'bg_thumb_height' => $thumb_height,
		'bg_comments_enabled' => $comments_enabled,
		'bg_likes_enabled' => $likes_enabled,
		'bg_album_download_chunk_mb' => $album_download_chunk_mb,
		'bg_media_per_page' => $media_per_page,
		'bg_member_storage_quota_mb' => $member_storage_quota_mb,
		'bg_show_main_menu' => $show_main_menu,
		'bg_nav_label' => $nav_label,
		'bg_nav_icon' => 'gallery',
		'bg_content_profile' => 'gallery',
		'bg_allowed_extensions' => '',
		'bg_portfolio_mode' => 0,
		'settings_updated' => time(),
	));


	redirectexit('action=admin;area=bettergallery;sa=settings;saved=1');
}

function bg_admin_storage_test()
{
	isAllowedTo('admin_forum');
	checkSession('post');

	// Cloud configuration is centralized in Better Storage. Keep this legacy route
	// only so old bookmarked/admin forms fail gracefully.
	if (bg_betterstorage_available())
		redirectexit('action=admin;area=betterstorage;sa=settings');

	redirectexit('action=admin;area=bettergallery;sa=settings');
}

function bg_admin_categories()
{
	global $context, $smcFunc;

	$context['bg_categories'] = bg_load_categories();
	$context['bg_category_edit'] = false;

	$id_category = !empty($_REQUEST['edit']) ? (int) $_REQUEST['edit'] : 0;

	if ($id_category > 0)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_category, id_parent, name, description, slug, sort_order, enabled
			FROM {db_prefix}bg_categories
			WHERE id_category = {int:id_category}
			LIMIT 1',
			array('id_category' => $id_category)
		);

		$context['bg_category_edit'] = $smcFunc['db_fetch_assoc']($request) ?: false;
		$smcFunc['db_free_result']($request);
	}

	$context['sub_template'] = 'bg_admin_categories';
}

function bg_admin_category_save()
{
	global $smcFunc;

	checkSession('post');

	$id_category = max(0, (int) ($_POST['id_category'] ?? 0));
	$name = trim((string) ($_POST['name'] ?? ''));
	$description = trim((string) ($_POST['description'] ?? ''));
	$id_parent = max(0, (int) ($_POST['id_parent'] ?? 0));
	$sort_order = max(0, (int) ($_POST['sort_order'] ?? 0));
	$enabled = !empty($_POST['enabled']) ? 1 : 0;

	if ($name === '')
		redirectexit('action=admin;area=bettergallery;sa=categories');

	$slug = bg_unique_category_slug($name, $id_category);

	if ($id_category > 0)
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}bg_categories
			SET id_parent = {int:id_parent},
				name = {string:name},
				description = {string:description},
				slug = {string:slug},
				sort_order = {int:sort_order},
				enabled = {int:enabled}
			WHERE id_category = {int:id_category}',
			array(
				'id_parent' => $id_parent,
				'name' => $name,
				'description' => $description,
				'slug' => $slug,
				'sort_order' => $sort_order,
				'enabled' => $enabled,
				'id_category' => $id_category,
			)
		);
	}
	else
	{
		$smcFunc['db_insert']('insert',
			'{db_prefix}bg_categories',
			array(
				'id_parent' => 'int',
				'name' => 'string',
				'description' => 'string',
				'slug' => 'string',
				'sort_order' => 'int',
				'enabled' => 'int',
				'created_at' => 'int',
			),
			array(
				$id_parent,
				$name,
				$description,
				$slug,
				$sort_order,
				$enabled,
				time(),
			),
			array('id_category')
		);
	}

	redirectexit('action=admin;area=bettergallery;sa=categories');
}

function bg_admin_category_delete()
{
	global $smcFunc;

	checkSession('post');

	$id_category = max(0, (int) ($_POST['id_category'] ?? 0));

	if (!$id_category)
		redirectexit('action=admin;area=bettergallery;sa=categories');

	// Albums survive category deletion and become uncategorized.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums
		SET id_category = {int:none}
		WHERE id_category = {int:id_category}',
		array('none' => 0, 'id_category' => $id_category)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_categories
		SET id_parent = {int:none}
		WHERE id_parent = {int:id_category}',
		array('none' => 0, 'id_category' => $id_category)
	);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}bg_categories
		WHERE id_category = {int:id_category}',
		array('id_category' => $id_category)
	);

	redirectexit('action=admin;area=bettergallery;sa=categories');
}


/* -------------------------------------------------------
 * Maintenance / cleanup
 * ----------------------------------------------------- */

function bg_maintenance_normalize_key($key)
{
	$key = str_replace('\\', '/', trim((string) $key));
	$key = ltrim($key, '/');

	if ($key === '' || strpos($key, '..') !== false)
		return '';

	return $key;
}

function bg_maintenance_count_query($sql, $params = array())
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', $sql, $params);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return $row ? (int) reset($row) : 0;
}

function bg_maintenance_storage_scan()
{
	global $smcFunc;

	$root = bg_storage_root();
	$root_real = is_dir($root) ? realpath($root) : false;
	$referenced = array();
	$missing_originals = array();
	$missing_thumbs = array();
	$missing_posters = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_media, id_album, filename, storage_backend, storage_key, thumb_key, poster_key
		FROM {db_prefix}bg_media
		ORDER BY id_media ASC');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		foreach (array('storage_key', 'thumb_key', 'poster_key') as $field)
		{
			$key = bg_maintenance_normalize_key($row[$field] ?? '');

			if ($key !== '')
				$referenced[$key] = true;
		}

		if (!empty($row['storage_backend']) && $row['storage_backend'] === 's3')
			continue;

		$original = bg_maintenance_normalize_key($row['storage_key'] ?? '');
		$thumb = bg_maintenance_normalize_key($row['thumb_key'] ?? '');
		$poster = bg_maintenance_normalize_key($row['poster_key'] ?? '');

		if ($original !== '' && !is_file($root . '/' . $original))
			$missing_originals[] = array(
				'id_media' => (int) $row['id_media'],
				'id_album' => (int) $row['id_album'],
				'filename' => (string) $row['filename'],
				'key' => $original,
			);

		if ($thumb !== '' && !is_file($root . '/' . $thumb))
			$missing_thumbs[] = array(
				'id_media' => (int) $row['id_media'],
				'filename' => (string) $row['filename'],
				'key' => $thumb,
			);

		if ($poster !== '' && !is_file($root . '/' . $poster))
			$missing_posters[] = array(
				'id_media' => (int) $row['id_media'],
				'filename' => (string) $row['filename'],
				'key' => $poster,
			);
	}
	$smcFunc['db_free_result']($request);

	$orphan_files = array();
	$orphan_bytes = 0;
	$cutoff = time() - 3600;
	$scan_dirs = array('originals', 'thumbs', 'videos', 'posters', 'files');

	if ($root_real !== false)
	{
		foreach ($scan_dirs as $dir_name)
		{
			$dir = $root . '/' . $dir_name;

			if (!is_dir($dir))
				continue;

			try
			{
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
					RecursiveIteratorIterator::LEAVES_ONLY
				);

				foreach ($iterator as $file)
				{
					if (!$file->isFile() || $file->isLink())
						continue;

					$path = $file->getPathname();
					$real = realpath($path);

					if ($real === false || strpos($real, $root_real . DIRECTORY_SEPARATOR) !== 0)
						continue;

					$relative = bg_maintenance_normalize_key(substr($real, strlen($root_real) + 1));

					if ($relative === '' || isset($referenced[$relative]))
						continue;

					// Avoid racing an upload or a just-created derivative.
					if ((int) $file->getMTime() > $cutoff)
						continue;

					$size = max(0, (int) $file->getSize());
					$orphan_bytes += $size;
					$orphan_files[] = array(
						'key' => $relative,
						'path' => $real,
						'size' => $size,
						'mtime' => (int) $file->getMTime(),
					);
				}
			}
			catch (UnexpectedValueException $e)
			{
				// An unreadable directory is reported by the environment/storage checks.
			}
		}
	}

	$stale_zips = array();
	$stale_zip_bytes = 0;
	$download_dir = $root . '/downloads';
	$zip_cutoff = time() - 7200;

	foreach ((array) glob($download_dir . '/album_*.zip') as $zip)
	{
		if (!is_file($zip) || (int) @filemtime($zip) >= $zip_cutoff)
			continue;

		$size = max(0, (int) @filesize($zip));
		$stale_zip_bytes += $size;
		$stale_zips[] = array(
			'path' => $zip,
			'name' => basename($zip),
			'size' => $size,
			'mtime' => (int) @filemtime($zip),
		);
	}

	return array(
		'referenced' => $referenced,
		'missing_originals' => $missing_originals,
		'missing_thumbs' => $missing_thumbs,
		'missing_posters' => $missing_posters,
		'orphan_files' => $orphan_files,
		'orphan_bytes' => $orphan_bytes,
		'stale_zips' => $stale_zips,
		'stale_zip_bytes' => $stale_zip_bytes,
	);
}

function bg_maintenance_database_scan()
{
	$counts = array();

	$counts['media_missing_album'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media AS m
		LEFT JOIN {db_prefix}bg_albums AS a ON (a.id_album = m.id_album)
		WHERE a.id_album IS NULL');

	$counts['media_tags'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_tags AS mt
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = mt.id_media)
		LEFT JOIN {db_prefix}bg_tags AS t ON (t.id_tag = mt.id_tag)
		WHERE m.id_media IS NULL OR t.id_tag IS NULL');

	$counts['comments'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_comments AS c
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = c.id_media)
		WHERE m.id_media IS NULL');

	$counts['likes'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_likes AS l
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = l.id_media)
		WHERE m.id_media IS NULL');

	$counts['media_favorites'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_media_favorites AS f
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = f.id_media)
		WHERE m.id_media IS NULL');

	$counts['album_access'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_album_access AS aa
		LEFT JOIN {db_prefix}bg_albums AS a ON (a.id_album = aa.id_album)
		WHERE a.id_album IS NULL');

	$counts['access_requests'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_access_requests AS ar
		LEFT JOIN {db_prefix}bg_albums AS a ON (a.id_album = ar.id_album)
		WHERE a.id_album IS NULL');

	$counts['album_favorites'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_album_favorites AS af
		LEFT JOIN {db_prefix}bg_albums AS a ON (a.id_album = af.id_album)
		WHERE a.id_album IS NULL');

	$counts['album_likes'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_album_likes AS al
		LEFT JOIN {db_prefix}bg_albums AS a ON (a.id_album = al.id_album)
		WHERE a.id_album IS NULL');

	$counts['reports'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_reports AS r
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = r.id_media)
		LEFT JOIN {db_prefix}bg_media_comments AS c ON (c.id_comment = r.id_comment)
		WHERE m.id_media IS NULL
			OR (r.target_type = {string:comment_type} AND (r.id_comment = 0 OR c.id_comment IS NULL))',
		array('comment_type' => 'comment')
	);

	$counts['unique_views'] =
		bg_maintenance_count_query('
			SELECT COUNT(*) AS total
			FROM {db_prefix}bg_unique_views AS uv
			LEFT JOIN {db_prefix}bg_albums AS a
				ON (uv.target_type = {string:album_type} AND a.id_album = uv.id_target)
			LEFT JOIN {db_prefix}bg_media AS m
				ON (uv.target_type = {string:media_type} AND m.id_media = uv.id_target)
			WHERE (uv.target_type = {string:album_type} AND a.id_album IS NULL)
				OR (uv.target_type = {string:media_type} AND m.id_media IS NULL)
				OR uv.target_type NOT IN ({array_string:valid_types})',
			array(
				'album_type' => 'album',
				'media_type' => 'media',
				'valid_types' => array('album', 'media'),
			)
		);

	$counts['bad_covers'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_albums AS a
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = a.id_cover_media)
		WHERE a.id_cover_media != 0
			AND (m.id_media IS NULL OR m.id_album != a.id_album)');

	$counts['bad_categories'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_albums AS a
		LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
		WHERE a.id_category != 0
			AND c.id_category IS NULL');

	$counts['album_count_drift'] = bg_maintenance_count_query('
		SELECT COUNT(*) AS total
		FROM {db_prefix}bg_albums AS a
		LEFT JOIN (
			SELECT id_album, COUNT(*) AS actual_count
			FROM {db_prefix}bg_media
			GROUP BY id_album
		) AS mc ON (mc.id_album = a.id_album)
		WHERE a.num_items != COALESCE(mc.actual_count, 0)');

	$counts['safe_orphan_rows'] =
		$counts['media_tags']
		+ $counts['comments']
		+ $counts['likes']
		+ $counts['media_favorites']
		+ $counts['album_access']
		+ $counts['access_requests']
		+ $counts['album_favorites']
		+ $counts['album_likes']
		+ $counts['reports']
		+ $counts['unique_views']
		+ $counts['bad_covers']
		+ $counts['bad_categories'];

	return $counts;
}

function bg_admin_maintenance()
{
	global $context;

	$storage = bg_maintenance_storage_scan();
	$database = bg_maintenance_database_scan();

	$context['bg_maintenance'] = array(
		'storage' => $storage,
		'database' => $database,
		'safe_total' => count($storage['orphan_files']) + count($storage['stale_zips']) + $database['safe_orphan_rows'],
		'attention_total' => count($storage['missing_originals']) + $database['media_missing_album'],
		'warning_total' => count($storage['missing_thumbs']) + count($storage['missing_posters']) + $database['album_count_drift'],
	);
	$context['sub_template'] = 'bg_admin_maintenance';
}

function bg_maintenance_delete_safe_orphan_files()
{
	$scan = bg_maintenance_storage_scan();
	$deleted = 0;
	$bytes = 0;

	foreach ($scan['orphan_files'] as $file)
	{
		if (!is_file($file['path']))
			continue;

		if (@unlink($file['path']))
		{
			$deleted++;
			$bytes += (int) $file['size'];
		}
	}

	return array($deleted, $bytes);
}

function bg_maintenance_clean_database_orphans()
{
	global $smcFunc;

	$before = bg_maintenance_database_scan();

	$queries = array(
		array('DELETE FROM {db_prefix}bg_media_tags
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_media_tags.id_media
			)
			OR NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_tags AS t WHERE t.id_tag = {db_prefix}bg_media_tags.id_tag
			)', array()),
		array('DELETE FROM {db_prefix}bg_media_comments
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_media_comments.id_media
			)', array()),
		array('DELETE FROM {db_prefix}bg_media_likes
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_media_likes.id_media
			)', array()),
		array('DELETE FROM {db_prefix}bg_media_favorites
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_media_favorites.id_media
			)', array()),
		array('DELETE FROM {db_prefix}bg_album_access
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_albums AS a WHERE a.id_album = {db_prefix}bg_album_access.id_album
			)', array()),
		array('DELETE FROM {db_prefix}bg_access_requests
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_albums AS a WHERE a.id_album = {db_prefix}bg_access_requests.id_album
			)', array()),
		array('DELETE FROM {db_prefix}bg_album_favorites
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_albums AS a WHERE a.id_album = {db_prefix}bg_album_favorites.id_album
			)', array()),
		array('DELETE FROM {db_prefix}bg_reports
			WHERE NOT EXISTS (
				SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_reports.id_media
			)
			OR (
				target_type = {string:comment_type}
				AND (
					id_comment = 0
					OR NOT EXISTS (
						SELECT 1 FROM {db_prefix}bg_media_comments AS c
						WHERE c.id_comment = {db_prefix}bg_reports.id_comment
					)
				)
			)', array('comment_type' => 'comment')),
		array('DELETE FROM {db_prefix}bg_unique_views
			WHERE target_type NOT IN ({array_string:valid_types})
				OR (
					target_type = {string:album_type}
					AND NOT EXISTS (
						SELECT 1 FROM {db_prefix}bg_albums AS a WHERE a.id_album = {db_prefix}bg_unique_views.id_target
					)
				)
				OR (
					target_type = {string:media_type}
					AND NOT EXISTS (
						SELECT 1 FROM {db_prefix}bg_media AS m WHERE m.id_media = {db_prefix}bg_unique_views.id_target
					)
				)', array(
					'valid_types' => array('album', 'media'),
					'album_type' => 'album',
					'media_type' => 'media',
				)),
	);

	foreach ($queries as $query)
		$smcFunc['db_query']('', $query[0], $query[1]);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums AS a
		LEFT JOIN {db_prefix}bg_media AS m ON (m.id_media = a.id_cover_media)
		SET a.id_cover_media = 0
		WHERE a.id_cover_media != 0
			AND (m.id_media IS NULL OR m.id_album != a.id_album)');

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums AS a
		LEFT JOIN {db_prefix}bg_categories AS c ON (c.id_category = a.id_category)
		SET a.id_category = 0
		WHERE a.id_category != 0
			AND c.id_category IS NULL');

	return $before['safe_orphan_rows'];
}

function bg_admin_maintenance_clean()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	list($files_deleted, $bytes_deleted) = bg_maintenance_delete_safe_orphan_files();
	$db_cleaned = bg_maintenance_clean_database_orphans();

	redirectexit(
		'action=admin;area=bettergallery;sa=maintenance'
		. ';cleaned_files=' . (int) $files_deleted
		. ';cleaned_bytes=' . (int) $bytes_deleted
		. ';cleaned_rows=' . (int) $db_cleaned
	);
}

function bg_admin_maintenance_recount()
{
	global $smcFunc;

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$before = bg_maintenance_database_scan();
	$drift = (int) $before['album_count_drift'];

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}bg_albums AS a
		LEFT JOIN (
			SELECT id_album, COUNT(*) AS actual_count
			FROM {db_prefix}bg_media
			GROUP BY id_album
		) AS mc ON (mc.id_album = a.id_album)
		SET a.num_items = COALESCE(mc.actual_count, 0),
			a.updated_at = {int:updated}',
		array('updated' => time())
	);

	redirectexit('action=admin;area=bettergallery;sa=maintenance;recounted=' . $drift);
}

function bg_admin_maintenance_zips()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		fatal_lang_error('no_access', false);

	checkSession('post');

	$scan = bg_maintenance_storage_scan();
	$deleted = 0;
	$bytes = 0;

	foreach ($scan['stale_zips'] as $zip)
	{
		if (!is_file($zip['path']))
			continue;

		if (@unlink($zip['path']))
		{
			$deleted++;
			$bytes += (int) $zip['size'];
		}
	}

	redirectexit(
		'action=admin;area=bettergallery;sa=maintenance'
		. ';zips_deleted=' . $deleted
		. ';zip_bytes=' . $bytes
	);
}


/* -------------------------------------------------------
 * Environment
 * ----------------------------------------------------- */

function bg_environment_checks()
{
	global $smcFunc, $db_prefix;

	$checks = array();

	$add = function ($status, $label, $detail) use (&$checks)
	{
		$checks[] = array('status' => $status, 'label' => $label, 'detail' => $detail);
	};

	$add(version_compare(PHP_VERSION, '7.4.0', '>=') ? 'pass' : 'fail', 'PHP runtime', 'PHP ' . PHP_VERSION);

	$add(
		function_exists('imagecreatefromjpeg') || class_exists('Imagick') ? 'pass' : 'warn',
		'Image processing',
		class_exists('Imagick') ? 'ImageMagick PHP extension available.' :
			(function_exists('imagecreatefromjpeg') ? 'GD image support available.' : 'No GD JPEG or ImageMagick support detected.')
	);

	$ffmpeg = bg_find_binary('ffmpeg');
	$ffprobe = bg_find_binary('ffprobe');

	$add($ffmpeg ? 'pass' : 'warn', 'FFmpeg', $ffmpeg ? 'Detected at ' . $ffmpeg : 'Not detected.');
	$add($ffprobe ? 'pass' : 'warn', 'ffprobe', $ffprobe ? 'Detected at ' . $ffprobe : 'Not detected.');
	$add(
		$ffmpeg ? 'pass' : 'warn',
		'Video poster frames / normalization',
		$ffmpeg
			? 'Available. Better Gallery can generate poster frames and normalize MOV/MKV to MP4.'
			: 'FFmpeg unavailable. Browser-native MP4/WebM uploads remain supported without poster generation.'
	);

	$storage_ready = bg_ensure_storage();
	$add(
		$storage_ready ? 'pass' : 'fail',
		'Gallery storage',
		$storage_ready
			? 'Writable protected storage at ' . bg_storage_root()
			: 'Gallery storage could not be created or is not writable.'
	);

	$gallery_tables = array(
		'Categories' => 'bg_categories',
		'Albums' => 'bg_albums',
		'Gallery Items' => 'bg_media',
		'Album Access Rules' => 'bg_album_access',
		'Access Requests' => 'bg_access_requests',
		'Tags' => 'bg_tags',
		'Gallery Item Tags' => 'bg_media_tags',
		'Comments' => 'bg_media_comments',
		'Likes' => 'bg_media_likes',
		'Gallery Item Favorites' => 'bg_media_favorites',
		'Album Favorites' => 'bg_album_favorites',
		'Unique Views' => 'bg_unique_views',
		'Reports' => 'bg_reports',
	);

	foreach ($gallery_tables as $label => $table)
	{
		$request = $smcFunc['db_query']('', '
			SHOW TABLES LIKE {string:table}',
			array('table' => $db_prefix . $table)
		);

		$exists = $smcFunc['db_num_rows']($request) > 0;
		$smcFunc['db_free_result']($request);

		$add($exists ? 'pass' : 'fail', 'Gallery data: ' . $label, $exists ? 'Ready.' : 'Missing required storage table (' . $table . ').');
	}

	return $checks;
}

function bg_find_binary($name)
{
	foreach (array('/usr/bin/', '/usr/local/bin/', '/opt/homebrew/bin/') as $dir)
	{
		$path = $dir . $name;
		if (is_file($path) && is_executable($path))
			return $path;
	}

	if (function_exists('shell_exec'))
	{
		$result = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
		if (!empty($result))
		{
			$result = trim($result);
			if (is_file($result) && is_executable($result))
				return $result;
		}
	}

	return '';
}

function bg_schema_counts()
{
	global $smcFunc;

	$counts = array();

	$gallery_tables = array(
		'Categories' => 'bg_categories',
		'Albums' => 'bg_albums',
		'Gallery Items' => 'bg_media',
		'Album Access Rules' => 'bg_album_access',
		'Access Requests' => 'bg_access_requests',
	);

	foreach ($gallery_tables as $label => $table)
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}' . $table,
			array()
		);

		list ($count) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$counts[$label] = (int) $count;
	}

	return $counts;
}

?>