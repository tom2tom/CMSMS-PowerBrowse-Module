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
	public static function SafeGet($sql, $args, $mode='all')
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
				$nt--;
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
	public static function SafeExec($sql, $args)
	{
		$db = \cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i=>$cmd) {
					$db->Execute($cmd, $args[$i]);
				}
			} else {
				$db->Execute($sql, $args);
			}
			if ($db->CompleteTrans()) {
				return TRUE;
			} else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	GetBrowserIDForRecord:
	@record_id: record identifier
	*/
	public static function GetBrowserIDForRecord($record_id)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_record WHERE record_id=?';
		return self::SafeGet($sql, [$record_id], 'one');
	}

	/**
	GetBrowserNameFromID:
	@browser_id: browser identifier
	*/
	public static function GetBrowserNameFromID($browser_id)
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
	public static function GetFormIDFromID($browser_id)
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
	public static function GetFormNameFromID($form_id, $internal=TRUE)
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
	public static function GetUploadsPath(&$mod)
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
	public static function GetUploadsUrl(&$mod)
	{
		$config = \cmsms()->GetConfig();
		$key = (empty($_SERVER['HTTPS'])) ? 'uploads_url':'ssl_uploads_url';
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

	/*
	@allfields: reference to array of (plaintext) field-data - see FormatRecord description
	On arrival, the 'current' member of @allfields is a SequenceStart field
	@htmlout: optional boolean, default TRUE
	*/
	private static function MergeSequenceData(&$allfields, $htmlout=TRUE)
	{
		$names = [];
		$vals = [];
		$first = TRUE;
		$joiner = ($htmlout) ? '<br />':PHP_EOL;
		$si = 0;
		while (1) {
			$field =& next($allfields);
			if ($field === FALSE) {
				//TODO handle error
				return [NULL,NULL];
			}
			if (count($field) > 2) {
				if (isset($field['_sb'])) { //field is intermediate SequenceEnd
					$first = FALSE;
					$si = 0;
					continue;
				} elseif (isset($field['_se'])) { //field is final SequenceEnd
					next($allfields); //skip this member
					return [$names,$vals]; //i.e. data + multi-store indicator
				} elseif (isset($field['_ss'])) { //field is SequenceStart (nested)
					list($subnames, $subvals) = self::MergeSequenceData($allfields, $htmlout); //recurse
					$field = [$subnames[0].',etc',implode($joiner, $subvals)]; //TODO something to store in $names,$vals
				} else {
					self::FormatRecord($mod, $field, $allfields, $htmlout);
				}
			}
			if ($first) {
				$names[$si] = $field[0];
				$vals[$si] = $field[1];
			} else {
				$vals[$si] .= $joiner.$field[1];
			}
			$si++;
		}
	}

	/**
	FormatRecord:
	@mod: reference to current module object
	@field: reference to current member of @allfields
	@allfields: reference to array of (plaintext) field-data, each member an array:
	 [0] = title for public display
	 [1] = value, probably not displayable
	 other member(s) relate to custom-formatting
	@htmlout: optional boolean, for possible downstream sequence-processing, default TRUE
	Returns: nothing, but @field content will probably be changed
	NOTE: the processing here must be suitably replicated in class.FormBrowser.php
	*/
	public static function FormatRecord(&$mod, &$field, &$allfields, $htmlout=TRUE)
	{
		static $dfmt = FALSE;
		static $tfmt = FALSE;
		static $dtfmt = FALSE;

		foreach (['dt', 'd', 't', '_ss', '_se', '_sb'] as $f) {
			if (isset($field[$f])) {
				switch ($f) {
				 case 'dt':
					if ($dtfmt === FALSE) {
						if ($dfmt === FALSE) {
							$dfmt = trim($mod->GetPreference('date_format'));
						}
						if ($tfmt === FALSE) {
							$tfmt = trim($mod->GetPreference('time_format'));
						}
						$dtfmt = trim($dfmt.' '.$tfmt);
					}
					if ($dtfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($dtfmt);
					}
					break;
				 case 'd':
					if ($dfmt === FALSE) {
						$dfmt = trim($mod->GetPreference('date_format'));
					}
					if ($dfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($dfmt);
					}
					break;
				 case 't':
					if ($tfmt === FALSE) {
						$tfmt = trim($mod->GetPreference('time_format'));
					}
					if ($tfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($tfmt);
					}
					break;
				 case '_ss': //sequence-start
					list($field[0], $field[1]) = self::MergeSequenceData($allfields, $htmlout); //accumulate sequence values
					break;
				 case '_se': //sequence-end, should never get to here
				 case '_sb': // -break ditto
					$field[0] = '';
					$field[1] = '';
					break;
				}
			}
		}
	}

	/**
	ProcessTemplate:
	@mod: reference to current PWFBrowse module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: string, processed template
	*/
	public static function ProcessTemplate(&$mod, $tplname, $tplvars, $cache=TRUE)
	{
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
			return $mod->ProcessTemplate($tplname);
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
			return $tpl->fetch();
		}
	}

	/**
	MergeJS:
	@jsincs: string or array of js 'include' directives
	@jsfuncs: string or array of js methods
	@jsloads: string or array of js onload-methods
	@$merged: reference to variable to be populated with the merged js string
	Returns: nothing
	*/
	public static function MergeJS($jsincs, $jsfuncs, $jsloads, &$merged)
	{
		if (is_array($jsincs)) {
			$all = $jsincs;
		} elseif ($jsincs) {
			$all = [$jsincs];
		} else {
			$all = [];
		}
		if ($jsfuncs || $jsloads) {
			$all[] =<<<'EOS'
<script type="text/javascript">
//<![CDATA[
EOS;
			if (is_array($jsfuncs)) {
				$all = array_merge($all, $jsfuncs);
			} elseif ($jsfuncs) {
				$all[] = $jsfuncs;
			}
			if ($jsloads) {
				$all[] =<<<'EOS'
$(document).ready(function() {
EOS;
				if (is_array($jsloads)) {
					$all = array_merge($all, $jsloads);
				} else {
					$all[] = $jsloads;
				}
				$all[] =<<<'EOS'
});
EOS;
			}
			$all[] =<<<'EOS'
//]]>
</script>
EOS;
		}
		$merged = implode(PHP_EOL, $all);
	}
}
