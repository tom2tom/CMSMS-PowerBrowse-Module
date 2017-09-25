<?php
/*
This file is part of CMS Made Simple (C) 2004-2017 Ted Kulp (wishy@users.sf.net)
CMS Made Simple homepage is: http://www.cmsmadesimple.org

This file is free software. You can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the
Free Software Foundation, either version 3 of the License, or (at your
option) any later version.

This file is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY, without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License
(http://www.gnu.org/licenses/licenses.html#AGPL) for more details.
*/

$logfile = '/var/www/html/cmsms/modules/PWFBrowse/lib/my.log'; //DEBUG
//error_log('jobinterface.php @ start'."\n", 3, $logfile);

if (!isset($_REQUEST['mact'])) {
    error_log('jobinterface.php @ exit1'."\n", 3, $logfile);
	exit;
}

$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
require_once $bp.'misc.functions.php';
$ary = explode(',', cms_htmlentities($_REQUEST['mact']), 4);
if (count($ary) != 4 || empty($ary[0]) || empty($ary[2])) {
    error_log('jobinterface.php @ exit2'."\n", 3, $logfile);
	exit;
}

if (!is_file(__DIR__.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$ary[0].DIRECTORY_SEPARATOR.'action.'.$ary[2].'.php')) {
    error_log('jobinterface.php @ exit3'."\n", 3, $logfile);
	exit;
}

 // defines
require_once $bp.'version.php';
define('CONFIG_FILE_LOCATION', __DIR__.DIRECTORY_SEPARATOR.'config.php');
require_once $bp.'classes'.DIRECTORY_SEPARATOR.'class.cms_config.php';
cms_config::get_instance();
define('CMS_SECURE_PARAM_NAME','_sk_');
define('CMS_USER_KEY','_userkey_');

require_once($bp.'classes'.DIRECTORY_SEPARATOR.'class.CmsApp.php'); //autoloader sets $gCms
require_once($bp.'autoloader.php');

$modops = ModuleOperations::get_instance();
$modinst = $modops->get_module_instance($ary[0], '', true);
if ($modinst) {
	$id = $ary[1];
	$params = $modops->GetModuleParameters($id);
	unset($modops); //keep this?
//	error_log('jobinterface.php action '.$ary[2].' parameters: '.serialize($params)."\n", 3, $logfile);

	cms_siteprefs::setup();
//	Events::setup();

	if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
	}
	// fix for IIS (and others)
	if (!isset($_SERVER['REQUEST_URI'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
		}
	}
	// sanitize
	$_SERVER = filter_var_array($_SERVER, FILTER_SANITIZE_STRING);
	$_GET = filter_var_array($_GET, FILTER_SANITIZE_STRING);

//	error_log('jobinterface.php @ 3'."\n", 3, $logfile);

//	$modinst->DoAction($ary[2], $id, $params); FAILS on smarty
//	$smarty = new Smarty_CMS;
//	$modinst->DoActionBase($ary[2], $id, $params, '', $smarty); FAILS
//	error_log('jobinterface.php @ before action'."\n", 3, $logfile);

	$modinst->DoActionJob($ary[2], $id, $params);

	error_log('jobinterface.php @ after action'."\n", 3, $logfile);
}

error_log('jobinterface.php @ end'."\n", 3, $logfile);
exit;
