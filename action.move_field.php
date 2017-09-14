<?php
/*
This file is part of CMS Made Simple module: PWFBrowse
Copyright (C) 2011-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWFBrowse.module.php
More info at http://dev.cmsmadesimple..org/projects/pwfbrowse
*/

if (!($this->_CheckAccess('admin') || $this->_CheckAccess('modify'))) {
	exit;
}

//TODO
//$params['field_id'] AND ($params['prev_id'] OR $params['next_id'])
