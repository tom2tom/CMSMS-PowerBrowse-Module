<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/powerbrowse
*/

if (!$this->_CheckAccess('modify')) {
	exit;
}

switch ($oldversion) {
/* case '0.8':
	$cfuncs = new PWFBrowse\CryptInit($this);
	$key = 'masterpass';
	$s = base64_decode($this->GetPreference($key));
	$t = $config['ssl_url'].$this->GetModulePath();
	$val = hash('crc32b',$this->GetPreference('nQCeESKBr99A').$t);
	$pw = $cfuncs->decrypt($s,$val);
	if (!$pw) {
		$pw = base64_decode('');
	}
	$this->RemovePreference($key);
	$this->RemovePreference('nQCeESKBr99A');
	$cfuncs->init_crypt();
	$cfuncs->encrypt_preference(PWFBrowse\Crypter::MKEY,$pw);
*/
}
