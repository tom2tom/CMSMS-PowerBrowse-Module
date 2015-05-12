<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!$this->CheckAccess('modify')) exit;

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

$this->CreatePermission('ModifyPwBrowsers',$this->Lang('perm_browsers'));
$this->CreatePermission('ModifyPwFormData',$this->Lang('perm_data'));
$this->CreatePermission('ViewPwFormData',$this->Lang('perm_see'));

//use system setting for default date format MAYBE THIS SHOULD BE IN UTILS CLASS
$format = get_site_preference('defaultdateformat');
if($format)
{
	$strftokens = array(
	// Day - no strf eq : S
	'a' => 'D', 'A' => 'l', 'd' => 'd', 'e' => 'j', 'j' => 'z', 'u' => 'N', 'w' => 'w',
	// Week - no date eq : %U, %W
	'V' => 'W',
	// Month - no strf eq : n, t
	'b' => 'M', 'B' => 'F', 'm' => 'm',
	// Year - no strf eq : L; no date eq : %C, %g
	'G' => 'o', 'y' => 'y', 'Y' => 'Y',
	// Full Date / Time - no strf eq : c, r; no date eq : %c
	's' => 'U', 'D' => 'j/n/y', 'F' => 'Y-m-d', 'x' => 'j F Y'
 	);
	$format = str_replace('%','',$format);
	$parts = explode(' ',$format);
	foreach($parts as $i => $fmt)
	{
		if(array_key_exists($fmt, $strftokens))
			$parts[$i] = $strftokens[$fmt];
		else
			unset($parts[$i]);
	}
	$format = implode(' ', $parts);
}
else
	$format = 'd F y';
$this->SetPreference('date_format',$format);
$this->SetPreference('default_phrase',uniqid('Suck it up, crackers! Guess ')); //TODO make this adjustable via UI
$this->SetPreference('export_file',0);
$this->SetPreference('export_file_encoding','ISO-8859-1');
$this->SetPreference('list_cssfile','');
$this->SetPreference('onchange_notices',0);
$this->SetPreference('oldmodule_data',0); //use FormBrowser/Builder data if avaialable
$this->SetPreference('owned_forms',0);	//enable user-specific browsing
$this->SetPreference('strip_on_export',0);
$this->SetPreference('uploads_path',$this->GetName());

?>
