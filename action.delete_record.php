<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!($this->CheckAccess('modify') || $this->CheckAccess('admin'))) exit;

//TODO more informative displayed message, func($params['record_id'])
$funcs = new pwbrRecordTasks();
$funcs->DeleteRecord($params['record_id']);

$message = $this->Lang('message_records_deleted', 1);
$params['message'] = $this->PrettyMessage($message,TRUE,FALSE,FALSE);

$this->Redirect($id,'browse_list',$returnid,$params);

?>
