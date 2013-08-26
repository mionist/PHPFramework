<?php

class Auth_Bundle_Database {
	
	public $table 				= 'users';
	public $field_login 		= 'login';
	public $field_password		= 'password';
	public $field_salt			= 'salt';
	public $field_banned		= 'banned';
	public $field_admin_rights 	= 'rights';
	public $method				= 'sha1';
	public $field_reset			= 'is_reset_needed';
}