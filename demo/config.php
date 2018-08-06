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
	'private_key'=> 'PRIVATEKEY',
	'public_key' => 'PUBLICKEY',



	'new' => [
		'app_id' => "xxx",
		'partner' => '', //合作身份者id，以2088开头的16位纯数字
		'key'   => '', //安全检验码，以数字和字母组成的32位字符
		'sign_type' => 'RSA2', //签名方式 不需修改
		'input_charset' => 'UTF-8',
		'notify_url' => "https://xxx/pay/alipaynewnotify/", //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
		'private_key_path'=> dirname(__FILE__).'/keys/alipay/rsa_private_key.pem',
		'public_key_path' => dirname(__FILE__).'/keys/alipay/rsa_public_key.pem',
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
		'product_code' => 'QUICK_MSECURITY_PAY',
		//如果存在则优先使用字符串配置，而不是路径 private_key_path, public同理
		'private_key' => '',
		'public_key' => ''
	],
];