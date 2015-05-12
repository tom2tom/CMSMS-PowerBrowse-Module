<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBrowser-module file (C) 2006-2011 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

if(!($this->CheckAccess('admin') || $this->CheckAccess('view'))) exit;

$funcs = new pwbrExport();
$fname = $funcs->ExportName($this,$params['browser_id']);

if($this->GetPreference('export_file',0))
{
	$updir = pwbrUtils::GetUploadsPath($this);
	if($updir)
	{
		$filepath = $updir.DIRECTORY_SEPARATOR.$fname;
		$fp = fopen($filepath, 'w');
		if($fp)
		{
			$success = $funcs->CSV($this,$params['browser_id'],FALSE,$fp);
			fclose($fp);
			if($success)
			{
				$url = pwbrUtils::GetUploadsUrl($this).'/'.$fname;
				@ob_clean();
				@ob_clean();
				header('Location: '.$url);
				exit;
			}
		}
	}
	unset($params);
	$this->Redirect($id,'defaultadmin',$returnid,array (
		'message' => $this->PrettyMessage('error_export',FALSE))); 
}

$reportString = $funcs->CSV($this,$params['browser_id']);
if($reportString)
{
	if(!empty($config['default_encoding']))
		$defchars = trim($config['default_encoding']);
	else
		$defchars = 'UTF-8';

	if(ini_get('mbstring.internal_encoding') !== FALSE) //conversion is possible
	{
		$expchars = $this->GetPreference('export_file_encoding','ISO-8859-1');
		$convert = (strcasecmp ($expchars,$defchars) != 0);
	}
	else
	{
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
	header('Content-Length: '.strlen($reportString));
	header('Content-Disposition: attachment; filename='.$fname);
	if($convert)
		echo mb_convert_encoding($reportString,$expchars,$defchars);
	else
		echo $reportString;
	exit;
}

$this->Redirect($id,'defaultadmin',$returnid,array(
	'message' => $this->PrettyMessage('error_export',FALSE)));

?>
