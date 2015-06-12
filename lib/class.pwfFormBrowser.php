<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwfFormBrowser extends pwfFieldBase
{
	var $ModName = 'PowerBrowse';
	var $MenuKey = 'field_label'; //lang key for fields-menu label, used by PowerForms
	var $mymodule; //used also by PowerForms, do not rename

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'FormBrowser';
		$this->mymodule = cms_utils::get_module($this->ModName);
	}

	function Load($id,&$params)
	{
		//TODO
		return FALSE;
	}

	function Store($deep=FALSE)
	{
		//TODO
		return FALSE;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Form Browser]'; //by convention, not translated
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = AdminPopulateCommon($id,FALSE);
		return array('main'=>$main,'adv'=>$adv);
	}

	/*
	Add form data to the save-queue : array (
		'formid' => form identifier
		'submitted' => timestamp representing when the form was submitted
		'data' => array in which each key = formfield id, corresponding value = array(field identifier, field value)
		)
	*/
	function Dispose($id,$returnid)
	{
		$browsedata = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->IsInput) //TODO is a browsable field
				$browsedata[$one->Id] = array($one->Name => $one->Value);
		}
		unset($one);
		if(!$browsedata)
			return array(TRUE,'');

		$token = md5(mt_rand(1,1000000).reset(reset($browsedata))); //almost absolutely unique
		$mod =& $this->mymodule;
		while(!$mod->Locker($token))
			usleep(mt_rand(10000,50000));
		$mod->queue[] = array(
			'formid' => $this->formdata->Id,
			'submitted' => time(),
			'data' => $browsedata);
		$mod->UnLocker();
		if(!$mod->running)
		{
			//initiate async queue processing
			if($mod->ch)
			{
				while(curl_multi_info_read($mod->mh))
					usleep(20000);
				curl_multi_remove_handle($mod->mh,$mod->ch);
				curl_close($mod->ch);
				$mod->ch = FALSE;
			}

			$ch = curl_init($mod->Qurl);
			curl_setopt($ch,CURLOPT_FAILONERROR,TRUE);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
			curl_setopt($ch,CURLOPT_FORBID_REUSE,TRUE);
			curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
			curl_setopt($ch,CURLOPT_HEADER,FALSE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);	//in case ...

			curl_multi_add_handle($mod->mh,$ch);
			$runcount = 0;
			do
			{
				$mrc = curl_multi_exec($mod->mh,$runcount);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM); //irrelevant for curl 7.20.0+ (2010-02-11)
//			if($mrc != CURLM_OK) i.e. CURLM_OUT_OF_MEMORY, CURLM_INTERNAL_ERROR
			if($runcount)
			{
				$mod->ch = $ch; //cache for later cleanup
			}
			else
			{
				curl_multi_remove_handle($mod->mh,$ch);
				curl_close($ch);
			}
		}
		unset($mod);
		return array(TRUE,'');
	}
}

?>
