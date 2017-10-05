<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

$pconfig = $this->_CheckAccess('admin');
if ($pconfig || $this->_CheckAccess('modify')) {
	$pmod = TRUE;
} elseif ($this->_CheckAccess('view')) {
	$pmod = FALSE;
} else {
	exit;
}

$tplvars = [];
$tplvars['pconfig'] = ($pconfig) ? 1 : 0;
$tplvars['pmod'] = ($pmod) ? 1 : 0;

if (isset($params['passthru'])) { //returning from add-record
	$params += unserialize($params['passthru']);
}

$bid = (int) $params['browser_id'];
$fid = (int) $params['form_id'];

$this->_BuildNav($id, $returnid, $params, $tplvars);
$tplvars['start_form'] = $this->CreateFormStart($id, 'multi_record', $returnid, 'POST', '', '', '',
	['browser_id' => $bid, 'form_id' => $fid]);
$tplvars['end_form'] = $this->CreateFormEnd();

if (!empty($params['message'])) {
	$tplvars['message'] = $params['message'];
}

$utils = new PWFBrowse\Utils();
$pre = cms_db_prefix();
$sql = 'SELECT name,pagerows FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
$data = $db->GetRow($sql, [$bid]);
$tplvars['browser_title'] = $data['name'];
$pagerows = (int) $data['pagerows']; //0 means unlimited

$sql = 'SELECT name,sorted FROM '.$pre.'module_pwbr_field
WHERE browser_id=? AND shown=1 ORDER BY order_by';
$data = $utils->SafeGet($sql, [$params['browser_id']]);

if (function_exists('array_column')) { //PHP 5.5+
	$colnames = array_column($data, 'name');
} else {
	$colnames = array_map(function ($one) {
		return $one['name'];
	}, $data);
}
$colsorts = array_map(function ($one) {
	return (int) $one['sorted'];
}, $data);
$tplvars['colnames'] = $colnames;
$tplvars['colsorts'] = $colsorts;

$theme = ($this->before20) ? cmsms()->get_variable('admintheme') :
	cms_utils::get_theme_object();

//script accumulators
$jsincs = [];
$jsfuncs = [];
$jsloads = [];
$baseurl = $this->GetModuleURLPath();

$sql = 'SELECT record_id,rounds,pass,contents FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
$data = $utils->SafeGet($sql, [$params['browser_id']]);
$rows = [];
//if ($data) {
	$cfuncs = new PWFBrowse\Crypter($this);
	$pwcache = [$cfuncs->decrypt_preference(PWFBrowse\Crypter::MKEY)]; //default P/W

	$icon_view = $theme->DisplayImage('icons/system/view.gif', $this->Lang('view'), '', '', 'systemicon');
	if ($pmod) {
		$icon_edit = $theme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
		$icon_delete = $theme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
	}
	$icon_export = $theme->DisplayImage('icons/system/export.gif', $this->Lang('export'), '', '', 'systemicon');
	$cn = count($colnames);
	$funcs = new PWFBrowse\RecordContent();
	foreach ($data as &$one) {
		$indx = $one['pass'] + 0;
		if (!isset($pwcache[$indx])) {
			$pwcache[$i] = $cfuncs->decrypt_preference('newpass'.$indx);
		}
		$pw = $pwcache[$indx];

		$browsedata = $funcs->Decrypt($this, $one['rounds'], $one['contents'], FALSE, $cfuncs, $pw);

		if ($browsedata) {
			$fields = [];
			$cd = count($browsedata); //variable: maybe missing ('Modified') or extra (sequences)
			for ($indx=0; $indx<$cn; $indx++) {
				$title = $colnames[$indx];
				foreach ($browsedata  as $key => &$field) {
					if ($field[0] == $title) {
						if (count($field) == 2) { //no format-parameter(s) present 
							$fields[$indx] = $field[1];
						} elseif ($key != '_ss') { //not a sequence start
							$fields[$indx] = $funcs->Format($this, $field, $browsedata);
						} else {
							$indx = $funcs->ListSequence($this, $colnames, $browsedata, $indx, $fields);
						}
						unset($field);
						continue 2;
					} elseif ($key == '_ss') { //unwanted sequence-start
						$indx = $funcs->PassSequence($browsedata, $indx);
						unset($field);
						continue 2;
					}
				}
				unset($field);
				$fields[$indx] = NULL;
			}

			$rid = (int) $one['record_id'];
			$oneset = new stdClass();
			$oneset->fields = $fields;
			$oneset->view = $this->CreateLink($id, 'open_record', '', $icon_view,
				['record_id' => $rid, 'browser_id' => $bid, 'form_id' => $fid]);
			if ($pmod) {
				$oneset->edit = $this->CreateLink($id, 'open_record', '', $icon_edit,
				['record_id' => $rid, 'browser_id' => $bid, 'form_id' => $fid, 'edit' => 1]);
			}
			$oneset->export = $this->CreateLink($id, 'export_record', '', $icon_export,
				['record_id' => $rid, 'browser_id' => $bid, 'form_id' => $fid]);
			if ($pmod) {
				$oneset->delete = $this->CreateLink($id, 'delete_record', '', $icon_delete,
				['record_id' => $rid, 'browser_id' => $bid, 'form_id' => $fid],
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
  paginate: true,
  pagesize: {$pagerows},
  currentid: 'cpage',
  countid: 'tpage'
 });
EOS;
		$jsloads[] = <<<'EOS'
 var shifted = false,
  firstClicked = null;
 $checks = $('#submissions > tbody').find('input[type="checkbox"]');
 $checks.click(function() {
  if (shifted && firstClicked) {
   var i,
    first = $checks.index(firstClicked),
    last = $checks.index(this),
    chk = firstClicked.checked;
   if (first < last) {
    for (i = first; i <= last; i++) {
     $checks[i].checked = chk;
    }
   } else if (first > last) {
    for (i = first; i >= last; i--) {
     $checks[i].checked = chk;
    }
   }
  }
  firstClicked = this;
 });
 $(this).keydown(function(e) {
  if (e.keyCode == 16) {
   shifted = true;
  }
 }).keyup(function(e) {
  if (e.keyCode == 16) {
   shifted = false;
  }
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
var $checks;
function select_all(cb) {
 $checks.attr('checked',cb.checked);
}
EOS;
		$tplvars['header_checkbox'] =
			$this->CreateInputCheckbox($id, 'selectall', TRUE, FALSE, 'onclick="select_all(this);"');
	} else {
		$tplvars['header_checkbox'] = NULL;
	}

	if ($pagerows && $rcount > $pagerows) {
		//more setup for SSsort
		$curpg = '<span id="cpage">1</span>';
		$totpg = '<span id="tpage">'.ceil($rcount / $pagerows).'</span>';

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
			'hasnav' => 1,
			'first' => '<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>',
			'prev' => '<a href="javascript:pageback()">'.$this->Lang('previous').'</a>',
			'next' => '<a href="javascript:pageforw()">'.$this->Lang('next').'</a>',
			'last' => '<a href="javascript:pagelast()">'.$this->Lang('last').'</a>',
			'pageof' => $this->Lang('pageof', $curpg, $totpg),
			'rowchanger' => $this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows, 'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows'),
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

	$jsfuncs[] = <<<'EOS'
function issel() {
 var c = false;
 $checks.each(function() {
  if (this.checked) {
   c = true;
   return false;
  }
 });
 return c;
}
function confirm_selected(msg) {
 if (issel()) {
  return confirm(msg);
 } else {
  return false;
 }
}
EOS;
	if ($this->_CheckAccess('view') || $this->_CheckAccess('admin')) {
		$tplvars['export'] = $this->CreateInputSubmit($id, 'export', $this->Lang('export'),
		'title="'.$this->Lang('tip_export_selected_records').
		'" onclick="return issel();"');
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
		['form_id' => $fid, 'browser_id' => $bid]);
	$tplvars['textlinkadd'] = $this->CreateLink($id, 'add_record', '', $t,
		['form_id' => $fid, 'browser_id' => $bid]);
}

//replace href attribute in existing stylesheet link (early in page-processing)
$cssfile = $this->GetPreference('list_cssfile');
$u = ($cssfile) ?
	$utils->GetUploadsUrl($this).'/'.$cssfile : //using custom css for table
	$baseurl.'/css/list-view.css';
echo <<<EOS
<script type="text/javascript">
//<![CDATA[
 document.getElementById('adminstyler').setAttribute('href',"{$u}");
//]]>
</script>
EOS;

$utils->Generate($this, 'browse_list.tpl', $tplvars, $jsincs, $jsfuncs, $jsloads);
