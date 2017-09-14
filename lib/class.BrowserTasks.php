<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple..org/projects/pwfbrowse
*/

namespace PWFBrowse;

class BrowserTasks
{
	/**
	StoreBrowser:
	@mod: reference to current PWFBrowse class object
	@params: reference to array of parameters for use here e.g.
		array
		  'browser_id' => string '9'
		  'browser_name' => string 'Change Requests'
		  'browser_pagerows' => string '10'
		  'browser_owner' => string '19' <<< MAYBE ABSENT
		  'frontshown' =>
			array
			  0 => string '59'
		  'orders' =>
			array (size=3)
			  0 => string '59'
			  1 => string '58'
			  2 => string '60'
		  'shown' =>
			array
			  0 => string '59'
		  'sortable' =>
			array
			  0 => string '58'
			  1 => string '60'
		  'active_tab' => string 'listtab'
		  'apply' => string 'Apply'
		  'action' => string 'open_browser'
	 Returns: value returned by the last-performed SQL insertion
	*/
	public function StoreBrowser(&$mod, &$params)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$browser_id = (int)$params['browser_id'];
		if (isset($params['browser_owner'])) {
			$owner = (int)$params['browser_owner'];
		} else {
			$owner = 0;
		}
		$sql = 'UPDATE '.$pre.'module_pwbr_browser SET name=?,owner=?,pagerows=? WHERE browser_id=?';
		$db->Execute($sql,
			[trim($params['browser_name']), $owner, (int)$params['browser_pagerows'], $browser_id]);
		$sql = 'UPDATE '.$pre.'module_pwbr_field set shown=?,frontshown=?,sorted=?,order_by=? WHERE field_id=?';
		foreach ($params['orders'] as $indx=>$fid) {
			$show = isset($params['shown']) && in_array($fid, $params['shown']);
			$fshow = isset($params['frontshown']) && in_array($fid, $params['frontshown']);
			$sort = isset($params['sortable']) && in_array($fid, $params['sortable']);
			$db->Execute($sql, [$show, $fshow, $sort, $indx+1, $fid]);
		}
	}

	/**
	DeleteBrowser:
	@browser_id: identifier of the browser to be removed
	Deletes browser data from tables
	Returns FALSE if no record found in main table
	*/
	public function DeleteBrowser($browser_id)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
		$db->Execute($sql, [$browser_id]);
		if ($db->Affected_Rows() == 0) {
			return FALSE;
		}
		$sql = 'DELETE FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
		if (!Utils::SafeExec($sql, [$browser_id])) {
			return FALSE;
		}
		$sql = 'DELETE FROM '.$pre.'module_pwbr_field WHERE browser_id=?';
		$db->Execute($sql, [$browser_id]);
		return TRUE;
	}

	/**
	AddBrowser:
	@mod: reference to current PWFBrowse module object
	@params: reference to array of parameters
	Returns: id of new browser
	*/
	public function AddBrowser(&$mod, &$params)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$formname = Utils::GetFormNameFromID($params['form_id'], FALSE);
		$db->Execute('INSERT INTO '.$pre.'module_pwbr_browser
(form_id,name,form_name) VALUES (?,?,?)',
			[$params['form_id'], $params['name'], $formname]);
		$newid = $db->Insert_ID();
		$funcs = new FormsIface();
		$list = $funcs->GetBrowsableFields($params['form_id']);
		if ($list) {
			$sql = 'INSERT INTO '.$pre.'module_pwbr_field
(browser_id,name,shown,frontshown,sorted,order_by) VALUES (?,?,?,?,?,?)';
			//record fake fields
			$fieldname = $mod->Lang('title_submitted');
			$db->Execute($sql, [$newid, $fieldname, 1, 0, 1, 1, 0]);
			$fieldname = $mod->Lang('title_modified');
			$db->Execute($sql, [$newid, $fieldname, 0, 0, 1, 2, 0]);
			$ord = 3;
			foreach ($list as &$fieldname) {
				//arbitrary choice about display parameters, here
				$show = ($ord < 8);
				$sort = ($ord < 5);
				$db->Execute($sql, [$newid, $fieldname, $show, $sort, $ord]);
				$ord++;
			}
			unset($fieldname);
		}
		return $newid;
	}

	/**
	CloneBrowser:
	@mod: reference to current PWFBrowse module object
	@params: reference to array of parameters
	Copies browser data except specific records, sets all fields to be displayed
	*/
	public function CloneBrowser(&$mod, &$params)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$browser_id = (int)$params['browser_id'];

		$row = $db->GetRow('SELECT * FROM'.pre.'module_pwbr_browser WHERE browser_id=?', [$browser_id]);
		unset($row['browser_id']);
		$row['name'] = (empty($params['browser_name'])) ?
			Utils::GetBrowserNameFromID($browser_id).' '.$mod->Lang('copy'):
			trim($params['browser_name']);
		$fields = implode(',', array_keys($row));
		$db->Execute('INSERT INTO '.$pre.'module_pwbr_browser ('.$fields.')
VALUES (?,?,?,?,?,?,?)', array_values($row));
		$newid = $db->Insert_ID();

		$list = $db->GetArray('SELECT browser_id,name,shown,frontshown,sorted,order_by FROM '.
			$pre.'module_pwbr_field WHERE browser_id=?', [$browser_id]);
		if ($list) {
			$sql = 'INSERT INTO '.$pre.'module_pwbr_field
(browser_id,name,shown,frontshown,sorted,order_by) VALUES (?,?,?,?,?,?)';
			foreach ($list as $row) {
				$row['browser_id'] = $newid;
//				$row['shown'] = 1;
//				$row['frontshown'] = 0;
				$db->Execute($sql, array_values($row));
			}
		}
	}
}
