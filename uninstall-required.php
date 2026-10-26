<?php
if (!defined('SMF'))
	die('No direct access.');

global $sourcedir;
require_once($sourcedir . '/Subs.php');

// This file is an ordinary <code> uninstall action, so SMF runs it whether or
// not "Remove all data associated with this modification" is checked.
// Hooks must always be detached before Package Manager removes Better Gallery's
// PHP files.
foreach (array(
	'integrate_actions' => 'bg_integrate_actions',
	'integrate_menu_buttons' => 'bg_integrate_menu_buttons',
	'integrate_admin_areas' => 'bg_integrate_admin_areas',
	'integrate_load_theme' => 'bg_integrate_load_theme',
	'integrate_load_permissions' => 'bg_integrate_load_permissions',
	'integrate_load_illegal_guest_permissions' => 'bg_integrate_load_illegal_guest_permissions',
) as $hook => $function)
	remove_integration_function($hook, $function, true, '$sourcedir/BetterGallery.php');

updateSettings(array('settings_updated' => time()));
