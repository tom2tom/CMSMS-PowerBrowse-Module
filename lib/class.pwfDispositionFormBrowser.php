<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

class pwfDispositionFormBrowser extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = 1;
		$this->IsDisposition = TRUE;
		$this->NeedsDiv = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'DispositionFormBrowser';
		$this->sortable = FALSE;
	}

	function GetFieldInput($id,&$params,$returnid)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->Value === FALSE)
			return '';
		return $mod->CreateInputHidden($id,'pwfp__'.$this->Id,
			$this->EncodeReqId($this->Value));
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$enc = ($this->GetOption('crypt','0') == '1'?$mod->Lang('yes'):$mod->Lang('no'));
		$feu = ($this->GetOption('feu_bind','0') == '1'?$mod->Lang('yes'):$mod->Lang('no'));
		return $mod->Lang('title_encryption').':'.$enc.','.$mod->Lang('title_feu_binding').':'.$feu;
	}

	function DecodeReqId($theVal)
	{
		$tmp = base64_decode($theVal);
		$tmp2 = str_replace(session_id(),'',$tmp);
		if(substr($tmp2,0,1) == '_')
			return substr($tmp2,1);
		else
			return -1;
	}

	function EncodeReqId($req_id)
	{
		return base64_encode(session_id().'_'.$req_id);
	}

	function SetValue($val)
	{
		$decval = base64_decode($val);

		if($val === FALSE)
		{
			// no value set,so we'll leave value as FALSE
		}
		elseif(strpos($decval,'_') === FALSE)
		{
			// unencrypted value,coming in from previous response
			$this->Value = $val;
		}
		else
		{
			// encrypted value coming in from a form,so we'll update.
			$this->Value = $this->DecodeReqId($val);
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$formdata = $this->formdata;
		$fields = $formdata->Fields;
		$fieldlist = array($mod->Lang('none')=>'-1');
		$main = array();
		$adv = array();
		foreach ($fields as &$one)
		{
			if($one->DisplayInSubmission())
				$fieldlist[$one->GetName()] = $one->GetId();
		}
		unset($one);
		$current_indexes = array();
		for ($i=1;$i<6;$i++)
		{
			$fname = array_search($this->GetOption('sortfield'.$i),$fieldlist);
			$main[] = array($mod->Lang('title_sortable_field',array($i)),
				$mod->CreateInputDropdown($formDescriptor,'opt_sortfield'.$i,$fieldlist,-1,
					$this->GetOption('sortfield'.$i,-1))
					);
			$current_indexes[] = $this->GetOption('sortfield'.$i,-1);
		}
		$main[] = array($mod->Lang('title_note'),$mod->Lang('help_changing_triggers_reindex').
			$mod->CreateInputHidden($formDescriptor,'opt_previous_indices',implode(':',$current_indexes))
			);

		$adv[] = array($mod->Lang('title_searchable'),
			$mod->CreateInputHidden($formDescriptor,'opt_searchable','0').
			$mod->CreateInputCheckbox($formDescriptor,'opt_searchable',
			'1',$this->GetOption('searchable','0')).
			$mod->Lang('help_searchable'));

		$feu = cmsms()->GetModuleInstance('FrontEndUsers');
		if($feu === null)
		{
			$adv[] = array($mod->Lang('title_feu_binding'),
				$mod->Lang('title_install_feu'));
		}
		else
		{
			$adv[] = array($mod->Lang('title_feu_binding'),
				$mod->CreateInputHidden($formDescriptor,'opt_feu_bind','0').
				$mod->CreateInputCheckbox($formDescriptor,'opt_feu_bind',
					'1',$this->GetOption('feu_bind','0')),
				$mod->Lang('help_feu_bind'));
		}

		$openssl = cmsms()->GetModuleInstance('OpenSSL');
		if($openssl === null && !function_exists('mcrypt_encrypt'))
		{
			$adv[] = array($mod->Lang('title_encryption_functions'),
				$mod->Lang('help_install_crypto'));
		}
		else
		{
			if($openssl !== null)
			{
				$keys = $openssl->getKeyList();
				$certs = $openssl->getCertList();
			}
			$adv[] = array($mod->Lang('title_encrypt_database_data'),
				   $mod->CreateInputHidden($formDescriptor,'opt_crypt','0').
						$mod->CreateInputCheckbox($formDescriptor,'opt_crypt',
						'1',$this->GetOption('crypt','0')).
						$mod->Lang('help_encrypt_database'));
			$adv[] = array($mod->Lang('title_encrypt_sortfields'),
				   $mod->CreateInputHidden($formDescriptor,'opt_hash_sort','0').
						$mod->CreateInputCheckbox($formDescriptor,'opt_hash_sort',
						'1',$this->GetOption('hash_sort','0')).
					  $mod->Lang('help_encrypt_sortfields'));
			$adv[] = array($mod->Lang('help_encryption_key'),
				$mod->CreateInputText($formDescriptor,'opt_keyfile',
						$this->GetOption('keyfile',''),40,255));

			$cryptlibs = array();
			if($openssl !== null)
			{
				$cryptlibs[$mod->Lang('openssl')]='openssl';
			}
			if(function_exists('mcrypt_encrypt'))
			{
				$cryptlibs[$mod->Lang('mcrypt')]='mcrypt';
			}

			$adv[] = array($mod->Lang('title_crypt_lib'),
				$mod->CreateInputDropdown($formDescriptor,'opt_crypt_lib',
				$cryptlibs,-1,$this->GetOption('crypt_lib')));

			if($openssl !== null)
			{
				$adv[] = array($mod->Lang('choose_crypt'),$mod->Lang('choose_crypt_long'));

				$adv[] = array($mod->Lang('title_crypt_cert'),
					$mod->CreateInputDropdown($formDescriptor,'opt_crypt_cert',$certs,
					-1,$this->GetOption('crypt_cert')));
				$adv[] = array($mod->Lang('title_private_key'),
					$mod->CreateInputDropdown($formDescriptor,'opt_private_key',$keys,
					-1,$this->GetOption('private_key')).$mod->Lang('help_cert_key_match'));
			}
		}

		return array('main'=>$main,'adv'=>$adv);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->HiddenDispositionFields($mainArray,$advArray);
	}

	function DisposeForm($returnid)
	{
TODO	list($res,$msg) = func ($this->formdata,($this->Value?$this->Value:-1),$this->approvedBy,$this);
		return array($res,$msg);
	}

}

?>
