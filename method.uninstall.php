<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

//NB caller must be very careful that top-level dir is valid!
function delTree($dir)
{
	$files = array_diff(scandir($dir), ['.', '..']);
	if ($files) {
		foreach ($files as $file) {
			$fp = $dir.DIRECTORY_SEPARATOR.$file;
			if (is_dir($fp)) {
				if (!delTree($fp)) {
					return FALSE;
				}
			} else {
				unlink($fp);
			}
		}
		unset($files);
	}
	return rmdir($dir);
}

if (!$this->_CheckAccess('admin')) {
	exit;
}

$dict = NewDataDictionary($db);
$pre = cms_db_prefix();

$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_browser');
$dict->ExecuteSQLArray($sqlarray);

$sql = $dict->DropIndexSQL('idx_fieldbrowser', $pre.'module_pwbr_field');
$dict->ExecuteSQLArray($sql);
$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_field');
$dict->ExecuteSQLArray($sqlarray);

$sql = $dict->DropIndexSQL('idx_recordbrowser', $pre.'module_pwbr_record');
$dict->ExecuteSQLArray($sql);
$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_record');
$dict->ExecuteSQLArray($sqlarray);

// remove permissions
$this->RemovePermission('ModifyPwBrowsers');
$this->RemovePermission('ModifyPwFormData');
$this->RemovePermission('ViewPwFormData');

$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$upd = $this->GetPreference('uploads_dir');
	if ($upd) {
		$fp = cms_join_path($fp, $upd);
		if ($fp && is_dir($fp)) {
			delTree($fp);
		}
	}
}
// remove preferences
$this->RemovePreference();

// remove disposer
$pfmod = $this->GetModuleInstance('PWForms');
if ($pfmod) {
	$fp = cms_join_path($this->GetModulePath(), 'lib', 'class.FormBrowser.php');
	$pfmod->DeregisterField($fp);
	unset($pfmod);
}
