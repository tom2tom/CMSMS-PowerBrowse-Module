<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!$this->_CheckAccess('modify')) exit;

if (isset($params['cancel'])) {
	unset($params);
	$this->Redirect($id,'defaultadmin');
} elseif (isset($params['submit'])) {
	$funcs = new PWFBrowse\BrowserTasks();
	$funcs->StoreBrowser($this,$params);
	unset($funcs);
	$message = $this->Lang('browser2','\''.$params['browser_name'].'\'',$this->Lang('saved'));
	unset($params);
	$this->Redirect($id,'defaultadmin','',array(
		'message'=>$this->_PrettyMessage($message,TRUE,FALSE)));
} elseif (isset($params['apply'])) {
	$funcs = new PWFBrowse\BrowserTasks();
	$funcs->StoreBrowser($this,$params);
	$message = $this->Lang('browser1',$this->Lang('updated'));
	$params['message'] = $this->_PrettyMessage($message,TRUE,FALSE);
}

$tplvars = array();

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.open_browser.php';

echo PWFBrowse\Utils::ProcessTemplate($this,'open_browser.tpl',$tplvars);
