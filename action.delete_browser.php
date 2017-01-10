<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!$this->_CheckAccess('modify')) {
	exit;
}

$funcs = new PWFBrowse\BrowserTasks();
$res = $funcs->DeleteBrowser($params['browser_id']);
$args = ($res) ?
	['message' => $this->_PrettyMessage('browser_deleted')]:
	['message' => $this->_PrettyMessage('error_failed', FALSE)];

$this->Redirect($id, 'defaultadmin', '', $args);
