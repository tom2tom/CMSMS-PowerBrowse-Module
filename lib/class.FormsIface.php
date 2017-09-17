<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

namespace PWFBrowse;

class FormsIface
{
	/**
	GetBrowsableForms:
	A form is considered browsable if it includes a 'FormBrowser' field
	Returns: array, each key = form id, value = form name
	*/
	public function GetBrowsableForms()
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT DISTINCT FM.form_id,FM.name FROM {$pre}module_pwf_form FM
JOIN {$pre}module_pwf_field FD ON FM.form_id=FD.form_id
WHERE FD.type='FormBrowser'
EOS;
		$db = \cmsms()->GetDb();
		return $db->GetAssoc($sql);
	}

	/**
	GetBrowsableFields:
	@form_id: form enumerator
	A field is considered browsable if it is an input, or flagged DisplayExternal
	Returns: array, each key = field id, value = field name
	*/
	public function GetBrowsableFields($form_id)
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT field_id,name,type FROM {$pre}module_pwf_field
WHERE form_id=? ORDER BY order_by
EOS;
		$db = \cmsms()->GetDb();
		$all = $db->GetAssoc($sql, [$form_id]);
		$result = [];
		if ($all) {
			$mod = \cms_utils::get_module('PWForms');
			$dummy = $mod->_GetFormData();
			$params = [];
			foreach ($all as $key => &$row) {
				$classPath = 'PWForms\\'.$row['type'];
				$fld = new $classPath($dummy, $params);
				if ($fld->IsInput || $fld->DisplayExternal) { //TODO check
					$result[$key] = $row['name'];
				}
				unset($fld);
			}
			unset($row);
		}
		return $result;
	}
}
