<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if (!$this->CheckAccess()) exit;

if (!isset($params['sel']))
	$this->Redirect($id,'browse_list'); //nothing selected

if (isset($params['delete'])) {
	if (!($this->CheckAccess('modify') || $this->CheckAccess('admin'))) exit;

	$funcs = new PowerBrowse\RecordTasks();
	$funcs->DeleteRecord($params['sel']);
	unset($funcs);
	$message = $this->Lang('message_records_deleted',count($params['sel']));
	$params['message'] = $this->PrettyMessage($message,TRUE,FALSE,FALSE);
} elseif (isset($params['export'])) {
	if (!$this->CheckAccess()) exit;

	$funcs = new PowerBrowse\Export();
	$res = $funcs->Export($this,FALSE,$params['sel']);
	if ($res === TRUE)
		exit;
	unset($funcs);
	$params['message'] = $this->PrettyMessage($res,FALSE);	
}

$this->Redirect($id,'browse_list',$returnid,$params);
