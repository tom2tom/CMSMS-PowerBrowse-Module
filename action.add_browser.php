<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/
if (!$this->_CheckAccess('modify')) {
	exit;
}

if (isset($params['cancel'])) {
	unset($params);
	$this->Redirect($id, 'defaultadmin');
}

if (isset($params['submit'])) {
	$funcs = new PWFBrowse\BrowserTasks();
	if ($params['browser_id'] == -1) { //add browser
		$browser_id = $funcs->AddBrowser($this, $params);
		$form_id = (int)$params['form_id'];
	} else { //clone
		$browser_id = (int)$params['browser_id'];
		$form_id = (int)$params['form_id'];
		$funcs->CloneBrowser($this, $params);
	}
	unset($params);
	$this->Redirect($id, 'open_browser', '', [
		'form_id'=>$form_id,
		'browser_id'=>$browser_id]);
}

$utils = new PWFBrowse\Utils();
$tplvars = [];

if ($params['browser_id'] == -1) { //add browser
	//TODO c.f. BrowserTasks->AddBrowser(&$mod,&$params)
	$funcs = new PWFBrowse\FormsIface();
	$formList = $funcs->GetBrowsableForms();
	if (!$formList) {
		unset($params);
		$message = $this->_PrettyMessage('noforms', FALSE);
		$this->Redirect($id, 'defaultadmin', '', ['message' => $message]);
	}
	$tplvars['hidden'] = $this->CreateInputHidden($id, 'browser_id', -1);
	$tplvars['title_form_name'] = $this->Lang('title_form_name');
	if (extension_loaded('intl') === TRUE) {
		collator_asort(collator_create(NULL), $formList, Collator::SORT_STRING);
	} else {
		natsort($formList);
	}
	$formSelect = array_flip($formList);
	//must return $params['form_id'],$params['name']
	$tplvars['input_form_name'] = $this->CreateInputDropdown($id, 'form_id',
		array_merge([$this->Lang('select_form')=>-1], $formSelect), -1);
	$tplvars['title_browser_name'] = $this->Lang('title_browser_name');
	$tplvars['input_browser_name'] = $this->CreateInputText($id, 'name', '', 50);
	$tpl = 'add_browser.tpl';
} else { //clone existing browser
	$bid = (int)$params['browser_id'];
	$fid = (int)$params['form_id'];
	$tplvars['hidden'] = $this->CreateInputHidden($id, 'browser_id', $bid).
		$this->CreateInputHidden($id, 'form_id', $fid);
	$tplvars['title_form_name'] = $this->Lang('title_form_name');
	$name = $utils->GetFormNameFromID($fid);
	$tplvars['form_name'] = $name;
	$tplvars['title_browser_oldname'] = $this->Lang('title_browser_oldname');
	$name = $utils->GetBrowserNameFromID($bid);
	$tplvars['browser_oldname'] = $name;
	$tplvars['title_browser_name'] = $this->Lang('title_browser_name');
	$tplvars['input_browser_name'] =
		$this->CreateInputText($id, 'browser_name', $name.' '.$this->Lang('copy'), 50, 256);
	$tpl = 'clone_browser.tpl';
}

$this->_BuildNav($id, $returnid, $params, $tplvars);

$tplvars['start_form'] = $this->CreateFormStart($id, 'add_browser', $returnid);
$tplvars['end_form'] = $this->CreateFormEnd();

$tplvars['save'] = $this->CreateInputSubmit($id, 'submit', $this->Lang('save'));
$tplvars['cancel'] = $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'));

echo $utils->ProcessTemplate($this, $tpl, $tplvars);
