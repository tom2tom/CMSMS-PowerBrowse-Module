<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

$padmin = $this->_CheckAccess('admin');
$pmod = $this->_CheckAccess('modify');
$pview = $this->_CheckAccess('view');
if (!($padmin || $pmod || $pview)) {
	exit;
}

if (isset($params['submit'])) {
	if ($padmin) {
		$t = $params['rounds_factor'] + 0;
		if ($t < 0.1) {
			$t = 0.1;
		} elseif ($t > 20) {
			$t = 20;
		}
		$this->SetPreference('rounds_factor', $t);
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

		$cfuncs = new PWFBrowse\Crypter($this);
		$key = PWFBrowse\Crypter::MKEY;
		$oldpw = $cfuncs->decrypt_preference($key);
		$t = trim($params[$key]);
		if ($oldpw != $t) {
			//re-encrypt all stored records
			$pre = cms_db_prefix();
			$rst = $db->Execute('SELECT record_id,rounds,contents FROM '.$pre.'module_pwbr_record');
			if ($rst) {
				$rounds = (int)($this->GetPreference('rounds_factor') * 100); //update to default rounds (if not already there)
				$sql = 'UPDATE '.$pre.'module_pwbr_record SET rounds='.$rounds.',contents=? WHERE record_id=?';
				while (!$rst->EOF) {
					$val = $cfuncs->decrypt_value($rst->fields['contents'], $rst->fields['rounds'], $oldpw);
					$val = $cfuncs->encrypt_value($val, $rounds, $t);
					if (!PWFBrowse\Utils::SafeExec($sql, [$val, $rst->fields['record_id']])) {
						//TODO handle error
					}
					if (!$rst->MoveNext()) {
						break;
					}
				}
				$rst->Close();
			}
			$cfuncs->encrypt_preference($key, $t);
		}
		$params['message'] = $this->_PrettyMessage('prefs_updated');
	}
	$params['active_tab'] = 'settings';
} elseif (isset($params['cancel'])) {
	$params['active_tab'] = 'settings';
}

$tplvars = [];

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.defaultadmin.php';

$jsall = NULL;
PWFBrowse\Utils::MergeJS($jsincs, $jsfuncs, $jsloads, $jsall);

echo PWFBrowse\Utils::ProcessTemplate($this, 'adminpanel.tpl', $tplvars);
if ($jsall) {
	echo $jsall;
}
