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
	//in-action parameter-identifiers
	const QKEY = '_qk_';
	const QSKEY = '_sk_'; //a.k.a. CMS_SECURE_PARAM_NAME, maybe N/A here

	protected $mod;

	public function __construct(&$mod)
	{
		$this->mod = $mod;
	}

	public function GetQLock()
	{
		$i = 5000;
		//TODO real threadsafe mutex
		while ($this->mod->GetPreference(self::QMUTEX, 0)) {
			if ($i < 500000) {
				$i += $i;
			}
			usleep($i);
		}
		$this->mod->SetPreference(self::QMUTEX, 1);
	}

	public function ReleaseQLock()
	{
		//TODO real threadsafe mutex
		$this->mod->SetPreference(self::QMUTEX, 0);
	}

	public function GetQ()
	{
		$this->GetQLock();
		$jobdata = $this->mod->GetPreference(self::QNAME);
		if ($jobdata) {
			return unserialize($jobdata);
		}
		return [];
	}

	public function SetQ($jobdata)
	{
		$jobdata = ($jobdata) ? serialize($jobdata) : '';
		$this->mod->SetPreference(self::QNAME, $jobdata);
		$this->ReleaseQLock();
	}

	/**
	@param jobdata 2- or 3-member array,
	 [0] = module name
	 [1] = module-action name
	 [2] = array of action-parameters (optional)
	 ATM jobs queue is stored as array, FIFO processed, no state-recall, no priorities c.f. SplPriorityQueue
	 */
	public function PushJob($jobdata)
	{
		if ($jobdata) {
			$qnow = $this->GetQ();
			$qnow[] = NULL;
			end($qnow);
			$key = key($qnow); //i.e. last-append
			if ($jobdata[2]) { //parameter(s) present
				$jobdata[2][self::QKEY] = $key;
			} else {
				$jobdata[2] = [self::QKEY => $key];
			}
			$qnow[$key] = $jobdata;
			$this->SetQ($qnow);
		}
	}

	public function PopJob($jobdatakey)
	{
		$qnow = $this->GetQ();
		unset($qnow[$jobdatakey]);
		$this->SetQ($qnow);
	}

	/**
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
					$p = strpos($key, '_mapi_pref_');
					$skey = substr($key, $p + 11);
					$matches[$skey] = $val;
				}
			}
			unset($val);
		}
		return $matches;
	}

	/**
	PHP implementation of MurmurHash3
	@author Stefano Azzolini (lastguest@gmail.com)
	@see https://github.com/lastguest/murmurhash-php
	@param  string $key   text to hash
	@param  number $seed  positive integer
	 @return number 32-bit positive integer
	 */
	public function murmurhash3_int($key, $seed = 5381)
	{
		$h1 = (int) $seed;
		$key = array_values(unpack('C*', (string) $key));
		$klen = count($key);
		$remainder = $klen & 3;
		$i = 0;
		$bytes = $klen - $remainder;

		while ($i < $bytes) {
			$k1 = $key[$i] | ($key[$i + 1] << 8) | ($key[$i + 2] << 16) | ($key[$i + 3] << 24);
			$k1 = (((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16))) & 0xffffffff;
			$k1 = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
			$k1 = (((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16))) & 0xffffffff;
			$h1 ^= $k1;
			$h1 = $h1 << 13 | ($h1 >= 0 ? $h1 >> 19 : (($h1 & 0x7fffffff) >> 19) | 0x1000);
			$h1b = (((($h1 & 0xffff) * 5) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 5) & 0xffff) << 16))) & 0xffffffff;
			$h1 = ((($h1b & 0xffff) + 0x6b64) + ((((($h1b >= 0 ? $h1b >> 16 : (($h1b & 0x7fffffff) >> 16) | 0x8000)) + 0xe654) & 0xffff) << 16));
		}

		$k1 = 0;
		$i += 4;
		switch ($remainder) {
			case 3: $k1 ^= $key[$i + 2] << 16;
			case 2: $k1 ^= $key[$i + 1] << 8;
			case 1: $k1 ^= $key[$i];
				$k1 = ((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16)) & 0xffffffff;
				$k1 = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
				$k1 = ((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16)) & 0xffffffff;
				$h1 ^= $k1;
		}
		$h1 ^= $klen;
		$h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);
		$h1 = ((($h1 & 0xffff) * 0x85ebca6b) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
		$h1 ^= ($h1 >= 0 ? $h1 >> 13 : (($h1 & 0x7fffffff) >> 13) | 0x40000);
		$h1 = (((($h1 & 0xffff) * 0xc2b2ae35) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
		$h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);

		return $h1;
	}

/* simpler hash
	public function djb2a_hash($key)
	{
		$key  = array_values(unpack('C*',(string) $key));
		$klen = count($key);
		$h1 = 5381;
		for ($i = 0; $i < $klen; $i++) {
			$h1 = ($h1 + ($h1 << 5)) ^ $key[$i]; //aka $h1 = $h1*33 ^ $key[$i]
		}

		return $h1;
	}
*/
	/**
	@param key string used to 'salt' uniqueid()
	Returns: 16-byte hexadecimal token, highly unlikely to be a collision
	Uses murmur3 hash : see
	https://softwareengineering.stackexchange.com/questions/49550/which-hashing-algorithm-is-best-for-uniqueness-and-speed
	http://fastcompression.blogspot.com.au/2012/04/selecting-checksum-algorithm.html?spref=tw
	 https://encode.ru/threads/2556-Improving-xxHash
	 */
	public function GetToken($key)
	{
		$val = uniqid($key, TRUE);
//		$num = $this->djb2a_hash($val);
		$num = $this->murmurhash3_int($val);
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
		$running = NULL;
		//execute the handle
		do {
			$mrc = curl_multi_exec($mh, $running);
//			curl_multi_select($mh);
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
		if (!is_file($fp)) {
			$fp = \cms_join_path(__DIR__, 'jobinterface.php');
			@copy($fp, $rootpath);
		}
	}
}
