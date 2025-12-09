<?php

#Auth Config
return [
	
	'ad' => [
		'hosts'				=> ['DC03.8way.com.tw'],
		'base_dn'       	=> 'dc=8way,dc=com,dc=tw',
		'username'			=> '', #testing : '8waytw\LDAP or LDAP' | 'cn=LDAP,dc=local,dc=com'
		'password'			=> '', #testing : 'a12345678!@'
		#'port'         	=> 389, #636 ldaps
		#'protocol'     	=> 'ldap://',
			
		'use_ssl'       	=> TRUE, #ssl or tls只能二選一
		'use_tls'       	=> FALSE, 
		#'use_sasl'      	=> TRUE, #測試帳號LDAP要設為FALSE / 正常狀況要設為TRUE
		'version'       	=> 3,
		'timeout'       	=> 5,
		'follow_referrals'	=> FALSE,
		
		'options' => [
			LDAP_OPT_X_TLS_CACERTFILE => config_path('web/cert/8way.pem'),
			LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_ALLOW
		],
		
		/*
		'sasl_options' => [
			'mech' 		=> null,
			'realm' 	=> null,
			'authc_id' 	=> null,
			'authz_id' 	=> null,
			'props' 	=> null,
		],*/
	],
	
	#my defined
	'domain' => '8way.com.tw', 
	
	'supervisor' => [
		'T2025098' => 'tristan.hsu',
	],
	
];
