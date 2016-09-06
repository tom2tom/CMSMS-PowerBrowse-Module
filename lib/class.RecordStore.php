<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class RecordStore
{
	/*
	@mod: reference to PWFBrowse module object
	@source: string to be encrypted
	Must be compatible with RecordLoad::Decrypt()
	*/
	private function Encrypt(&$mod,$source)
	{
		return Utils::encrypt_value($mod,$source);
	}

	/**
	Insert:
	@browser_id: identifier of browser to which the data belong
	@form_id: identifier of form from which the data are sourced (<0 for FormBrowser forms)
	@stamp: timestamp for form submission
	@data: reference to array of plaintext form-data to be stored
	@db: reference to database connection object
	@pre: table-names prefix
	Returns: boolean indicating success
	*/
	public function Insert($browser_id,$form_id,$stamp,&$data,&$mod,&$db,$pre)
	{
		$when = date('Y-m-d H:i:s',$stamp);
		$cont = self::Encrypt($mod,serialize($data));
		return Utils::SafeExec('INSERT INTO '.$pre.
		'module_pwbr_record (browser_id,form_id,submitted,contents) VALUES (?,?,?,?)',
			array($browser_id,$form_id,$when,$cont));
	}

	/**
	Update:
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored
	@db: reference to database connection object
	@pre: table-names prefix
	Returns: boolean indicating success
	*/
	public function Update($record_id,&$data,&$mod,&$db,$pre)
	{
		$when = date('Y-m-d H:i:s');
		$cont = self::Encrypt($mod,serialize($data));
		return Utils::SafeExec('UPDATE '.$pre.
		'module_pwbr_record SET submitted=?,contents=? WHERE record_id=?',
			array($when,$cont,$record_id));
	}

}