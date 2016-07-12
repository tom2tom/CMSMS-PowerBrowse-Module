<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

//setup tabbed page to edit a browser's parameters

$this->BuildNav($id,$returnid,$params,$tplvars);
if (!empty($params['message']))
	$tplvars['message'] = $params['message'];
$tab = $this->GetActiveTab($params);
$tplvars['tabs_start'] = $this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('tab_main'),($tab == 'maintab')).
	$this->SetTabHeader('listtab',$this->Lang('tab_list'),($tab == 'listtab')).
	$this->EndTabHeaders() . $this->StartTabContent();

$tplvars = $tplvars + array(
	'start_form'=>$this->CreateFormStart($id,'edit_browser',$returnid),
	'end_form'=>$this->CreateFormEnd(),
	'hidden'=>$this->CreateInputHidden($id,'browser_id',$params['browser_id']).
		$this->CreateInputHidden($id,'active_tab',''),
	'tabs_end'=>$this->EndTabContent(),
	'tab_end'=>$this->EndTab(), //must be after EndTabContent() - CMSMS2 workaround
	'maintab_start'=>$this->StartTab('maintab'),
	'listtab_start'=>$this->StartTab('listtab')
);

//script accumulators
$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

//======= MAIN TAB ========

$pre = cms_db_prefix();
$row = $db->GetRow('SELECT * FROM '.$pre.'module_pwbr_browser WHERE browser_id=?',array($params['browser_id']));

$tplvars = $tplvars + array(
	'title_form_name'=>$this->Lang('title_form_name'),
	'form_name'=>$row['form_name'],
	'title_browser_name'=>$this->Lang('title_browser_name'),
	'input_browser_name'=>$this->CreateInputText($id,'browser_name',$row['name'],50,256)
);

if ($this->GetPreference('owned_forms')) {
	$sel = array('&lt;'.$this->Lang('all').'&gt;' => 0);
	//find all valid owners
	//NOTE cmsms function check_permission() always returns FALSE for everyone
	//except the current user, so we replicate its backend operation here
	$sql = 'SELECT DISTINCT U.user_id,U.username,U.first_name,U.last_name
FROM '.$pre.'users U
JOIN '.$pre.'user_groups UG ON U.user_id = UG.user_id
JOIN '.$pre.'group_perms GP ON GP.group_id = UG.group_id
JOIN '.$pre.'permissions P ON P.permission_id = GP.permission_id
JOIN '.$pre.'groups GR ON GR.group_id = UG.group_id
WHERE U.admin_access=1 AND U.active=1 AND GR.active=1 AND
P.permission_name IN("ModifyPwBrowsers","ModifyPwFormData")
ORDER BY U.last_name,U.first_name';
	$rs = $db->Execute($sql);
	if ($rs) {
		while($urow = $rs->FetchRow())
		{
			$name = trim($urow['first_name'].' '.$urow['last_name']);
			if ($name == '')
				$name = trim($urow['username']);
			$sel[$name] = (int)$urow['user_id'];
		}
		$rs->Close();
	}
	$tplvars['title_browser_owner'] = $this->Lang('title_browser_owner');
	$tplvars['input_browser_owner'] =
		$this->CreateInputDropdown($id,'browser_owner',$sel,-1,$row['owner']);
}

//======= DISPLAY TAB ==========

$tplvars['title_pagerows'] = $this->Lang('title_pagerows');
$tplvars['input_pagerows'] =
	$this->CreateInputText($id,'browser_pagerows',$row['pagerows'],5);
$tplvars['help_pagerows'] = $this->Lang('help_pagerows');

$sql = 'SELECT * FROM '.$pre.'module_pwbr_field WHERE browser_id=? ORDER BY order_by';
$fields = $db->GetAll($sql,array($params['browser_id']));
if ($fields) {
	$tplvars = $tplvars + array(
		'title_data'=>$this->Lang('title_data'),
		'title_name'=>$this->Lang('title_field_identity'),
		'title_display'=>$this->Lang('title_display'),
		'title_sort'=>$this->Lang('title_sort'),
		'title_move'=>$this->Lang('title_move')
	);

	$mc = 0;
	$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
		cms_utils::get_theme_object();
	$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$this->Lang('up'),'','','systemicon');
	$icondn = $theme->DisplayImage('icons/system/arrow-d.gif',$this->Lang('down'),'','','systemicon');

	$formatted = array();
	foreach ($fields as &$one) {
		$fid = (int)$one['field_id'];
		$oneset = new stdClass();
		$oneset->order = '<input type="hidden" name="'.$id.'orders[]" value="'.$fid.'" />';
		$oneset->name = $one['name'];
		$oneset->display = $this->CreateInputCheckbox($id,'shown[]',$fid,(($one['shown'])?$fid:-1));
		$oneset->sort = $this->CreateInputCheckbox($id,'sortable[]',$fid,(($one['sorted'])?$fid:-1));
		$oneset->down = '';
		if ($mc) {
			//there's a previous item,create the appropriate links
			$oneset->up = $this->CreateLink($id,'move_field',$returnid,
				$iconup,array('field_id'=>$fid,'prev_id'=>$previd));
			$formatted[($mc-1)]->down = $this->CreateLink($id,'move_field',$returnid,
				$icondn,array('field_id'=>$previd,'next_id'=>$fid));
		} else
			$oneset->up = '';
		$mc++;
		$previd = $fid; //i.e. always set before use
		$formatted[] = $oneset;
	}
	unset($one);

	$tplvars['fields'] = $formatted;
	$rc = count($fields);
	$tplvars['rcount'] = $rc;

	if ($rc > 1) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/jquery.tablednd.min.js"></script>
EOS;
		$jsloads[] = <<<EOS
 $('.updown').css('display','none');
 $('.dndhelp').css('display','block');
 $('#listfields').addClass('table_drag').tableDnD({
  dragClass: 'row1hover',
  onDrop: function(table, droprows) {
	var odd = true;
	var oddclass = 'row1';
	var evenclass = 'row2';
	var droprow = $(droprows)[0];
	$(table).find('tbody tr').each(function() {
	 var name = odd ? oddclass : evenclass;
	 if (this === droprow) {
	   name = name+'hover';
	 }
	 $(this).removeClass().addClass(name);
	 odd = !odd;
	});
  }
 }).find('tbody tr').removeAttr('onmouseover').removeAttr('onmouseout').mouseover(function() {
	var now = $(this).attr('class');
	$(this).attr('class', now+'hover');
 }).mouseout(function() {
	var now = $(this).attr('class');
	var to = now.indexOf('hover');
	$(this).attr('class', now.substring(0,to));
 });

EOS;
		$jsfuncs[] =<<<EOS
function select_all(cb) {
 var keep,target,boxes,st;
 switch (cb.name) {
  case '{$id}allshow':
    keep = true;
    target = 'shown[]';
    break;
  case '{$id}allsort':
    keep = false;
    target = 'sortable[]';
    break;
  default:
    return;
 }
 boxes = $('#listfields > tbody').find('input[name="{$id}'+target+'"]');
 st = cb.checked;
 boxes.attr('checked',st);
 if (keep && !st) {
  $(boxes[0]).attr('checked',true);
 }
}

EOS;
		$tplvars = $tplvars + array(
			'select_all1'=>$this->CreateInputCheckbox($id,'allshow',true,false,'onclick="select_all(this);"'),
			'select_all2'=>$this->CreateInputCheckbox($id,'allsort',true,false,'onclick="select_all(this);"'),
			'help_order'=>$this->Lang('help_order'),
			'help_dnd'=>$this->Lang('help_dnd')
		);
	}
} else {
	$tplvars = $tplvars + array(
		'nofields',$this->Lang('nofields'),
		'rcount',0
	);
}

$jsfuncs[] = <<<EOS
function set_tab() {
 var active = $('#page_tabs > .active');
 $('#{$id}active_tab').val(active.attr('id'));
}

EOS;

if ($jsloads) {
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}

$tplvars = $tplvars + array(
	'save'=>$this->CreateInputSubmit($id,'submit',$this->Lang('save'),'onclick="set_tab();"'),
	'apply'=>$this->CreateInputSubmit($id,'apply',$this->Lang('apply'),'title="'.$this->Lang('save_and_continue').'" onclick="set_tab();"'),
	'cancel'=>$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'),'onclick="set_tab();"'),
	'jsincs'=>$jsincs,
	'jsfuncs'=>$jsfuncs
);
