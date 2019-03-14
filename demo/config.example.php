<?php
return $cfg = [
	'app_id' => "xxx",
	'partner' => '', //合作身份者id，以2088开头的16位纯数字
	'key'   => '', //安全检验码，以数字和字母组成的32位字符
	'sign_type' => 'RSA2', //签名方式 推荐使用RSA2
	'input_charset' => 'UTF-8',
	'notify_url' => "[URL]", //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
	//可以配置路径或者字符串
	'private_key'=> '****',
	'public_key' => '****',
	'alipay_public_key' => '****',

	'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
	'product_code' => 'QUICK_MSECURITY_PAY',
];