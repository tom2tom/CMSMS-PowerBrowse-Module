<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

$pconfig = $this->CheckAccess('admin');
if($pconfig || $this->CheckAccess('modify'))
	$pmod = TRUE;
elseif($this->CheckAccess('view'))
	$pmod = FALSE;
else
	exit;

$smarty->assign('pconfig',(($pconfig)?1:0));
$smarty->assign('pmod',(($pmod)?1:0));

$bid = (int)$params['browser_id'];
$fid = (int)$params['form_id'];

$this->BuildNav($id,$returnid,$params);
$smarty->assign('start_form',
	$this->CreateFormStart($id,'multi_record',$returnid,'POST','','','',
		array('browser_id'=>$bid,'form_id'=>$fid)));
$smarty->assign('end_form',$this->CreateFormEnd());

if(!empty($params['message']))
	$smarty->assign('message',$params['message']);

$pre = cms_db_prefix();
$sql = 'SELECT name,pagerows FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
$data = $db->GetRow($sql,array($bid));
$smarty->assign('browser_title',$data['name']);
$pagerows = (int)$data['pagerows']; //0 means unlimited

$sql = 'SELECT name,sorted FROM '.$pre.'module_pwbr_field
WHERE browser_id=? AND shown=1 ORDER BY order_by';
$data = $db->GetArray($sql,array($params['browser_id']));
$colnames = array();
$colsorts = array();
//if($data) //REDUNDANT - NEVER COME HERE, OTHERWISE
//{
	foreach($data as &$one)
	{
		$colnames[] = $one['name'];
		$colsorts[] = (int)$one['sorted'];
	}
	unset($one);
//}
$smarty->assign('colnames',$colnames);
$smarty->assign('colsorts',$colsorts);

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();

$js = array(); //script accumulator
		
$sql = 'SELECT record_id,submitted,contents FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
$data = $db->GetArray($sql,array($params['browser_id']));
$rows = array();
//if($data)
//{
	$smarty->assign('title_submit_when',$this->Lang('title_submit_when'));

	$icon_delete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
	$icon_edit = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon');
	$icon_export = $theme->DisplayImage('icons/system/export.gif',$this->Lang('export'),'','','systemicon');
	$icon_view = $theme->DisplayImage('icons/system/view.gif',$this->Lang('view'),'','','systemicon');

	$funcs = new pwbrRecordLoad();
	foreach($data as &$one)
	{
		$fields = array();
		$submission = $funcs->Decrypt($this,$one['contents']);
		if($submission)
		{
			//include data for fields named in $colnames
			foreach($submission as &$sub) //TODO any use for field index?
			{
				$indx = array_search($sub[0],$colnames);
				if ($indx !== FALSE)
					$fields[$indx] = $sub[1];
			}
			unset($sub);
		}
		if($fields)
		{
			$rid = (int)$one['record_id'];
			$oneset = new stdClass();
			$oneset->submitted = $one['submitted'];
			ksort($fields);
			$oneset->fields = $fields;
			$oneset->view = $this->CreateLink($id,'browse_record','',
				$icon_view,
				array('record_id'=>$rid,'browser_id'=>$bid,'form_id'=>$fid));
			if($pmod)
			 $oneset->edit = $this->CreateLink($id,'browse_record','',
				$icon_edit,
				array('record_id'=>$rid,'browser_id'=>$bid,'form_id'=>$fid,'edit'=>1));
			$oneset->export = $this->CreateLink($id,'export_record','',
				$icon_export,
				array('record_id'=>$rid,'browser_id'=>$bid));
			if($pmod)
			 $oneset->delete = $this->CreateLink($id,'delete_record','',
				$icon_delete,
				array('record_id'=>$rid,'browser_id'=>$bid),
				$this->Lang('confirm_delete_record'));
			$oneset->selected = $this->CreateInputCheckbox($id,'sel[]',$rid,-1);
			$rows[] = $oneset;
		}
	}
	unset($one);
//}

$smarty->assign('rows',$rows);
$rcount = count($rows);
if($rcount)
{
	if($pagerows && $rcount>$pagerows)
	{
		$smarty->assign('hasnav',1);
		//setup for SSsort
		$choices = array(strval($pagerows) => $pagerows);
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if($n < $rcount)
			$choices[strval($n)] = $n;
		$n *= 2;
		if($n < $rcount)
			$choices[strval($n)] = $n;
		$choices[$this->Lang('all')] = 0;
		$smarty->assign('rowchanger',
			$this->CreateInputDropdown($id,'pagerows',$choices,-1,$pagerows,
			'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows'));
		$curpg='<span id="cpage">1</span>';
		$totpg='<span id="tpage">'.ceil($rcount/$pagerows).'</span>';
		$smarty->assign('pageof',$this->Lang('pageof',$curpg,$totpg));
		$smarty->assign('first',
		'<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>');
		$smarty->assign('prev',
		'<a href="javascript:pageback()">'.$this->Lang('previous').'</a>');
		$smarty->assign('next',
		'<a href="javascript:pageforw()">'.$this->Lang('next').'</a>');
		$smarty->assign('last',
		'<a href="javascript:pagelast()">'.$this->Lang('last').'</a>');
		$js[] = <<<EOS
function pagefirst() {
 $.SSsort.movePage($('#submissions')[0],false,true);
}
function pagelast() {
 $.SSsort.movePage($('#submissions')[0],true,true);
}
function pageforw() {
 $.SSsort.movePage($('#submissions')[0],true,false);
}
function pageback() {
 $.SSsort.movePage($('#submissions')[0],false,false);
}
function pagerows(cb) {
 $.SSsort.setCurrent($('#submissions')[0],'pagesize',parseInt(cb.value));
}

EOS;
	}
	else
	{
		$smarty->assign('hasnav',0);
	}

	if($rcount > 1)
	{
		$js[] = <<<EOS
$(document).ready(function(){
 $('#submissions').addClass('table_sort').SSsort({
  sortClass: 'SortAble',
  ascClass: 'SortUp',
  descClass: 'SortDown',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s',
  paginate: true,
  pagesize: {$pagerows},
  currentid: 'cpage',
  countid: 'tpage'
 });
});
function select_all(cb) {
 $('#submissions > tbody').find('input[type="checkbox"]').attr('checked',cb.checked);
}

EOS;
		$smarty->assign('header_checkbox',
			$this->CreateInputCheckbox($id,'selectall',true,false,'onclick="select_all(this);"'));
	}
	else
		$smarty->assign('header_checkbox','');

/*	$js[] = <<< EOS
 $.SSsort.addParser({
  id: 'textinput',
  is: function(s,node) {
   var n = node.childNodes[0];
   return (n && n.nodeName.toLowerCase() == 'input' && n.type.toLowerCase() == 'text');
  },
  format: function(s,node) {
   return $.trim(node.childNodes[0].value);
  },
  watch: true,
  type: 'text'
 });

EOS;
*/
	$js[] = <<<EOS
function sel_count() {
 var cb = $('input[name="{$id}sel[]"]:checked');
 return cb.length;
}
function any_selected() {
 return (sel_count() > 0);
}
function confirm_selected(msg) {
 if(sel_count() > 0) {
  return confirm(msg);
 } else {
  return false;
 }
}

EOS;
	if($this->CheckAccess('view') || $this->CheckAccess('admin'))
		$smarty->assign('export',$this->CreateInputSubmit($id,'export',$this->Lang('export'),
		'title="'.$this->Lang('tip_export_selected_records').
		'"  onclick="return any_selected();"'));
	if($pmod)
		$smarty->assign('delete',$this->CreateInputSubmit($id,'delete',$this->Lang('delete'),
		'title="'.$this->Lang('tip_delete_selected_records').
		'" onclick="return confirm_selected(\''.$this->Lang('confirm_delete_sel').'\');"'));
}
else
{
	$smarty->assign('norecords',$this->Lang('norecords'));
}

if($pmod)
{
	$t = $this->Lang('title_add_record');
	$icon_add = $theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon');
	$smarty->assign('iconlinkadd',
		$this->CreateLink($id,'add_record','',
			$icon_add,
			array('form_id'=>$fid,
			'browser_id'=>$bid)));
	$smarty->assign('textlinkadd',
		$this->CreateLink($id,'add_record','',
			$t,
			array('form_id'=>$fid,
			'browser_id'=>$bid)));
}

$myurl = $this->GetModuleURLPath();
$smarty->assign('modurl',$myurl);
$cssfile = $this->GetPreference('list_cssfile');
$t = ($cssfile) ?
	pwbrUtils::GetUploadsUrl($this).'/'.$cssfile: //using custom css for table
	$myurl.'/css/list-view.css';
$smarty->assign('cssurl',$t);

$smarty->assign('jsfuncs',$js);

echo $this->ProcessTemplate('browse_list.tpl');

?>
