<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwbrMutex
{
	/**
	Get:
	@module: reference to current PowerForms module object
	@storage: optional cache-type name, one (or more, ','-separated) of
		auto,memcache,file,database
		default = 'auto'
	Returns: mutex-object $module->mutex (after creating it if not already done) or NULL
	*/
	public static function Get(&$module, $storage = 'auto')
	{
		if($module->mutex)
			return $module->mutex;

		$config = cmsms()->GetConfig();
		$url = $config['root_url'];
		if($storage)
			$storage = strtolower($storage);
		else
			$storage = 'auto';
		if(strpos($storage,'auto') !== FALSE)
			$storage = 'memcache,file,database';

		$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'mutex'.DIRECTORY_SEPARATOR;
		require($path.'interface.Mutex.php');
		require($path.'MutexBase.php');

		$types = explode(',',$storage);
		foreach($types as $one)
		{
			$one = trim($one);
			require($path.$one.'.php');
			$class = 'Mutex_'.$one;
			try
			{
				$module->cache = new $class($settings);
				return $module->cache;
			}
			catch(Exception $e) {}
		}
		return NULL;
	}

}

?>
