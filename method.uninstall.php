<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

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
$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_seq');
$dict->ExecuteSQLArray($sqlarray);

// remove permissions
$this->RemovePermission('ModifyPwBrowsers');
$this->RemovePermission('ModifyPwFormData');
$this->RemovePermission('ViewPwFormData');

$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$ud = $this->GetPreference('uploads_dir');
	if ($ud) {
		$fp .= DIRECTORY_SEPARATOR.$ud;
		if ($fp && is_dir($fp)) {
			recursive_delete($fp);
		}
	}
}

// remove jobs-queue (if any)
$handle = $this->GetPreference('Qhandle');
if ($handle) {
	$funcs = new Async\Qface();
	$funcs->DropQ($handle);
}
// BUT DON'T remove jobs-queue processor
/*
$cache = ;
if ($cache) {
	$cache->cleanall(PWFBrowse::ASYNCSPACE);
}
$mutex = ;
if ($mutex) {
	$mutex->cleanall(PWFBrowse::ASYNCSPACE);
}
*/

// remove preferences
$this->RemovePreference();

// remove form-disposer
$pfmod = $this->GetModuleInstance('PWForms');
if ($pfmod) {
	$fp = cms_join_path($this->GetModulePath(), 'lib', 'class.FormBrowser.php');
	$pfmod->DeregisterField($fp);
	unset($pfmod);
}
