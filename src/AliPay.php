<?php

/**
 * Class AliPay
 */
class AliPay {
	/**
	 * 支付宝网关地址
	 */
	const ALIPAY_GATEWAY = 'https://mapi.alipay.com/gateway.do?';
	/**
	 * 支付宝WAP网关地址
	 */
	const ALIPAY_MOBILE_GATEWAY = 'http://wappaygw.alipay.com/service/rest.htm?';
	/**
	 * HTTPS形式消息验证地址
	 */
	const HTTPS_VERIFY_URL = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
	 * HTTP形式消息验证地址
	 */
	const HTTP_VERIFY_URL = 'http://notify.alipay.com/trade/notify_query.do?';

	/**
	 * 开放平台网关地址
	 */
	const GATEWAY_OPENAPI = "https://openapi.alipay.com/gateway.do";

	/**
	 * 请求参数配置，支付宝接口文档中所需的参数
	 * partner         合作身份者id，以2088开头的16位纯数字
	 * key             安全检验码，以数字和字母组成的32位字符
	 * sign_type       签名方式 MD5|RSA|DES
	 * input_charset   字符编码格式 目前支持 gbk 或 utf-8
	 * cacert          CA证书路径地址，用于curl中ssl校验
	 * transport       访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
	 * seller_email    卖家支付宝帐户邮箱
	 * notify_url      服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
	 * return_url      页面跳转同步通知页面路径//需http://格式的完整路径，不能加?id=123这类自定义参数
	 * 以下退款需要
	 * refund_notify_url 退款后台通知地址
	 * seller_user_id  卖家支付宝账户ID
	 */
	private $config=[];

	/**
	 * @var string
	 */
	private $payForm = <<<HTML
	<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>
	<form id="alipaysubmit" name="alipaysubmit" action="%s_input_charset=%s" method="%s">
		%s<input type='submit' value="%s" />
	</form>
	<script>//document.forms['alipaysubmit'].submit();</script>
	</body></html>
HTML;

	public $responseObject = null;

	public function __construct($config){
		$this->config = $config;
	}

	public function wapPay($outTradeNo,$body,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => 'QUICK_WAP_WAY',
		],$ext);
		return $this->buildForm("alipay.trade.wap.pay",$params);
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp 请求参数数组
	 * @return 提交表单HTML文本
	 */
	protected function buildForm($apiName,$params) {
		$sysParams["app_id"] = $this->config['app_id'];
		$sysParams["version"] = $this->config['version'];
		$sysParams["format"] = $this->config['format'];
		$sysParams["sign_type"] = $this->config['sign_type'];
		$sysParams["method"] = $apiName;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["charset"] = $this->config['input_charset'];

		$params = ['biz_content'=>json_encode($params)];

		$sysParams["sign"] = $this->sign(array_merge($params, $sysParams), $this->config['sign_type']);

		$allparams = array_merge($sysParams,$params);
		$url = self::GATEWAY_OPENAPI . '?charset=' . $this->config['input_charset'];
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='{$url}' method='POST'>";

		foreach ($allparams as $k => $v) {
			if(!$v || trim($v)=='' ) continue;
			$sHtml.= "<input type='hidden' name='{$k}' value='{$v}'/>";
		}

		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml."<input type='submit' value='ok'></form>";

		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

		return $sHtml;
	}


	/**
	 * 当面付 读取用户二维码、条码、声波码支付
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.pay/
	 * @param $outTradeNo
	 * @param $scene
	 * @param $authCode
	 * @param $amt
	 * @param string $subject
	 * @param string $body
	 * @param array $ext
	 * @return mixed
	 * @throws Exception
	 */
	public function posPay($outTradeNo,$scene,$authCode,$amt,$subject = '',$body = '',$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'scene' => $scene,
			'auth_code' => $authCode,
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => 'FACE_TO_FACE_PAYMENT',
		],$ext);
		return $this->post("alipay.trade.pay",$params);
	}

	/**
	 * 支付结果通知处理
	 * @param $notify_data array|string 通知数据
	 * @param $callback callable 回调
	 * @return null
	 * @throws Exception
	 */
	public function onPaidNotify($notify_data,callable $callback = null){
		if(true === $this->verifySignature($notify_data)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$notify_data] );
			}
		}else{
			throw new Exception('Invalid paid notify data');
		}
	}

	/**
	 * 交易查询(参数二选一,out_trade_no如果同时存在优先取trade_no)
	 * @param $outTradeNo string 商户订单号
	 * @param $tradeNo string 支付宝交易号
	 * @throws Exception
	 * @return stdClass
	 */
	public function query($outTradeNo,$tradeNo){
		$params = [];
		if($outTradeNo) $params['out_trade_no'] = $outTradeNo;
		if($tradeNo) $params['trade_no'] = $tradeNo;
		return $this->post("alipay.trade.query",$params);
	}

	/**
	 * 退款(根据商户订单号)
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.refund/
	 * @param $outtradeno
	 * @param $amt
	 * @param $reason
	 * @param $requestno
	 * @param string $operatorid
	 * @param string $storeid
	 * @param string $terminalid
	 * @return result/json
	 */
	public function refundByOutTradeNo($outTradeNo,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'refund_amount' => $amt
		],$ext);
		return $this->post('alipay.trade.refund',$params);
	}

	/**
	 * 退款(根据支付订单号)
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.refund/
	 * @param $tradeNo
	 * @param $amt
	 * @param $reason
	 * @param $requestno
	 * @param string $operatorid
	 * @param string $storeid
	 * @param string $terminalid
	 * @return result/json
	 */
	public function refundByTradeNo($tradeNo,$amt,$ext = []){
		$params = array_merge([
			'trade_no' => $tradeNo,
			'refund_amount' => $amt
		],$ext);
		return $this->post('alipay.trade.refund',$params);
	}

	/**
	 * 查询交易(根据商户订单号)
	 * @param string $outtradeno
	 * @return mixed
	 */
	public function queryByOutTradeNo($outtradeno){
		$params = ['out_trade_no'=>$outtradeno];
		return $this->post('alipay.trade.query',$params);
	}

	/**
	 * 查询交易(根据支付订单号)
	 * @param string $tradeno
	 * @return mixed
	 */
	public function queryByTradeNo($tradeno){
		$params = ['trade_no'=>$tradeno];
		return $this->post('alipay.trade.query',$params);
	}

	private function post($apiName, $params) {
		$sysParams["app_id"] = $this->config['app_id'];
		$sysParams["version"] = $this->config['version'];
		$sysParams["format"] = $this->config['format'];
		$sysParams["sign_type"] = $this->config['sign_type'];
		$sysParams["method"] = $apiName;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["charset"] = $this->config['input_charset'];

		$params = ['biz_content'=>json_encode($params)];

		$sysParams["sign"] = $this->sign(array_merge($params, $sysParams), $this->config['sign_type']);

		$url = self::GATEWAY_OPENAPI . "?" . http_build_query($sysParams);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$postBodyString = "";
		$encodeArray = Array();
		$postMultipart = false;
		$inputcharset = $this->config['input_charset'];
		if (is_array($params) && 0 < count($params)) {
			foreach ($params as $k => $v) {
				if ("@" != substr($v, 0, 1)){ //不是文件上传
					$postBodyString .= "$k=" . urlencode($this->characet($v, $inputcharset)) . "&";
					$encodeArray[$k] = $this->characet($v, $inputcharset);
				} else {//文件上传用multipart/form-data，否则用www-form-urlencoded
					$postMultipart = true;
					$encodeArray[$k] = new \CURLFile(substr($v, 1));
				}

			}
			unset ($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postMultipart) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
			}
		}

		if ($postMultipart) {
			$headers = array('content-type: multipart/form-data;charset=' . $inputcharset . ';boundary=' . $this->getMillisecond());
		} else {
			$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $inputcharset);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$resp = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new Exception($resp, $httpStatusCode);
			}
		}
		curl_close($ch);

		$r = $resp;
		$jsonObj = json_decode($r);
		$sign = $jsonObj->sign;
		$node = str_replace('.','_',$apiName) . '_response';
		$signData = json_encode($jsonObj->$node);
		$checkResult = $this->verify($signData, $sign);
		if (!$checkResult) {
			throw new Exception("check sign Fail! [sign=" . $signData->sign . ", signSourceData=" . $signData->signSourceData . "]");
		}

		return $jsonObj->$node;
	}

	private function nullOrBlank($v){
		return empty($v) || trim($v)=='';
	}

	private function verify($data, $sign) {
		$signType = $this->config['sign_type'];
		$res = "-----BEGIN PUBLIC KEY-----\n" .
			wordwrap($this->config['alipay_public_key'], 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";

		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		}
		return $result;
	}

	/**
	 * 转换字符集编码 //todo remove this ugly
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	function characet($data, $targetCharset) {
		if (!empty($data)) {
			$fileType = 'UTF-8';
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
			}
		}
		return $data;
	}

	/**
	 * 退款交易查询
	 */
	public function refundQuery(){

	}

	/**
	 * 查询对账单下载地址
	 */
	public function datadownload(){

	}

	/**
	 * 交易关闭
	 */
	public function close(){

	}

	/**
	 * 批量退款(需要密码)
	 * @deprecated
	 * 参考：http://doc.open.alipay.com/doc2/detail?spm=0.0.0.0.Fyw0eq&treeId=66&articleId=103600&docType=1
	 * 错误代码参考：http://wenku.baidu.com/view/520a0c6748d7c1c708a1456a.html###
	 * @param $refundDate
	 * @param $batchNo
	 * @param $detail
	 * @return 提交表单HTML文本
	 */
	public function batchRefundForm($refundDate,$batchNo,$detail){
		$detaildata = [];
		foreach($detail as $each){
			$tmp = [$each['tradeId'], $each['amount'],$each['reason']];
			$detaildata[] = implode('^',$tmp);
		}
		$detaildata = implode('#',$detaildata);

		$parameter = array(
			"service" => "refund_fastpay_by_platform_pwd",
			"partner" => trim($this->config['partner']),
			"notify_url"	=> $this->config['refund_notify_url'], //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
			"seller_email"	=> $this->config['seller_email'],//卖家支付宝帐户//必填
			"refund_date" => $refundDate,
			'batch_no' => $batchNo,
			'batch_num' => count($detail),
			'detail_data' =>$detaildata,
			"_input_charset"=> trim(strtolower($this->config['input_charset']))
		);
		$form = $this->buildRequestForm($parameter,"get", "确认退款");
		return $form;
	}

	/**
	 * 批量退款(不需要密码)(旧版)
	 * 如报错:ILLEGAL_PARTNER_EXTERFACE - 需要确认有没有开通该接口
	 * @deprecated
	 * @param $refundDate
	 * @param $batchNo
	 * @param $detail
	 * @return 提交表单HTML文本
	 */
	public function batchRefund($refundDate,$batchNo,$detail){
		$detaildata = [];
		foreach($detail as $each){
			$tmp = [$each['tradeId'], $each['amount'],$each['reason']];
			$detaildata[] = implode('^',$tmp);
		}
		$detaildata = implode('#',$detaildata);
		$parameter = array(
			"service" => "refund_fastpay_by_platform_nopwd",
			"partner" => trim($this->config['partner']),
			"sign_type" => trim($this->config['sign_type']),
			"notify_url"	=> $this->config['refund_notify_url'], //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
			"refund_date" => $refundDate,
			'batch_no' => $batchNo,
			'batch_num' => count($detail),
			'detail_data' =>$detaildata,
			"_input_charset"=> trim(strtolower($this->config['input_charset']))
		);
		$parameter['sign'] = $this->buildRequestMysign($parameter);
		$url = self::ALIPAY_GATEWAY.$this->createQueryString($parameter);
		$result = $this->httpGet($url);
		return $result;
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp 请求参数数组
	 * @param string $method 提交方式 post、get
	 * @param string $btnLabel 确认按钮显示文字
	 * @return 提交表单HTML文本
	 */
	private function buildRequestForm($para_temp, $method = 'get', $btnLabel = '支付') {
		$para = $this->buildRequestPara($para_temp);
		$input = "";
		foreach($para as $key => $val) {
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">\n";
		}
		return sprintf($this->payForm, self::ALIPAY_GATEWAY,'utf-8', $method, $input,$btnLabel);
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp 请求参数数组
	 * @param string $method 提交方式 post、get
	 * @param string $btnLabel 确认按钮显示文字
	 * @return 提交表单HTML文本
	 */
	private function buildRequestFormMobile($para_temp, $method = 'get', $btnLabel = '支付') {
		$para = $this->buildRequestPara($para_temp);
		$input = "";
		foreach($para as $key => $val) {
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">\n";
		}
		return sprintf($this->payForm, self::ALIPAY_MOBILE_GATEWAY,'utf-8', $method, $input,$btnLabel);
	}

	/**
	 * 生成签名结果
	 *
	 * @param $para_sort 已排序要签名的数组
	 * @return 签名结果字符串
	 */
	private function buildRequestMysign($para_sort) {
		$mysign = "";
		switch (strtoupper(trim($this->config['sign_type']))) {
			case "MD5" :
				$mysign = $this->sign($para_sort,$this->config['sign_type']);
				break;
			default :
				$mysign = "";
		}
		return $mysign;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 *
	 * @param $para 需要拼接的数组
	 * @return 拼接完成以后的字符串
	 */
	private function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		$arg = substr($arg,0,count($arg)-2);
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		return $arg;
	}

	/**
	 * 验证退款异步通知参数合法性
	 *
	 * @return boolean
	 */
	public function verifyRefundNotify($data) {
		$params = $this->filter($data); //过滤待签名数据
		$responseTxt = 'true';
		if( !empty( $params['notify_id'] ) ) {
			$responseTxt = $this->getResponseByNotifyId($params['notify_id']);
		}
		if($this->config['sign_type'] == 'RSA') {
			$signString = $this->getSignString($data);
			return $this->rsaVerify($params,$signString);
		} else {
			$sign = $this->sign($data,$this->config['sign_type']);
			if ( preg_match("/true$/i",$responseTxt) && ($sign == $data['sign']) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	private function verifySignature($data){
		return true;
		$params = $this->filter($data); //过滤待签名数据
		if($data['sign_type'] == 'RSA2'){

		}elseif($data['sign_type'] == 'RSA'){
			$signString = $this->getSignString($data);
			return $this->rsaVerify($params,$signString);
		}elseif($data['sign_type'] == 'MD5'){

		}
		return true;


		$signType = $this->config['sign_type'];
		if(!$this->nullOrBlank($this->config['public_key'])){
			$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($this->config['public_key'], 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
		}else {
			$pubKey = file_get_contents($this->config['public_key_path']);
			$res = openssl_get_publickey($pubKey);
		}

		if(!$res) throw new Exception(' RSA public key(or path) not found ');
		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		}
		if(!$this->nullOrBlank($this->config['public_key'])) {
			openssl_free_key($res);//释放资源
		}
		return $result;
	}

	/**
	 * 验证移动(wap和app)支付完成异步通知参数
	 * @param array $data 待验证数组,来此POST,包含xml
	 * @return boolean
	 */
	public function verifyMobileNotify($data) {
		$params = $this->filter($data); //过滤待签名数据
		$responseTxt = 'true';
		if( !empty( $params['notify_id'] ) ) {
			$responseTxt = $this->getResponseByNotifyId($params['notify_id']);
		}
		if($this->config['sign_type'] == 'RSA') {
			$signString = $this->getSignString($data);
			return $this->rsaVerify($params,$signString);
		} else {
			$sign = $this->sign($data,$this->config['sign_type']);
			if ( preg_match("/true$/i",$responseTxt) && ($sign == $data['sign']) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * 验证移动(wap和app)支付完成同步跳转通知参数
	 * @param array $data 待验证数组,来自GET
	 * @return boolean
	 */
	public function verifyMobileReturn($data) {
		$params = $this->filter($data); //过滤待签名数据
		$signstr = $this->getSignString($params);
		return $this->md5Verify($signstr,$data['sign'],$this->config['key']);
	}

	/**
	 * 数据签名
	 * @param $data
	 * @return string
	 */
	private function sign($data,$signType) {
		$data = $this->filter($data);
		ksort($data);
		reset($data);
		$stringSignTemp = $this->getSignString($data);
		$sign = '';

		switch ($signType) {
			case 'RSA':
				$sign = $this->rsaSign($stringSignTemp);
				break;
			case 'RSA2':
				$sign = $this->rsaSign($stringSignTemp,'RSA2');
				break;
			case 'DES':
				break;
			default:
				$sign = $this->md5Sign($stringSignTemp,$this->config['key']);
		}
		return $sign;
	}

	/**
	 * 获得待签名数据
	 * @return string
	 */
	private function getSignString($data) {
		$param_tmp = $this->filter($data); //过滤待签名数据
		//排序
		ksort($param_tmp);
		reset($param_tmp);
		//创建查询字符串形式的待签名数据
		return $this->createQueryString($param_tmp);
	}

	/**
	 * 过滤待签名数据，去掉sign、sign_type及空值
	 *
	 * @return array
	 */
	private function filter($data) {
		$para_filter = [];
		foreach($data as $key => $value){
			if($key =="sign" || empty($value)) continue;
			if(strtoupper($this->config['sign_type']) == 'MD5' && $key == 'sign_type') continue; //旧版md5签名时sign_type不参与签名
			else $para_filter[$key] = $value;
		}
		return $para_filter;
	}

	/**
	 * 用&拼接字符串,形成URL查询字符串
	 *
	 * @param array $data
	 * @param boolean $urlencode 是否对值做urlencode
	 * @return string
	 */
	private function createQueryString($data,$urlencode = false) {
		$args = [];
		foreach( $data as $key => $value ) {
			if($urlencode) {
				$key = urlencode($key);
				$value = urlencode($value);
			}
			$args[] = "$key=$value";
		}
		$args = implode('&',$args);
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()) {$args = stripslashes($args);}
		return $args;
	}

	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 *
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 *
	 */
	public function getResponseByNotifyId($notifyId) {
		$transport = strtolower(trim($this->config['transport']));
		$partner = trim($this->config['partner']);
		$verify_url = $transport == 'https'?self::HTTPS_VERIFY_URL:self::HTTP_VERIFY_URL;
		$verify_url = "{$verify_url}partner={$partner}&notify_id={$notifyId}";
		$responseTxt = $this->httpGet($verify_url);
		return $responseTxt;
	}

	/**
	 * RSA签名
	 * @param $data string 待签名数据
	 * @param $signtype string 签名方式 RSA | RSA2
	 * @return string 签名结果
	 * @throws Exception
	 */
	private function rsaSign($data,$signtype = 'RSA') {
		if(isset($this->config['private_key']) && trim($this->config['private_key'])!=''){
			$priKey = $this->config['private_key'];
			$res = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($priKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
		}elseif(isset($this->config['private_key_path']) && trim($this->config['private_key_path'])!=''
			&& file_exists($this->config['private_key_path'])
		){
			$priKey = file_get_contents($this->config['private_key_path']);
			$res = (string)openssl_get_privatekey($priKey);
		}else{
			throw new Exception('Require RSA key configuration.');
		}
		$sign = '';
		if('RSA2' == $signtype){
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($data, $sign, $res);
		}
//		openssl_free_key($res);
		$sign = base64_encode($sign);

		return $sign;
	}

	/**
	 * RSA验签
	 * @param $data array 待签名数据
	 * @param $sign string 要校对的的签名结果
	 * @return boolean 验证结果
	 */
	private function rsaVerify($data, $sign)  {
		$pubKey = file_get_contents($this->config['public_key_path']);
		$res = openssl_get_publickey($pubKey);
		$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}

	/**
	 * RSA解密
	 * @param $content 需要解密的内容，密文
	 * @return 解密后内容，明文
	 */
	private function rsaDecrypt($content) {
		$priKey = file_get_contents($this->config['private_key_path']);
		$res = openssl_get_privatekey($priKey);
		//用base64将内容还原成二进制
		$content = base64_decode($content);
		//把需要解密的内容，按128位拆开解密
		$result  = '';
		for($i = 0; $i < strlen($content)/128; $i++  ) {
			$data = substr($content, $i * 128, 128);
			openssl_private_decrypt($data, $decrypt, $res);
			$result .= $decrypt;
		}
		openssl_free_key($res);
		return $result;
	}

	/**
	 * MD5签名
	 * @param $prestr string 需要签名的字符串
	 * @param $key string 私钥
	 * @return string 签名结果
	 */
	public function md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}

	/**
	 * MD5验证签名
	 * @param $prestr string 需要签名的字符串
	 * @param $sign string 签名结果
	 * @param $key string 私钥
	 * @return boolean 签名结果
	 */
	function md5Verify($prestr, $sign, $key) {
		$prestr = $prestr . $key;
		$mysgin = md5($prestr);
		if($mysgin == $sign) return true;
		else return false;
	}

	private function httpPost($url, $data) {
		$data["sign"] = $this->sign($data,$this->config['sign_type']);
		$data["sign_type"] = $this->config['sign_type'];
		if (trim($this->config['input_charset']) != '') {
			$url = $url."_input_charset=".$this->config['input_charset'];
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$this->config['cacert']);//证书地址
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_POST,true); // post传输数据
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data);// post传输数据
		$responseText = curl_exec($curl);
		curl_close($curl);
		return $responseText;
	}

	private function getMillisecond() {
		list($s1, $s2) = explode(' ', microtime());
		return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
	}

	private function httpGet($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$this->config['cacert']);//证书地址
		$responseText = curl_exec($curl);
		curl_close($curl);
		return $responseText;
	}
}