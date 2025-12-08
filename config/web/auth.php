<?php

#Auth Config
return [
	
	'adtest' => [
		'connection' => [
			'hosts'				=> ['DC03.8way.com.tw'],
			'base_dn'       	=> 'dc=8way,dc=com,dc=tw',
			'username'			=> '', #testing : '8waytw\LDAP or LDAP' | 'cn=LDAP,dc=local,dc=com'
			'password'			=> '', #testing : 'a12345678!@'
			
			'use_ssl'       	=> TRUE, 	#ssl or tls只能二選一
			'use_tls'       	=> FALSE,	#389 Port, 文件建議選項 
			#'use_sasl'      	=> FALSE, 	#測試帳號LDAP要設為FALSE / 其它帳號要設為TRUE(不知為何)
			'version'       	=> 3,
			'timeout'       	=> 5,
			'follow_referrals'	=> FALSE,
		],
	],
	#Old Setting
	'ad' => [
		'connection' => [
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
		],
		
		'options' => [LDAP_OPT_X_TLS_CACERTFILE => config_path('web\cert\dc01.pem')],
		
		/*
		'sasl_options' => [
			'mech' 		=> null,
			'realm' 	=> null,
			'authc_id' 	=> null,
			'authz_id' 	=> null,
			'props' 	=> null,
		],*/
	],
	
	'supervisor' => [
		'T2025098' => 'tristan.hsu',
	],
	
];
