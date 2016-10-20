<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!($this->_CheckAccess('admin') || $this->_CheckAccess('modify'))) exit;

//$funcs = new PWFBrowse\RecordTasks();
//$funcs->AddRecord($params['form_id'],$this->GetPreference('onchange_notices'));
$params['message'] = 'NOT YET IMPLEMENTED'; //TODO

$this->Redirect($id,'browse_list',$returnid,$params);
