<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/
//functions involving FormBrowser and/or FormBuilder modules (assumed present)

namespace PWFBrowse;

class Transition
{
/*	//FormBrowser/Builder-module table use here
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
*/
	/**
	MigrateIds:
	@mod: reference to PWFBrowse module object
	@form_id: FormBuilder form_id, may be < 0
	Returns: PowerBrowse form_id corresponding to @form_id, or FALSE
	*/
	public function MigrateIds(&$mod, $form_id)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$converts = self::Get_Converts($db, $pre, $form_id);
		if ($converts) {
			$newfid = (int) reset($converts);
			$sql = 'SELECT record_id,rounds,contents FROM '.$pre.'module_pwbr_record WHERE form_id=?';
			$data = Utils::SafeGet($sql, [$form_id]);
			if ($data) {
				$converts = self::Get_Converts($db, $pre, 0);
				if ($converts) {
					$funcs = new RecordContent();
					foreach ($data as &$one) {
						$olddata = $funcs->Decrypt($mod, $one['rounds'], $one['contents']);
						if ($olddata) {
							$newdata = [];
							foreach ($olddata as $key => $field) {
								if (is_numeric($key) && $key < 0) {
									$key = -$key;
								}
								if (array_key_exists($key, $converts)) {
									$key = (int) $converts[$key];
								}
								$newdata[$key] = $field;
							}
							$funcs->Update($mod, $pre, $one['record_id'], $newdata, TRUE);
						} else {
							//TODO warn user
						}
					}
					unset($one);
				}
			}
			return $newfid;
		}
		return FALSE;
	}

	/**
	ImportBrowsers:
	@mod: reference to PWFBrowse module object
	FormBrowser/Builder-module table use here
	Returns: 2-member array,
	 [0] = no. of browsers processed
	 [1] = no. of browsers skipped due to no data
	*/
	public function ImportBrowsers(&$mod)
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT B.browser_id,B.form_id,B.name,F.name AS formname FROM {$pre}module_fbr_browser B
LEFT JOIN {$pre}module_fb_form F ON B.form_id=F.form_id
ORDER BY B.form_id
EOS;
		$db = \cmsms()->GetDb();
		$olds = $db->GetArray($sql);
		if ($olds) {
			$fb = \cms_utils::get_module('FormBuilder');
			$funcs = new RecordContent();
			$sql = 'INSERT INTO '.$pre.'module_pwbr_browser
(form_id,name,form_name) VALUES (?,?,?)';
			$renums = [];
			$converts = self::Get_Converts($db, $pre, 0); //field_id translations
			foreach ($olds as &$row) {
				$oldfid = (int) $row['form_id'];
				$flds = [];
				$parms = [];
//GetSortedResponses($form_id,$start_point,$number=100,$admin_approved=FALSE,$user_approved=FALSE,$field_list=array(),$dateFmt='d F y',&$params)
				list($count, $names, $details) = $fb->GetSortedResponses($oldfid, -1, -1,
					FALSE, FALSE, $flds, 'Y-m-d', $parms);
				if ($count > 0) {
					$fconv = self::Get_Converts($db, $pre, $oldfid);
					$newfid = ($fconv) ? reset($fconv) : -$oldfid; //form id < 0 signals FormBuilder form id

					$db->Execute($sql, [$newfid, $row['name'], $row['formname']]);
					$newbid = $db->Insert_ID();
					$renums[$newbid] = (int) $row['browser_id']; //a.k.a. oldbid
					foreach ($details as &$one) {
						$olddata = [];
						foreach ($one->fields as $fid => $fval) {
							if ($converts && array_key_exists($fid, $converts)) {
								$nid = (int) $converts[$fid];
							} else {
								$nid = -$fid; //id < 0 signals FormBuilder field
							}
							$olddata[$nid] = [$names[$fid], $fval];
						}
						$funcs->Insert($mod, $pre, $newbid, $newfid, $one->submitted_date, $olddata, 0);
					}
					unset($one);
				}
			}
			unset($row);

			$oc = count($olds);
			if ($renums) {
				foreach ($renums as $newbid => $oldbid) {
					self::Get_Attrs($mod, $db, $pre, $newbid, $oldbid, $converts);
				}
				$ic = count($renums);
				return [$ic, $oc - $ic];
			}
			return [0, $oc];
			//TODO initiate RecordsUpdate task
		}
		return [0, 0];
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

	private function Get_Attrs(&$mod, &$db, $pre, $newbid, $oldbid, &$fieldconverts)
	{
		$sql = <<<EOS
SELECT * FROM {$pre}module_fbr_browser_attr WHERE browser_id=?
AND (name='admin_list_fields' OR name='admin_rows_per_page')
ORDER BY browser_attr_id
EOS;
		$data = $db->GetArray($sql, [$oldbid]);
		if ($data) {
			$sql = 'UPDATE '.$pre.'module_pwbr_browser SET pagerows=? WHERE browser_id=?';
			foreach ($data as &$row) {
				switch ($row['name']) {
				case 'admin_list_fields':
					self::Get_Fields($mod, $db, $pre, $oldbid, $newbid, $row['value'], $fieldconverts);
					break;
				case 'admin_rows_per_page':
					$db->Execute($sql, [(int) $row['value'], $newbid]);
					break;
				}
			}
			unset($row);
		}
	}

	//$value = string like 45,0:46,1:47,2:48,3:49,4:50,5:51,6:52,7:53,8:54,9:55,10:57,11:56,12:58,13:246,-1:247,-1
	//$fieldconverts = array (old_fid=>new_fid ...) OR FALSE
	private function Get_Fields(&$mod, &$db, $pre, $oldbid, $newbid, &$value, &$fieldconverts)
	{
		$sql = <<<EOS
SELECT F.field_id,F.name FROM {$pre}module_fb_field F
JOIN {$pre}module_fbr_browser B ON F.form_id=B.form_id
WHERE B.browser_id=?
ORDER BY F.field_id
EOS;
		$names = $db->GetAssoc($sql, [$oldbid]);
		$parts = explode(':', $value);
		$i = 3;
		$l = count($parts);
		$sql = 'INSERT INTO '.$pre.'module_pwbr_field
(browser_id,name,shown,frontshown,sorted,order_by,form_field) VALUES (?,?,?,?,?,?,?)';
		//record internal-use fields
		$nm = $mod->Lang('title_submitted');
		$db->Execute($sql, [$newbid, $nm, 1, 0, 1, 1, 0]);
		$nm = $mod->Lang('title_modified');
		$db->Execute($sql, [$newbid, $nm, 0, 0, 1, 2, 0]);
		foreach ($parts as $one) {
			list($indx, $order) = explode(',', $one);
			if ($order != -1) {
				$see = 1;
				$order += 2;
			} else {
				$see = 0;
				$order = $l + $i;
			}
			$nm = ($indx && !empty($names[$indx])) ? $names[$indx] : 'unnamed-'.$oldbid.':'.$i;
			$fid = (int) $one['field_id'];
			if ($fieldconverts && array_key_exists($fid, $fieldconverts)) {
				$fid = (int) $fieldconverts[$fid];
			} else {
				$fid = -$fid; //id < 0 signals FormBuilder field id
			}
			$db->Execute($sql, [$newbid, $nm, $see, 0, 0, $order, $fid]);
			$i++;
		}
	}

	//$form_id may be < 0, or 0 to get only the field_id conversions
	private function Get_Converts(&$db, $pre, $form_id)
	{
		if ($form_id < 0) {
			$form_id = -$form_id;
		}
		$sql = 'SELECT old_id,new_id FROM '.$pre.'module_pwf_trans WHERE ';
		if ($form_id) {
			$sql .= 'isform AND old_id=?';
			$args = [$form_id];
		} else {
			$sql .= 'NOT isform ORDER BY old_id';
			$args = [];
		}
		return $db->GetAssoc($sql, $args);
	}
}
