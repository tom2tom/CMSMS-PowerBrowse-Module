<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_memcache extends MutexBase implements Mutex
{
	var $pause;
	var $maxtries;
	var $instant;
	var $lockid;

	function __construct($timeout=50,$tries=0)
	{
		if(class_exists('Memcache') && function_exists('memcache_connect'))
		{
			$this->pause = $timeout;
			$this->maxtries = $tries;
			$this->instant = new Memcache;
			$config = cmsms()->GetConfig();
			$this->instant->connect($config['root_url'],11211);
			$this->lockid = uniqid('pwf',TRUE);
		}
		else
			throw new Exception('no memcache storage');
	}

	function timeout($msec=50)
	{
		$this->pause = $usec;
	}

	function lock($token)
	{
		$count = 0;
		do
		{
			if($this->instant->add($this->lockid,$token)) //only nominally atomic
			{
				$cas_token = 0.0;
				if($this->instant->get($this->lockid,NULL,$cas_token) !== $token)
				{
					$mc =& $this->instant;
					while(!$mc->cas($cas_token,$this->lockid,$token) || 
						   $mc->getResultCode() != Memcached::RES_SUCCESS)
					{
						$stored = $mc->get($this->lockid);  //reset last access for CAS
						usleep($this->pause);
					}
				}
				return TRUE;
			}
			elseif $this->instant->get($this->lockid) === $token)
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE;
	}

	function unlock()
	{
		$this->instant->delete($this->lockid);
	}

	function reset()
	{
		$this->instant->delete($this->lockid);
/*		$this->instant = new Memcache;
		$config = cmsms()->GetConfig();
		$this->instant->connect($config['root_url'],11211);
		$this->lockid = uniqid('pwf',TRUE);
*/
	}
}

?>
