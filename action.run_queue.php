<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

//action to be asynchronously initiated by curl, to run the data-save queue

//$token = uniqid('pwbrDEBUG.'.mt_rand(100,1000100),FALSE); //for debugging
//$fh = fopen("/tmp/{$token}.txt",'c');

global $db;
$pre = cms_db_prefix();
$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
$pass = $this->GetPreference('default_phrase');
$funcs = new pwbrRecordStore();
//something sufficiently unique and which can't coincide with a SaveFormData() token
$token = uniqid('pwbrQ.'.mt_rand(100,1000100),FALSE);
$this->running = TRUE; //flag that Q is being processed now

while(!$this->Locker($token))
	usleep(60000); //bit longer than SaveFormData() timeout

while($data = reset($this->queue))
{
	$datakey = key($this->queue);
	$browsers = $db->GetCol($sql,array($data['formid']));
	if($browsers)
	{
		$stamp = (int)$data['submitted'];
		foreach($browsers as $browser_id)
			$funcs->Insert($browser_id,$form_id,$stamp,$data['data'],$pass,$db,$pre);
	}

	unset($this->queue[$datakey],$data);

	$this->UnLocker();
	do
	{
		usleep(mt_rand(10000,60000));
	} while(!$this->Locker($token));
}

$this->UnLocker();
$this->running = FALSE;

//fwrite($fh,"Q has been processed\n");
//fclose($fh);

exit;

?>
