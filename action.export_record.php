<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!$this->_CheckAccess()) exit;

$funcs = new PWFBrowse\Export();
$res = $funcs->Export($this,FALSE,$params['record_id']);
if ($res === TRUE)
	exit;
unset($funcs);
$params['message'] = $this->_PrettyMessage($res,FALSE);
$this->Redirect($id,'browse_list',$returnid,$params);
