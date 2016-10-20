<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!($this->_CheckAccess('admin') || $this->_CheckAccess('view'))) exit;

if (isset($params['cancel']))
	$this->Redirect($id,'browse_list',$returnid,$params);

$pre = cms_db_prefix();
if (isset($params['submit'])) {
	$collapsed = array();
	//TODO field identifiers in the saved data
	foreach ($params['field'] as $k=>$name)
		$collapsed[] = array($name, html_entity_decode($params['value'][$k])); //decode probably not needed
	$funcs = new PWFBrowse\RecordStore();
	$funcs->Update($params['record_id'],$collapsed,$this,$db,$pre);
	$this->Redirect($id,'browse_list',$returnid,$params);
}

$funcs = new PWFBrowse\RecordLoad();
list($when,$data) = $funcs->Load($params['record_id'],$this,$db,$pre);
if (!$when) {
	$params['message']= $this->_PrettyMessage('error_data',FALSE);
	$this->Redirect($id,'browse_list',$returnid,$params);
}

$tplvars = array();
$this->_BuildNav($id,$returnid,$params,$tplvars);

$tplvars['start_form'] =
	$this->CreateFormStart($id,'browse_record',$returnid,'POST','','','',
		array('record_id'=>$params['record_id'],
		'browser_id'=>$params['browser_id'],
		'form_id'=>$params['form_id'],
		'submit_when'=>$when));
$tplvars['end_form'] = $this->CreateFormEnd();
$tplvars['title_submit_when'] = $this->Lang('title_submit_when');
$tplvars['submit_when'] = $when;

$bname = PWFBrowse\Utils::GetBrowserNameFromID($params['browser_id']);
$content = array();
if (isset($params['edit'])) {
	$tplvars['title_browser'] = $this->Lang('title_submitted_edit',$bname);
	foreach ($data as &$one) {
		$title = $one[0];
		$value = $one[1];
		$len = strlen($value);
		$newline = strpos($value,PHP_EOL) !== FALSE || strpos($value,"<br") !== FALSE;
		if ($len > 50 || $newline) {
			$rows = $len / 50 + $newline + 3;
			$input = $this->CreateTextArea(FALSE,$id,$value,'value[]','','','','',
				50,$rows,'','',
				'style="width:50em;height:'.$rows.'em;"');
		} else
			$input = $this->CreateInputText($id,'value[]',$value,60,250);
		$content[] = array(htmlentities($title),
			$this->CreateInputHidden($id,'field[]',$title).$input);
	}
	unset($one);
	$tplvars['btncancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));
	$tplvars['btnsubmit'] = $this->CreateInputSubmit($id,'submit',$this->Lang('submit'));
} else { //view
	$tplvars['title_browser'] = $this->Lang('title_submitted_as',$bname);
	foreach ($data as &$one) {
		$content[] = array(htmlentities($one[0]),htmlentities($one[1]));
	}
	unset($one);
	$tplvars['btncancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('close'));
}
$tplvars['content'] = $content;

echo PWFBrowse\Utils::ProcessTemplate($this,'browse_record.tpl',$tplvars);
