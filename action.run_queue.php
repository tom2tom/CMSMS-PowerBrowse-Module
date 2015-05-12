<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse
//action to be asynchronously initiated by curl, to run the data-save queue

//if($this->mcache == NULL) exit;

//$token = uniqid('pwbrDEBUG.'.mt_rand(100,1000100),FALSE); //for debugging
//$fh = fopen("/tmp/{$token}.txt","c");

global $db;
$pre = cms_db_prefix();
$pass = $this->GetPreference('default_phrase');
$funcs = new pwbrRecordStore();
//something sufficiently unique and which can't coincide with a SaveFormData() token
$token = uniqid('pwbrQ.'.mt_rand(100,1000100),FALSE);
$this->running = TRUE; //flag that Q is being processed now

while(!$this->Lock($token))
	usleep(60000); //bit longer than SaveFormData() timeout

$data = current($this->queue);
while($data)
{
	$form_id = (int)$data['formid'];
	$browsers = $db->GetCol('SELECT browser_id FROM '.$pre.
		'module_pwbr_browser WHERE form_id=?',array($form_id));
	if($browsers)
	{
		$stamp = (int)$data['submitted'];
		foreach($browsers as $browser_id)
			$funcs->Insert($browser_id,$form_id,$stamp,$data['data'],$pass,$db,$pre);
	}

	$datakey = key($this->queue);
	next($this->queue);
	unset($this->queue[$datakey],$data);

	$this->UnLock();
	do
	{
//		usleep(60000);
		usleep(mt_rand(10000,60000));
	} while (!$this->Lock($token));
}

$this->UnLock();
$this->running = FALSE;

//fwrite($fh,"Q has been processed\n");
//fclose($fh);

exit;

?>
