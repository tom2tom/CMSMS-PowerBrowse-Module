<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

$iseditor = $this->CheckPermission('Modify Any Page');

$tplvars['message'] = (isset($params['message']))?$params['message']:NULL;

$tab = $this->_GetActiveTab($params);
$t = $this->starttabheaders().
	$this->settabheader('browsers', $this->lang('title_browsers'), ($tab == 'maintab'));
if ($padmin) {
	$t .= $this->settabheader('settings', $this->lang('tab_settings'), ($tab == 'settings'));
}
$t .= $this->endtabheaders().$this->starttabcontent();
//workaround CMSMS2 crap 'auto-end', EndTab() & EndTabContent() before [1st] StartTab()
$tplvars += [
	'tabs_header'=>$t,
	'end_tab'=>$this->EndTab(),
	'tabs_footer'=>$this->EndTabContent(),
	'start_browsers_tab'=>$this->StartTab('browsers'),
	'start_browsersform'=>$this->CreateFormStart($id, 'multi_browser', $returnid),
	'end_form'=>$this->CreateFormEnd()
];
if ($padmin) {
	$tplvars['start_settings_tab'] = $this->StartTab('settings');
}

$theme = ($this->before20) ? cmsms()->get_variable('admintheme'):
	cms_utils::get_theme_object();
//script accumulators
$jsfuncs = [];
$jsloads = [];
$jsincs = [];
$baseurl = $this->GetModuleURLPath();

$pre = cms_db_prefix();
$sql = <<<EOS
SELECT BR.*,COALESCE (R.count,0) AS record_count FROM {$pre}module_pwbr_browser BR
LEFT JOIN (SELECT form_id,COUNT(*) as count FROM {$pre}module_pwbr_record GROUP BY form_id) R ON BR.form_id=R.form_id
EOS;
if (!$padmin && $this->GetPreference('owned_forms')) {
	$uid = get_userid(FALSE);
	$sql .= ' WHERE BR.owner IN (0,'.$uid.')';
}
$sql .= ' ORDER BY BR.name';
$browsers = $utils->SafeGet($sql, FALSE);
if ($browsers) {
	$tplvars['title_browser_name'] = $this->Lang('title_browser_name');
	if ($iseditor) {
		$tplvars['title_related_form'] = $this->Lang('title_related_form');
	}
	$tplvars['title_records'] = $this->Lang('title_records');

	$alt = $this->Lang('inspect');
	$icon_admin =
	'<img class="systemicon" src="'.$this->GetModuleURLPath().'/images/administer.png" alt="'.$alt.'" title="'.$alt.'" />';
	$icon_clone = $theme->DisplayImage('icons/system/copy.gif', $this->Lang('clone'), '', '', 'systemicon');
	$icon_delete = $theme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
	$icon_edit = $theme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
	$icon_export = $theme->DisplayImage('icons/system/export.gif', $this->Lang('export'), '', '', 'systemicon');
	$icon_view = $theme->DisplayImage('icons/system/view.gif', $this->Lang('view'), '', '', 'systemicon');
	$data = [];
	foreach ($browsers as &$one) {
		$oneset = new stdClass();
		$num = $one['record_count'];
		$oneset->recordcount = $num;
		$bid = (int)$one['browser_id'];
		$fid = (int)$one['form_id'];
		if ($pmod) {
			$oneset->name = $this->CreateLink($id, 'open_browser', '',
				$one['name'], ['form_id'=>$fid, 'browser_id'=>$bid]);
			$oneset->editlink = $this->CreateLink($id, 'open_browser', '',
				$icon_edit, ['form_id'=>$fid, 'browser_id'=>$bid]);
			if ($num > 0) {
				$oneset->adminlink = $this->CreateLink($id, 'browse_list', '',
					$icon_admin,
					['form_id'=>$fid, 'browser_id'=>$bid]);
			} else {
				$oneset->adminlink = '';
			}
			$oneset->clonelink = $this->CreateLink($id, 'add_browser', '',
				$icon_clone,
				['form_id'=>$fid, 'browser_id'=>$bid]);
			$oneset->deletelink = $this->CreateLink($id, 'delete_browser', '',
				$icon_delete,
				['form_id'=>$fid, 'browser_id'=>$bid],
				$this->Lang('confirm_delete_browser', $one['name']));
		} else {
			$oneset->name = $one['name'];
			$oneset->editlink = '';
			if ($num > 0) {
				if ($padmin) {
					$oneset->adminlink = $this->CreateLink($id, 'browse_list', '',
					$icon_admin,
					['form_id'=>$fid, 'browser_id'=>$bid]);
				} else {
					$oneset->adminlink = $this->CreateLink($id, 'browse_list', '',
					$icon_view,
					['form_id'=>$fid, 'browser_id'=>$bid]);
				}
			} else {
				$oneset->adminlink = '';
			}
			$oneset->clonelink = '';
			$oneset->deletelink = '';
		}
		if ($num > 0) {
			$oneset->exportlink = $this->CreateLink($id, 'export_browser', '',
				$icon_export, ['browser_id'=>$bid]);
		} else {
			$oneset->exportlink = '';
		}
		$oneset->selected = $this->CreateInputCheckbox($id, 'sel[]', $bid, -1);
		if ($iseditor) {
			//info for site-content developers
			$oneset->form_name=$one['form_name'];
		}
		$data[] = $oneset;
	}
	unset($one);

	$t = count($data);
	$tplvars['browser_count'] = $t;
	if ($t) {
		$tplvars['browsers'] = $data;

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
		$tplvars['exportbtn'] = $this->CreateInputSubmit($id, 'export', $this->Lang('export'),
			'title="'.$this->Lang('tip_export_selected_browsers').
			'" onclick="return any_selected();"');
		$tplvars['clonebtn'] = $this->CreateInputSubmit($id, 'clone', $this->Lang('clone'),
			'title="'.$this->Lang('tip_clone_selected_browsers').
			'" onclick="return any_selected();"');
		$tplvars['deletebtn'] = $this->CreateInputSubmit($id, 'delete', $this->Lang('delete'),
			'title="'.$this->Lang('tip_delete_selected_browsers').
			'" onclick="return confirm_selected(\''.$this->Lang('confirm').'\');"');

		if ($t > 1) {
			$jsfuncs[] = <<<EOS
function select_all(cb) {
 $('input[name="{$id}sel[]"][type="checkbox"]').attr('checked',cb.checked);
}
EOS;
			$t = $this->CreateInputCheckbox($id, 'selectall', TRUE, FALSE, 'onclick="select_all(this);"');
		} else {
			$t = '';
		}
		$tplvars['selectall_browsers'] = $t;
	} else {
		$tplvars['nobrowsers'] = $this->Lang('nobrowsers');
	}
} else {
	$tplvars['nobrowsers'] = $this->Lang('nobrowsers');
	$tplvars['browser_count'] = 0;
}

if ($padmin || $pmod) {
	$tplvars['addlink'] = $this->CreateLink($id, 'add_browser', '',
		$theme->DisplayImage('icons/system/newobject.gif', $this->Lang('title_add_browser'), '', '', 'systemicon'),
		['browser_id'=>-1]);
	$tplvars['addbrowser'] = $this->CreateLink($id, 'add_browser', '',
		$this->Lang('title_add_browser'),
		['browser_id'=>-1]);

	$fb = $this->GetModuleInstance('FormBuilder');
	if ($fb) {
		unset($fb);
		$tplvars['importbtn'] =
			$this->CreateInputSubmit($id, 'import', $this->Lang('import_browsers'),
				'title="'.$this->Lang('tip_import_browsers').'"');
	}
}

if ($padmin) {
	$tplvars['start_settingsform'] = $this->CreateFormStart($id, 'defaultadmin', $returnid);

	$configs = [];

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_rounds_factor');
	$oneset->input = $this->CreateInputText($id, 'rounds_factor', $this->GetPreference('rounds_factor'), 4, 4);
	$oneset->help = $this->Lang('help_rounds_factor');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_uploads_dir');
	$oneset->input = $this->CreateInputText($id, 'uploads_dir', $this->GetPreference('uploads_dir'), 40, 255);
	$oneset->help = $this->Lang('help_uploads_dir');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_list_cssfile');
	$oneset->input = $this->CreateInputText($id, 'list_cssfile', $this->GetPreference('list_cssfile'), 40, 255);
	$oneset->help = $this->Lang('help_list_cssfile');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_dateformat');
	$oneset->input = $this->CreateInputText($id, 'date_format', $this->GetPreference('date_format'), 10, 12);
	$oneset->help = $this->Lang('help_date');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_timeformat');
	$oneset->input = $this->CreateInputText($id, 'time_format', $this->GetPreference('time_format'), 8, 12);
	$oneset->help = $this->Lang('help_time');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_owned_forms');
	$oneset->input = $this->CreateInputCheckbox($id, 'owned_forms', 1,
		   $this->GetPreference('owned_forms'));
	$oneset->help = $this->Lang('help_owned_forms');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_onchange_notices');
	$oneset->input = $this->CreateInputCheckbox($id, 'onchange_notices', 1,
		   $this->GetPreference('onchange_notices'));
	$oneset->help = $this->Lang('help_onchange_notices');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_export_file');
	$oneset->input = $this->CreateInputCheckbox($id, 'export_file', 1,
		   $this->GetPreference('export_file'));
	$oneset->help = $this->Lang('help_export_file');
	$configs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_strip_on_export');
	$oneset->input = $this->CreateInputCheckbox($id, 'strip_on_export', 1,
		   $this->GetPreference('strip_on_export'));
	$oneset->help = $this->Lang('help_strip_on_export');
	$configs[] = $oneset;

	if (ini_get('mbstring.internal_encoding') !== FALSE) { //PHP's encoding-conversion capability is installed
		$oneset = new stdClass();
		$oneset->title = $this->Lang('title_export_file_encoding');
		$encodings = ['utf-8'=>'UTF-8','windows-1252'=>'Windows-1252','iso-8859-1'=>'ISO-8859-1'];
		$expchars = $this->GetPreference('export_file_encoding', 'ISO-8859-1');
		$oneset->input = $this->CreateInputRadioGroup($id, 'export_file_encoding', $encodings, $expchars, '', '&nbsp;&nbsp;');
		$configs[] = $oneset;
	}

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_password');
	if (!$asyncpw) {
		$cfuncs = new PWFBrowse\Crypter($this);
		$key = PWFBrowse\Crypter::MKEY;
		$t = $cfuncs->decrypt_preference($key);
		$oneset->input = $this->CreateTextArea(FALSE, $id, $t, $key, 'cloaked',
			$id.'passwd', '', '', 40, 2);
	} else {
		$oneset->input = $this->Lang('pending_password');
	}
	$configs[] = $oneset;

	if (!$asyncpw) {
		$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery-inputCloak.min.js"></script>
EOS;
	$jsloads[] = <<<EOS
 $('#{$id}passwd').inputCloak({
  type:'see4',
  symbol:'\u25CF'
 });
EOS;
	}

	$tplvars['configs'] = $configs;

	$jsfuncs[] = <<<EOS
function set_tab() {
 var active = $('#page_tabs > .active');
 $('#{$id}active_tab').val(active.attr('id'));
}
EOS;
	$tplvars['save'] =
		$this->CreateInputSubmit($id, 'submit', $this->Lang('apply'),
		'onclick="set_tab();"');
	$tplvars['cancel'] =
		$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'),
		'onclick="set_tab();"');
	$tplvars['pconfig'] = 1;
} else {
	$tplvars['pconfig'] = 0;
}
$tplvars['pmod'] = (($pmod)?1:0);
$tplvars['pdev'] = (($iseditor)?1:0);
