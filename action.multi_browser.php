<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

if (!$this->CheckAccess()) exit;

if (isset($params['import'])) {
	if (!($this->CheckAccess('modify') || $this->CheckAccess('admin'))) exit;

	$funcs = new PWFBrowse\Transition();
	list($done,$skips) = $funcs->ImportBrowsers($this);
	if ($done) {
		if ($skips)
			$parms = array('message' => $this->PrettyMessage('error_data_incomplete',FALSE));
		else
			$parms = array();
	} else {
		$parms = array('message' => $this->PrettyMessage('error_database',FALSE));
	}
	$this->Redirect($id,'defaultadmin','',$parms);
}

if (!isset($params['sel']))
	$this->Redirect($id,'defaultadmin'); //nothing selected

if (isset($params['clone'])) {
	if (!($this->CheckAccess('modify') || $this->CheckAccess('admin'))) exit;

	$funcs = new PWFBrowse\BrowserTasks();
	foreach ($params['sel'] as $browser_id) {
		$params['browser_id'] = $browser_id;
		$funcs->CloneBrowser($this,$params);
	}
	unset($funcs);
	$params = array(); //nothing to report
} elseif (isset($params['delete'])) {
	if (!($this->CheckAccess('modify') || $this->CheckAccess('admin'))) exit;

	$success = TRUE;
	$funcs = new PWFBrowse\BrowserTasks();
	foreach ($params['sel'] as $browser_id) {
		if (!$funcs->DeleteBrowser($browser_id))
			$success = FALSE;
	}
	unset($funcs);
	$params = ($success)?
		array():
		array('message' => $this->PrettyMessage('error_failed',FALSE));
} elseif (isset($params['export'])) {
	if (!$this->CheckAccess()) exit;

	$funcs = new PWFBrowse\Export();
	if (count($params['sel']) == 1) {
		$browser_id = reset($params['sel']);
		$res = $funcs->Export($this,$browser_id);
		if ($res === TRUE)
			exit;
		$params = array('message' => $this->PrettyMessage($res,FALSE));
	} else {
		//cannot export multi browsers as a single item, so stuff em into a zip
		$fn = $this->GetName().$this->Lang('export').
			'-'.implode('-',$params['sel']).'-'.date('Y-m-d-H-i').'.zip';
		$fp = PWFBrowse\Utils::GetUploadsPath($this);
		if (!$fp)
			$fp = cms_join_path($config['root_path'],'tmp');
		$fp .= DIRECTORY_SEPARATOR.$fn;
		$zip = new ZipArchive();
		if ($zip && $zip->open($fp,ZipArchive::CREATE) === TRUE) {
			foreach ($params['sel'] as $browser_id) {
				$fname = $funcs->ExportName($this,$browser_id);
				$content = $funcs->CSV($this,$browser_id);
				if (!$content)
					$content = 'OOPS - CSV file creation failed';
				$zip->addFromString($fname,$content);
			}
			$zip->close();
			if (is_file($fp)) {
				$content = @file_get_contents($fp);
				unlink($fp);

				@ob_clean();
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
				header('Cache-Control: private',FALSE);
				header('Content-Description: File Transfer');
				header('Content-Type: application/zip');
				header('Content-Length: '.strlen($content));
				header('Content-Disposition: attachment; filename="'.$fn.'"');
				echo $content;
				exit;
			}
		}
		$params = array('message'=>$this->PrettyMessage('error_zip',FALSE));
	}
	unset($funcs);
}

$this->Redirect($id,'defaultadmin',$returnid,$params);
