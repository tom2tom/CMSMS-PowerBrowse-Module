<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class Crypter Extends Encryption
{
	const MKEY = 'masterpass';
	const SKEY = 'prefsalt';
	const STRETCHES = 8192;
	protected $mod;

	/**
	constructor:
	@mod: reference to current module object
	@method: optional openssl cipher type to use, default 'BF-CBC'
	@stretches: optional number of extension-rounds to apply, default 8192
	*/
	public function __construct(&$mod, $method='BF-CBC', $stretches=self::STRETCHES)
	{
		$this->mod = $mod;
		parent::__construct($method, 'default', $stretches);
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
	init_crypt:
	Must be called ONCE (during installation and/or after any localisation change)
	before any hash or preference-crypt
	@s: optional 'localisation' string, default ''
	*/
	public function init_crypt($s='')
	{
		if (!$s) {
			$s = $this->localise().self::SKEY;
		}
		$value = str_shuffle(openssl_random_pseudo_bytes(9).microtime(TRUE));
		$value = parent::encrypt($value,
			hash_hmac('sha256', $s, self::SKEY));
		$this->mod->SetPreference(hash('tiger192,3', $s),
			base64_encode($value));
	}

	/**
	encrypt_preference:
	@value: value to be stored, normally a string
	@key: module-preferences key
	@s: optional 'localisation' string, default ''
	*/
	public function encrypt_preference($key, $value, $s='')
	{
		if (!$s) {
			$s = $this->localise();
		}
		$value = parent::encrypt(''.$value,
			hash_hmac('sha256', $s.$this->decrypt_preference(self::SKEY), $key));
		$this->mod->SetPreference(hash('tiger192,3', $s.$key),
			base64_encode($value));
	}

	/**
	decrypt_preference:
	@key: module-preferences key
	@s: optional 'localisation' string, default ''
	Returns: plaintext string, or FALSE
	*/
	public function decrypt_preference($key, $s='')
	{
		if (!$s) {
			$s = $this->localise();
		}
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
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	@escaped: optional boolean, whether to escape single-quote chars in the (raw) encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty or if password is empty
	*/
	public function encrypt_value($value, $pw=FALSE, $based=FALSE, $escaped=FALSE)
	{
		if ($value) {
			if (!$pw) {
				$pw = self::decrypt_preference(self::MKEY);
			}
			if ($pw) {
				$value = parent::encrypt(''.$value, $pw);
				if ($based) {
					$value = base64_encode($value);
				} elseif ($escaped) {
					$value = str_replace('\'', '\\\'', $value); //facilitate db-field storage
				}
			}
		}
		return $value;
	}

	/**
	decrypt_value:
	@value: string to be decrypted, may be empty
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether @value is base64_encoded, default FALSE
	@escaped: optional boolean, whether single-quote chars in (raw) @value have been escaped, default FALSE
	Returns: decrypted @value, or just @value if it's empty or if password is empty
	*/
	public function decrypt_value($value, $pw=FALSE, $based=FALSE, $escaped=FALSE)
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
				$value = parent::decrypt($value, $pw);
			}
		}
		return $value;
	}
}
