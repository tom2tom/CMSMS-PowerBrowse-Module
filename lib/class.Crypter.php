<?php
# This file is part of CMS Made Simple module: PWFBrowse
# Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWFBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/PWFBrowse

namespace PWFBrowse;

class Crypter Extends Encryption
{
	const STRETCHES = 8192;
	protected $mod;

	/*
	@mod: reference to current module object
	*/
	public function __construct(&$mod, $algo='BF-CBC', $stretches=self::STRETCHES)
	{
		$this->mod = $mod;
		parent::__construct($algo, 'default', $stretches);
	}

	/**
	encrypt_preference:
	@value: value to be stored, normally a string
	@key: module-preferences key
	*/
	public function encrypt_preference($key, $value)
	{
		$pw = hash('crc32b', $this->mod->GetPreference('nQCeESKBr99A').$this->mod->GetModulePath()); //site&module-dependent
		$s = parent::encrypt($value, $pw);
		$this->mod->SetPreference($key, base64_encode($s));
	}

	/**
	decrypt_preference:
	@key: module-preferences key
	Returns: plaintext string, or FALSE
	*/
	public function decrypt_preference($key)
	{
		$s = base64_decode($this->mod->GetPreference($key));
		$pw = hash('crc32b', $this->mod->GetPreference('nQCeESKBr99A').$this->mod->GetModulePath());
		return parent::decrypt($s, $pw);
	}

	/**
	encrypt_value:
	@value: string to encrypted, may be empty
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether to base64_encode the encrypted value, default FALSE
	Returns: encrypted @value, or just @value if it's empty
	*/
	public function encrypt_value($value, $pw=FALSE, $based=FALSE)
	{
		if ($value) {
			if (!$pw) {
				$pw = self::decrypt_preference('masterpass');
			}
			if ($pw) {
				$value = parent::encrypt($value, $pw);
				if ($based) {
					$value = base64_encode($value);
				}
			}
		}
		return $value;
	}

	/**
	decrypt_value:
	@value: string to decrypted, may be empty
	@pw: optional password string, default FALSE (meaning use the module-default)
	@based: optional boolean, whether @value is base64_encoded, default FALSE
	Returns: decrypted @value, or just @value if it's empty
	*/
	public function decrypt_value($value, $pw=FALSE, $based=FALSE)
	{
		if ($value) {
			if (!$pw) {
				$pw = self::decrypt_preference('masterpass');
			}
			if ($pw) {
				if ($based) {
					$value = base64_decode($value);
				}
				$value = parent::decrypt($value, $pw);
			}
		}
		return $value;
	}
}
