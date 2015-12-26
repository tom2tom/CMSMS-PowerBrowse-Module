<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_database implements iMutex
{
	private $pause;
	private $maxtries;
	private $table;

	function __construct($config)
	{
		if(!empty($config['table']))
			$this->table = $config['table'];
		else
			throw new Exception('no database cache');
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 500;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
	}

	function lock($token)
	{
		$db = cmsms()->GetDb();
		$flid = abs(crc32($token.'.mx.lock'));
		$stamp = $db->sysTimeStamp;
		$sql = 'INSERT INTO '.$this->table.' (flock_id,flock) VALUES ('.$flid.','.$stamp.')';
		$count = 0;
		do
		{
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if($db->CompleteTrans())
				return TRUE; //success
/*TODO		$sql = 'SELECT flock_id FROM '.$this->table.' WHERE flock < '.$stamp + 15;
			if($db->GetOne($sql))
				$db->Execute('DELETE FROM '.$this->table);
*/
			usleep($this->pause);
		} while(/*$this->maxtries == 0 || */$count++ < $this->maxtries);
		return FALSE; //failed
	}

	function unlock($token)
	{
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.$this->table.' WHERE flock_id='.abs(crc32($token.'.mx.lock'));
		while(1)
		{
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if($db->CompleteTrans())
				return;
		}
	}

	function reset()
	{
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.$this->table;
		while(1)
		{
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if($db->CompleteTrans())
				return;
		}
	}
}

?>
