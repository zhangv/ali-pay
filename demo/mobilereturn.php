<?php

require_once __DIR__ . "../AliPay.php";
$host = $_SERVER['HTTP_HOST'];
$cfg = require 'mobileconfig.php';
$alipay = new AliPay($cfg);

if($alipay->verifyMobileReturn($_GET)){
	$orderid = $_GET['out_trade_no'];//商户订单号
	$trade_no = $_GET['trade_no'];//支付宝交易号
	$trade_status = $_GET['result'];//交易状态 //和普通接口不同！！！
	//TODO 具体业务处理
} else {
	echo '支付异常';
}