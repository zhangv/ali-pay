<?php

require_once __DIR__ . "../AliPay.php";
$host = $_SERVER['HTTP_HOST'];
$cfg = require 'mobileconfig.php';
$alipay = new AliPay($cfg);

if($alipay->verifyMobileNotify($_POST)) {
	$notify_data = $_POST['notify_data'];
	//注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。
	$doc = new DOMDocument();
	$doc->loadXML($notify_data);
	if( ! empty($doc->getElementsByTagName( "notify" )->item(0)->nodeValue) ) {
		$orderid = $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;//商户订单号
		$trade_no = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;//支付宝交易号
		$trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;//交易状态
		//TODO 具体业务处理
	}
} else {
	//验证失败
	echo "fail";
}