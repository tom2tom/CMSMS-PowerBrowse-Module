<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwbrUtils
{
	const ENC_ROUNDS = 10000;

	private static $mxtype = FALSE; //type of mutex in use - 'memcache' etc
	private static $instance = NULL; //'instance' object for mutex class, if needed

	/**
	GetMutex:
	@storage: optional cache-type name, one (or more, ','-separated) of
		auto,memcache,semaphore,file,database, default = 'auto'
	Returns: mutex-object or NULL
	*/
	public static function GetMutex($storage = 'auto')
	{
		$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'mutex'.DIRECTORY_SEPARATOR;
		require($path.'interface.Mutex.php');

		if(self::$mxtype)
		{
			$one = self::$mxtype;
			require($path.$one.'.php');
			$class = 'pwbrMutex_'.$one;
			$mutex = new $class(self::$instance);
			return $mutex;
		}
		else
		{
			if($storage)
				$storage = strtolower($storage);
			else
				$storage = 'auto';
			if(strpos($storage,'auto') !== FALSE)
				$storage = 'memcache,semaphore,file,database';

			$types = explode(',',$storage);
			foreach($types as $one)
			{
				$one = trim($one);
				$class = 'pwbrMutex_'.$one;
				try
				{
					require($path.$one.'.php');
					$mutex = new $class();
				}
				catch(Exception $e)
				{
					continue;
				}
				self::$mxtype = $one;
				if(isset($mutex->instance))
					self::$instance =& $mutex->instance;
				else
					self::$instance = NULL;
				return $mutex;
			}
			return NULL;
		}
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
	GetBrowserIDForRecord:
	@record_id: record identifier
	*/
	public static function GetBrowserIDForRecord($record_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_pwbr_record WHERE record_id=?';
		return $db->GetOne($sql,array($record_id));
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

	/**
	encrypt_value:
	@mod: reference to current module object
	@value: string to encrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default TRUE
	Returns: encrypted @value, or just @value if it's empty
	*/
	public static function encrypt_value(&$mod,$value,$passwd=FALSE,$based=TRUE)
	{
		if($value)
		{
			if(!$passwd)
			{
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if($passwd && $mod->havemcrypt)
			{
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->encrypt($value,$passwd);
				if($based)
					$value = base64_encode($value);
			}
			else
				$value = self::fusc($passwd.$value);
		}
		return $value;
	}

	/**
	decrypt_value:
	@mod: reference to current module object
	@value: string to decrypted, may be empty
	@passwd: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_decode the value, default TRUE
	Returns: decrypted @value, or just @value if it's empty
	*/
	public static function decrypt_value(&$mod,$value,$passwd=FALSE,$based=TRUE)
	{
		if($value)
		{
			if(!$passwd)
			{
				$passwd = self::unfusc($mod->GetPreference('masterpass'));
			}
			if($passwd && $mod->havemcrypt)
			{
				if($based)
					$value = base64_decode($value);
				$e = new Encryption(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC,self::ENC_ROUNDS);
				$value = $e->decrypt($value,$passwd);
			}
			else
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
		if($str)
		{
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
		if($str)
		{
			$s = base64_decode(substr($str,5));
			return substr($s,5);
		}
		return '';
	}

}

?>
