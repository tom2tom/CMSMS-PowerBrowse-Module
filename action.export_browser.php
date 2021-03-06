<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

if (!$this->_CheckAccess()) {
	exit;
}

$funcs = new PWFBrowse\RecordExport();
$res = $funcs->Export($this, $params['browser_id']);
if ($res === TRUE) {
	exit;
}
unset($funcs);

$this->Redirect($id, 'defaultadmin', $returnid,
	['message' => $this->_PrettyMessage($res, FALSE)]);
