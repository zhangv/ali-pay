<?php
return $cfg = [
	'partner' => 'TODO', //合作身份者id，以2088开头的16位纯数字
	'key'   => 'TODO', //安全检验码，以数字和字母组成的32位字符
	'sign_type' => 'MD5', //签名方式 不需修改
	'input_charset' => strtolower('utf-8'), //字符编码格式 目前支持 gbk 或 utf-8
	'cacert'=> '/PATH/TO/cacertmobile.pem',//ca证书路径地址，用于curl中ssl校验, 请保证cacert.pem文件在当前文件夹目录中
	'transport' => 'https',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
	'seller_email' => 'TODO',//卖家支付宝帐户
	'notify_url' => "http://TODO/demo/mobilenotify.php",
	'return_url' => "http://TODO/demo/mobilereturn.php/",
	'private_key_path'=> '/PATH/TO/rsa_private_key.pem',
	'public_key_path' => '/PATH/TO/rsa_public_key.pem',
];