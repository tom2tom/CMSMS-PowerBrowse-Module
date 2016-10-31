<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class RecordTasks
{
	/**
	DeleteRecord:
	@record_id: single identifier or array of such
	Returns: boolean indicating success
	*/
	public function DeleteRecord($record_id)
	{
		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwbr_record WHERE record_id';
		$db = \cmsms()->GetDb();
		if (is_array($record_id)) {
			$fillers = str_repeat('?,',count($record_id)-1);
			$sql .= ' IN('.$fillers.'?)';
			$args = $record_id;
		} else {
			$sql .= '=?';
			$args = array($record_id);
		}
		return Utils::SafeExec($sql,$args);
	}

	/**
	AddRecord:
	@form_id: identifier
	@notify: boolean, whether to issue notice about added record
	Returns: boolean indicating success
	*/
/*	public function AddRecord($form_id, $notify)
	{
		$pre = \cms_db_prefix();
		$sql = 'INSERT INTO '.$pre.'module_pwbr_record TODO';
//		$db = \cmsms()->GetDb();
		//TODO
		$ret = Utils::SafeExec($sql,$args);
		if ($ret && $notify) {
			//send notice
		}
		return $ret;
	}
*/
}
