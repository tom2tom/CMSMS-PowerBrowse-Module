<?php
#------------------------------------------------------------------------
# This is CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <@>
# Derived in part from FormBrowser module, copyright (C) 2006-2011, Samuel Goldstein <sjg@cmsmodules.com>
# This project's forge-page is: http://dev.cmsmadesimple.org/projects/powerbrowse
#
# This module is free software. You can redistribute it and/or modify it under
# the terms of the GNU Affero General Public License as published by the Free
# Software Foundation, either version 3 of that License, or (at your option)
# any later version.
#
# This module is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License (www.gnu.org/licenses/licenses.html#AGPL)
# for more details
#-----------------------------------------------------------------------

class PowerBrowse extends CMSModule
{
	private $mutex = FALSE; //fake mutex for serializing
//	private $mcache = FALSE; //memcache to use as mutex for serializing queue access
//	private $lockid = FALSE; //memcache key
	private $mh = FALSE; //curl_multi handle for async queue processing
	protected $running = FALSE; //whether the queue-processor is active
	protected $queue = array();

/*	function __construct()
	{
		parent::__construct();
		$this->mcache = new Memcache;
		$this->mcache->connect($config['root_url'],11211);
		$this->lockid = uniqid('pwbr',TRUE);
		$this->mh = curl_multi_init();
	}
	
	function __destruct()
	{
		if(is_object($this->mcache))
			$this->mcache->delete($this->lockid); //just in case ...
		curl_multi_close($this->mh);
//		parent::__destruct();
	}
*/
	function AllowAutoInstall()
	{
		return FALSE;
	}

	function AllowAutoUpgrade()
	{
		return FALSE;
	}

	function InstallPostMessage()
	{
		return $this->Lang('postinstall');
	}

	function UninstallPreMessage()
	{
		return $this->Lang('confirm_uninstall');
	}

	function UninstallPostMessage()
	{
		return $this->Lang('postuninstall');
	}

	function GetName()
	{
		return 'PowerBrowse';
	}

	function GetFriendlyName()
	{
		return $this->Lang('friendlyname');
	}

	function GetHelp()
	{
		return $this->Lang('help_module');
	}

	function GetVersion()
	{
		return '0.7';
	}

	function GetAuthor()
	{
		return 'tomphantoo';
	}

	function GetAuthorEmail()
	{
		return 'tpgww@onepost.net';
	}

	function GetAdminDescription()
	{
		return $this->Lang('admindescription');
	}

	function GetChangeLog()
	{
		$fn = cms_join_path(dirname(__FILE__),'include','changelog.inc');
		return ''.@file_get_contents($fn);
	}

	function GetDependencies()
	{
		return array('PowerForms'=>'0.7');
	}

	function MinimumCMSVersion()
	{
		return '1.9'; //CHECKME class auto-loading needed
	}

	function MaximumCMSVersion()
	{
		return '1.19.99';
	}

	function IsPluginModule()
	{
		return TRUE;
	}

	function HasAdmin()
	{
		return TRUE;
	}

	function LazyLoadAdmin()
	{
		return FALSE;
	}

	function GetAdminSection()
	{
		return 'content';
	}

	function VisibleToAdminUser()
	{
		$v = $this->CheckPermission('ViewPwFormData');
		if(!$v) $v = $this->CheckPermission('ModifyPwFormData');
		if(!$v) $v = $this->CheckPermission('ModifyPwBrowsers');
		return $v;
	}

	function GetHeaderHTML()
	{
	}

	function AdminStyle()
	{
		$fn = cms_join_path(dirname(__FILE__),'css','module.css');
		return ''.@file_get_contents($fn);
	}

	function SuppressAdminOutput(&$request)
	{
		if(isset($request['mact']))
		{
			if(strpos($request['mact'],',export'))//export_browser or export_record
				return TRUE;
			if(isset($request['m1_export']))
				return TRUE;
		}
		return FALSE;
	}

	function SupportsLazyLoading()
	{
		return FALSE; //nothing to load
	}

	function LazyLoadFrontend()
	{
		return FALSE;
	}

	//setup for pre-1.10
	function SetParameters()
	{
		$this->InitializeAdmin();
		$this->InitializeFrontend();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	function InitializeFrontend()
	{
		//$this->RegisterModulePlugin();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	function InitializeAdmin()
	{
		//document only the parameters relevant for external (page-tag) usage
	}

// ~~~~~~~~~~~~~~~~~~~~~ NON-CMSModule METHODS ~~~~~~~~~~~~~~~~~~~~~

	function CheckAccess($permission='')
	{
		$allow = 0;
		switch($permission)
		{
		case '':  // any form-browse-related permission
			$a1 = $this->CheckPermission('ModifyPwBrowsers');
			$a2 = $this->CheckPermission('ModifyPwFormData');
			$a3 = $this->CheckPermission('ViewPwFormData');
			$allow = ($a1 || $a2 || $a3);
			break;
		case 'modify':
			$allow = $this->CheckPermission('ModifyPwBrowsers');
			break;
		case 'admin':
			$allow = $this->CheckPermission('ModifyPwFormData');
			break;
		case 'view':
			$allow = $this->CheckPermission('ViewPwFormData');
			break;
		default:
			$allow = 0;
			break;
		}
		return $allow;
	}

	function PrettyMessage($text,$success=TRUE,$faillink=FALSE,$key=TRUE)
	{
		$base = ($key) ? $this->Lang($text) : $text;
		if ($success)
			return $this->ShowMessage($base);
		else
		{
			$msg = $this->ShowErrors($base);
			if ($faillink == FALSE)
			{
				//strip the link
				$pos = strpos($msg,'<a href=');
				$part1 = ($pos !== FALSE) ? substr($msg,0,$pos) : '';
				$pos = strpos($msg,'</a>',$pos);
				$part2 = ($pos !== FALSE) ? substr($msg,$pos+4) : $msg;
				$msg = $part1.$part2;
			}
			return $msg;
		}
	}

	function GetActiveTab(&$params)
	{
		if(!empty($params['active_tab']))
			return $params['active_tab'];
		else
			return 'maintab';
	}

	function buildBrowseNav($id,$returnid,&$params)
	{
		$navstr = $this->CreateLink($id, 'defaultadmin', $returnid,
		'&#171; '.$this->Lang('title_browsers'));
		if(isset($params['browser_id']) && isset($params['form_id']) && isset($params['record_id']))
		{
			$navstr .= ' '.$this->CreateLink($id,'browse_list',$returnid,
			'&#171; '.$this->Lang('title_records'),array(
			'form_id'=>$params['form_id'],
			'browser_id'=>$params['browser_id']));
		}
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('inner_nav',$navstr);
	}

	/**
	Lock:
	@token: sufficiently-unique identifier of the calling process
	Returns: boolean, whether the lock was obtained
	This is a mutex-equivalent. Atomic. May block if multiple servers are in play.
	*/
	protected function Lock($token)
	{
/* Memcache works in this context, but it's not always available
		$mc =& $this->mcache; 
		$stored = $mc->get($this->lockid);
		if($stored)
			return ($stored === $token);
		if(!$mc->add($this->lockid,$token)) //only nominally atomic
			return FALSE;
		$cas_token = 0.0;
		if($mc->get($this->lockid,NULL,$cas_token) !== $token)
		{
			while(!$mc->cas($cas_token,$this->lockid,$token) || 
				   $mc->getResultCode() != Memcached::RES_SUCCESS)
			{
				$stored = $mc->get($this->lockid);  //reset last access for CAS
				usleep(mt_rand(1000,100000));
			}
		}
*/
		//TODO find some way that's more-atomic and also generally available
		list($was,$this->mutex) = array($this->mutex,$token);
		if($was !== $token && $was !== FALSE)
		{
			$this->mutex = $was;
			return FALSE;
		}
		return TRUE;
	}

	protected function UnLock()
	{
//		$this->mcache->delete($this->lockid);
		$this->mutex = FALSE;
	}

	/**
	SaveFormData:
	Adds @contents to the save-queue.
	For use by PowerForms module, when saving the contents of a submitted form
	Field identifiers and values in the data are not necessarily unique
	@contents: reference to array (
		'formid' => form identifier
		'submitted' => timestamp representing when the form was submitted
		'data' => array in which each key = formfield id, corresponding value = array(field identifier, field value)
		)
	*/
	function SaveFormData(&$contents)
	{
		$token = md5(mt_rand(1,1000000).reset($contents['data'])); //almost absolutely unique
		while(!$this->Lock($token))
			usleep(mt_rand(10000,50000));
		$this->queue[] = $contents;
		$this->UnLock();
		if(!$this->running)
		{
			//cmsms 1.10+ also has ->create_url();
			//bogus frontend link (i.e. no admin login needed)
			$url = $this->CreateLink('_','run_queue',1,'',array(),'',TRUE);
			//strip the (trailing) fake returnid, hence use the default
			$sep = strpos($url,'&amp;');
			$url = substr($url,0,$sep);

			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_FAILONERROR,TRUE);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
			curl_setopt($ch,CURLOPT_FORBID_REUSE,TRUE);
			curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
			curl_setopt($ch,CURLOPT_HEADER,FALSE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);	//in case ...

			$mh = curl_multi_init(); //TODO in module constructor
			curl_multi_add_handle($mh,$ch);
			$running = NULL;
			do
			{
				$mrc = curl_multi_exec($mh,$running);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM); //irrelevant for curl 7.20.0+ (2010-02-11)
//			if($mrc != CURLM_OK) >> CURLM_OUT_OF_MEMORY, CURLM_INTERNAL_ERROR
			if(running === 0) //otherwise, leak!
			{
				curl_multi_remove_handle($mh,$ch);
				curl_multi_close($mh); //TODO in module destructor
				curl_close($ch);
			}
		}
	}

}

?>
