<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

namespace PWFBrowse;

class Jobber
{
	//module-preference identifiers
	const QMUTEX = 'JobsMtx';
	const QNAME = 'JobsQ';

	protected $mod;

	public function __construct(&$mod)
	{
		$this->mod = $mod;
	}

	public function GetQLock()
	{
		while ($this->mod->GetPreference(self::QMUTEX, 0)) {
			usleep(10000);
		}
		$this->mod->SetPreference(self::QMUTEX, 1);
	}

	public function ReleaseQLock()
	{
		$this->mod->SetPreference(self::QMUTEX, 0);
	}

	public function GetQ()
	{
		$this->GetQLock();
		$qdata = $this->mod->GetPreference(self::QNAME);
		if ($qdata) {
			return unserialize($qdata);
		}
		return [];
	}

	public function SetQ($qdata)
	{
		$qdata = ($qdata) ? serialize($qdata) : '';
		$this->mod->SetPreference(self::QNAME, $qdata);
		$this->ReleaseQLock();
	}

	//ATM jobs queue is stored as array, FIFO processed, no state-recall, no priorities c.f. SplPriorityQueue
	public function PushJob($qdata)
	{
		if ($qdata) {
			$qnow = $this->GetQ();
			$qnow[] = $qdata;
			$this->SetQ($qnow);
		}
	}

	public function PopJob($qdata)
	{
		if ($qdata) {
			$qnow = $this->GetQ();
			$p = array_search($qdata, $qnow); //TODO CHECK THIS WORKS
			if ($p !== FALSE) {
				unset($qnow[$p]);
				$this->SetQ($qnow);
			} else {
				$this->ReleaseQLock();
			}
		}
	}

	/*
	@param key string regexp to match in current-module preferences
	Returns: associative array, keyed by matching preference name
	*/
	public function GetPreferencesLike($key)
	{
		$matches = [];
		$prefs = \CMSMS\internal\global_cache::get('cms_siteprefs'); //CMSMS 1.10+
		if ($prefs) {
			$patn = '~'.$this->mod->GetName().'.+'.$key.'~';
			foreach ($prefs as $key => &$val) {
				if (preg_match($patn, $key)) {
					$p = strpos($key,'_mapi_pref_');
					$skey = substr($key, $p + 11);
					$matches[$skey] = $val;
				}
			}
			unset($val);
		}
		return $matches;
	}

	/*
	@param key string used to 'salt' uniqueid()
	Returns: 16-byte hexadecimal token, highly likely to be a collision
	Uses djb2a hash : see https://softwareengineering.stackexchange.com/questions/49550/which-hashing-algorithm-is-best-for-uniqueness-and-speed
	*/
	public function GetToken($key)
	{
		$val = uniqid($key, TRUE);
		$l = strlen($val);
		$num = 5381;
		for ($i = 0; $i < $l; $i++) {
			$num = ($num + ($num << 5)) ^ $val[$i]; //aka $num = $num*33 ^ $val[$i]
		}
		return dechex($num);
	}

	public function CreateActionURL($modname, $action, $params = [])
	{
		if ($this->mod->before20) {
			global $config;
			$root = $config['root_url'];
		} else {
			$root = CMS_ROOT_URL;
		}

		$id = 'aj_';

		$url = $root.'/jobinterface.php?mact='.$modname.','.$id.','.$action.',0';

		if ($params) {
			$ignores = ['assign', 'id', 'returnid', 'action', 'module'];
			foreach ($params as $key => $value) {
				$key = cms_htmlentities($key);
				if (!in_array($key, $ignores)) {
					$value = cms_htmlentities($value);
					$url .= '&'.$id.$key.'='.rawurlencode($value);
				}
			}
		}
		return $url;
	}

	/**
	StartJob:
	Initiate async task(s) recorded in queue
	@param action name of module-action to be run
	@param params optional array of action-parameters
	@param qdata otional data to be appended to jobs-queue-array
	*/
	public function StartJob($jobdata = NULL)
	{
		$logfile = '/var/www/html/cmsms/modules/PWFBrowse/lib/my.log'; //DEBUG

		if ($jobdata) {
			$this->PushJob($jobdata);
		}

		$qnow = $this->GetQ();
		if (!$qnow) {
			$this->ReleaseQLock();
			return;
		}
		$jobdata = reset($qnow);
		$this->ReleaseQLock();

		$url = $this->CreateActionURL($jobdata[0], $jobdata[1], $jobdata[2]);
		error_log('Start async request to '.$url."\n", 3, $logfile);

//		redirect($url); //DEBUG

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST => 0, //BAD for production !
			CURLOPT_SSL_VERIFYPEER => 0,
			]);
		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch);

//======================================
		$running = null;
		//execute the handle
		do {
			$mrc = curl_multi_exec($mh, $running);
//				curl_multi_select($mh);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		//PITY ABOUT THIS ?
		while ($running && $mrc == CURLM_OK) {
			if (curl_multi_select($mh) != -1) {
				do {
					$mrc = curl_multi_exec($mh, $running);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
		curl_multi_remove_handle($mh, $ch);
		curl_multi_close($mh);
		curl_close($ch);

		error_log('Async request completed, '.$running.' running'."\n", 3, $logfile);
//======================================
	}

	//module-installation stuff, could be in sub-class etc but not really worth that
	public function init()
	{
		$this->mod->SetPreference(self::QMUTEX, 0);
		$this->mod->SetPreference(self::QNAME, '');
		//install job processor (if not done before)
		$config = \cmsms()->GetConfig();
		$rootpath = $config['root_path'];
		$fp = \cms_join_path($rootpath, 'jobinterface.php');
		if (!is_file ($fp)) {
			$fp = \cms_join_path(__DIR__, 'jobinterface.php');
			@copy($fp, $rootpath);
		}
	}
}
