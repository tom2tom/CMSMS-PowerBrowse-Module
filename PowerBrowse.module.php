<?php
#------------------------------------------------------------------------
# This is CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <@>
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
	public $before20;
	public $havemcrypt;
	private $mh; //curl_multi handle for async queue processing
	private $ch = FALSE; //cached curl handle for unfinished process
	private $Qurl;
	protected $running = FALSE; //whether the queue-processor is active
	protected $queue = array();

	function __construct()
	{
		parent::__construct();
		$this->mh = curl_multi_init();
		global $CMS_VERSION;
		$this->before20 = (version_compare($CMS_VERSION,'2.0') < 0);
		$this->havemcrypt = function_exists('mcrypt_encrypt');
		//bogus frontend link (i.e. no admin login needed)
		$url = $this->CreateLink('_','run_queue',1,'',array(),'',TRUE);
		//strip the (trailing) fake returnid, hence use the default
		$sep = strpos($url,'&amp;');
		$this->Qurl = substr($url,0,$sep);
	}
	
	function __destruct()
	{
		if($this->ch)
		{
			curl_multi_remove_handle($this->mh,$this->ch);
			curl_close($this->ch);
		}
		curl_multi_close($this->mh);
		if($this->mutex)
			$this->mutex->reset();
//		parent::__destruct();
	}
	
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
		$fp = cms_join_path(dirname(__FILE__),'css','list-view.css');
		$cont = @file_get_contents($fp);
		if($cont)
		{
			$example = preg_replace(array('~\s?/\*(.*)?\*/~Usm','~\s?//.*$~m'),array('',''),$cont);
			$example = str_replace(array("\n\n","\n","\t"),array('<br />','<br />',' '),trim($example));
		}
		else
			$example = $this->Lang('error_missing');
		return $this->Lang('help_module',$example);
	}

	function GetVersion()
	{
		return '0.1';
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
		return array('PowerForms'=>'0.1');
	}

	function MinimumCMSVersion()
	{
		return '1.9'; //CHECKME class auto-loading needed
	}

/*	function MaximumCMSVersion()
	{
	}
*/
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

	function BuildNav($id,$returnid,&$params)
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
}

?>
