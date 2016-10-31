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
	@raw: optional boolean, whether to skip unserialization of decrypted value, default FALSE
	Must be compatible with RecordStore encryption i.e. Utils::encrypt_value($mod,serialize($store))
	*/
	public function Decrypt(&$mod, $source, $raw=FALSE)
	{
		if ($source) {
			$decrypted = Utils::decrypt_value($mod,$source);
			if ($decrypted) {
				if ($raw)
					return $decrypted;
				else
					return unserialize($decrypted);
			}
		}
		return '';
	}

	/**
	Load:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@record_id: identifier of record to retrieve
	Returns: 2-member array:
	 [0] = submissiondate/time or FALSE
	 [1] = array of data or error message
	*/
	public function Load(&$mod, $pre, $record_id)
	{
		$data = Utils::SafeGet(
		'SELECT contents FROM '.$pre.'module_pwbr_record WHERE record_id=?',
			array($record_id),'one');
		if ($data) {
			$formdata = self::Decrypt($mod,$data);
			if ($formdata)
				return array($formdata['submitted'][1],$formdata);
			$errkey = 'error_data';
		} else {
			$errkey = 'error_database';
		}
		return array(FALSE,$mod->_PrettyMessage($errkey,FALSE));
	}
}
