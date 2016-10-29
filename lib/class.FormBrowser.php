<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWForms;

class FormBrowser extends FieldBase
{
	const MODNAME = 'PWFBrowse'; //initiator/owner module name
	public $MenuKey = 'field_label'; //owner-module lang key for this field's menu label, used by PWForms
	public $mymodule; //used also by PWForms, do not rename

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'FormBrowser';
		$this->mymodule =& \cms_utils::get_module(self::MODNAME);
	}

	public function Load($id, &$params)
	{
		return TRUE;
	}

	public function Store($deep=FALSE)
	{
		return TRUE;
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
		$nm = self::MODNAME;
		if (!$this->mymodule instanceof $nm) {
			$this->mymodule =& \cms_utils::get_module(self::MODNAME);
		}
		return '*'.$this->mymodule->Lang($this->MenuKey); //disposition-prefix
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,FALSE);
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Dispose($id, $returnid)
	{
		$browsedata = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsInput || $one->DisplayExternal) //TODO is a browsable field
				$browsedata[$one->Id] = array($one->Name => $one->Value);
		}
		unset($one);
		if ($browsedata) {
			$nm = self::MODNAME;
			if (!$this->mymodule instanceof $nm) {
				$this->mymodule =& \cms_utils::get_module(self::MODNAME);
			}
			$pre = \cms_db_prefix();
			$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
			$db = \cmsms()->GetDb();
			$form_id = $this->formdata->Id;
			$browsers = $db->GetCol($sql,array($form_id));
			if ($browsers) {
				$stamp = time();
				$funcs = new PWFBrowse\RecordStore();
				foreach ($browsers as $browser_id)
					$funcs->Insert($browser_id,$form_id,$stamp,$browsedata,$this->mymodule,$db,$pre);
			}
		}
		return array(TRUE,'');
	}
}
