<?php

require_once __DIR__ . "../AliPay.php";
$host = $_SERVER['HTTP_HOST'];
$cfg = require 'mobileconfig.php';
$alipay = new AliPay($cfg);
$merchant_url = "http://$host/demo/mobilepay.php"; //用户付款中途退出返回商户的地址
$orderno = time();
$subject = '订单名称';//必填
$total_fee = '0.01';//付款金额 必填
$form = $alipay->mobilepayForm($orderno,$subject,$total_fee,$merchant_url);
echo $form;