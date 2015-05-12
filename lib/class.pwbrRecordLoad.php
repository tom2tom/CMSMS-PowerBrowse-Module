<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwbrRecordLoad
{
	/*
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	Must be compatible with pwbrRecordStore::Encrypt()
	*/
	public function Decrypt($source,$pass_phrase)
	{
		$decrypt = explode('|', $source.'|');
		$decoded = base64_decode($decrypt[0]);
		$iv = base64_decode($decrypt[1]);
		if(strlen($iv) !== mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC))
			return FALSE;
		$key = hash('sha256',$pass_phrase);
		$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$decoded,MCRYPT_MODE_CBC,$iv));
		$mac = substr($decrypted,-64);
		$decrypted = substr($decrypted,0,-64);
		$calcmac = hash_hmac('sha256',$decrypted,substr($key,-32));
		if($calcmac !== $mac)
			return FALSE;
		$decrypted = unserialize($decrypted);
		return $decrypted;
	}

	/**
	Load:
	@record_id: identifier of record to retrieve
	@pass: refrence to password for data decryption, or FALSE
	@mod: optional reference to PowerBrowse module (for error message), default NULL
	@db: optional reference to database connection object, default NULL
	@pre: optional table-names prefix, default ''
	Returns: 2-member array, in which 1st is submissiondate/time or FALSE,
		2nd is array of data or error message
	*/
	public function Load($record_id,&$pass,&$mod=NULL,&$db=NULL,$pre='')
	{
		if(!$db)
			$db = cmsms()->GetDb();
		if(!$pre)
			$pre = cms_db_prefix();
		$row = $db->GetRow(
		'SELECT submitted,contents FROM '.$pre.'module_pwbr_record WHERE record_id=?',
		array($record_id));
		if($row)
		{
			$formdata = ($pass) ?
				self::Decrypt($row['contents'],$pass):
				unserialize($row['contents']);
			if($formdata)
				return array($row['submitted'],$formdata);
			$errkey = 'error_data';
		}
		else
		{
			$errkey = 'error_database';
		}
		if(!$mod)
			$mod = cms_utils::get_module('PowerBrowse');
		return array(FALSE,$mod->PrettyMessage($errkey,FALSE));
	}
}

?>
