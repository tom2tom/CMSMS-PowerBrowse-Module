<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/
//store and crypt a form-record

//$logfile = $config['root_path'].DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'async'.DIRECTORY_SEPARATOR.'debug.log'; //DEBUG
//error_log('action_store start'."\n", 3, $logfile);

if (!isset($gCms)) {
//	error_log('action_store exit no gCms'."\n", 3, $logfile);
	exit;
}

//error_log('action_store @1'."\n", 3, $logfile);
//error_log('supplied $_REQUEST '.serialize($_REQUEST)."\n", 3, $logfile);
//error_log('supplied parameters '.serialize($params)."\n", 3, $logfile);

$handle = $this->GetPreference('Qhandle');
$funcs = new CMSMS\Assets\Queue\Qface();
if (!$funcs->CheckJob($handle, $params)) {
//	error_log('action_store exit invalid security parameters'."\n", 3, $logfile);
	exit;
}

$c = count(ob_list_handlers());
//error_log('action_store '.$c.' handlers'."\n", 3, $logfile);
for ($cnt = 0; $cnt < $c; $cnt++) {
	ob_end_clean();
}

//error_log('action_store before headers'."\n", 3, $logfile);

ignore_user_abort(true);
header('Connection: Close');
$out = 'X-CMSMS: Processing';
$size = strlen($out);
header("Content-Length: $size");
header($out);
flush();
usleep(20000);

//error_log('action_store @5'."\n", 3, $logfile);
$data = unserialize(html_entity_decode($params['formdata']));
//$data = unserialize($data);
//error_log('action_store '. count($data) .' keys'."\n", 3, $logfile);
//error_log('action_store @5'."\n", 3, $logfile);
$data['_m'][0] = $this->Lang('title_submitted'); //column title not set when the form was disposed
$data = $db->qStr(serialize($data));

$form_id = $params['formid'];
$pre = \cms_db_prefix();
$sql = <<< EOS
INSERT INTO {$pre}module_pwbr_record (browser_id,form_id,rounds,contents)
SELECT browser_id,?,0,{$data}
FROM {$pre}module_pwbr_browser WHERE form_id=?
EOS;
$db->Execute($sql, [$form_id, $form_id]); //TODO $utils->SafeExec()

//error_log('action_store @8'."\n", 3, $logfile);

//MAYBE $q = ; $q->PushJob($handle,...); $funcs->MoreQ($handle);
$funcs->CancelJob($handle, $params);

$funcs = new PWFBrowse\RecordContent();
$funcs->StartUpdate($this);

//error_log('action_store end'."\n", 3, $logfile);
exit;

