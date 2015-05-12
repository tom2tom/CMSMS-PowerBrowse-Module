<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse
//TODO optimise this for high traffic-volume
class pwbrRecordStore
{
	/*
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	Must be compatible with pwbrRecordLoad::Decrypt()
	*/
	private function Encrypt($source,$pass_phrase)
	{
		$flag = (defined('MCRYPT_DEV_URANDOM')) ? MCRYPT_DEV_URANDOM : MCRYPT_RAND;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),$flag);
		$encrypt = serialize($source);
		$key = hash('sha256', $pass_phrase); // $key is a 64-character hexadecimal string
		$mac = hash_hmac('sha256', $encrypt, substr($key,-32));
		$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$encrypt.$mac,MCRYPT_MODE_CBC,$iv);
		$encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
		return $encoded;
	}

	/**
	Insert:
	@browser_id: identifier of browser to which the data belong
	@form_id: identifier of form from which the data are sourced (<0 for FormBrowser forms)
	@stamp: timestamp for form submission
	@data: reference to array of plaintext form-data to be stored
	@pass: refrence to password for data encryption, or FALSE
	@db: reference to database connection object
	@pre: table-names prefix
	*/
	public function Insert($browser_id,$form_id,$stamp,&$data,&$pass,&$db,$pre)
	{
		$when = date('Y-m-d H:i:s',$stamp);
		$cont = ($pass) ? self::Encrypt($data,$pass) : serialize($data);
		$db->Execute('INSERT INTO '.$pre.
		'module_pwbr_record (browser_id,form_id,submitted,contents) VALUES (?,?,?,?)',
			array($browser_id,$form_id,$when,$cont));
	}

	/**
	Update:
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored
	@pass: refrence to password for data encryption, or FALSE
	@db: reference to database connection object
	@pre: table-names prefix
	*/
	public function Update($record_id,&$data,&$pass,&$db,$pre)
	{
		$when = date('Y-m-d H:i:s');
		$cont = ($pass) ? self::Encrypt($data,$pass) : serialize($data);
		$db->Execute('UPDATE '.$pre.'module_pwbr_record SET submitted=?,contents=? WHERE record_id=?',
			array($when,$cont,$record_id));
	}

}

?>
