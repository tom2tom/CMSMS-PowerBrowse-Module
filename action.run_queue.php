<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

//action to be asynchronously initiated by curl, to run the data-save queue

try
{
	$cache = pwfUtils::GetCache()
}
catch Exception ($e)
{
	echo $this->Lang('error_system');
	exit;
}
try
{
	$mx = pwfUtils::GetMutex($this);
}
catch Exception ($e)
{
	echo $this->Lang('error_system');
	exit;
}
$pre = cms_db_prefix();
$sql = 'SELECT browser_id FROM '.$pre.'module_pwbr_browser WHERE form_id=?';
$funcs = new pwbrRecordStore();
$token = abs(crc32($this->GetName().'Qmutex')); //same token as in pwfFormBrowser::Dispose()
$cache->driver_set('pwbrQrunning',TRUE,1200); flag that Q is being processed, 20-minute max retention
if(!$mx->lock($token))
{
	$cache->driver_delete('pwbrQrunning');
	echo $this->Lang('error_lock');
	exit;
}

$queue = $cache->driver_get('pwbrQarray');
if($queue)
{
	$cache->driver_delete('pwbrQarray');
	while($data = reset($queue))
	{
		$datakey = key($queue);
		//each Q-item = array('formid'=>$this->formdata->Id,'submitted'=>time(),'data'=>$browsedata)
		$form_id = (int)$data['formid'];
		$browsers = $db->GetCol($sql,array($form_id));
		if($browsers)
		{
			$stamp = (int)$data['submitted'];
			foreach($browsers as $browser_id)
				$funcs->Insert($browser_id,$form_id,$stamp,$data['data'],$this,$db,$pre);
		}
		unset($queue[$datakey],$data);

		//allow update by PowerForms disposer
		$mx->unlock($token);
		do
		{
			usleep(mt_rand(10000,60000));
		} while(!$mx->lock($token));
		$q2 = $cache->driver_get('pwbrQarray');
		if($q2)
		{
			$cache->driver_delete('pwbrQarray');
			$queue = array_merge($queue,$q2);
		}
	}
}
$mx->unlock($token);
$cache->driver_delete('pwbrQrunning');

exit;

?>
