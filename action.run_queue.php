<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

//action to be asynchronously initiated by curl, to run the data-save queue

//$token = uniqid('pwbrDEBUG.'.mt_rand(100,1000100),FALSE); //for debugging
//$fh = fopen("/tmp/{$token}.txt",'c');

$pre = cms_db_prefix();
$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
$funcs = new pwbrRecordStore();
$token = abs(crc32($this->GetName().'Qmutex')); //same token as in pwfFormBrowser::Dispose()
$this->running = TRUE; //flag that Q is being processed now
$mx = pwbrUtils::GetMutex();
if(!$mx || !$mx->lock($token))
{
	$this->running = FALSE;
	//TODO fail with error report $this->Lang('error_lock')
	exit;
}

while($data = reset($this->queue))
{
	$datakey = key($this->queue);
//	each Q-item = array('formid'=>$this->formdata->Id,'submitted'=>time(),'data'=>$browsedata)
	$form_id = (int)$data['formid'];
	$browsers = $db->GetCol($sql,array($form_id));
	if($browsers)
	{
		$stamp = (int)$data['submitted'];
		foreach($browsers as $browser_id)
			$funcs->Insert($browser_id,$form_id,$stamp,$data['data'],$this,$db,$pre);
	}

	unset($this->queue[$datakey],$data);

	$mx->unlock($token);
	do
	{
		usleep(mt_rand(10000,60000));
	} while(!$mx->lock($token));
}

$mx->unlock($token);
$this->running = FALSE;

//fwrite($fh,"Q has been processed\n");
//fclose($fh);

exit;

?>
