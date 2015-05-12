<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwbrUtils
{
	/**
	GetBrowserNameFromID:
	@browser_id: browser identifier
	*/
	public static function GetBrowserNameFromID($browser_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_pwbr_browser WHERE browser_id=?';
		return $db->GetOne($sql,array($browser_id));
	}

	/**
	GetFormIDFromID:
	@browser_id: browser identifier
	*/
	public static function GetFormIDFromID($browser_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwbr_browser WHERE browser_id=?';
		return $db->GetOne($sql,array($browser_id));
	}

	/**
	GetFormNameFromID:
	@form_id: form identifier
	@internal: optional, default TRUE
	*/
	public static function GetFormNameFromID($form_id,$internal=TRUE)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		if($internal)
			$sql = 'SELECT form_name FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
		else
			$sql = 'SELECT name FROM '.$pre.'module_pwf_form WHERE form_id=?';
		return $db->GetOne($sql,array($form_id));
	}

	/**
	GetUploadsPath:
	@mod: reference to current PowerBrowse module object
	*/
	public static function GetUploadsPath(&$mod)
	{
		$config = cmsms()->GetConfig();
		$up = $config['uploads_path'];
		if($up)
		{
			$rp = $mod->GetPreference('uploads_path');
			if($rp)
				$up .= DIRECTORY_SEPARATOR.$rp;
			if(is_dir($up))
				return $up;
		}
		return FALSE;
	}

	/**
	GetUploadsUrl:
	@mod: reference to current PowerBrowse module object
	*/
	public static function GetUploadsUrl(&$mod)
	{
		$config = cmsms()->GetConfig();
		$up = $config['uploads_url'];
		if($up)
		{
			$rp = $mod->GetPreference('uploads_path');
			if($rp)
			{
				$rp = str_replace('\\','/',$rp);
				$up .= '/'.$rp;
			}
			return $up;
		}
		return FALSE;
	}

}

?>
