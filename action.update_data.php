<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/
//re-crypt a batch of stored records (if any are in need of such)

//$logfile = $config['root_path'].DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'async'.DIRECTORY_SEPARATOR.'debug.log'; //DEBUG
//error_log('action_update start'."\n", 3, $logfile);

if (!isset($gCms)) {
//	error_log('action_update exit no gCms'."\n", 3, $logfile);
	exit;
}

//error_log('action_update @1'."\n", 3, $logfile);
//error_log('supplied $_REQUEST '.serialize($_REQUEST)."\n", 3, $logfile);
//error_log('supplied parameters '.serialize($params)."\n", 3, $logfile);

$handle = $this->GetPreference('Qhandle');
$funcs = new Async\Queue\Qface();
if (!$funcs->CheckJob($handle, $params)) {
//	error_log('action_update exit invalid security parameters'."\n", 3, $logfile);
	exit;
}

$c = count(ob_list_handlers());
//error_log('action_update '.$c.' handlers'."\n", 3, $logfile);
for ($cnt = 0; $cnt < $c; $cnt++) {
	ob_end_clean();
}

//error_log('action_update before headers'."\n", 3, $logfile);

ignore_user_abort(true);
header('Connection: Close');
$out = 'X-CMSMS: Processing';
$size = strlen($out);
header("Content-Length: $size");
header($out);
flush();
usleep(20000);

//error_log('action_update @5'."\n", 3, $logfile);
//touch('/var/www/html/cmsms/modules/PWFBrowse/lib/toucher.txt');

$funcs = new PWFBrowse\RecordContent();
$funcs->DoUpdate($this, $params);

//error_log('action_update end'."\n", 3, $logfile);
exit;

