<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class RecordLoad
{
	/*
	@mod: reference to PWFBrowse module object
	@source: string to be decrypted
	@getstruct: optional boolean, whether to unserialize decrypted value, default TRUE
	Must be compatible with RecordStore::Encrypt()
	*/
	public function Decrypt(&$mod,$source,$getstruct=TRUE)
	{
		if ($source) {
			$decrypted = Utils::decrypt_value($mod,$source);
			if ($decrypted) {
				if ($getstruct)
					return unserialize($decrypted);
				else
					return $decrypted;
			}
		}
		return '';
	}

	/**
	Load:
	@record_id: identifier of record to retrieve
	@mod: optional reference to PWFBrowse module object, default NULL
	@db: optional reference to database connection object, default NULL
	@pre: optional table-names prefix, default ''
	Returns: 2-member array, in which 1st is submissiondate/time or FALSE,
		2nd is array of data or error message
	*/
	public function Load($record_id,&$mod=NULL,&$db=NULL,$pre='')
	{
		if (!$mod)
			$mod = cms_utils::get_module('PWFBrowse');
		if (!$db)
			$db = cmsms()->GetDb();
		if (!$pre)
			$pre = cms_db_prefix();
		$row = Utils::SafeGet(
		'SELECT submitted,contents FROM '.$pre.'module_pwbr_record WHERE record_id=?',
		array($record_id));
		if ($row) {
			$formdata = self::Decrypt($mod,$row['contents']);
			if ($formdata)
				return array($row['submitted'],$formdata);
			$errkey = 'error_data';
		} else {
			$errkey = 'error_database';
		}
		return array(FALSE,$mod->PrettyMessage($errkey,FALSE));
	}
}
