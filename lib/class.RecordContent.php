<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/PWFBrowse
*/

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
	@rounds: optional no. of key-stretches, default 0
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	 Each member of @data is array:
	 [0] = (public) title
	 [1] = value
	 [2] (maybe) = extra stuff e.g. 'stamp' flag
	Returns: boolean indicating success
	*/
	public function Insert(&$mod, $pre, $browser_id, $form_id, $stamp, &$data, $rounds=0, &$cfuncs=NULL)
	{
		//insert fake field with read-only key and datetime marker
		$store = ['_s'=>[0=>$mod->Lang('title_submitted'),1=>$stamp,'dt'=>'']] + $data;
		if ($cfuncs == NULL) {
			$cfuncs = new Crypter($mod);
		}
		$cont = $cfuncs->encrypt_value(serialize($store), $rounds);
		unset($store);
		return Utils::SafeExec('INSERT INTO '.$pre.'module_pwbr_record (browser_id,form_id,rounds,contents) VALUES (?,?,?,?)',
			[$browser_id, $form_id, $rounds, $cont]);
	}

	/**
	Update:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored, or if @raw=TRUE, serialized form-data
	@stamp: optional boolean, whether to skip adding a modification-time, default FALSE
	@raw: optional boolean, whether to skip serialization & encryption of @data, default FALSE
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	Returns: boolean indicating success
	*/
	public function Update(&$mod, $pre, $record_id, &$data, $stamp=FALSE, $raw=FALSE, &$cfuncs=NULL)
	{
		if ($raw) {
			$cont = $data;
		} else {
			if ($stamp) {
				$store = $data;
			} else {
				//prepend/update fake field with read-only key and datetime marker
				$stamp = time();
				$store = ['_m'=>[0=>$mod->Lang('title_modified'),1=>$stamp,'dt'=>'']] + $data;
			}
			if ($cfuncs == NULL) {
				$cfuncs = new Crypter($mod);
			}
// TODO set rounds per browser-records-count
			$cont = $cfuncs->encrypt_value(serialize($store)); //default (aka 0) rounds
			unset($store);
		}
		return Utils::SafeExec('UPDATE '.$pre.'module_pwbr_record SET rounds=0,contents=? WHERE record_id=?',
			[$cont, $record_id]);
	}

	/**
	@mod: reference to PWFBrowse module object
	@rounds: number of key-stretches
	@source: string to be decrypted
	@raw: optional boolean, whether to skip unserialization of decrypted value, default FALSE
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	Must be compatible with self::Insert/Update
	*/
	public function Decrypt(&$mod, $rounds, $source, $raw=FALSE, &$cfuncs=NULL)
	{
		if ($source) {
			if ($cfuncs == NULL) {
				$cfuncs = new Crypter($mod);
			}
			$decrypted = $cfuncs->decrypt_value($source, $rounds);
			if ($decrypted) {
				if ($raw) {
					return $decrypted;
				} else {
					return unserialize($decrypted);
				}
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
	 [0] = T/F success-indicator
	 [1] = array of data or error message
	*/
	public function Load(&$mod, $pre, $record_id)
	{
		$data = Utils::SafeGet(
		'SELECT rounds,contents FROM '.$pre.'module_pwbr_record WHERE record_id=?',
			[$record_id], 'row');
		if ($data) {
			$browsedata = self::Decrypt($mod, $data['rounds'], $data['contents']);
			if ($browsedata) {
				return [TRUE,$browsedata];
			}
			$errkey = 'error_data';
		} else {
			$errkey = 'error_database';
		}
		return [FALSE,$mod->_PrettyMessage($errkey, FALSE)];
	}
}
