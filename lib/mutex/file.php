<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwbrMutex_file implements pwbrMutex
{
	var $pause;
	var $maxtries;
	var $fp; //lock-file directory path (with trailing separator)
	var $fh; //opened-file handle

	function __construct(&$instance,$timeout=200,$tries=200)
	{
		if(!function_exists('flock'))
			throw new Exception('Error getting file lock');
		$mod = cms_utils::get_module('PowerBrowse');
		$ud = pwbrUtils::GetUploadsPath($mod);
		if($ud == FALSE)
			throw new Exception('Error getting file lock');
		$dir = $ud.DIRECTORY_SEPARATOR.'file_locks';
		if(!file_exists($dir))
		{
			if(!@mkdir($dir) && !file_exists($dir))
				throw new Exception('Error getting file lock');
		}
		$this->pause = $timeout;
		$this->maxtries = $tries;
		$this->fp = $dir.DIRECTORY_SEPARATOR;
		//TODO .htaccess for $dir
	}

	function lock($token)
	{
		$fp = $this->fp.$token.'pwbr.lock';
		touch($fp,time());
		$this->fh = fopen($fp,'r');
		if($this->fh === FALSE)
			throw new Exception("Error opening lock file {$fp}");
		$count = 0;
		do
		{
			if(flock($this->fh,LOCK_EX))
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE; //failed
	}

	function unlock($token)
	{
		if(!flock($this->fh,LOCK_UN))
			throw new Exception('Error unlocking mutex');
		fclose($this->fh);
	}

	function reset()
	{
		$this->unlock('');
		if(is_dir(dirname($this->fp)))
			unlink($this->fp.'*pwbr.lock');
	}
}

?>
