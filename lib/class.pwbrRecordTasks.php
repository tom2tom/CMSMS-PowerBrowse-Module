<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwbrRecordTasks
{
	/**
	DeleteRecord:
	@record_id: single identifier or array of such
	*/
	public function DeleteRecord($record_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.cms_db_prefix().'module_pwbr_record WHERE record_id';
		if(is_array($record_id))
		{
			$fillers = str_repeat('?,',count($record_id)-1);
			$sql .= ' IN('.$fillers.'?)';
			$args = $record_id;
		}
		else
		{
			$sql .= '=?';
			$args = array($record_id);
		}
		$db->Execute($sql,$args);
	}
}

?>
