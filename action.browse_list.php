<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

$pconfig = $this->_CheckAccess('admin');
if ($pconfig || $this->_CheckAccess('modify')) {
	$pmod = TRUE;
} elseif ($this->_CheckAccess('view')) {
	$pmod = FALSE;
} else {
	exit;
}

$tplvars = [];
$tplvars['pconfig'] = ($pconfig)?1:0;
$tplvars['pmod'] = ($pmod)?1:0;

if (isset($params['passthru'])) { //returning from add-record
	$params += unserialize($params['passthru']);
}

$bid = (int)$params['browser_id'];
$fid = (int)$params['form_id'];

$this->_BuildNav($id, $returnid, $params, $tplvars);
$tplvars['start_form'] = $this->CreateFormStart($id, 'multi_record', $returnid, 'POST', '', '', '',
	['browser_id'=>$bid, 'form_id'=>$fid]);
$tplvars['end_form'] = $this->CreateFormEnd();

if (!empty($params['message'])) {
	$tplvars['message'] = $params['message'];
}

$pre = cms_db_prefix();
$sql = 'SELECT name,pagerows FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
$data = $db->GetRow($sql, [$bid]);
$tplvars['browser_title'] = $data['name'];
$pagerows = (int)$data['pagerows']; //0 means unlimited

$sql = 'SELECT name,sorted FROM '.$pre.'module_pwbr_field
WHERE browser_id=? AND shown=1 ORDER BY order_by';
$data = PWFBrowse\Utils::SafeGet($sql, [$params['browser_id']]);
$colnames = array_column($data, 'name');
$colsorts = array_map(function ($v) {
	return (int)$v;
}, array_column($data, 'sorted'));
$tplvars['colnames'] = $colnames;
$tplvars['colsorts'] = $colsorts;

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();

//script accumulators
$jsincs = [];
$jsfuncs = [];
$jsloads = [];
$baseurl = $this->GetModuleURLPath();

$sql = 'SELECT record_id,contents FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
$data = PWFBrowse\Utils::SafeGet($sql, [$params['browser_id']]);
$rows = [];
//if ($data) {
	$icon_view = $theme->DisplayImage('icons/system/view.gif', $this->Lang('view'), '', '', 'systemicon');
	if ($pmod) {
		$icon_edit = $theme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
		$icon_delete = $theme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
	}
	$icon_export = $theme->DisplayImage('icons/system/export.gif', $this->Lang('export'), '', '', 'systemicon');

	$funcs = new PWFBrowse\RecordContent();
	foreach ($data as &$one) {
		$fields = [];
		$browsedata = $funcs->Decrypt($this, $one['contents']);
		if ($browsedata) {
			//include data for fields named in $colnames
			foreach ($browsedata as $field) { //$key unused
				if (count($field) == 2) {
					$indx = array_search($field[0], $colnames);
					if ($indx !== FALSE) {
						$fields[$indx] = $field[1];
					}
				} else { //format-parameter(s) present
					PWFBrowse\Utils::FormatRecord($this, $field, $browsedata);
					if (!is_array($field[0])) {
						$indx = array_search($field[0], $colnames);
						if ($indx !== FALSE) {
							$fields[$indx] = $field[1];
						}
					} else {
						//output sequence-fields
						foreach ($field[0] as $skey=>$sname) {
							$indx = array_search($sname, $colnames);
							if ($indx !== FALSE) {
								$fields[$indx] = $field[1][$skey];
							}
						}
					}
				}
			}
		}
		if ($fields) {
			$rid = (int)$one['record_id'];
			$oneset = new stdClass();
			ksort($fields); //conform order to titles
			$oneset->fields = $fields;
			$oneset->view = $this->CreateLink($id, 'browse_record', '', $icon_view,
				['record_id'=>$rid, 'browser_id'=>$bid, 'form_id'=>$fid]);
			if ($pmod) {
				$oneset->edit = $this->CreateLink($id, 'browse_record', '', $icon_edit,
				['record_id'=>$rid, 'browser_id'=>$bid, 'form_id'=>$fid, 'edit'=>1]);
			}
			$oneset->export = $this->CreateLink($id, 'export_record', '', $icon_export,
				['record_id'=>$rid, 'browser_id'=>$bid, 'form_id'=>$fid]);
			if ($pmod) {
				$oneset->delete = $this->CreateLink($id, 'delete_record', '', $icon_delete,
				['record_id'=>$rid, 'browser_id'=>$bid, 'form_id'=>$fid],
				$this->Lang('confirm_delete_record'));
			}
			$oneset->selected = $this->CreateInputCheckbox($id, 'sel[]', $rid, -1);
			$rows[] = $oneset;
		}
	}
	unset($one);
//}

$tplvars['rows'] = $rows;
$rcount = count($rows);
$tplvars['rcount'] = $rcount;
if ($rcount) {
	if ($rcount > 1) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.metadata.min.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.SSsort.min.js"></script>
EOS;
		$jsloads[] = <<<EOS
 $('#submissions').addClass('table_sort').SSsort({
  sortClass: 'SortAble',
  ascClass: 'SortUp',
  descClass: 'SortDown',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s',
  paginate: TRUE,
  pagesize: {$pagerows},
  currentid: 'cpage',
  countid: 'tpage'
 });
EOS;
/*TODO js-equivalent of mb_sort
	$jsfuncs[] = <<<'EOS'
 $.SSsort.addParser({
  id: 'textinput',
  is: function(s,node) {
   var n = node.childNodes[0];
   return (n && n.nodeName.toLowerCase() == 'input' && n.type.toLowerCase() == 'text');
  },
  format: function(s,node) {
   return $.trim(node.childNodes[0].value);
  },
  watch: TRUE,
  type: 'text'
 });
EOS;
*/
		$jsfuncs[] = <<<'EOS'
function select_all(cb) {
 $('#submissions > tbody').find('input[type="checkbox"]').attr('checked',cb.checked);
}
EOS;
		$tplvars['header_checkbox'] =
			$this->CreateInputCheckbox($id, 'selectall', TRUE, FALSE, 'onclick="select_all(this);"');
	} else {
		$tplvars['header_checkbox'] = NULL;
	}

	if ($pagerows && $rcount>$pagerows) {
		//more setup for SSsort
		$curpg='<span id="cpage">1</span>';
		$totpg='<span id="tpage">'.ceil($rcount/$pagerows).'</span>';

		$choices = [strval($pagerows) => $pagerows];
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if ($n < $rcount) {
			$choices[strval($n)] = $n;
		}
		$n *= 2;
		if ($n < $rcount) {
			$choices[strval($n)] = $n;
		}
		$choices[$this->Lang('all')] = 0;

		$tplvars += [
			'hasnav'=>1,
			'first'=>'<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>',
			'prev'=>'<a href="javascript:pageback()">'.$this->Lang('previous').'</a>',
			'next'=>'<a href="javascript:pageforw()">'.$this->Lang('next').'</a>',
			'last'=>'<a href="javascript:pagelast()">'.$this->Lang('last').'</a>',
			'pageof'=>$this->Lang('pageof', $curpg, $totpg),
			'rowchanger'=>$this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows, 'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows')
		];

		$jsfuncs[] = <<<'EOS'
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
	} else {
		$tplvars['hasnav'] = 0;
	}

	$jsfuncs[] = <<<EOS
function sel_count() {
 var cb = $('input[name="{$id}sel[]"]:checked');
 return cb.length;
}
function any_selected() {
 return (sel_count() > 0);
}
function confirm_selected(msg) {
 if (sel_count() > 0) {
  return confirm(msg);
 } else {
  return false;
 }
}
EOS;
	if ($this->_CheckAccess('view') || $this->_CheckAccess('admin')) {
		$tplvars['export'] = $this->CreateInputSubmit($id, 'export', $this->Lang('export'),
		'title="'.$this->Lang('tip_export_selected_records').
		'"  onclick="return any_selected();"');
	}
	if ($pmod) {
		$tplvars['delete'] = $this->CreateInputSubmit($id, 'delete', $this->Lang('delete'),
		'title="'.$this->Lang('tip_delete_selected_records').
		'" onclick="return confirm_selected(\''.$this->Lang('confirm_delete_sel').'\');"');
	}
} else {
	$tplvars['norecords'] = $this->Lang('norecords');
}

if ($pmod) {
	$t = $this->Lang('title_add_record');
	$icon_add = $theme->DisplayImage('icons/system/newobject.gif', $t, '', '', 'systemicon');
	$tplvars['iconlinkadd'] = $this->CreateLink($id, 'add_record', '', $icon_add,
			['form_id'=>$fid, 'browser_id'=>$bid]);
	$tplvars['textlinkadd'] = $this->CreateLink($id, 'add_record', '', $t,
			['form_id'=>$fid, 'browser_id'=>$bid]);
}

//replace href attribute in existing stylesheet link (early in page-processing)
$cssfile = $this->GetPreference('list_cssfile');
$u = ($cssfile) ?
	PWFBrowse\Utils::GetUploadsUrl($this).'/'.$cssfile: //using custom css for table
	$baseurl.'/css/list-view.css';
$t = <<<EOS
<script type="text/javascript">
//<![CDATA[
 document.getElementById('adminstyler').setAttribute('href',"{$u}");
//]]>
</script>
EOS;

$jsall = NULL;
PWFBrowse\Utils::MergeJS($jsincs, $jsfuncs, $jsloads, $jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo $t;
echo PWFBrowse\Utils::ProcessTemplate($this, 'browse_list.tpl', $tplvars);
if ($jsall) {
	echo $jsall;
}
