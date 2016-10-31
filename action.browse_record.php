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
	foreach ($params['field'] as $key=>$name) {
		$collapsed[$key] = array($name, html_entity_decode($params['value'][$key])); //decode probably not needed
	}
	$funcs = new PWFBrowse\RecordStore();
	$funcs->Update($this,$pre,$params['record_id'],$collapsed);
	$this->Redirect($id,'browse_list',$returnid,$params);
}

$funcs = new PWFBrowse\RecordLoad();
list($when,$browsedata) = $funcs->Load($this,$pre,$params['record_id']);
if (!$browsedata) {
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

$bname = PWFBrowse\Utils::GetBrowserNameFromID($params['browser_id']);
$dtfmt = FALSE;
$content = array();
if (isset($params['edit'])) {
	$hidden = array();
	$tplvars['title_browser'] = $this->Lang('title_submitted_edit',$bname);
	foreach ($browsedata as $key=>$field) {
		$title = $field[0];
		$hidden[] = $this->CreateInputHidden($id,'field['.$key.']',$title);
		if ($key == 'submitted' || $key == 'modified' || (isset($field[2]) && $field[2]=='stamp')) {
			if ($dtfmt === FALSE) {
				$dtfmt = trim($this->GetPreference('date_format').' '.$this->GetPreference('time_format'));
			}
			if ($dtfmt) {
				$dt = new DateTime('@'.$field[1],NULL);
				$value = $dt->format($dtfmt);
				if ($key == 'submitted' || $key == 'modified') {
					$hidden[] = $this->CreateInputHidden($id,'value['.$key.']',$field[1]);
					$content[] = array($title,$value); //no change for this value
				} else {
					$input = $this->CreateInputText($id,'value['.$key.']',$value,60);
					$content[] = array(htmlentities($title),$input);
				}
				continue;
			}
		}
		$value = $field[1];
		$len = strlen($value);
		$newline = strpos($value,PHP_EOL) !== FALSE || strpos($value,"<br") !== FALSE;
		if ($len > 50 || $newline) {
			$rows = $len / 50 + $newline + 3;
			$input = $this->CreateTextArea(FALSE,$id,$value,'value['.$key.']','','','','',
				50,$rows,'','',
				'style="width:50em;height:'.$rows.'em;"');
		} else {
			$input = $this->CreateInputText($id,'value['.$key.']',$value,60,250);
		}
		$content[] = array(htmlentities($title),$input);
	}
	$tplvars['submit'] = $this->CreateInputSubmit($id,'submit',$this->Lang('submit'));
	$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));
} else { //view
	$hidden = NULL;
	$tplvars['title_browser'] = $this->Lang('title_submitted_as',$bname);
	foreach ($browsedata as $key=>$field) {
		if ($key == 'submitted' || $key == 'modified' || (isset($field[2]) && $field[2]=='stamp')) {
			if ($dtfmt === FALSE) {
				$dtfmt = trim($this->GetPreference('date_format').' '.$this->GetPreference('time_format'));
			}
			if ($dtfmt) {
				$dt = new DateTime('@'.$field[1],NULL);
				$content[] = array($field[0],$dt->format($dtfmt));
				continue;
			}
		}
		$content[] = array(htmlentities($field[0]),htmlentities($field[1]));
	}
	$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('close'));
}
$tplvars['content'] = $content;
$tplvars['hidden'] = implode(PHP_EOL,$hidden);

echo PWFBrowse\Utils::ProcessTemplate($this,'browse_record.tpl',$tplvars);
