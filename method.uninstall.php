<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

//NB caller must be very careful that top-level dir is valid!
function delTree($dir)
{
	$files = array_diff(scandir($dir),array('.','..'));
	if($files)
	{
		foreach($files as $file)
		{
			$fp = $dir.DIRECTORY_SEPARATOR.$file;
			if(is_dir($fp))
			{
			 	if(!delTree($fp))
					return false;
			}
			else
				unlink($fp);
		}
		unset($files);
	}
	return rmdir($dir);
}

if(!$this->CheckAccess('admin')) exit;

$dict = NewDataDictionary($db);
$pre = cms_db_prefix();

$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_browser');
$dict->ExecuteSQLArray($sqlarray);
$db->DropSequence($pre.'module_pwbr_browser_seq');

$sql = $dict->DropIndexSQL('idx_fieldbrowser', $pre.'module_pwbr_field');
$dict->ExecuteSQLArray($sql);
$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_field');
$dict->ExecuteSQLArray($sqlarray);
$db->DropSequence($pre.'module_pwbr_field_seq');

$sql = $dict->DropIndexSQL('idx_recordbrowser', $pre.'module_pwbr_record');
$dict->ExecuteSQLArray($sql);
$sqlarray = $dict->DropTableSQL($pre.'module_pwbr_record');
$dict->ExecuteSQLArray($sqlarray);
$db->DropSequence($pre.'module_pwbr_record_seq');

// remove permissions
$this->RemovePermission('ModifyPwBrowsers');
$this->RemovePermission('ModifyPwFormData');
$this->RemovePermission('ViewPwFormData');

$fp = $config['uploads_path'];
if($fp && is_dir($fp))
{
	$ud = $this->GetPreference('uploads_dir');
	if($ud)
	{
		$fp = $fp.DIRECTORY_SEPARATOR.$ud;
		if($fp && is_dir($fp))
			delTree($fp);
	}
}
// remove preferences
$this->RemovePreference();

?>
