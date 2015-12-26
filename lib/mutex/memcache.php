<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwrMutex_memcache implements pwriMutex
{
	var $pause;
	var $maxtries;
	var $instance;

	function __construct(&$instance=NULL,$timeout=50,$tries=0)
	{
		if($instance)
			$this->instance = $instance;
		else
		{
			if(class_exists('Memcache') && function_exists('memcache_connect'))
				$this->instance = new Memcache;
			else
				throw new Exception('no memcache storage');
		}
		$config = cmsms()->GetConfig();
		$this->instance->connect($config['root_url'],11211);
		$this->pause = $timeout;
		$this->maxtries = $tries;
	}

	function lock($token)
	{
		$token .= 'pwr.lock';
		$count = 0;
		do
		{
			if($this->instance->add($token,$token)) //only nominally atomic
			{
				$cas_token = 0.0;
				if($this->instance->get($token,NULL,$cas_token) !== $token)
				{
					$mc =& $this->instance;
					while(!$mc->cas($cas_token,$token,$token) ||
						   $mc->getResultCode() != Memcached::RES_SUCCESS)
					{
						$stored = $mc->get($token);  //reset last access for CAS
						usleep($this->pause);
					}
				}
				return TRUE;
			}
			elseif ($this->instance->get($token) === $token)
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE;
	}

	function unlock($token)
	{
		$this->instance->delete($token.'pwr.lock');
	}

	function reset()
	{
		$this->instance->flush();
	}
}

?>
