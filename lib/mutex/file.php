<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_file extends MutexBase implements Mutex
{
	var $pause;
	var $maxtries;
	var $fp; //lock-file path
	var $fh; //file handle

	function __construct(&$mod,$timeout=200,$tries=200) //TODO $mod
	{
		$ud = pwbrUtils::GetUploadsPath($mod)
		if($ud == FALSE)
			throw new Exception("Error TODO");
		if(!function_exists('flock'))
			throw new Exception("Error TODO");
		$dir = $ud.DIRECTORY_SEPARATOR.'file_locks';
		if(!file_exists($dir))
		{
			if(!@mkdir($dir) && !file_exists($dir)) 
				throw new Exception("Error TODO");
		}
		$this->pause = $timeout;
		$this->maxtries = $tries;
		$name = uniqid('pwbr',TRUE);
		$this->fp = $dir.DIRECTORY_SEPARATOR.$name.'.lock';
		touch($this->fp,time());
		//TODO .htaccess for $dir
	}

	function timeout($msec=200)
	{
		$this->pause = $usec;
	}

	function lock($token)
	{
		$this->fh = fopen($this->fp,'r');
		if($this->fh === FALSE)
			throw new Exception("Error opening lock file {$this->fp}");
		$count = 0;
		do
		{
			if(flock($this->fh,LOCK_EX))
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries)
		return FALSE; //failed
	}

	function unlock()
	{
		if(!flock($this->fh,LOCK_UN))
			throw new Exception("Error unlocking mutex");
		fclose($this->fh);
	}

	function reset()
	{
		$this->unlock();
	}
}

?>
