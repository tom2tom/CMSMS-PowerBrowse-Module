<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if (!($this->CheckAccess('admin') || $this->CheckAccess('modify'))) exit;

$funcs = new PowerBrowse\RecordTasks();
$funcs->AddRecord($params['form_id'],$this->GetPreference('onchange_notices'));

$this->Redirect($id,'browse_list',$returnid,$params);
