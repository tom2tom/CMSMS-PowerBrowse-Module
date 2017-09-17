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
	@rounds: no. of key-stretches, 0 if no encryption
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	 Each member of @data is array:
	 [0] = (public) title
	 [1] = value
	 [2] (maybe) = extra stuff e.g. 'stamp' flag
	Returns: boolean indicating success
	*/
	public function Insert(&$mod, $pre, $browser_id, $form_id, $stamp, &$data, $rounds, &$cfuncs = NULL)
	{
		//insert fake field with read-only key and datetime marker
		$store = ['_s' => [0 => $mod->Lang('title_submitted'), 1 => $stamp, 'dt' => '']] + $data;
		if ($rounds > 0) {
			$defr = (int) ($mod->GetPreference('rounds_factor') * 100); //maybe 0
			$status = ($defr == $rounds) ? 0 : 1;
			if ($cfuncs == NULL) {
				$cfuncs = new Crypter($mod);
			}
			$cont = $cfuncs->encrypt_value(serialize($store), $rounds);
		} else {
			$status = 1;
			$cont = serialize($store);
		}
		unset($store);
		return Utils::SafeExec(
'INSERT INTO '.$pre.'module_pwbr_record (browser_id,form_id,rounds,flags,contents) VALUES ('.$browser_id.','.$form_id.','.$rounds.','.$status.',?)',
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
		return Utils::SafeExec('UPDATE '.$pre.'module_pwbr_record SET rounds='.$rounds.',flags=0,contents=? WHERE record_id='.$record_id,
			[$cont]);
	}

	/**
	Trigger async RecordsUpdate task
	*/
	public function StartUpdate()
	{
/* CmsJobManager as of 2.2.2 is too flaky TODO find something better
		global $CMS_VERSION;
		if (version_compare($CMS_VERSION, '2.2.2') < 0) {
	$adbg = 1; //TODO no public API for just-in-time processing - see CmsRegularTaskHandler class
		} else {
			$tasker = new RecordsUpdateTask();
			$job = new \CMSMS\Async\RegularTask($tasker);
			$job->module = 'PWFBrowse';
			$jobber = \ModuleOperations::get_instance()->get_module_instance('CmsJobManager', '', TRUE);
			$jobber->save_job($job);
		}
*/
	}

	/**
	@mod: reference to PWFBrowse module object
	@rounds: number of key-stretches, 0 if no encryption
	@source: string to be decrypted
	@raw: optional boolean, whether to skip unserialization of decrypted value, default FALSE
	@cfuncs: optional Crypter-object, default NULL (populate this when batching)
	Must be compatible with self::Insert/Update
	*/
	public function Decrypt(&$mod, $rounds, $source, $raw = FALSE, &$cfuncs = NULL)
	{
		if ($source) {
			if ($rounds > 0) {
				if ($cfuncs == NULL) {
					$cfuncs = new Crypter($mod);
				}
				$decrypted = $cfuncs->decrypt_value($source, $rounds);
				if (!$decrypted) {
					$pw = $cfuncs->decrypt_preference(Crypter::MKEY.'OLD');
					if ($pw) {
						$decrypted = $cfuncs->decrypt_value($source, $rounds, $pw);
						if ($decrypted) {
							self::StartUpdate();
						}
					} else {
					}
				}
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
		$data = Utils::SafeGet(
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
	NOTE: the processing here must be suitably replicated in class.FormBrowser.php
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
