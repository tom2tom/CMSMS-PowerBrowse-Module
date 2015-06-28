<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwbrMutex_database implements pwbrMutex
{
	var $pause;
	var $maxtries;
	var $table;

	function __construct(&$instance,$timeout=500,$tries=200)
	{
		$this->pause = $timeout;
		$this->maxtries = $tries;
		$this->table = cms_db_prefix().'module_pwbr_flock'; 
	}

	function lock($token)
	{
		$flid = abs(crc32($token.'pwbr.lock'));
		$db = cmsms()->GetDb();
		$stamp = $db->sysTimeStamp;
		$sql = 'INSERT INTO '.$this->table.' (flock_id,flock) VALUES ('.$flid.','$stamp.')';
		$count = 0;
		do
		{
			if($db->Execute($sql))
				return TRUE; //success
/*TODO		$sql = 'SELECT flock_id FROM '.$this->table.' WHERE flock < '.$stamp + 15;
			if($db->GetOne($sql))
				$db->Execute('DELETE FROM '.$this->table);
*/
			usleep($this->pause);
		} while(/*$this->maxtries == 0 || */$count++ < $this->maxtries)
		return FALSE; //failed
	}

	function unlock($token)
	{
		$flid = abs(crc32($token.'pwbr.lock'));
		$db = cmsms()->GetDb();
		$db->Execute('DELETE FROM '.$this->table.' WHERE flock_id='.$flid);
	}

	function reset()
	{
		$db = cmsms()->GetDb();
		$db->Execute('DELETE FROM '.$this->table);
	}
}

?>

