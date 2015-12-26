<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!$this->CheckAccess('modify')) exit;

$funcs = new pwbrBrowserTasks();
$res = $funcs->DeleteBrowser($params['browser_id']);
$args = ($res) ?
	array('message' => $this->PrettyMessage('browser_deleted')):
	array('message' => $this->PrettyMessage('error_failed',FALSE));

$this->Redirect($id,'defaultadmin','',$args);

?>
