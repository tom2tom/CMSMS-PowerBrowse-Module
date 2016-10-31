<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!($this->_CheckAccess('admin') || $this->_CheckAccess('modify'))) exit;

$pf = cms_utils::get_module('PWForms');
if ($pf) {
	if ($params['form_id'] < 0) {
		$funcs = new PWFBrowse\Transition();
		$fid = $funcs->MigrateIds($this,$params['form_id']);
		if ($fid !== FALSE) {
			$params['form_id'] = $fid;
		} else {
			$params['message'] = $this->_PrettyMessage('error_data',FALSE);
			$this->Redirect($id,'browse_list',$returnid,$params);
		}
	} else {
		$fid = $params['form_id'];
	}
	$parms = array(
		'form_id'=>$fid,
		'resume'=>'PWFBrowse,browse_list', //parameters for cancellation-redirect
		'passthru'=>serialize($params) //scalar data to be provided as a parameter to the 'resume' action
	);
	$pf->DoAction('show_form',$id,$parms,$returnid);
	return;
} else {
	$params['message'] = $this->_PrettyMessage('error_module_forms',FALSE);
	$this->Redirect($id,'browse_list',$returnid,$params);
}
