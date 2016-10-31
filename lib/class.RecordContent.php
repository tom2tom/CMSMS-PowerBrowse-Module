<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class RecordContent
{
	/**
	Insert:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@browser_id: identifier of browser to which the data belong
	@form_id: identifier of form from which the data are sourced (<0 for FormBrowser forms)
	@stamp: timestamp for form submission
	@data: reference to array of plaintext form-data to be stored
	 Each member of @data is array:
	 [0] = (public) title
	 [1] = value
	 [2] (maybe) = extra stuff e.g. 'stamp' flag
	Returns: boolean indicating success
	*/
	public function Insert(&$mod, $pre, $browser_id, $form_id, $stamp, &$data)
	{
		//insert fake field
		$store = array('submitted'=>array($mod->Lang('title_submitted'),$stamp,'stamp')) + $data;
		$cont = Utils::encrypt_value($mod,serialize($store));
		unset($store);
		return Utils::SafeExec('INSERT INTO '.$pre.'module_pwbr_record (browser_id,form_id,contents) VALUES (?,?,?)',
			array($browser_id,$form_id,$cont));
	}

	/**
	Update:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored, or if @raw=TRUE, serialized form-data
	@raw: optional boolean, whether to skip serialization of @data, default FALSE
	Returns: boolean indicating success
	*/
	public function Update(&$mod, $pre, $record_id, &$data, $raw=FALSE)
	{
		if ($raw) {
			$cont = $data;
		} else {
			//insert/update fake field
			$stamp = time();
			$store = array('modified'=>array($mod->Lang('title_modified'),$stamp,'stamp')) + $data;
			$cont = Utils::encrypt_value($mod,serialize($store));
			unset($store);
		}
		return Utils::SafeExec('UPDATE '.$pre.'module_pwbr_record SET contents=? WHERE record_id=?',
			array($cont,$record_id));
	}

	/*
	@mod: reference to PWFBrowse module object
	@source: string to be decrypted
	@raw: optional boolean, whether to skip unserialization of decrypted value, default FALSE
	Must be compatible with self::Insert/Update
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
	 [0] = submission timestamp or FALSE
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