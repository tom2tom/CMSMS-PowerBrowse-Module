<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!($this->_CheckAccess('admin') || $this->_CheckAccess('view'))) {
	exit;
}

if (isset($params['cancel'])) {
	$this->Redirect($id, 'browse_list', $returnid, $params);
}

$funcs = new PWFBrowse\RecordContent();
$pre = cms_db_prefix();
if (isset($params['submit'])) {
	$collapsed = [];
	foreach ($params['field'] as $key=>$name) {
		$collapsed[$key] = [$name, html_entity_decode($params['value'][$key])]; //decode probably not needed
	}
	$funcs->Update($this, $pre, $params['record_id'], $collapsed);
	$this->Redirect($id, 'browse_list', $returnid, $params);
}

list($res, $browsedata) = $funcs->Load($this, $pre, $params['record_id']);
if (!$res) {
	$params['message']= $this->_PrettyMessage('error_data', FALSE);
	$this->Redirect($id, 'browse_list', $returnid, $params);
}

$tplvars = [];
$this->_BuildNav($id, $returnid, $params, $tplvars);

$tplvars['start_form'] =
	$this->CreateFormStart($id, 'browse_record', $returnid, 'POST', '', '', '',
		['record_id'=>$params['record_id'],
		'browser_id'=>$params['browser_id'],
		'form_id'=>$params['form_id']]);
$tplvars['end_form'] = $this->CreateFormEnd();

$bname = PWFBrowse\Utils::GetBrowserNameFromID($params['browser_id']);
$content = [];
if (isset($params['edit'])) {
	$hidden = [];
	$tplvars['title_browser'] = $this->Lang('title_submitted_edit', $bname);
	foreach ($browsedata as $key=>$field) {
		$title = $field[0];
		$hidden[] = $this->CreateInputHidden($id, 'field['.$key.']', $title);
		if (count($field) > 2) { //format-parameter(s) present
			$value = $field[1]; //log current value
			PWFBrowse\Utils::FormatRecord($this, $field, $browsedata);
			if (!is_array($field[0])) {
				if ($key[0] == '_') { //internal-use fake field, not editable
					$hidden[] = $this->CreateInputHidden($id, 'value['.$key.']', $value); //un-formatted value
					$content[] = [htmlentities($title),$field[1]]; //no change for this value
				} else {
					$input = $this->CreateInputText($id, 'value['.$key.']', $field[1], 60); //TODO reconvert when saving
					$content[] = [htmlentities($title),$input];
				}
				continue;
			} else {
				//maybe-editable sequence-fields
//TODO fix this
				foreach ($field[0] as $skey=>$sname) {
					if ($skey[0] == '_') { //internal-use fake field, not editable
						$hidden[] = $this->CreateInputHidden($id, 'value['.$key.']', $field[1][$skey]); //un-formatted value
						$content[] = [htmlentities($title),$field[1][$skey]]; //no change for this value
					} else {
						$input = $this->CreateInputText($id, 'value['.$key.']', $field[1][$skey], 60); //TODO reconvert when saving
						$content[] = [htmlentities($title),$input];
					}
				}
			}
		}
		$value = $field[1];
		$len = strlen($value);
		$newline = strpos($value, PHP_EOL) !== FALSE || strpos($value, "<br") !== FALSE;
		if ($len > 50 || $newline) {
			$rows = $len / 50 + $newline + 3;
			$input = $this->CreateTextArea(FALSE, $id, $value, 'value['.$key.']', '', '', '', '',
				50, $rows, '', '',
				'style="width:50em;height:'.$rows.'em;"');
		} else {
			$input = $this->CreateInputText($id, 'value['.$key.']', $value, 60, 250);
		}
		$content[] = [htmlentities($title),$input];
	}
	$tplvars['hidden'] = implode(PHP_EOL, $hidden);
	$tplvars['submit'] = $this->CreateInputSubmit($id, 'submit', $this->Lang('submit'));
	$tplvars['cancel'] = $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'));
} else { //view
	$tplvars['title_browser'] = $this->Lang('title_submitted_as', $bname);
	foreach ($browsedata as $key=>$field) {
		if (count($field) > 2) { //format-parameter(s) present
			PWFBrowse\Utils::FormatRecord($this, $field, $browsedata);
			if (is_array($field[0])) {
				//output sequence-fields
				foreach ($field[0] as $skey=>$sname) {
					$content[] = [htmlentities($sname),htmlentities($field[1][$skey])];
				}
				continue;
			}
		}
		$content[] = [htmlentities($field[0]),htmlentities($field[1])];
	}
	$tplvars['hidden'] = NULL;
	$tplvars['cancel'] = $this->CreateInputSubmit($id, 'cancel', $this->Lang('close'));
}
$tplvars['content'] = $content;

echo PWFBrowse\Utils::ProcessTemplate($this, 'browse_record.tpl', $tplvars);
