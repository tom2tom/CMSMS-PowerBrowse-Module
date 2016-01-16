<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!$this->CheckAccess('modify')) exit;

if(isset($params['cancel']))
{
	unset($params);
	$this->Redirect($id,'defaultadmin');
}
elseif(isset($params['submit']))
{
	$funcs = new pwbrBrowserTasks();
	$funcs->StoreBrowser($this,$params);
	unset($funcs);
	$message = $this->Lang('browser2','\''.$params['browser_name'].'\'',$this->Lang('saved'));
	unset($params);
	$this->Redirect($id,'defaultadmin','',array(
		'message'=>$this->PrettyMessage($message,TRUE,FALSE,FALSE)));
}
elseif(isset($params['apply']))
{
	$funcs = new pwbrBrowserTasks();
	$funcs->StoreBrowser($this,$params);
	$message = $this->Lang('browser1',$this->Lang('updated'));
	$params['message'] = $this->PrettyMessage($message,TRUE,FALSE,FALSE);
}

$tplvars = array();

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.edit_browser.php';

pwbrUtils::ProcessTemplate($this,'edit_browser.tpl',$tplvars);

?>
