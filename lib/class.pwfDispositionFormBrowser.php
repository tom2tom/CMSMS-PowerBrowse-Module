<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwfDispositionFormBrowser extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = 1;
		$this->IsDisposition = TRUE;
		$this->NeedsDiv = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'DispositionFormBrowser';
		$this->sortable = FALSE;
	}

	/*
	Add form data to the save-queue : array (
		'formid' => form identifier
		'submitted' => timestamp representing when the form was submitted
		'data' => array in which each key = formfield id, corresponding value = array(field identifier, field value)
		)
	*/
	function DisposeForm($returnid)
	{
		$formdata = array(
			21=>array('identifier1'=>'value1'),
			22=>array('identifier2'=>'value2'),
			10=>array('identifier3'=>'some other value')); //TODO func($this->Value) ?
		$contents = array(
			'formid' => $this->formdata->Id,
			'submitted' => time(),
			'data' => $formdata;
		);
		$mod = cms_utils::get_module('PowerBrowse');
		$token = md5(mt_rand(1,1000000).reset(reset($formdata))); //almost absolutely unique
		while(!$mod->Locker($token))
			usleep(mt_rand(10000,50000));
		$mod->queue[] = $contents;
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
		array(TRUE,'');
	}
}

?>
