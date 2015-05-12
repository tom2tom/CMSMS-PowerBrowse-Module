<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!($this->CheckAccess('admin') || $this->CheckAccess('view'))) exit;

if(isset($params['cancel']))
	$this->Redirect($id,'browse_list',$returnid,$params);
if(isset($params['submit']))
{
	$collapsed = array();
	//TODO field identifiers in the saved data
	foreach($params['field'] as $k=>$name)
		$collapsed[] = array($name, html_entity_decode($params['value'][$k]));
	$pass = $this->GetPreference('default_phrase');
	$funcs = new pwbrRecordStore();
	$funcs->Update($params['record_id'],$collapsed,$pass,$db,cms_db_prefix());
	$this->Redirect($id,'browse_list',$returnid,$params);
}

$funcs = new pwbrRecordLoad();
$pass = $this->GetPreference('default_phrase');
list($when,$data) = $funcs->Load($params['record_id'],$pass,$this);
if(!$when)
{
	$params['message']= $this->PrettyMessage('error_data',FALSE);
	$this->Redirect($id,'browse_list',$returnid,$params);
}

$this->buildBrowseNav($id,$returnid,$params);

$smarty->assign('start_form',
	$this->CreateFormStart($id,'browse_record',$returnid,'POST','','','',
		array('record_id'=>$params['record_id'],
		'browser_id'=>$params['browser_id'],
		'form_id'=>$params['form_id'],
		'submit_when'=>$when)));
$smarty->assign('end_form',$this->CreateFormEnd());
$bname = pwbrUtils::GetBrowserNameFromID($params['browser_id']);
$smarty->assign('title_submit_when',$this->Lang('title_submit_when'));
$smarty->assign('submit_when',$when);

$content = array();
if(isset($params['edit']))
{
	$smarty->assign('title_browser',$this->Lang('title_submitted_edit',$bname));
	foreach($data as &$one)
	{
		$title = $one[0];
		$value = $one[1];
		$len = strlen($value);
		$newline = strpos($value,"\n") !== FALSE || strpos($value,"<br") !== FALSE;
		if($len > 50 || $newline)
		{
			$rows = $len / 50 + $newline + 3;
			$input = $this->CreateTextArea(FALSE,$id,$value,'value[]','','','','',
				50,$rows,'','',
				'style="width:50em;height:'.$rows.'em;"');
		}
		else
			$input = $this->CreateInputText($id,'value[]',$value,60,250);
		$content[] = array(htmlentities($title),
			$this->CreateInputHidden($id,'field[]',$title).$input);
	}
	unset($one);
	$smarty->assign('btncancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));
	$smarty->assign('btnsubmit',$this->CreateInputSubmit($id,'submit',$this->Lang('submit')));
}
else //view
{
	$smarty->assign('title_browser',$this->Lang('title_submitted_as',$bname));
	foreach($data as &$one)
	{
		$content[] = array(htmlentities($one[0]),htmlentities($one[1]));
	}
	unset($one);
	$smarty->assign('btncancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('close')));
}
$smarty->assign('content',$content);

echo $this->ProcessTemplate('browse_record.tpl');

?>
