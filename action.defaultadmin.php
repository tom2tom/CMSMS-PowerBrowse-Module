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

if (isset($params['submit'])) {
	if ($padmin) {
		$t = $params['rounds_factor'] + 0;
		if ($t < 0.01) {
			$t = 0.01;
		} elseif ($t > 15) {
			$t = 15;
		}
		$oldrounds = $this->GetPreference('rounds_factor') + 0;
		$rehash = $oldrounds != $t;
		$this->SetPreference('rounds_factor', $t);

		$cfuncs = new PWFBrowse\Crypter($this);
		$key = PWFBrowse\Crypter::MKEY;
		$oldpw = $cfuncs->decrypt_preference($key);
		$t = trim($params[$key]);
		if ($oldpw != $t) {
			$cfuncs->encrypt_preference($key, $t);
			$cfuncs->encrypt_preference($key.'OLD', $oldpw);
			$rehash = TRUE;
		}

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

		if ($rehash) {
			//flag all records for update
			$pre = cms_db_prefix();
			$sql = 'UPDATE '.$pre.'module_pwbr_record SET flags=1';
			//initiate async update
			$funcs = new PWFBrowse\RecordContent();
			$funcs->StartUpdate();
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
