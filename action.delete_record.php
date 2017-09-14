<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple..org/projects/pwfbrowse
*/

if (!($this->_CheckAccess('modify') || $this->_CheckAccess('admin'))) {
	exit;
}

$funcs = new PWFBrowse\RecordOperations();
$funcs->Delete($params['record_id']);
//TODO more informative displayed message, func($params['record_id'])
$message = $this->Lang('message_records_deleted', 1);
$params['message'] = $this->_PrettyMessage($message, TRUE, FALSE);

$this->Redirect($id, 'browse_list', $returnid, $params);
