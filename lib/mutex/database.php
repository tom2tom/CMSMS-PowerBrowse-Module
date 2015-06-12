<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_database extends MutexBase implements Mutex
{
	var $pause;
	var $maxtries;
	var $dbhandle;
	var $table;

	function __construct($timeout=500,$tries=200)
	{
		$this->pause = $timeout;
		$this->maxtries = $tries;
		$this->dbhandle = cmsms()->GetDb();
		$this->table = cms_db_prefix().'module_pwbr_flock'; 
	}

	function timeout($usec=500)
	{
		$this->pause = $usec;
	}

	//c.f. pwfUtils::GetFileLock();
	function lock($token)
	{
		$count = 0;
		do
		{
			$stamp = $this->dbhandle->sysTimeStamp;
			$sql = 'INSERT INTO '.$this->table.' (flock_id,flock) VALUES (1,'.$stamp.')';
			if($this->dbhandle->Execute($sql))
				return TRUE; //success
/*TODO		$sql = 'SELECT flock_id FROM '.$this->table.' WHERE flock < '.$stamp + 15;
			if($this->dbhandle->GetOne($sql))
				$this->dbhandle->Execute('DELETE FROM '.$this->table);
*/
			usleep($this->pause);
		} while(/*$this->maxtries == 0 || */$count++ < $this->maxtries)
		return FALSE; //failed
	}

	//c.f. pwfUtils::ClearFileLock();
	function unlock()
	{
		$this->dbhandle->Execute('DELETE FROM '.$this->table);
	}

	function reset()
	{
		$this->dbhandle->Execute('DELETE FROM '.$this->table);
	}
}

?>

