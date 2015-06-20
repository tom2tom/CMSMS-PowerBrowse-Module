<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwbrMutex_semaphore implements pwbrMutex
{
	var $pause;
	var $maxtries;
	var $instance;

	function __construct(&$mod,$timeout=200,$tries=200)
	{
		if(!function_exists('sem_get'))
			throw new Exception('Error geting semaphore');
		$fp = dirname(__FILE__).'pwbr';
		$key = ftok($fp) % 101 + PHP_INT_MAX / 2;
		$this->instance = sem_get($key,1);
		if($this->instance === FALSE)
			throw new Exception('Error geting semaphore');
		$this->pause = $timeout;
		$this->maxtries = $tries;
	}

	function timeout($msec=200)
	{
		$this->pause = $usec;
	}

	function lock($token)
	{	
		$count = 0;
		do
		{
			if(sem_acquire($this->instance))
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries)
		return FALSE; //failed
	}
		
	function unlock()
	{
		if(!sem_release($this->instance))
			throw new Exception('Error unlocking mutex');
	}

	//as of 2014, "sem_remove() shouldn't be part of a normal cleanup/teardown
		and should be called very rarely due to bugs in the implementation"
	function reset()
	{
		$this->unlock();
	}

?>
