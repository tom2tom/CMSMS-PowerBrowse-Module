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
		$this->mymodule = \cms_utils::get_module(self::MODNAME);
	}

	public function Load($id, &$params)
	{
		return TRUE;
	}

	public function Store($deep=FALSE)
	{
		return TRUE;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Form Browser]'; //by convention, not translated
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetDisplayType()
	{
		return '*'.$this->mymodule->Lang($this->MenuKey); //disposition-prefix
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,FALSE,FALSE);
		return array('main'=>$main,'adv'=>$adv);
	}

	/*
	NOTE: presentation control(s) here must be suitably replicated in Utils::FormatRecord()
	*/
	public function Dispose($id, $returnid)
	{
		$browsedata = array();
		foreach ($this->formdata->Fields as &$obfld) {
			if (($obfld->IsInput && $obfld->DisplayInForm) //TODO is a browsable field methods: $obfld->IsInputField() && $obfld->DisplayInForm()
				|| $obfld->IsSequence) {
				$save = array($obfld->Name,$obfld->DisplayableValue());
				//TODO other presentation control(s) if relevant
				if ($obfld->IsTimeStamp) {
					if ($obfld->ShowDate) {
						if ($obfld->ShowTime)
							$save['dt'] = '';
						else
							$save['d'] = '';
					} elseif ($obfld->ShowTime) {
						$save['t'] = '';
					} else {
						continue;
					}
				} elseif ($obfld->IsSequence) {
					$save[0] = ''; //nothing displayed for these
					$save[1] = '';
					if($obfld->Type == 'SequenceStart') {
						$save['_ss'] = '';
					} elseif ($obfld->LastBreak) {
						$save['_se'] = ''; //final 'SequenceEnd'
					} else {
						$save['_sb'] = ''; //intermediate 'SequenceEnd'
					}
				}
				$browsedata[$obfld->Id] = $save;
			}
		}
		unset($obfld);
		if ($browsedata) {
			$pre = \cms_db_prefix();
			$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
			$db = \cmsms()->GetDb();
			$form_id = $this->formdata->Id;
			$browsers = $db->GetCol($sql,array($form_id)); //TODO support high-load
			if ($browsers) {
				$stamp = time(); //TODO default locale OK?
				$funcs = new \PWFBrowse\RecordContent();
				foreach ($browsers as $browser_id) {
					$funcs->Insert($this->mymodule,$pre,$browser_id,$form_id,$stamp,$browsedata);
				}
			} else {
				return array(FALSE,$this->formdata->formsmodule->Lang('missing_type','browser for form')); //TODO lang
			}
		}
		return array(TRUE,'');
	}

	public function __toString()
	{
 		$ob = $this->mymodule;
		$this->mymodule = NULL;
		$ret = parent::__toString();
		$this->mymodule = $ob;
		return $ret;
	}

	public function unserialize($serialized)
	{
		parent::unserialize($serialized);
		$this->mymodule = \cms_utils::get_module(self::MODNAME);
	}
}
