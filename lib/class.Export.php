<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class Export
{
	/**
	ExportName:
	@mod: reference to current PWFBrowse module object
	@browser_id: index of the form browser to process, or FALSE if @record_id is provided
	@record_id: index of the record to process, or array of such, or FALSE if @record_id is provided
	*/
	public function ExportName(&$mod, $browser_id=FALSE, $record_id=FALSE)
	{
		if (!$browser_id) {
			if (is_array($record_id))
				$rid = reset($record_id);
			else
				$rid = $record_id;
			$browser_id = Utils::GetBrowserIDForRecord($rid);
		}
		$bname = Utils::GetBrowserNameFromID($browser_id);
		$sname = preg_replace('/\W/','_',$bname);
		$datestr = date('Y-m-d-H-i');
		return $mod->GetName().$mod->Lang('export').'-'.$sname.'-'.$datestr.'.csv';
	}

	/**
	CSV:
	@mod: reference to current PWFBrowse module object
	@browser_id: index of the form browser to process, or FALSE if @record_id is provided
	@record_id: index of a single response to process, or array of such indices,
		or FALSE to process the whole @browser_id, default=FALSE
	@fp: handle of open file, if writing data to disk, or FALSE if constructing in memory, default = FALSE
	@$sep: field-separator in output data, assumed single-byte ASCII, default = ','

	Constructs a CSV string for specified/all records belonging to @browser_id,
	and returns the string or writes it progressively to the file associated with @fp
	(which must be opened and closed upstream)
	To avoid field-corruption, existing separators in headings or data are converted
	to something else, generally like &#...;
	(except when the separator is '&', '#' or ';', those become %...%)
	Returns: TRUE/string, or FALSE on error
	*/
	public function CSV(&$mod, $browser_id=FALSE, $record_id=FALSE, $fp = FALSE, $sep = ',')
	{
		global $db; //$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		if ($browser_id) {
			$sql = 'SELECT record_id FROM '.$pre.'module_pwbr_record WHERE browser_id=? ORDER BY record_id';
			$all = Utils::SafeGet($sql,array($browser_id),'col');
		} elseif ($record_id) {
			if (is_array($record_id))
				$all = $record_id;
			else
				$all = array($record_id);
		} else
			return FALSE;

		if ($fp && ini_get ('mbstring.internal_encoding') !== FALSE) { //send to file, and conversion is possible
			$config = \cmsms()->GetConfig();
			if (!empty($config['default_encoding']))
				$defchars = trim($config['default_encoding']);
			else
				$defchars = 'UTF-8';
			$expchars = $mod->GetPreference('export_file_encoding','ISO-8859-1');
			$convert = (strcasecmp ($expchars,$defchars) != 0);
		} else
			$convert = FALSE;

		$sep2 = ($sep != ' ')?' ':',';
		switch ($sep) {
		 case '&':
			$r = '%38%';
			break;
		 case '#':
			$r = '%35%';
			break;
		 case ';':
			$r = '%59%';
			break;
		 default:
			$r = '&#'.ord($sep).';';
			break;
		}

		$strip = $mod->GetPreference('strip_on_export');

		if ($all) {
			$funcs = new RecordContent();
			//header line
			list($when,$data) = $funcs->Load($mod,$pre,$all[0]);
			if (!$data)
				return FALSE;
			$names = array();
			foreach ($data as &$one) {
				$fn = $one[0];
				if ($strip)
					$fn = strip_tags($fn);
				$names[] = str_replace($sep,$r,$fn);
			}
			unset($one);
			if ($names) {
				$outstr = implode($sep,$names);
				$outstr .= PHP_EOL;
			} else
				return FALSE;
			$dtfmt = FALSE;
			//data lines(s)
			foreach ($all as $one) {
				list($when,$data) = $funcs->Load($mod,$pre,$one);
				if (!$data)
					continue;	//decryption error
				$vals = array();
				foreach ($data as &$one) {
	//TODO process field-sequence data
					if (isset($one[2]) && $one[2] == 'stamp') {
						if ($dtfmt === FALSE) {
							$dtfmt = trim($mod->GetPreference('date_format').' '.$mod->GetPreference('time_format'));
							if ($dtfmt)
								$dt = new \DateTime('@0',NULL);
						}
						if ($dtfmt) {
							$dt->setTimestamp($one[1]);
							$vals[] = str_replace($sep,$r,$dt->format($dtfmt));
						} else {
							$vals[] = $one[1];
						}
					} else {
						$fv = $one[1];
						if ($strip)
							$fv = strip_tags($fv);
						$fv = str_replace($sep,$r,$fv);
						$vals[] = preg_replace('/[\n\t\r]/',$sep2,$fv);
					}
				}
				unset($one);
				$outstr .= implode($sep,$vals);
				$outstr .= PHP_EOL;
				if ($fp) {
					if ($convert) {
						$conv = mb_convert_encoding($outstr, $expchars, $defchars);
						fwrite($fp, $conv);
						unset($conv);
					} else {
						fwrite($fp, $outstr);
					}
					$outstr = '';
				}
			}
			if ($fp)
				return TRUE;
			else
				return $outstr; //encoding conversion upstream
		} else {
			//no data, produce just a header line
			$sql = 'SELECT name FROM '.$pre.'module_pwbr_field WHERE browser_id=? ORDER BY order_by';
			$names = $db->GetCol($sql,array($params['browser_id']));
			//cleanup messy field-names
			foreach ($names as $i => &$one) {
				if ($strip)
					$one = strip_tags($one);
				$one = str_replace($sep,$r,$one);
			}
			unset($one);

			$outstr = str_replace($sep,$r,$mod->Lang('title_submitted'));
			if ($names)
				$outstr .= $sep.implode($sep,$names);
			$outstr .= PHP_EOL;

			if ($fp) {
				if ($convert) {
					$conv = mb_convert_encoding($outstr, $expchars, $defchars);
					fwrite($fp, $conv);
					unset($conv);
				} else {
					fwrite($fp, $outstr);
				}
				return TRUE;
			}
			return $outstr; //encoding conversion upstream
		}
	}

	/**
	Export:
	@mod: reference to current PWFBrowse module object
	@browser_id: optional browser identifier, default FALSE
	@record_id: optional record_id, or array of such id's, default FALSE
	@sep: optional field-separator for exported content default ','
	At least one of @browser_id, @record_id must be provided
	Returns: TRUE on success, or lang key for error message upon failure
	*/
	public function Export(&$mod, $browser_id=FALSE, $record_id=FALSE, $sep = ',')
	{
		if (!($browser_id || $record_id))
			return 'error_system';
		$fname = $this->ExportName($mod,$browser_id,$record_id);

		if ($mod->GetPreference('export_file')) {
			$updir = Utils::GetUploadsPath($mod);
			if ($updir) {
				$filepath = $updir.DIRECTORY_SEPARATOR.$fname;
				$fp = fopen($filepath,'w');
				if ($fp) {
					$success = $this->CSV($mod,$browser_id,$record_id,$fp,$sep);
					fclose($fp);
					if ($success) {
						$url = Utils::GetUploadsUrl($mod).'/'.$fname;
						@ob_clean();
						@ob_clean();
						header('Location: '.$url);
						return TRUE;
					}
				}
			}
		} else {
			$csv = $this->CSV($mod,$browser_id,$record_id,FALSE,$sep);
			if ($csv) {
				$config = \cmsms()->GetConfig();
				if (!empty($config['default_encoding']))
					$defchars = trim($config['default_encoding']);
				else
					$defchars = 'UTF-8';

				if (ini_get('mbstring.internal_encoding') !== FALSE) { //conversion is possible
					$expchars = $mod->GetPreference('export_file_encoding','ISO-8859-1');
					$convert = (strcasecmp ($expchars,$defchars) != 0);
				} else {
					$expchars = $defchars;
					$convert = FALSE;
				}

				@ob_clean();
				@ob_clean();
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: private',FALSE);
				header('Content-Description: File Transfer');
				//note: some older HTTP/1.0 clients did not deal properly with an explicit charset parameter
				header('Content-Type: text/csv; charset='.$expchars);
				header('Content-Length: '.strlen($csv));
				header('Content-Disposition: attachment; filename='.$fname);
				if ($convert)
					echo mb_convert_encoding($csv,$expchars,$defchars);
				else
					echo $csv;
				return TRUE;
			}
		}
		return 'error_export';
	}
}
