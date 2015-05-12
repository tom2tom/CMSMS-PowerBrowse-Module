<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

//Setup tabbed page to edit a browser's parameters

$this->BuildNav($id,$returnid,$params);
if(!empty($params['message']))
	$smarty->assign('message',$params['message']);
$smarty->assign('start_form',
	$this->CreateFormStart($id,'edit_browser',$returnid));
$smarty->assign('hidden',
	$this->CreateInputHidden($id,'browser_id',$params['browser_id']));
$tab = $this->GetActiveTab($params);
$smarty->assign('tabs_start',$this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('tab_main'),($tab == 'maintab')).
	$this->SetTabHeader('listtab',$this->Lang('tab_list'),($tab == 'listtab')).
	$this->EndTabHeaders() . $this->StartTabContent());
$smarty->assign('tabs_end',$this->EndTabContent());
$smarty->assign('maintab_start',$this->StartTab('maintab'));
$smarty->assign('listtab_start',$this->StartTab('listtab'));
$smarty->assign('tab_end',$this->EndTab());
$smarty->assign('end_form',$this->CreateFormEnd());

$smarty->assign('hidden',$this->CreateInputHidden($id,'active_tab',''));

//======= MAIN TAB ========

$pre = cms_db_prefix();
$row = $db->GetRow('SELECT * FROM '.$pre.'module_pwbr_browser WHERE browser_id=?',array($params['browser_id']));
$smarty->assign('title_form_name', $this->Lang('title_form_name'));
$smarty->assign('form_name',$row['form_name']);
$smarty->assign('title_browser_name',$this->Lang('title_browser_name'));
$smarty->assign('input_browser_name',
	$this->CreateInputText($id,'browser_name',$row['name'],50,256));
if($this->GetPreference('owned_forms'))
{
	$sel = array('&lt;'.$this->Lang('all').'&gt;' => 0);
	//find all valid owners
	//NOTE cmsms function check_permission() always returns FALSE for everyone
	//except the current user, so we replicate its backend operation here
	$sql = 'SELECT DISTINCT U.user_id,U.username,U.first_name,U.last_name
FROM '.$pre.'users U
INNER JOIN '.$pre.'user_groups UG ON U.user_id = UG.user_id
INNER JOIN '.$pre.'group_perms GP ON GP.group_id = UG.group_id
INNER JOIN '.$pre.'permissions P ON P.permission_id = GP.permission_id
INNER JOIN '.$pre.'groups GR ON GR.group_id = UG.group_id
WHERE U.admin_access=1 AND U.active=1 AND GR.active=1 AND
P.permission_name IN("ModifyPwBrowsers","ModifyPwFormData")
ORDER BY U.last_name,U.first_name';
	$rs = $db->Execute($sql);
	if($rs)
	{
		while($urow = $rs->FetchRow())
		{
			$name = trim($urow['first_name'].' '.$urow['last_name']);
			if($name == '')
				$name = trim($urow['username']);
			$sel[$name] = (int)$urow['user_id'];
		}
		$rs->Close();
	}
	$smarty->assign('title_browser_owner',$this->Lang('title_browser_owner'));
	$smarty->assign('input_browser_owner',
		$this->CreateInputDropdown($id,'browser_owner',$sel,-1,$row['owner']));
}

//======= DISPLAY TAB ==========

$js = array(); //script accumulator

$smarty->assign('title_pagerows',$this->Lang('title_pagerows'));
$smarty->assign('input_pagerows',
	$this->CreateInputText($id,'browser_pagerows',$row['pagerows'],5));
$smarty->assign('help_pagerows',$this->Lang('help_pagerows'));

$sql = 'SELECT * FROM '.$pre.'module_pwbr_field WHERE browser_id=? ORDER BY order_by';
$fields = $db->GetArray($sql,array($params['browser_id']));
if($fields)
{
	$smarty->assign('title_data',$this->Lang('title_data'));
	$smarty->assign('title_name',$this->Lang('title_field_identity'));
	$smarty->assign('title_display',$this->Lang('title_display'));
	$smarty->assign('title_sort',$this->Lang('title_sort'));
	$smarty->assign('title_move',$this->Lang('title_move'));

	$mc = 0;
	$previd	= -10;
	$theme = cmsms()->variables['admintheme'];
	$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$this->Lang('up'),'','','systemicon');
	$icondn = $theme->DisplayImage('icons/system/arrow-d.gif',$this->Lang('down'),'','','systemicon');

	$formatted = array();
	foreach($fields as &$one)
	{
		$fid = (int)$one['field_id'];
		$oneset = new stdClass();
		$oneset->order = '<input type="hidden" name="'.$id.'orders[]" value="'.$fid.'" />';
		$oneset->name = $one['name'];
		$oneset->display = $this->CreateInputCheckbox($id,'shown[]',$fid,(($one['shown'])?$fid:-1));
		$oneset->sort = $this->CreateInputCheckbox($id,'sortable[]',$fid,(($one['sorted'])?$fid:-1));
		$oneset->down = '';
		if ($mc)
		{
			//there's a previous item,create the appropriate links
			$oneset->up = $this->CreateLink($id,'move_field',$returnid,
				$iconup,array('field_id'=>$fid,'prev_id'=>$previd));
			$formatted[($mc-1)]->down = $this->CreateLink($id,'move_field',$returnid,
				$icondn,array('field_id'=>$previd,'next_id'=>$fid));
		}
		else
			$oneset->up = '';
		$mc++;
		$formatted[] = $oneset;
	}
	unset($one);

	$smarty->assign('fields',$formatted);
	$rc = count($fields);
	$smarty->assign('rcount',$rc);

	if($rc > 1)
	{
		$js[] =<<<EOS
function set_tab() {
 var active = $('#page_tabs > .active');
 $('#{$id}active_tab').val(active.attr('id'));
}
function select_all(cb) {
 var keep,target,boxes,st = $(cb).attr('checked');
 if(!st) st = false;
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
 boxes=$('#listfields > tbody').find('input[name="{$id}'+target+'"]');
 boxes.attr('checked',st);
 if(keep && !st) {
  $(boxes[0]).attr('checked',true);
 }
}
$(document).ready(function() {
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
});

EOS;
		$smarty->assign('select_all1',
			$this->CreateInputCheckbox($id,'allshow',true,false,'onclick="select_all(this);"'));
		$smarty->assign('select_all2',
			$this->CreateInputCheckbox($id,'allsort',true,false,'onclick="select_all(this);"'));
		$smarty->assign('help_order',$this->Lang('help_order'));
		$smarty->assign('help_dnd',$this->Lang('help_dnd')); 
	}
}
else
{
	$smarty->assign('nofields',$this->Lang('nofields'));
	$smarty->assign('rcount',0);
}

$smarty->assign('save',
	$this->CreateInputSubmit($id,'submit',$this->Lang('save'),'onclick="set_tab();"'));
$smarty->assign('apply',
	$this->CreateInputSubmit($id,'apply',$this->Lang('apply'),
		'title="'.$this->Lang('save_and_continue').'" onclick="set_tab();"'));
$smarty->assign('cancel',
	$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'),'onclick="set_tab();"'));

$smarty->assign('modurl',$this->GetModuleURLPath()); //for including js files
$smarty->assign('jsfuncs',$js);

?>
