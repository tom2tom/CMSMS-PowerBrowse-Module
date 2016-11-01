<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

$pre = \cms_db_prefix();

if (isset($params['browser'])) {
	$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE name=?';
	$bid = $db->GetOne($sql,array($params['browser']));
} elseif (isset($params['browser_id'])) {
	$bid = (int)$params['browser_id'];
}

$tplvars = array();

if (!empty($params['message']))
	$tplvars['message'] = $params['message'];

$sql = 'SELECT name,pagerows FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
$data = $db->GetRow($sql,array($bid));
$tplvars['browser_title'] = $data['name'];
$pagerows = (int)$data['pagerows']; //0 means unlimited

$sql = 'SELECT name,sorted FROM '.$pre.'module_pwbr_field
WHERE browser_id=? AND frontshown=1 ORDER BY order_by';
$data = PWFBrowse\Utils::SafeGet($sql,array($bid));
$colnames = array_column($data,'name');
$colsorts = array_map(function($v){ return (int)$v; },array_column($data,'sorted'));
$tplvars['colnames'] = $colnames;
$tplvars['colsorts'] = $colsorts;

//script accumulators
$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

$sql = 'SELECT contents FROM '.$pre.'module_pwbr_record WHERE browser_id=?';
$data = PWFBrowse\Utils::SafeGet($sql,array($bid),'col');
$rows = array();
//if ($data) {
	$dtfmt = FALSE;
	$funcs = new PWFBrowse\RecordContent();
	foreach ($data as $stored) {
		$fields = array();
		$browsedata = $funcs->Decrypt($this,$stored);
		if ($browsedata) {
			//include data for fields named in $colnames
			foreach ($browsedata as $key=>$field) {
				$indx = array_search($field[0],$colnames);
				if ($indx !== FALSE) {
					if (isset($field['dt']) {
						if ($dtfmt === FALSE) {
							$dtfmt = trim($this->GetPreference('date_format').' '.$this->GetPreference('time_format'));
						}
						if ($dtfmt) {
							$dt = new DateTime('@'.$field[1],NULL);
							$field[1] = $dt->format($dtfmt);
						}
					}
					$fields[$indx] = $field[1];
//TODO identify & handle FieldsetStart/End : multi-rows instead of multi-cols? how to sort?
				}
			}
		}
		if ($fields) {
			ksort($fields); //conform order to titles
//TODO identify & handle FieldsetStart/End : multi-values per cell instead of multi-cols? how to sort?
			$rows[] = $fields;
		}
	}
//}

$tplvars['rows'] = $rows;
$rcount = count($rows);
$tplvars['rcount'] = $rcount;
if ($rcount) {
	if ($rcount > 1) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/jquery.metadata.min.js"></script>
<script type="text/javascript" src="{$baseurl}/include/jquery.SSsort.min.js"></script>
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
  watch: true,
  type: 'text'
 });
EOS;
*/
	}

	if ($pagerows && $rcount>$pagerows) {
		//more setup for SSsort
		$curpg='<span id="cpage">1</span>';
		$totpg='<span id="tpage">'.ceil($rcount/$pagerows).'</span>';

		$choices = array(strval($pagerows) => $pagerows);
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if ($n < $rcount)
			$choices[strval($n)] = $n;
		$n *= 2;
		if ($n < $rcount)
			$choices[strval($n)] = $n;
		$choices[$this->Lang('all')] = 0;

		$tplvars = $tplvars + array(
			'hasnav'=>1,
			'first'=>'<a href="javascript:pagefirst()">'.$this->Lang('first').'</a>',
			'prev'=>'<a href="javascript:pageback()">'.$this->Lang('previous').'</a>',
			'next'=>'<a href="javascript:pageforw()">'.$this->Lang('next').'</a>',
			'last'=>'<a href="javascript:pagelast()">'.$this->Lang('last').'</a>',
			'pageof'=>$this->Lang('pageof',$curpg,$totpg),
			'rowchanger'=>$this->CreateInputDropdown($id,'pagerows',$choices,-1,$pagerows,'onchange="pagerows(this);"').'&nbsp;&nbsp;'.$this->Lang('pagerows')
		);

		$jsfuncs[] = <<<EOS
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

//replace href attribute in existing stylesheet link (early in page-processing)
$cssfile = $this->GetPreference('list_cssfile');
$u = ($cssfile) ?
	PWFBrowse\Utils::GetUploadsUrl($this).'/'.$cssfile: //using custom css for table
	$baseurl.'/css/list-view.css';
$t = <<<EOS
<script type="text/javascript">
//<![CDATA[
//TODO frontend styling
 document.getElementById('adminstyler').setAttribute('href',"{$u}");
//]]>
</script>
EOS;

$jsall = NULL;
PWFBrowse\Utils::MergeJS($jsincs,$jsfuncs,$jsloads,$jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo $t;
echo PWFBrowse\Utils::ProcessTemplate($this,'default.tpl',$tplvars);
if ($jsall)
	echo $jsall;
