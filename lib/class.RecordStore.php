<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class RecordStore
{
	/**
	Insert:
	@browser_id: identifier of browser to which the data belong
	@form_id: identifier of form from which the data are sourced (<0 for FormBrowser forms)
	@stamp: timestamp for form submission
	@data: reference to array of plaintext form-data to be stored
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	Returns: boolean indicating success
	*/
	public function Insert($browser_id, $form_id, $stamp, &$data, &$mod, $pre)
	{
		//insert fake field
		$when = date('Y-m-d H:i:s',$stamp);
		$store = array('submitted'=>array($mod->Lang('title_submit_when'),$when)) + $data;
		$cont = Utils::encrypt_value($mod,serialize($store));
		unset($store);
		return Utils::SafeExec('INSERT INTO '.$pre.
		'module_pwbr_record (browser_id,form_id,contents) VALUES (?,?,?)',
			array($browser_id,$form_id,$cont));
	}

	/**
	Update:
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	Returns: boolean indicating success
	*/
	public function Update($record_id, &$data, &$mod, $pre)
	{
		//insert fake field
		$when = date('Y-m-d H:i:s');
		$store = array('submitted'=>array($mod->Lang('title_submit_when'),$when)) + $data;
		$cont = Utils::encrypt_value($mod,serialize($store));
		unset($store);
		return Utils::SafeExec('UPDATE '.$pre.
		'module_pwbr_record SET contents=? WHERE record_id=?',
			array($cont,$record_id));
	}

}
