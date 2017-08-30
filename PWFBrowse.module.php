<?php
#------------------------------------------------------------------------
# This is CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <@>
# Derived in part from FormBrowser module, copyright (C) 2006-2011, Samuel Goldstein <sjg@cmsmodules.com>
# This project's forge-page is: http://dev.cmsmadesimple.org/projects/PWFBrowse
#
# This module is free software. You can redistribute it and/or modify it under
# the terms of the GNU Affero General Public License as published by the Free
# Software Foundation, either version 3 of that License, or (at your option)
# any later version.
#
# This module is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# Read the License online: http://www.gnu.org/licenses/licenses.html#AGPL
#-----------------------------------------------------------------------

class PWFBrowse extends CMSModule
{
	public $before20;
	public $oldtemplates;

	public function __construct()
	{
		parent::__construct();
		global $CMS_VERSION;
		$this->before20 = (version_compare($CMS_VERSION, '2.0') < 0);
		$this->oldtemplates = $this->before20 || 1; //TODO
		//TODO detect whether customised autoloader is in place, if so don't autoregister here
		spl_autoload_register([$this, 'cmsms_spacedload']);
	}

	public function __destruct()
	{
		spl_autoload_unregister([$this, 'cmsms_spacedload']);
		if (function_exists('parent::__destruct')) {
			parent::__destruct();
		}
	}

	/* namespace autoloader - CMSMS 2.1- default autoloader doesn't do spacing for modules */
	private function cmsms_spacedload($class)
	{
		$prefix = get_class().'\\'; //our namespace prefix
		$o = ($class[0] != '\\') ? 0:1;
		$p = strpos($class, $prefix, $o);
		if ($p === 0 || ($p == 1 && $o == 1)) {
			// directory for the namespace
			$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		} else {
			$p = strpos($class, '\\', 1);
			if ($p === FALSE) {
				return;
			}
			$prefix = substr($class, $o, $p-$o);
			$bp = dirname(__DIR__).DIRECTORY_SEPARATOR.$prefix.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		}
		// relative class name
		$len = strlen($prefix) + $o;
		$relative_class = trim(substr($class, $len), '\\');

		if (($p = strrpos($relative_class, '\\', -1)) !== FALSE) {
			$relative_dir = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
			$bp .= substr($relative_dir, 0, $p+1);
			$base = substr($relative_dir, $p+1);
		} else {
			$base = $relative_class;
		}

		$fp = $bp.'class.'.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
			return;
		}
		$fp = $bp.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
		}
	}

	public function AllowAutoInstall()
	{
		return FALSE;
	}

	public function AllowAutoUpgrade()
	{
		return FALSE;
	}

	//for 1.11+
	public function AllowSmartyCaching()
	{
		return FALSE; //no frontend use
	}

	public function GetName()
	{
		return 'PWFBrowse';
	}

	public function GetFriendlyName()
	{
		return $this->Lang('friendlyname');
	}

	public function GetHelp()
	{
		$fp = cms_join_path(dirname(__FILE__), 'css', 'list-view.css');
		$cont = @file_get_contents($fp);
		if ($cont) {
			$example = preg_replace(['~\s?/\*(.*)?\*/~Usm', '~\s?//.*$~m'], ['', ''], $cont);
			$example = str_replace([PHP_EOL.PHP_EOL, PHP_EOL, "\t"], ['<br />', '<br />', ' '], trim($example));
		} else {
			$example = $this->Lang('error_missing');
		}
		return $this->Lang('help_module', $example);
	}

	public function GetVersion()
	{
		return '0.8';
	}

	public function GetAuthor()
	{
		return 'tomphantoo';
	}

	public function GetAuthorEmail()
	{
		return 'tpgww@onepost.net';
	}

	public function GetChangeLog()
	{
		$fn = cms_join_path(dirname(__FILE__), 'lib','doc', 'changelog.htm');
		return ''.@file_get_contents($fn);
	}

	public function GetDependencies()
	{
		return ['PWForms'=>'0.7'];
	}

	public function MinimumCMSVersion()
	{
		return '1.11'; //same as dependent PWForms
	}

/*	public function MaximumCMSVersion()
	{
	}
*/
	public function InstallPostMessage()
	{
		return $this->Lang('postinstall');
	}

	public function UninstallPreMessage()
	{
		return $this->Lang('confirm_uninstall');
	}

	public function UninstallPostMessage()
	{
		return $this->Lang('postuninstall');
	}

	public function IsPluginModule()
	{
		return TRUE;
	}

	public function HasAdmin()
	{
		return TRUE;
	}

	public function LazyLoadAdmin()
	{
		return TRUE;
	}

	public function GetAdminSection()
	{
		return 'content';
	}

	public function GetAdminDescription()
	{
		return $this->Lang('admindescription');
	}

	public function VisibleToAdminUser()
	{
		return self::_CheckAccess();
	}

/*	public function AdminStyle()
	{
	}
*/
	public function GetHeaderHTML()
	{
		$url = $this->GetModuleURLPath();
		//the 2nd link is for dynamic style-changes, via js at runtime
		return <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}/css/admin.css" />
<link rel="stylesheet" type="text/css" id="adminstyler" href="#" />
EOS;
	}

	public function SuppressAdminOutput(&$request)
	{
		if (isset($_SERVER['QUERY_STRING'])) {
			//$adbg = $_SERVER;
//$this->Crash();
			if (strpos($_SERVER['QUERY_STRING'], 'export') !== FALSE) {
				return TRUE;
			}
		}
/*		if (isset($request['mact'])) {
			if (strpos($request['mact'],',export'))//export_browser or export_record
				return TRUE;
			if (isset($request['m1_export']))
				return TRUE;
		}
*/
		return FALSE;
	}

	public function SupportsLazyLoading()
	{
		return FALSE; //nothing to load
	}

	public function LazyLoadFrontend()
	{
		return FALSE;
	}

	//setup for pre-1.10
	public function SetParameters()
	{
		self::InitializeAdmin();
		self::InitializeFrontend();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	public function InitializeFrontend()
	{
		$this->RegisterModulePlugin(TRUE);
		$this->RestrictUnknownParams();
		$this->SetParameterType('browser', CLEAN_STRING);
		$this->SetParameterType('browser_id', CLEAN_INT);
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	public function InitializeAdmin()
	{
		//document only the parameters relevant for external (page-tag) usage
		$this->CreateParameter('browser', '', $this->Lang('help_browser'), FALSE);
	}

// ~~~~~~~~~~~~~~~~~~~~~ NON-CMSModule METHODS ~~~~~~~~~~~~~~~~~~~~~

	public function _CheckAccess($permission='')
	{
		switch ($permission) {
		 case '':  // any module permission
			$allow = $this->CheckPermission('ViewPwFormData');
			if (!$allow) {
				$allow = $this->CheckPermission('ModifyPwFormData');
			}
			if (!$allow) {
				$allow = $this->CheckPermission('ModifyPwBrowsers');
			}
			break;
		 case 'view':
			$allow = $this->CheckPermission('ViewPwFormData');
			break;
		 case 'modify':
			$allow = $this->CheckPermission('ModifyPwFormData');
			break;
		 case 'admin':
			$allow = $this->CheckPermission('ModifyPwBrowsers');
			break;
		 default:
			$allow = 0;
			break;
		}
		return $allow;
	}

	public function _PrettyMessage($text, $success=TRUE, $key=TRUE)
	{
		$base = ($key) ? $this->Lang($text) : $text;
		if ($success) {
			return $this->ShowMessage($base);
		} else {
			$msg = $this->ShowErrors($base);
			//strip the link
			$pos = strpos($msg, '<a href=');
			$part1 = ($pos !== FALSE) ? substr($msg, 0, $pos) : '';
			$pos = strpos($msg, '</a>', $pos);
			$part2 = ($pos !== FALSE) ? substr($msg, $pos+4) : $msg;
			$msg = $part1.$part2;
			return $msg;
		}
	}

	public function _GetActiveTab(&$params)
	{
		if (!empty($params['active_tab'])) {
			return $params['active_tab'];
		} else {
			return 'maintab';
		}
	}

	public function _BuildNav($id, $returnid, &$params, &$tplvars)
	{
		$navstr = $this->CreateLink($id, 'defaultadmin', $returnid,
		'&#171; '.$this->Lang('module_nav'));
		if (isset($params['browser_id']) && isset($params['form_id']) && isset($params['record_id'])) {
			$navstr .= ' '.$this->CreateLink($id, 'browse_list', $returnid,
			'&#171; '.$this->Lang('title_records'), [
			'form_id'=>$params['form_id'],
			'browser_id'=>$params['browser_id']]);
		}
		$tplvars['inner_nav'] = $navstr;
	}
}
