<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!$this->CheckAccess('modify')) exit;

if(isset($params['cancel']))
{
	unset($params);
	$this->Redirect($id,'defaultadmin');
}

if(isset($params['submit']))
{
	$funcs = new pwbrBrowserTasks();
	if($params['browser_id'] == -1)//add browser
	{
		$browser_id = $funcs->AddBrowser($this,$params);
		$form_id = (int)$params['form_id'];
	}
	else //clone
	{
		$browser_id = (int)$params['browser_id'];
		$form_id = (int)$params['form_id'];
		$funcs->CloneBrowser($this,$params);
	}
	unset($params);
	$this->Redirect($id,'edit_browser','',array(
		'form_id'=>$form_id,
		'browser_id'=>$browser_id));
}

if($params['browser_id'] == -1)//add browser
{
	$funcs = new pwfBrowserIface('PowerForms');
	if($funcs == FALSE)
	{
		unset($params);
		$message = $this->PrettyMessage('error_module',FALSE);
		$this->Redirect($id,'defaultadmin','',array('message' => $message));
	}
	$formList = $funcs->GetBrowsableForms();
	if(!$formList)
	{
		unset($params);
		$message = $this->PrettyMessage('noforms',FALSE);
		$this->Redirect($id,'defaultadmin','',array('message' => $message));
	}
	$smarty->assign('hidden',$this->CreateInputHidden($id,'browser_id',-1));
	$smarty->assign('title_form_name',$this->Lang('title_form_name'));
	if (extension_loaded('intl') === TRUE)
		collator_asort(collator_create(NULL),$formList,Collator::SORT_STRING);
	else
		natsort($formList);
	$formSelect = array_flip($formList);
	//must return $params['form_id'],$params['name']
	$smarty->assign('input_form_name',
		$this->CreateInputDropdown($id,'form_id',
		array_merge(array($this->Lang('select_form')=>-1),$formSelect),-1));
	$smarty->assign('title_browser_name',$this->Lang('title_browser_name'));
	$smarty->assign('input_browser_name',
		$this->CreateInputText($id,'name','',50));
	$tpl = 'add_browser.tpl';
}
else //clone existing browser
{
	$bid = (int)$params['browser_id'];
	$fid = (int)$params['form_id'];
	$smarty->assign('hidden',$this->CreateInputHidden($id,'browser_id',$bid).
		$this->CreateInputHidden($id,'form_id',$fid));
	$smarty->assign('title_form_name',$this->Lang('title_form_name'));
	$name = pwbrUtils::GetFormNameFromID($fid);
	$smarty->assign('form_name',$name);
	$smarty->assign('title_browser_oldname',$this->Lang('title_browser_oldname'));
	$name = pwbrUtils::GetBrowserNameFromID($bid);
	$smarty->assign('browser_oldname',$name);
	$smarty->assign('title_browser_name',$this->Lang('title_browser_name'));
	$smarty->assign('input_browser_name',
		$this->CreateInputText($id,'browser_name',$name.' '.$this->Lang('copy'),50,256));
	$tpl = 'clone_browser.tpl';
}

$this->BuildNav($id,$returnid,$params);
$tab = $this->GetActiveTab($params);

$smarty->assign('start_form',
	$this->CreateFormStart($id,'add_browser',$returnid));
$smarty->assign('end_form',$this->CreateFormEnd());

$smarty->assign('save',
	$this->CreateInputSubmit($id,'submit',$this->Lang('save')));
$smarty->assign('cancel',
	$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

echo $this->ProcessTemplate($tpl);

?>
