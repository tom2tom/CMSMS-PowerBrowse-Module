<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWForms;

class FormBrowser extends FieldBase
{
	var $ModName = 'PWFBrowse';
	var $MenuKey = 'field_label'; //lang key for fields-menu label, used by PWForms
	var $mymodule; //used also by PWForms, do not rename

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'FormBrowser';
		$this->mymodule =& \cms_utils::get_module($this->ModName);
	}

	public function Load($id, &$params)
	{
		//TODO
		return FALSE;
	}

	public function Store($deep=FALSE)
	{
		//TODO
		return FALSE;
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Form Browser]'; //by convention, not translated
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetDisplayType()
	{
		if (!($this->IsInput || $this->IsSortable)) //TODO check this
			$t = '-';
		elseif ($this->IsDisposition)
			$t = '*';
		else
			$t = '';
		if (!$this->mymodule instanceof $this->ModName) {
			$this->mymodule =& \cms_utils::get_module($this->ModName);
		}
		return $t.$this->mymodule->Lang($this->MenuKey);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Dispose($id, $returnid)
	{
		$browsedata = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsInput) //TODO is a browsable field
				$browsedata[$one->Id] = array($one->Name => $one->Value);
		}
		unset($one);
		if (!$browsedata)
			return array(TRUE,'');

		$mod = &$this->mymodule;
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
		$form_id = $this->formdata->Id;
		$browsers = $db->GetCol($sql,array($form_id));
		if ($browsers) {
			$stamp = time();
			$funcs = new PWFBrowse\RecordStore();
			foreach ($browsers as $browser_id)
				$funcs->Insert($browser_id,$form_id,$stamp,$browsedata,$mod,$db,$pre);
		}
		unset($mod);
		return array(TRUE,'');
	}
}
