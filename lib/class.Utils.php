<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

namespace PWFBrowse;

class Utils
{
	/**
	SafeGet:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command
	@args: array of arguments for @sql
	@mode: optional type of get - 'one','row','col','assoc' or 'all', default 'all'
	Returns: boolean indicating successful completion
	*/
	public function SafeGet($sql, $args, $mode = 'all')
	{
		$db = \cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			switch ($mode) {
			 case 'one':
				$ret = $db->GetOne($sql, $args);
				break;
			 case 'row':
				$ret = $db->GetRow($sql, $args);
				break;
			 case 'col':
				$ret = $db->GetCol($sql, $args);
				break;
			 case 'assoc':
				$ret = $db->GetAssoc($sql, $args);
				break;
			 default:
				$ret = $db->GetArray($sql, $args);
				break;
			}
			if ($db->CompleteTrans()) {
				return $ret;
			} else {
				--$nt;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	SafeExec:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command, or array of them
	@args: array of arguments for @sql, or array of them
	Returns: boolean indicating successful completion
	*/
	public function SafeExec($sql, $args)
	{
		$db = \cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i => $cmd) {
					$db->Execute($cmd, $args[$i]);
				}
			} else {
				$db->Execute($sql, $args);
			}
			if ($db->CompleteTrans()) {
				return TRUE;
			} else {
				--$nt;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	GetBrowserIDForRecord:
	@record_id: record identifier
	*/
	public function GetBrowserIDForRecord($record_id)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_record WHERE record_id=?';
		return self::SafeGet($sql, [$record_id], 'one');
	}

	/**
	GetBrowserNameFromID:
	@browser_id: browser identifier
	*/
	public function GetBrowserNameFromID($browser_id)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT name FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
		$db = \cmsms()->GetDb();
		return $db->GetOne($sql, [$browser_id]);
	}

	/**
	GetFormIDFromID:
	@browser_id: browser identifier
	*/
	public function GetFormIDFromID($browser_id)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT form_id FROM '.$pre.'module_pwbr_browser WHERE browser_id=?';
		$db = \cmsms()->GetDb();
		return $db->GetOne($sql, [$browser_id]);
	}

	/**
	GetFormNameFromID:
	@form_id: form identifier
	@internal: optional, default TRUE
	*/
	public function GetFormNameFromID($form_id, $internal = TRUE)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		if ($internal) {
			$sql = 'SELECT form_name FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
		} else {
			$sql = 'SELECT name FROM '.$pre.'module_pwf_form WHERE form_id=?';
		}
		return $db->GetOne($sql, [$form_id]);
	}

	/**
	GetUploadsPath:
	@mod: reference to current PWFBrowse module object
	Returns: absolute path string or FALSE
	*/
	public function GetUploadsPath(&$mod)
	{
		$config = \cmsms()->GetConfig();
		$up = $config['uploads_path'];
		if ($up) {
			$rp = $mod->GetPreference('uploads_dir');
			if ($rp) {
				$up .= DIRECTORY_SEPARATOR.$rp;
			}
			if (is_dir($up)) {
				return $up;
			}
		}
		return FALSE;
	}

	/**
	GetUploadsUrl:
	@mod: reference to current PWFBrowse module object
	Returns: absolute url string or FALSE
	*/
	public function GetUploadsUrl(&$mod)
	{
		$config = \cmsms()->GetConfig();
		$key = (empty($_SERVER['HTTPS'])) ? 'uploads_url' : 'ssl_uploads_url';
		$up = $config[$key];
		if ($up) {
			$rp = $mod->GetPreference('uploads_dir');
			if ($rp) {
				$rp = str_replace('\\', '/', $rp);
				$up .= '/'.$rp;
			}
			return $up;
		}
		return FALSE;
	}

	/**
	Generate:
	@mod: reference to current PWFBrowse module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@jsincs: optional string or array of js 'include' directives, default NULL
	@jsfuncs: optional string or array of js methods, default NULL
	@jsloads: optional string or array of js onload-methods, default NULL
	@cache: optional boolean, default TRUE
	Returns: nothing
	*/
	public function Generate(&$mod, $tplname, $tplvars, $jsincs = NULL, $jsfuncs = NULL, $jsloads = NULL, $cache = TRUE)
	{
		$jsall = self::MergeJS($jsincs, $jsfuncs, $jsloads);
		unset($jsincs);
		unset($jsfuncs);
		unset($jsloads);

		if ($mod->before20) {
			global $smarty;
		} else {
			$smarty = $mod->GetActionTemplateObject();
			if (!$smarty) {
				global $smarty;
			}
		}
		$smarty->assign($tplvars);
		if ($mod->oldtemplates) {
			$cache_id = ($cache) ? md5('pwbr'.$tplname.serialize(array_keys($tplvars))) : '';
			echo $mod->ProcessTemplate($tplname, '', $cache, $cache_id);
		} else {
			if ($cache) {
				$cache_id = md5('pwbr'.$tplname.serialize(array_keys($tplvars)));
				$lang = \CmsNlsOperations::get_current_language();
				$compile_id = md5('pwbr'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname), $cache_id, $compile_id, $smarty);
				if (!$tpl->isCached()) {
					$tpl->assign($tplvars);
				}
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname), NULL, NULL, $smarty, $tplvars);
			}
			$tpl->display();
		}
		if ($jsall) {
			echo $jsall;
		}
	}

	/**
	MergeJS:
	@jsincs: string or array of js 'include' directives
	@jsfuncs: string or array of js methods
	@jsloads: string or array of js onload-methods
	Returns: js string
	*/
	public function MergeJS($jsincs, $jsfuncs, $jsloads)
	{
		if (is_array($jsincs)) {
			$all = $jsincs;
		} elseif ($jsincs) {
			$all = [$jsincs];
		} else {
			$all = [];
		}
		if ($jsfuncs || $jsloads) {
			$all[] = <<<'EOS'
<script type="text/javascript">
//<![CDATA[
EOS;
			if (is_array($jsfuncs)) {
				$all = array_merge($all, $jsfuncs);
			} elseif ($jsfuncs) {
				$all[] = $jsfuncs;
			}
			if ($jsloads) {
				$all[] = <<<'EOS'
$(document).ready(function() {
EOS;
				if (is_array($jsloads)) {
					$all = array_merge($all, $jsloads);
				} else {
					$all[] = $jsloads;
				}
				$all[] = <<<'EOS'
});
EOS;
			}
			$all[] = <<<'EOS'
//]]>
</script>
EOS;
		}
		return implode(PHP_EOL, $all);
	}
}
