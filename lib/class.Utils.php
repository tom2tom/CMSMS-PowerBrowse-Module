<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class Utils
{
	const ENC_ROUNDS = 1000;

	/**
	SafeGet:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command
	@args: array of arguments for @sql
	@mode: optional type of get - 'one','row','col','assoc' or 'all', default 'all'
	Returns: boolean indicating successful completion
	*/
	public static function SafeGet($sql,$args,$mode='all')
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while($nt > 0)
		{
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			switch ($mode) {
			 case 'one':
				$ret = $db->GetOne($sql,$args);
				break;
			 case 'row':
				$ret = $db->GetRow($sql,$args);
				break;
			 case 'col':
				$ret = $db->GetCol($sql,$args);
				break;
			 case 'assoc':
				$ret = $db->GetAssoc($sql,$args);
				break;
			 default:
				$ret = $db->GetArray($sql,$args);
				break;
			}
			if ($db->CompleteTrans())
				return $ret;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	SafeExec:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command, or array of them
	@args: array of arguments for @sql, or array of them
	Returns: boolean indicating successful completion
	*/
	public static function SafeExec($sql,$args)
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while($nt > 0)
		{
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i=>$cmd)
					$db->Execute($cmd,$args[$i]);
			} else
				$db->Execute($sql,$args);
			if ($db->CompleteTrans())
				return TRUE;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	GetBrowserIDForRecord:
	@record_id: record identifier
	*/
	public static function GetBrowserIDForRecord($record_id)
	{
		$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_pwbr_record WHERE record_id=?';
		return self::SafeGet($sql,array($record_id),'one');
	}

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
		if ($internal)
			$sql = 'SELECT form_name FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
		else
			$sql = 'SELECT name FROM '.$pre.'module_pwf_form WHERE form_id=?';
		return $db->GetOne($sql,array($form_id));
	}

	/**
	GetUploadsPath:
	@mod: reference to current PWFBrowse module object
	Returns: absolute path string or false
	*/
	public static function GetUploadsPath(&$mod)
	{
		$config = cmsms()->GetConfig();
		$up = $config['uploads_path'];
		if ($up) {
			$rp = $mod->GetPreference('uploads_path');
			if ($rp)
				$up .= DIRECTORY_SEPARATOR.$rp;
			if (is_dir($up))
				return $up;
		}
		return FALSE;
	}

	/**
	GetUploadsUrl:
	@mod: reference to current PWFBrowse module object
	Returns: absolute url string or false
	*/
	public static function GetUploadsUrl(&$mod)
	{
		$config = cmsms()->GetConfig();
		$key = (empty($_SERVER['HTTPS'])) ? 'uploads_url':'ssl_uploads_url';
		$up = $config[$key];
		if ($up) {
			$rp = $mod->GetPreference('uploads_path');
			if ($rp) {
				$rp = str_replace('\\','/',$rp);
				$up .= '/'.$rp;
			}
			return $up;
		}
		return FALSE;
	}

	/**
	encrypt_value:
	@mod: reference to current module object
	@value: string to encrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty
	*/
	public static function encrypt_value(&$mod,$value,$passwd=FALSE,$based=FALSE)
	{
		if ($value) {
			if (!$passwd) {
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if ($passwd && $mod->havemcrypt) {
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->encrypt($value,$passwd);
				if ($based)
					$value = base64_encode($value);
			} else
				$value = self::fusc($passwd.$value);
		}
		return $value;
	}

	/**
	decrypt_value:
	@mod: reference to current module object
	@value: string to decrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_decode the value, default FALSE
	Returns: decrypted @value, or just @value if it's empty
	*/
	public static function decrypt_value(&$mod,$value,$passwd=FALSE,$based=FALSE)
	{
		if ($value) {
			if (!$passwd) {
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if ($passwd && $mod->havemcrypt) {
				if ($based)
					$value = base64_decode($value);
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->decrypt($value,$passwd);
			} else
				$value = substr(strlen($passwd),self::unfusc($value));
		}
		return $value;
	}

	/**
	fusc:
	@str: string or FALSE
	obfuscate @str
	*/
	public static function fusc($str)
	{
		if ($str) {
			$s = substr(base64_encode(md5(microtime())),0,5);
			return $s.base64_encode($s.$str);
		}
		return '';
	}

	/**
	unfusc:
	@str: string or FALSE
	de-obfuscate @str
	*/
	public static function unfusc($str)
	{
		if ($str) {
			$s = base64_decode(substr($str,5));
			return substr($s,5);
		}
		return '';
	}

	/**
	ProcessTemplate:
	@mod: reference to current PWFBrowse module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: string, processed template
	*/
	public static function ProcessTemplate(&$mod,$tplname,$tplvars,$cache=TRUE)
	{
		global $smarty;
		if ($mod->before20) {
			$smarty->assign($tplvars);
			return $mod->ProcessTemplate($tplname);
		} else {
			if ($cache) {
				$cache_id = md5('pwbr'.$tplname.serialize(array_keys($tplvars)));
				$lang = CmsNlsOperations::get_current_language();
				$compile_id = md5('pwbr'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),$cache_id,$compile_id,$smarty);
				if (!$tpl->isCached())
					$tpl->assign($tplvars);
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),NULL,NULL,$smarty,$tplvars);
			}
			return $tpl->fetch();
		}
	}
}
