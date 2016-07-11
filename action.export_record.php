<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if (!$this->CheckAccess()) exit;

$funcs = new PowerBrowse\Export();
$res = $funcs->Export($this,FALSE,$params['record_id']);
if ($res === TRUE)
	exit;
unset($funcs);
$params['message'] = $this->PrettyMessage($res,FALSE);
$this->Redirect($id,'browse_list',$returnid,$params);
