<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

//functions involving FormBrowser and/or FormBuilder modules (assumed present)

namespace PWFBrowse;

class Transition
{
	//FormBrowser/Builder-module table use here
	public function GetBrowsersSummary()
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT BR.*,F.name as form_name,COALESCE (C.count,0) AS record_count FROM {$pre}module_fbr_browser BR
INNER JOIN {$pre}module_fb_form F ON BR.form_id=F.form_id
LEFT JOIN (SELECT form_id, COUNT(*) as count FROM {$pre}module_fb_formbrowser GROUP BY form_id) C ON BR.form_id=C.form_id
ORDER BY BR.name
EOS;
		$db = \cmsms()->GetDb();
		return $db->GetArray($sql);
	}

	/*
	FormBrowser/Builder-module table use here
	Returns: 2-member array:
	 [0] = no. of browsers processed
	 [1] = no. of browsers skipped due to no data
	*/
	public function ImportBrowsers(&$mod)
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT B.browser_id,B.form_id,B.name,F.name AS formname FROM {$pre}module_fbr_browser B
LEFT JOIN {$pre}module_fb_form F ON B.form_id=F.form_id
ORDER BY B.browser_id
EOS;
		$db = \cmsms()->GetDb();
		$olds = $db->GetArray($sql);
		if ($olds) {
			$oc = count($olds);
			$sql = 'INSERT INTO '.$pre.'module_pwbr_browser
(browser_id,form_id,name,form_name) VALUES (?,?,?,?)';
			$renums = array();
			foreach ($olds as $row) {
				$bid = $db->GenID($pre.'module_pwbr_browser_seq');
				$oldid = (int)$row['browser_id'];
				if (self::Get_Data($mod,$db,$pre,$bid,$oldid,$row['form_id'])) {
					$renums[$bid] = $oldid;
					$db->Execute($sql,array($bid,-$row['form_id'],$row['name'],$row['formname'])); //form id < 0 signals FormBuilder form
				}
			}
			if ($renums) {
				foreach ($renums as $new=>$old) {
					self::Get_Attrs($mod,$db,$pre,$old,$new);
				}
				$ic = count($renums);
				return array($ic,$oc-$ic);
			}
			return array(0,$oc);
		}
		return array(0,0);
	}

/* example data from FormBuilder::GetSortedResponses()
$count = string '4' (length=1)
$names = array (size=3)
  231 => string 'Your name' (length=9)
  232 => string 'How can we contact you?' (length=23)
  233 => string 'What do you wish to change?' (length=27)
$vals = array (size=whatever)
  0 =>
    object(stdClass)[56]
      public 'id' => string '737' (length=3)
      public 'user_approved' => string '' (length=0)
      public 'admin_approved' => string '' (length=0)
      public 'submitted' => string '22 March 2015' (length=13)
      public 'user_approved_date' => string '' (length=0)
      public 'admin_approved_date' => string '' (length=0)
      public 'submitted_date' => int 1427019558
      public 'xml' => string '<?xml version="1.0" encoding="utf-8"?>
<response form_id="21">
	<field id="231"
		type="TextField"
		validation_type="none"
		order_by="1"
		required="1"
		hide_label="0"
		display_in_submission="1">
			<field_name><![CDATA[Your name]]></field_name>
			<options>
			<option name="length"><![CDATA[80]]></option>
			<option name="readonly"><![CDATA[0]]></option>
			<option name="field_alias"><![CDATA[]]></option>
			<option name="css_class"><![CDATA[]]></option>
			<option name="helptext"><![CDATA[]]></option>'... (length=5439)
      public 'fields' =>
        array (size=3)
          231 => string 'DAVID POUND' (length=11)
          232 => string 'PH 94597059 Or email davidjunefrank@bigpond.com' (length=47)
          233 => string 'No longer availabe for competition due to shoulder injury.' (length=58)
      public 'fieldsbyalias' =>
        array (size=1)
          '' => string 'No longer availabe for competition due to shoulder injury.' (length=58)
  1 =>
  and so on
*/
	//this saves nothing and returns FALSE if there's no recorded response to interrogate
	private function Get_Data(&$mod, &$db, $pre, $newbid, $oldbid, $oldfid)
	{
		$newfid = -(int)$oldfid; //id < 0 signals FormBuilder form
		$fb = \cms_utils::get_module('FormBuilder');
		$flds = array();
		$parms = array();
//GetSortedResponses($form_id,$start_point,$number=100,$admin_approved=false,$user_approved=false,$field_list=array(),$dateFmt='d F y',&$params)
		list($count,$names,$details) = $fb->GetSortedResponses($oldfid,	-1,-1,
			FALSE,FALSE,$flds,'Y-m-d',$parms);
		if ($count > 0) {
			$funcs = new RecordStore();
			foreach ($details as &$one) {
				$browsedata = array();
				foreach ($one->fields as $fid=>$fval)
					$browsedata[-$fid] = array($names[$fid],$fval); //id < 0 signals FormBuilder field
				$funcs->Insert($mod,$pre,$newbid,$newfid,$one->submitted_date,$browsedata);
			}
			unset($one);
			return TRUE;
		}
		return FALSE;
	}

	private function Get_Attrs(&$mod, &$db, $pre, $oldbid, $newbid)
	{
		$sql = <<<EOS
SELECT * FROM {$pre}module_fbr_browser_attr WHERE browser_id=?
AND (name='admin_list_fields' OR name='admin_rows_per_page')
ORDER BY browser_attr_id
EOS;
		$data = $db->GetArray($sql,array($oldbid));
		if ($data) {
			$sql = 'UPDATE '.$pre.'module_pwbr_browser SET pagerows=? WHERE browser_id=?';
			foreach ($data as &$row) {
				switch ($row['name']) {
				case 'admin_list_fields':
					self::Get_Fields($mod,$db,$pre,$oldbid,$newbid,$row['value']);
					break;
				case 'admin_rows_per_page':
					$db->Execute($sql,array((int)$row['value'],$newbid));
					break;
				}
			}
			unset($row);
		}
	}

	//$value like 45,0:46,1:47,2:48,3:49,4:50,5:51,6:52,7:53,8:54,9:55,10:57,11:56,12:58,13:246,-1:247,-1
	private function Get_Fields(&$mod, &$db, $pre, $oldbid, $newbid, &$value)
	{
		$sql = <<<EOS
SELECT F.field_id,F.name FROM {$pre}module_fb_field F
JOIN {$pre}module_fbr_browser B ON F.form_id=B.form_id
WHERE B.browser_id=?
ORDER BY F.field_id
EOS;
		$names = $db->GetAssoc($sql,array($oldbid));
		$parts = explode(':',$value);
		$i = 1;
		$l = count($parts);
		$sql = 'INSERT INTO '.$pre.'module_pwbr_field
(browser_id,name,shown,frontshown,sorted,order_by,form_field) VALUES (?,?,?,?,?,?,?)';
		//record fake field for date/time submitted
		$nm = $mod->Lang('title_submit_when');
		$db->Execute($sql,array($newbid,$nm,0,0,0,-1,0));
		foreach ($parts as $one) {
			list($indx,$order) = explode(',',$one);
			if ($order != -1) {
				$see = 1;
				$order = (int)$order;
			} else {
				$see = 0;
				$order = $l+$i;
			}
			$nm = ($indx && !empty($names[$indx])) ? $names[$indx] : 'unnamed-'.$oldbid.':'.$i;
			$db->Execute($sql,array($newbid,$nm,$see,0,0,$order,-$one['field_id'])); //id < 0 signals FormBuilder field
			$i++;
		}
	}
}
