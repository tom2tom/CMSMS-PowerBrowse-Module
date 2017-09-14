<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple..org/projects/pwfbrowse
*/

namespace PWFBrowse;

class Crypter Extends Encryption
{
	const MKEY = 'masterpass';
	const SKEY = 'prefsalt';
	const BATCHED = 1000;
	protected $mod;

	/**
	constructor:
	@mod: reference to current module object
	@method: optional openssl cipher type to use, default 'BF-CBC'
	@stretches: optional number of extension-rounds to apply, default 8192
	*/
	public function __construct(&$mod, $method='BF-CBC')
	{
		$this->mod = $mod;
		parent::__construct($method, 'default');
	}

	/*
	localise:
	Get constant site/host-specific string.
	All hashes and crypted preferences depend on this
	*/
	protected function localise()
	{
		return cmsms()->GetConfig()['ssl_url'].$this->mod->GetModulePath();
	}

	/**
	encrypt_preference:
	@value: value to be stored, normally a string
	@key: module-preferences key
	*/
	public function encrypt_preference($key, $value)
	{
		$s = $this->localise();
		$value = parent::encrypt(''.$value,
			hash_hmac('sha256', $s.$this->decrypt_preference(self::SKEY), $key));
		$this->mod->SetPreference(hash('tiger192,3', $s.$key),
			base64_encode($value));
	}

	/**
	decrypt_preference:
	@key: module-preferences key
	Returns: plaintext string, or FALSE
	*/
	public function decrypt_preference($key)
	{
		$s = $this->localise();
		if ($key != self::SKEY) {
			$value = base64_decode(
				$this->mod->GetPreference(hash('tiger192,3', $s.self::SKEY)));
			$p = parent::decrypt($value,
				hash_hmac('sha256', $s.self::SKEY, self::SKEY));
		} else {
			$p = $key;
		}
		$value = base64_decode(
			$this->mod->GetPreference(hash('tiger192,3', $s.$key)));
		return parent::decrypt($value,
			hash_hmac('sha256', $s.$p, $key));
	}

	/**
	encrypt_value:
	@value: value to encrypted, may be empty string
	@stretches: optional number of rounds to be used for key 'stretching', default 1024 (0 sets this too)
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	@escaped: optional boolean, whether to escape single-quote chars in the (raw) encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty or if password is empty
	*/
	public function encrypt_value($value, $stretches=self::BATCHED, $pw=FALSE, $based=FALSE, $escaped=FALSE)
	{
		if ($value || is_numeric($value)) {
			if (!$pw) {
				$pw = self::decrypt_preference(self::MKEY);
			}
			if ($pw) {
				if ($stretches < 1) {
					$stretches = self::BATCHED;
				}
				$value = parent::encrypt(''.$value, $pw, $stretches);
				if ($based) {
					return base64_encode($value);
				} elseif ($escaped) {
					return str_replace('\'', '\\\'', $value); //facilitate db-field storage
				}
			}
		}
		return $value;
	}

	/**
	decrypt_value:
	@value: string to be decrypted, may be empty
	@stretches: optional number of rounds to be used for key 'stretching', default 1024 (0 sets this too)
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether @value is base64_encoded, default FALSE
	@escaped: optional boolean, whether single-quote chars in (raw) @value have been escaped, default FALSE
	Returns: decrypted @value, or just @value if it's empty or if password is empty
	*/
	public function decrypt_value($value, $stretches=self::BATCHED, $pw=FALSE, $based=FALSE, $escaped=FALSE)
	{
		if ($value) {
			if (!$pw) {
				$pw = self::decrypt_preference(self::MKEY);
			}
			if ($pw) {
				if ($based) {
					$value = base64_decode($value);
				} elseif ($escaped) {
					$value = str_replace('\\\'', '\'', $value);
				}
				if ($stretches < 1) {
					$stretches = self::BATCHED;
				}
				return parent::decrypt($value, $pw, $stretches);
			}
		}
		return $value;
	}
}
