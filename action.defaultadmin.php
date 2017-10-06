<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/powerbrowse
*/

$padmin = $this->_CheckAccess('admin');
$pmod = $this->_CheckAccess('modify');
$pview = $this->_CheckAccess('view');
if (!($padmin || $pmod || $pview)) {
	exit;
}

$asyncpw = '';
if (isset($params['submit'])) {
	if ($padmin) {
		$this->SetPreference('date_format', trim($params['date_format']));
		$this->SetPreference('export_file', !empty($params['export_file']));
		$this->SetPreference('export_file_encoding', trim($params['export_file_encoding']));
		$this->SetPreference('list_cssfile', trim($params['list_cssfile']));
		$this->SetPreference('onchange_notices', !empty($params['onchange_notices']));
		$this->SetPreference('owned_forms', !empty($params['owned_forms']));
		$this->SetPreference('strip_on_export', !empty($params['strip_on_export']));
		$this->SetPreference('time_format', trim($params['time_format']));
		$t = trim($params['uploads_dir']);
		if ($t && $t[0] == DIRECTORY_SEPARATOR) {
			$t = substr($t, 1);
		}
		if ($t) {
			$fp = $config['uploads_path'];
			if ($fp && is_dir($fp)) {
				$fp = $fp.DIRECTORY_SEPARATOR.$t;
				if (!(is_dir($fp) || mkdir($fp, 0644))) {
					$t = '';
				}
			} else {
				$t = '';
			}
		}
		$this->SetPreference('uploads_dir', $t);

		$key = 'rounds_factor';
		$i = $params[$key] + 0;
		if ($i < 0.01) {
			$i = 0.01;
		} elseif ($i > 15) {
			$i = 15;
		}
		$oldrounds = $this->GetPreference($key) + 0;
		$rehash = $oldrounds != $i;
		if ($rehash) {
			$this->SetPreference($key, $i);
		}

		$asyncpw = $this->Lang('pending_password');

		$cfuncs = new PWFBrowse\Crypter($this);
		$key = PWFBrowse\Crypter::MKEY;
		$oldpw = $cfuncs->decrypt_preference($key);
		$newpw = trim($params[$key]);
		if ($oldpw != $newpw && $newpw != $asyncpw) {
			$rehash = TRUE;
		} else {
			$asyncpw = '';
		}

		if ($rehash) {
			$pre = cms_db_prefix();
			$i = $db->GetOne('SELECT COUNT(1) FROM '.$pre.'module_pwbr_record');
			if ($i > 0) {
				if ($oldpw != $newpw) {
					//new P/W id = unique, 1-byte, > 0 (i.e. not default)
					$i = $db->GenID($pre.'module_pwbr_seq');
					$i &= 0xff;
					if ($i === 0) {
						$i = 1;
					}
		 			$cfuncs->encrypt_preference('newpass'.$i, $newpw);
					//remember, for later cleanup
					$t = $this->GetPreference('newpasses');
					if ($t) {
						$t .= ','.$i;
					} else {
						$t = ''.$i;
					}
					$this->SetPreference('newpasses', $t);
					$sql = 'UPDATE '.$pre.'module_pwbr_record SET newpass='.$i;
					$db->Execute($sql);
				}
				//initiate async update
				$funcs = new PWFBrowse\RecordContent();
				$funcs->StartUpdate($this);
			} elseif ($oldpw != $newpw) {
	 			$cfuncs->encrypt_preference($key, $newpw);
			}
		}
		$params['message'] = $this->_PrettyMessage('prefs_updated');
	}
	$params['active_tab'] = 'settings';
} elseif (isset($params['cancel'])) {
	$params['active_tab'] = 'settings';
}

$utils = new PWFBrowse\Utils();
$tplvars = [];

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.defaultadmin.php';

$utils->Generate($this, 'adminpanel.tpl', $tplvars, $jsincs, $jsfuncs, $jsloads);
