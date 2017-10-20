<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

namespace PWForms;

class FormBrowser extends FieldBase
{
	const MODNAME = 'PWFBrowse'; //initiator/owner module name
	public $MenuKey = 'field_label'; //owner-module lang key for this field's menu label, used by PWForms
	public $mymodule = NULL; //used also by PWForms, do not rename

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'FormBrowser';
	}

	protected function GetModule()
	{
		if (!$this->mymodule) {
			$this->mymodule = \ModuleOperations::get_instance()->get_module_instance(self::MODNAME, '', TRUE);
		}
	}

/*	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [];
	}
*/
/*	public function GetSynopsis()
	{
		$this->GetModule();
		return $this->mymodule->Lang('').': STUFF';
	}
*/
	public function DisplayableValue($as_string = TRUE)
	{
		$ret = '[Form Browser]'; //by convention, not translated
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetDisplayType()
	{
		$this->GetModule();
		return '*'.$this->mymodule->Lang($this->MenuKey); //disposition-prefix
	}

	public function Load($id, &$params)
	{
		return TRUE;
	}

	public function Store($deep = FALSE)
	{
		return TRUE;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, 'title_smarty_eval', FALSE, FALSE);
		return ['main' => $main, 'adv' => $adv];
	}

	//NB RecordContent::Format() recognizes special-cases: 'dt','d','t','_ss','_se','_sb'
	public function Dispose($id, $returnid)
	{
		$browsedata = [];
		foreach ($this->formdata->Fields as &$obfld) {
	//TODO is a browsable field by API methods: $obfld->IsInputField() && $obfld->DisplayInForm()
			if (($obfld->IsInput && $obfld->DisplayInForm) || $obfld->IsSequence) {
				$save = [$obfld->Name, $obfld->DisplayableValue()];
				if ($obfld->IsTimeStamp) {
					if ($obfld->ShowDate) {
						if ($obfld->ShowTime) {
							$save['dt'] = '';
						} else {
							$save['d'] = '';
						}
					} elseif ($obfld->ShowTime) {
						$save['t'] = '';
					} else {
						continue;
					}
				} elseif ($obfld->IsSequence) {
					$sid = $obfld->GetProperty('privatename'); //identifier
					if ($obfld->Type == 'SequenceStart') {
						$save['_ss'] = $sid; //'SequenceStart'
						$save[0] = $sid.'>>'; //title to facilitate list-setup
					} elseif ($obfld->LastBreak) {
						$save['_se'] = $sid; //final 'SequenceEnd'
						$save[0] = $sid.'<<';
					} else {
						$save['_sb'] = $sid; //intermediate 'SequenceEnd'
						$save[0] = $sid.'||';
					}
					$save[1] = ''; //no value
				}
				$browsedata[$obfld->Id] = $save;
			}
		}
		unset($obfld);

		if ($browsedata) {
			$browsedata = serialize(['_m' => [0 => NULL, 1 => time(), 'dt' => '']] + $browsedata); //defer title (i.e.[0]) translation
			//avoid module-loading
			$handle = \cms_siteprefs::get(self::MODNAME.'_mapi_pref_Qhandle', '');
			$jobkey = $this->djb2a_hash(microtime().getmypid()) & 0xffff; //not ABSOLUTELY unique, but good enough
			//minimise autoloading
			$config = cmsms()->GetConfig();
			//TODO whatever configured assets folder
			$fp = $config['root_path'].DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'Queue'.DIRECTORY_SEPARATOR.'class.Qface.php';
			require_once $fp;
			$funcs = new \CMSMS\Assets\Queue\Qface();
			$funcs->StartJob($handle, $jobkey,
			[self::MODNAME, 'store_data', ['formid' => $this->formdata->Id, 'formdata' => $browsedata]],
			1); //highest priority
		}
		return [TRUE, ''];
	}

	protected function djb2a_hash($key)
	{
		$key = array_values(unpack('C*',(string) $key)); //actual byte-length (i.e. no mb interference);
		$klen = count($key);
		$h1 = 5381;
		for ($i = 0; $i < $klen; ++$i) {
			$h1 = ($h1 + ($h1 << 5)) ^ $key[$i]; //aka $h1 = $h1*33 ^ $key[$i]
		}
		return $h1;
	}

	public function __toString()
	{
		$ob = $this->mymodule;
		$this->mymodule = NULL;
		$ret = parent::__toString();
		$this->mymodule = $ob;
		return $ret;
	}

/*	public function unserialize($serialized)
	{
		parent::unserialize($serialized);
		//$this->mymodule stays NULL
	}
*/
}
