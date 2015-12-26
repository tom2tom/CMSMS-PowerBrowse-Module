<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!$this->CheckAccess('modify')) exit;

$pfmod = $this->GetModuleInstance('PowerForms');
if(!$pfmod)
	return $this->PrettyMessage('error_module',FALSE);

$taboptarray = array('mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
 'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci');
$dict = NewDataDictionary($db);
$pre = cms_db_prefix();

$flds = "
	browser_id I(2) KEY,
	form_id I(2),
	name C(256),
	form_name C(256),
	owner I(4) DEFAULT 0,
	pagerows I(2) DEFAULT 10
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_browser',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$db->CreateSequence($pre.'module_pwbr_browser_seq');

$flds = "
	field_id I(2) AUTO KEY,
	browser_id I(2),
 	name C(256),
	shown I(1) DEFAULT 1,
	sorted I(1) DEFAULT 0,
	order_by I(2) DEFAULT -1,
	form_field I(2) DEFAULT 0
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_field',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwbr_field_seq');
$sqlarray = $dict->CreateIndexSQL('idx_fieldbrowser',$pre.'module_pwbr_field','browser_id');
$dict->ExecuteSQLArray($sqlarray);

$flds = "
	record_id I(4) AUTO KEY,
	browser_id I(2),
	form_id I(2),
	submitted ".CMS_ADODB_DT.",
	contents B
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_record',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwbr_record_seq');
$sqlarray = $dict->CreateIndexSQL('idx_recordbrowser',$pre.'module_pwbr_record','browser_id');
$dict->ExecuteSQLArray($sqlarray);

$flds = "
	flock_id I KEY,
	flock T
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_flock',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$this->CreatePermission('ModifyPwBrowsers',$this->Lang('perm_browsers'));
$this->CreatePermission('ModifyPwFormData',$this->Lang('perm_data'));
$this->CreatePermission('ViewPwFormData',$this->Lang('perm_see'));

$this->SetPreference('masterpass','MmFjNTW1Gak5TdWNrIGl0IHVwLCBjcmFja2VycyEgVHJ5IHRvIGd1ZXNz');
$this->SetPreference('export_file',0);
$this->SetPreference('export_file_encoding','ISO-8859-1');
$this->SetPreference('list_cssfile','');
$this->SetPreference('onchange_notices',0);
$this->SetPreference('oldmodule_data',0); //use FormBrowser/Builder data if avaialable
$this->SetPreference('owned_forms',0);	//enable user-specific browsing
$this->SetPreference('strip_on_export',0);
$fp = $config['uploads_path'];
if($fp && is_dir($fp))
{
	$ud = $this->GetName();
	$fp = $fp.DIRECTORY_SEPARATOR.$ud;
	if(!(is_dir($fp) || mkdir($fp,0644)))
		$ud = '';
}
else
	$ud = '';
$this->SetPreference('uploads_path',$ud);

//install our disposer
$fp = cms_join_path($this->GetModulePath(),'lib','class.pwfFormBrowser.php');
$pfmod->RegisterField($fp);
unset($pfmod);

?>
