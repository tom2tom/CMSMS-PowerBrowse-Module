<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

$pre = \cms_db_prefix();

if (isset($params['browser'])) {
	$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE name=?';
	$bid = $db->GetOne($sql, [$params['browser']]);
} elseif (isset($params['browser_id'])) {
	$bid = (int)$params['browser_id'];
}

$tplvars = [];

if (!empty($params['message'])) {
	$tplvars['message'] = $params['message'];
}

$utils = new PWFBrowse\Utils();

$sql = 'SELECT name,pagerows FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
$data = $db->GetRow($sql, [$bid]);
$tplvars['browser_title'] = $data['name'];
$pagerows = (int)$data['pagerows']; //0 means unlimited

$sql = 'SELECT name,sorted FROM '.$pre.'module_pwbr_field
WHERE browser_id=? AND frontshown=1 ORDER BY order_by';
$data = $utils->SafeGet($sql, [$bid]);
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

//script accumulators
$jsincs = [];
$jsfuncs = [];
$jsloads = [];
$baseurl = $this->GetModuleURLPath();

$sql = 'SELECT rounds,contents FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
$data = $utils->SafeGet($sql, [$bid]);
$rows = [];
//if ($data) {
	$cn = count($colnames);
	$funcs = new PWFBrowse\RecordContent();
	foreach ($data as &$one) {
		$browsedata = $funcs->Decrypt($this, $one['rounds'], $one['contents']);
		if ($browsedata) {
			$fields = [];
			//include data for fields named in $colnames
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
			$rows[] = $fields;
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
	$jsfuncs[] = <<<EOS
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
	} else { //rowscount <= pagerows
		$tplvars['hasnav'] = 0;
	}
} else {
	$tplvars['norecords'] = $this->Lang('norecords');
}

//apply styling
$cssfile = $this->GetPreference('list_cssfile');
$url = ($cssfile) ?
	$utils->GetUploadsUrl($this).'/'.$cssfile: //using custom css for table
	$baseurl.'/css/list-view.css';
$t = <<<EOS
var linkadd = '<link rel="stylesheet" type="text/css" href="{$url}" />',
 \$head = $('head'),
 \$linklast = \$head.find('link[rel="stylesheet"]:last');
if (\$linklast.length) {
 \$linklast.after(linkadd);
} else {
 \$head.append(linkadd);
}
EOS;
echo $utils->MergeJS(NULL, $t, NULL);

$utils->Generate($this, 'default.tpl', $tplvars, $jsincs, $jsfuncs, $jsloads);
