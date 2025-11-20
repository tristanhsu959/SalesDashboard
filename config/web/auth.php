<?php

#Auth Config
return [
	
	'ad' => [
		'connection' => [
			'hosts'				=> ['DC03.8way.com.tw'],
			'base_dn'       	=> 'dc=8way,dc=com,dc=tw',
			'username'			=> '', #testing : '8waytw\LDAP or LDAP' | 'cn=LDAP,dc=local,dc=com'
			'password'			=> '', #testing : 'a12345678!@'
			#'port'         	=> 389, #636 ldaps
			#'protocol'     	=> 'ldap://',
				
			'use_ssl'       	=> true, #ssl or tls只能二選一
			'use_tls'       	=> false, 
			'use_sasl'      	=> true, #測試帳號LDAP要設為FALSE / 正常狀況要設為TRUE
			'version'       	=> 3,
			'timeout'       	=> 5,
			'follow_referrals'	=> false,
		],
		
		'options' => [],
		
		'sasl_options' => [
			'mech' 		=> null,
			'realm' 	=> null,
			'authc_id' 	=> null,
			'authz_id' 	=> null,
			'props' 	=> null,
		],
	],
	
];
