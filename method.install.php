<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

if (!$this->_CheckAccess('modify')) {
	exit;
}

$pfmod = $this->GetModuleInstance('PWForms');
if (!$pfmod) {
	return $this->_PrettyMessage('error_module_forms', FALSE);
}

$taboptarray = ['mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
 'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = NewDataDictionary($db);
$pre = cms_db_prefix();

$flds = '
browser_id I(2) AUTO KEY,
form_id I(2),
name C(256),
form_name C(256),
owner I(4) DEFAULT 0,
pagerows I(2) DEFAULT 10,
flags I(1) DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_browser', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
field_id I(2) AUTO KEY,
browser_id I(2),
name C(256),
shown I(1) DEFAULT 1,
frontshown I(1) DEFAULT 0,
sorted I(1) DEFAULT 0,
order_by I(2) DEFAULT -1,
form_field I(2) DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_field', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
record_id I(4) AUTO KEY,
browser_id I(2),
form_id I(2),
rounds I(2) DEFAULT 0,
flags I(1) DEFAULT 0,
contents B(16364)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_record', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->CreateIndexSQL('idx_recordbrowser', $pre.'module_pwbr_record', 'browser_id');
$dict->ExecuteSQLArray($sqlarray);

$flds = '
id I(2),
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwbr_seq', $flds); //NOT a I(11) standard sequence
$dict->ExecuteSQLArray($sqlarray);
$db->Execute('INSERT INTO '.$pre.'module_pwbr_seq FIELDS(id) VALUES(0)');

$this->CreatePermission('ModifyPwBrowsers', $this->Lang('perm_browsers'));
$this->CreatePermission('ModifyPwFormData', $this->Lang('perm_data'));
$this->CreatePermission('ViewPwFormData', $this->Lang('perm_see'));

$cfuncs = new PWFBrowse\CryptInit($this);
$cfuncs->init_crypt();
$t = substr(str_shuffle(base64_encode(time().$config['root_url'].rand(10000000, 99999999))), 0, 10);
$t = sprintf(base64_decode('Q3JhY2tlcnMgd2lsbCBoYXZlIHRvIGZpZ3VyZSBvdXQgJXMh'), $t);
$cfuncs->encrypt_preference(PWFBrowse\Crypter::MKEY, $t);

$this->SetPreference('date_format', 'Y-m-d');
$this->SetPreference('export_file', 0);
$this->SetPreference('export_file_encoding', 'ISO-8859-1');
$this->SetPreference('list_cssfile', '');
$this->SetPreference('onchange_notices', 0);
$this->SetPreference('owned_forms', 0);	//enable user-specific browsing
$this->SetPreference('rounds_factor', 5);
$this->SetPreference('strip_on_export', 0);
$this->SetPreference('time_format', 'H:i:s');
$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$ud = $this->GetName();
	$fp = $fp.DIRECTORY_SEPARATOR.$ud;
	if (!(is_dir($fp) || mkdir($fp, 0777))) { //can't be sure how the server is running
		$ud = '';
	}
} else {
	$ud = '';
}
$this->SetPreference('uploads_dir', $ud);

//install job processor (if not done before)
$funcs = new PWFBrowse\Jobber($this);
$funcs->init();

//install our form-dispose field
$fp = cms_join_path($this->GetModulePath(), 'lib', 'class.FormBrowser.php');
$pfmod->RegisterField($fp);
unset($pfmod);
