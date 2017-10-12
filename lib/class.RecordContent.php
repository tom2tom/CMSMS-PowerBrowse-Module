<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple.org/projects/pwfbrowse
*/

namespace PWFBrowse;

class RecordContent
{
	/**
	Insert:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@browser_id: identifier of browser to which the data belong
	@form_id: identifier of form from which the data are sourced (<0 for FormBrowser forms)
	@stamp: timestamp for form submission
	@data: reference to array of plaintext form-data to be stored
	 Each member of @data is array:
	 [0] = (public) title
	 [1] = value
	 [2] (maybe) = extra stuff e.g. 'stamp' flag
	@rounds: no. of key-stretches, 0 if no encryption
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	Returns: boolean indicating success
	*/
	public function Insert(&$mod, $pre, $browser_id, $form_id, $stamp, &$data, $rounds, &$cfuncs = NULL)
	{
		//insert fake field with read-only key and datetime marker
		$store = ['_s' => [0 => $mod->Lang('title_submitted'), 1 => $stamp, 'dt' => '']] + $data;
		if ($rounds > 0) {
			$defr = (int) ($mod->GetPreference('rounds_factor') * 100); //maybe 0
			if ($cfuncs == NULL) {
				$cfuncs = new Crypter($mod);
			}
			$cont = $cfuncs->encrypt_value(serialize($store), $rounds);
		} else {
			$cont = serialize($store);
		}
		unset($store);
		$utils = new Utils();
		return $utils->SafeExec(
'INSERT INTO '.$pre.'module_pwbr_record (browser_id,form_id,rounds,contents) VALUES ('.$browser_id.','.$form_id.','.$rounds.',?)',
			[$cont]);
	}

	/**
	Update:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@record_id: identifier of record to which the data belong
	@data: reference to array of plaintext form-data to be stored, or if @raw=TRUE, serialized form-data
	@stamp: optional boolean, whether to skip adding a modification-time, default FALSE
	@raw: optional boolean, whether to skip serialization & encryption of @data, default FALSE
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	Returns: boolean indicating success
	*/
	public function Update(&$mod, $pre, $record_id, &$data, $stamp = FALSE, $raw = FALSE, &$cfuncs = NULL)
	{
		if ($raw) {
			$cont = $data;
		} else {
			if ($stamp) {
				$store = $data;
			} else {
				//prepend/update fake field with read-only key and datetime marker
				$stamp = time();
				$store = ['_m' => [0 => $mod->Lang('title_modified'), 1 => $stamp, 'dt' => '']] + $data;
			}
			if ($cfuncs == NULL) {
				$cfuncs = new Crypter($mod);
			}
			//update to default rounds (if not already there)
			$rounds = (int) ($mod->GetPreference('rounds_factor') * 100); //maybe 0
			$cont = $cfuncs->encrypt_value(serialize($store), $rounds);
			unset($store);
		}
		$utils = new Utils();
		return $utils->SafeExec('UPDATE '.$pre.'module_pwbr_record SET rounds='.$rounds.',pass=0,newpass=0,contents=? WHERE record_id='.$record_id,
			[$cont]);
	}

	/**
	StartUpdate:
	@mod: reference to PWFBrowse module object
	@params: optional assoc. array of job-parameters, default []
	*/
	public function StartUpdate(&$mod, $params = [])
	{
		$handle = $mod->GetPreference('Qhandle');
		$pre = \cms_db_prefix();
		$db = \cmsms()->GetDB();
		$jobkey = $db->GenID($pre.'module_pwbr_seq');
		$jobdata = [$mod->GetName(), 'update_data'];
		if ($params) {
			$jobdata[] = $params;
		}
		$funcs = new \Async\Qface();
		$funcs->StartJob($handle, $jobkey, $jobdata, 2);
	}

	/**
	DoUpdate:
	Update records-data asynchronously
	@mod: reference to PWFBrowse module object
	@params: assoc. array of request-parameters, or equivalent
	*/
	public function DoUpdate(&$mod, $params = [])
	{
		$pre = \cms_db_prefix();
		$newrounds = (int) ($mod->GetPreference('rounds_factor') * 100); //update to default rounds (if not already there)
		$sql = 'SELECT record_id,rounds,pass,newpass,contents FROM '.$pre.'module_pwbr_record WHERE rounds!='.$newrounds.' OR pass!=newpass ORDER BY browser_id,record_id';
		$db = \cmsms()->GetDB();
		$rst = $db->Execute($sql);

//$logfile = '/var/www/html/cmsms/modules/Async/my.log'; //DEBUG

		if ($rst) {

//$p = 0;

			if (!$rst->EOF) {

//error_log('DoUpdate recordset found'."\n", 3, $logfile);

				$utils = new Utils();
				$cfuncs = new Crypter($mod);
				$pwcache = [$cfuncs->decrypt_preference(Crypter::MKEY)]; //default P/W
				$limit = time() + $mod->GetPreference('Qjobtimeout', 10);
				$sql = 'UPDATE '.$pre.'module_pwbr_record SET rounds='.$newrounds.',pass=newpass,contents=? WHERE record_id=';
				while (!$rst->EOF && time() < $limit) {
					$i = $rst->fields['pass'] + 0;
					if (!isset($pwcache[$i])) {
						$pwcache[$i] = $cfuncs->decrypt_preference('newpass'.$i);
					}
					$oldpw = $pwcache[$i];

					$i = $rst->fields['newpass'] + 0;
					if (!isset($pwcache[$i])) {
						$pwcache[$i] = $cfuncs->decrypt_preference('newpass'.$i);
					}
					$newpw = $pwcache[$i];

					$oldrounds = $rst->fields['rounds'];
					$val = ($oldrounds > 0) ?
						$cfuncs->decrypt_value($rst->fields['contents'], $oldrounds, $oldpw) :
						$rst->fields['contents'];
					if ($newrounds > 0) {
						$val = $cfuncs->encrypt_value($val, $newrounds, $newpw);
					}
					if (!$utils->SafeExec($sql.$rst->fields['record_id'], [$val])) {
//						TODO handle error
						$adbg1 = 1;
					}
//++$p;
					$rst->MoveNext();
				}
			}

//error_log('DoUpdate recordset ('.$p.') finished'."\n", 3, $logfile);

			$handle = $mod->GetPreference('Qhandle');
			$funcs = new \Async\Qface();
			if ($rst->EOF) {

//error_log('DoUpdate job finished'."\n", 3, $logfile);

				$funcs->CancelJob($handle, $params);

//error_log('DoUpdate CancelJob finished'."\n", 3, $logfile);

				$t = $mod->GetPreference('newpasses');
				if ($t) { //any 'newpass'.* passwords used here or before
					$used = explode(',', $t);
					$t = end($used); //'last-used' password
					$newpw = $cfuncs->decrypt_preference('newpass'.$t);
					foreach ($used as $t) {
						$cfuncs->remove_preference('newpass'.$t);
					}
					$mod->SetPreference('newpasses', '');
					if ($newpw !== FALSE) {
						$cfuncs->encrypt_preference(Crypter::MKEY, $newpw);
					}
				}
				$sql = 'UPDATE '.$pre.'module_pwbr_record SET pass=0,newpass=0';
				$db->Execute($sql);
			} else { //timed out, re-run the job

//error_log('DoUpdate job restart triggered'."\n", 3, $logfile);

				$utils->StartJob($handle);
			}
			$rst->Close();
		} else {

//error_log('DoUpdate no records'."\n", 3, $logfile);

			$handle = $mod->GetPreference('Qhandle');
			$funcs = new \Async\Qface();
			$funcs->CancelJob($handle, $params);

			$t = $mod->GetPreference('newpasses');
			if ($t) { //any 'newpass'.* passwords used here or before
				$used = explode(',', $t);
				$t = end($used); //'last-used' password
				$cfuncs = new Crypter($mod);
				$newpw = $cfuncs->decrypt_preference('newpass'.$t);
				foreach ($used as $t) {
					$cfuncs->remove_preference('newpass'.$t);
				}
				$mod->SetPreference('newpasses', '');
				if ($newpw !== FALSE) {
					$cfuncs->encrypt_preference(Crypter::MKEY, $newpw);
				}
			}
			$sql = 'UPDATE '.$pre.'module_pwbr_record SET pass=0,newpass=0';
			$db->Execute($sql);
		}
	}

	/**
	Decrypt:
	@mod: reference to PWFBrowse module object
	@rounds: number of key-stretches, 0 if no encryption
	@source: string to be decrypted
	@raw: optional boolean, whether to skip unserialization of decrypted value, default FALSE
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	@pw: optional plaintext password, default FALSE (populate this when batching)
	Must be compatible with self::Insert/Update
	*/
	public function Decrypt(&$mod, $rounds, $source, $raw = FALSE, &$cfuncs = NULL, $pw = FALSE)
	{
		if ($source) {
			if ($rounds > 0) {
				if ($cfuncs == NULL) {
					$cfuncs = new Crypter($mod);
				}
				$decrypted = $cfuncs->decrypt_value($source, $rounds, $pw);
			} else {
				$decrypted = $source;
			}
			if ($decrypted) {
				if ($raw) {
					return $decrypted;
				} else {
					return unserialize($decrypted);
				}
			}
		}
		return '';
	}

	/**
	Load:
	@mod: reference to PWFBrowse module object
	@pre: table-names prefix
	@record_id: identifier of record to retrieve
	Returns: 2-member array:
	 [0] = T/F success-indicator
	 [1] = array of data or error message
	*/
	public function Load(&$mod, $pre, $record_id)
	{
		$utils = new Utils();
		$data = $utils->SafeGet(
		'SELECT rounds,contents FROM '.$pre.'module_pwbr_record WHERE record_id=?',
			[$record_id], 'row');
		if ($data) {
			$browsedata = self::Decrypt($mod, $data['rounds'], $data['contents']);
			if ($browsedata) {
				return [TRUE, $browsedata];
			}
			$errkey = 'error_data';
		} else {
			$errkey = 'error_database';
		}
		return [FALSE, $mod->_PrettyMessage($errkey, FALSE)];
	}

	/**
	ListSequence:
	@mod: reference to current module object
	@colnames: array of column-titles displayed in the list
	@datarow: array of data for a record, each member an array:
	 [0] = title for public display
	 [1] = value, probably not displayable
	 other member(s) relate to custom-formatting
	@startcol: index in @datarow where a sequence starts
	@outvals: array to be populated with values corresponding to @colnames
	Returns: @datarow index >= @startcol + 1, where the sequence ends (unless some error)
	*/
	public function ListSequence(&$mod, $colnames, $datarow, $startcol, &$outvals)
	{
		$token = $datarow[$startcol]['_ss']; //sequence-identifier
		$repeats = 0;
		$first = TRUE;
		$c = count($datarow);
		for ($si = $startcol + 1; $si < $c; ++$si) {
/*	$datarow[$si]['_ss'] = $token; //'SequenceStart'
	$datarow[$si][0] = $token.'>>'; pseudo title
	$datarow[$si]['_se'] = $token; //final 'SequenceEnd'
	$datarow[$si][0] = $token.'<<';
	$datarow[$si]['_sb'] = $token; //intermediate 'SequenceEnd'
	$datarow[$si][0] = $token.'||';
*/
			if (isset($datarow[$si]['_se'])) {
				if ($datarow[$si]['_se'] === $token) {
					++$repeats;
					//TODO populate ? - $repeats, $datarow[$startcol/$si][0] ? outvals[] ?
					return $si;
				}
			} elseif (isset($datarow[$si]['_sb'])) {
				if ($datarow[$si]['_sb'] === $token) {
					++$repeats;
					$first = FALSE;
				}
			} elseif (isset($datarow[$si]['_ss'])) {
				self::ListSequence($mod, $colnames, $datarow, $si, $outvals); //recurse
			} elseif ($first) {
				if (in_array($datarow[$si][0], $colnames)) {
					if (count($datarow[$si]) == 2) {
						$outvals[] = $datarow[$si][1]; //TODO specific index! display-order maybe != $datarow order
					} else {
						$outvals[] = self::Format($mod, $datarow[$si], $datarow);
					}
				}
			}
		}
		return min($startcol + 1, $c - 1);
	}

	/**
	PassSequence:
	@datarow: array of data for a record, each member an array:
	 [0] = title for public display
	 [1] = value, probably not displayable
	 other member(s) relate to custom-formatting
	@startcol: index in @datarow where a sequence starts
	Returns: @datarow index >= @startcol + 1, where the sequence ends (unless some error)
	*/
	public function PassSequence($datarow, $startcol)
	{
		$token = $datarow[$startcol]['_ss']; //sequence-identifier
		$c = count($datarow);
		for ($si = $startcol + 1; $si < $c; ++$si) {
			if (isset($datarow[$si]['_se'])) {
				if ($datarow[$si]['_se'] === $token) {
					return $si;
				}
			}
		}
		return min($startcol + 1, $c - 1);
	}

	/*
	@datarow: reference to array of (plaintext) field-data - see Format description
	On arrival, the 'current' member of @datarow is a SequenceStart field
	@htmlout: optional boolean, default TRUE
	*/
	private function MergeSequenceData(&$datarow, $htmlout = TRUE)
	{
		$names = [];
		$vals = [];
		$first = TRUE;
		$joiner = ($htmlout) ? '<br />' : PHP_EOL;
		$si = 0;
		while (1) {
			$field = &next($datarow);
			if ($field === FALSE) {
				//TODO handle error
				return [NULL, NULL];
			}
			if (count($field) > 2) {
				if (isset($field['_sb'])) { //field is intermediate SequenceEnd
					$first = FALSE;
					$si = 0;
					continue;
				} elseif (isset($field['_se'])) { //field is final SequenceEnd
					next($datarow); //skip this member
					return [$names, $vals]; //i.e. data + multi-store indicator
				} elseif (isset($field['_ss'])) { //field is SequenceStart (nested)
					list($subnames, $subvals) = self::MergeSequenceData($datarow, $htmlout); //recurse
					$field = [$subnames[0].',etc', implode($joiner, $subvals)]; //TODO something to store in $names,$vals
				} else {
					self::Format($mod, $field, $datarow, $htmlout);
				}
			}
			if ($first) {
				$names[$si] = $field[0];
				$vals[$si] = $field[1];
			} else {
				$vals[$si] .= $joiner.$field[1];
			}
			++$si;
		}
	}

	/**
	Format:
	@mod: reference to current module object
	@field: reference to current member of @datarow
	@datarow: reference to array of (plaintext) field-data, each member an array:
	 [0] = title for public display
	 [1] = value, probably not displayable
	 other member(s) relate to custom-formatting
	@htmlout: optional boolean, for possible downstream sequence-processing, default TRUE
	Returns: nothing, but @field content will probably be changed
	NOTE: the processing here must be suitably replicated in FormBrowser::Dispose()
	*/
	public function Format(&$mod, &$field, &$datarow, $htmlout = TRUE)
	{
		static $dfmt = FALSE;
		static $tfmt = FALSE;
		static $dtfmt = FALSE;

		foreach (['dt', 'd', 't', '_ss', '_se', '_sb'] as $f) {
			if (isset($field[$f])) {
				switch ($f) {
				 case 'dt':
					if ($dtfmt === FALSE) {
						if ($dfmt === FALSE) {
							$dfmt = trim($mod->GetPreference('date_format'));
						}
						if ($tfmt === FALSE) {
							$tfmt = trim($mod->GetPreference('time_format'));
						}
						$dtfmt = trim($dfmt.' '.$tfmt);
					}
					if ($dtfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($dtfmt);
					}
					break 2;
				 case 'd':
					if ($dfmt === FALSE) {
						$dfmt = trim($mod->GetPreference('date_format'));
					}
					if ($dfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($dfmt);
					}
					break 2;
				 case 't':
					if ($tfmt === FALSE) {
						$tfmt = trim($mod->GetPreference('time_format'));
					}
					if ($tfmt) {
						$dt = new \DateTime('@'.$field[1], NULL);
						$field[1] = $dt->format($tfmt);
					}
					break 2;
				 case '_ss': //sequence-start
					list($field[0], $field[1]) = self::MergeSequenceData($datarow, $htmlout); //accumulate sequence values
					break;
				 case '_se': //sequence-end, should never get to here
				 case '_sb': // -break ditto
					$field[0] = '';
					$field[1] = '';
					break 2;
				}
			}
		}
		return $field[1];
	}
}
