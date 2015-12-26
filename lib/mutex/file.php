<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_file implements iMutex
{
	private $pause;
	private $maxtries;
	private $fp; //lock-file directory path (with trailing separator)
	private $fh; //opened-file handle

	function __construct($config)
	{
		if(!function_exists('flock'))
			throw new Exception('Error getting file lock');
		if(!empty($config['updir']))
			$ud = $config['updir'];
		else
		{
			$sysconfig = cmsms()->GetConfig();
			$ud = $sysconfig['uploads_path'];
			if($ud == FALSE)
				throw new Exception('Error getting file lock');
		}
		$dir = $ud.DIRECTORY_SEPARATOR.'file_locks';
		if(!is_dir($dir))
		{
			if(!@mkdir($dir) && !file_exists($dir))
				throw new Exception('Error getting file lock');
		}
		$this->fp = $dir.DIRECTORY_SEPARATOR;
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 500;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
		//TODO .htaccess for $dir
	}

	function lock($token)
	{
		$fp = $this->fp.$token.'.mx.lock';
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
			unlink($this->fp.'*.mx.lock');
	}
}

?>
