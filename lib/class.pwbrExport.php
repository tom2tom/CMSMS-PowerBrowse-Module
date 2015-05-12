<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwbrExport
{
	/**
	ExportName:
	@mod: reference to current PowerBrowse module object
	@browser_id: index of the form browser to process, or FALSE if @record_id is provided
	*/
	public function ExportName(&$mod,$browser_id)
	{
		$bname = pwbrUtils::GetBrowserNameFromID($browser_id);
		$sname = preg_replace('/\W/','_',$bname);
		$datestr = date('Y-m-d-H-i');
		return $mod->GetName().$mod->Lang('export').'-'.$sname.'-'.$datestr.'.csv';
	}

	/**
	CSV:
	@mod: reference to current PowerBrowse module object
	@browser_id: index of the form browser to process, or FALSE if @record_id is provided
	@record_id: index of a single reponse to process, or array of such indices,
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
	public function CSV(&$mod,$browser_id=FALSE,$record_id=FALSE,$fp = FALSE,$sep = ',')
	{
		global $db;
		$pre = cms_db_prefix();
		if($browser_id)
		{
			$sql = 'SELECT record_id FROM '.$pre.
			'module_pwbr_record WHERE browser_id=? ORDER BY submitted';
			$all = $db->GetCol($sql,array($browser_id));
		}
		elseif($record_id)
		{
			if(is_array($record_id))
				$all = $record_id;
			else
				$all = array($record_id);
		}
		else
			return FALSE;
	
		if($fp && ini_get ('mbstring.internal_encoding') !== FALSE) //send to file, and conversion is possible
		{
			$config = cmsms()->GetConfig();
			if(!empty($config['default_encoding']))
				$defchars = trim($config['default_encoding']);
			else
				$defchars = 'UTF-8';
			$expchars = $mod->GetPreference('export_file_encoding','ISO-8859-1');
			$convert = (strcasecmp ($expchars,$defchars) != 0);
		}
		else
			$convert = FALSE;

		$sep2 = ($sep != ' ')?' ':',';
		switch ($sep)
		{
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

		if($all)
		{
			$pass = $mod->GetPreference('default_phrase');
			$funcs = new pwbrRecordLoad();
			//header line
			$data = $funcs->Load($all[0],$pass,$mod,$db,$pre);
			if(!$data[0])
				return FALSE;
			$names = array();				
			foreach($data[1] as &$one)
			{
				$fn = $one[0];
				if($strip)
					$fn = strip_tags($fn);
				$names[] = str_replace($sep,$r,$fn);
			}
			unset($one);
			$outstr = str_replace($sep,$r,$mod->Lang('title_submit_when'));
			if($names)
				$outstr .= $sep.implode($sep,$names);
			$outstr .= "\n";
			//data lines(s)
			foreach($all as $one)
			{
				$data = $funcs->Load($one,$pass,$mod,$db,$pre);
				if(!$data[0])
					continue;	//decryption error
				$outstr .= str_replace($sep,$r,$data[0]);
				foreach($data[1] as &$one)
				{
					$fv = $one[1];
					if($strip)
						$fv = strip_tags($fv);
					$fv = str_replace($sep,$r,$fv);
					$outstr .= $sep.preg_replace('/[\n\t\r]/',$sep2,$fv);
				}
				unset($one);
				$outstr .= "\n";
				if($fp)
				{
					if($convert)
					{
						$conv = mb_convert_encoding($outstr, $expchars, $defchars);
						fwrite($fp, $conv);
						unset($conv);
					}
					else
					{
						fwrite($fp, $outstr);
					}
					$outstr = '';
				}
			}
			if($fp)
				return TRUE;
			else
				return $outstr; //encoding conversion upstream
		}
		else
		{
			//no data, produce just a header line
			$sql = 'SELECT name FROM '.$pre.
			'module_pwbr_field WHERE browser_id=? ORDER BY order_by';
			$names = $db->GetCol($sql,array($params['browser_id']));
			//cleanup messy field-names
			foreach($names as $i => &$one)
			{
				if($strip)
					$one = strip_tags($one);
				$one = str_replace($sep,$r,$one);
			}
			unset($one);

			$outstr = str_replace($sep,$r,$mod->Lang('title_submit_when'));
			if($names)
				$outstr .= $sep.implode($sep,$names);
			$outstr .= "\n";
			
			if($fp)
			{
				if($convert)
				{
					$conv = mb_convert_encoding($outstr, $expchars, $defchars);
					fwrite($fp, $conv);
					unset($conv);
				}
				else
				{
					fwrite($fp, $outstr);
				}
				return TRUE;
			}
			return $outstr; //encoding conversion upstream
		}

	}

}

?>
