<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

$padmin = $this->CheckAccess('admin');
$pmod = $this->CheckAccess('modify');
$pview = $this->CheckAccess('view');
if(!($padmin || $pmod || $pview)) exit;

if($padmin)
{
	if(isset($params['submit']))
	{
		$old = $this->GetPreference('masterpass');
		if($old)
			$old = pwbrUtils::unfusc($oldpw);
		$t = trim($params['masterpass']);
		if($old != $t)
		{
			//re-encrypt all stored records
			$pre = cms_db_prefix();
			$rst = $db->Execute('SELECT record_id,contents FROM '.$pre.'module_pwbr_record');
			if($rst)
			{
				$sql = 'UPDATE '.$pre.'module_pwbr_record SET contents=? WHERE record_id=?';
				while(!$rst->EOF)
				{
					$val = pwbrUtils::decrypt_value($mod,$rst->fields[1],$old);
					$val = pwbrUtils::encrypt_value($mod,$val,$t);
					if(!pwbrUtils::SafeExec($sql,array($val,$rst->fields[0])))
					{
						//TODO handle error
					}
					if(!$rst->MoveNext())
						break;
				}
				$rst->Close();
			}

			if($t)
				$t = pwbrUtils::fusc($t);
			$this->SetPreference('masterpass',$t);
		}
		$this->SetPreference('export_file',!empty($params['export_file']));
		$this->SetPreference('export_file_encoding',trim($params['export_file_encoding']));
		$this->SetPreference('list_cssfile',trim($params['list_cssfile']));
		$this->SetPreference('onchange_notices',!empty($params['onchange_notices']));
		$this->SetPreference('oldmodule_data',!empty($params['oldmodule_data']));
		$this->SetPreference('owned_forms',!empty($params['owned_forms']));
		$this->SetPreference('strip_on_export',!empty($params['strip_on_export']));
		$t = trim($params['uploads_path']);
		if($t && $t[0] == DIRECTORY_SEPARATOR)
			$t = substr($t,1);
		if($t)
		{
			$fp = $config['uploads_path'];
			if($fp && is_dir($fp))
			{
				$fp = $fp.DIRECTORY_SEPARATOR.$t;
				if(!(is_dir($fp) || mkdir($fp,0644)))
					$t = '';
			}
			else
				$t = '';
		}
		$this->SetPreference('uploads_path',$t);

		$params['message'] = $this->PrettyMessage('prefs_updated');
		$params['active_tab'] = 'settings';
	}
	elseif(isset($params['cancel']))
	{
		$params['active_tab'] = 'settings';
	}
}

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.defaultadmin.php';

echo $this->ProcessTemplate('adminpanel.tpl');

?>
