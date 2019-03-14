<?php
namespace zhangv\alipay;
use zhangv\alipay\util\HttpClient;
use zhangv\alipay\util\Signer;

/**
 * Class AliPay
 */
class AliPay {
	const SIGNTYPE_RSA = 'RSA', SIGNTYPE_RSA2 = 'RSA2';

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
	/** @var HttpClient */
	private $httpClient = null;
	/** @var Signer */
	private $signer = null;

	public function __construct($config){
		$this->config = $config;
		$this->getHttpClient();
		$this->getSigner();
	}

	public function getHttpClient(){
		if(!$this->httpClient) $this->httpClient = new HttpClient();
		return $this->httpClient;
	}

	public function getSigner(){
		if(empty($this->config['public_key']) || empty($this->config['private_key'])) throw new \Exception('Public key and private key are required');
		if(!$this->signer) $this->signer = new Signer($this->config['public_key'],$this->config['private_key'],$this->config['alipay_public_key']);
		return $this->signer;
	}

	/**
	 * 统一收单交易创建接口
	 * @param $outTradeNo
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return mixed
	 * @throws \Exception
	 */
	public function create($outTradeNo,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'total_amount' => $amt,
			'subject' => $subject,
		],$ext);
		return $this->post('alipay.trade.create',$params);
	}

	/**
	 * 统一收单线下交易预创建
	 * @param $outTradeNo
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return mixed
	 * @throws \Exception
	 */
	public function preCreate($outTradeNo,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'total_amount' => $amt,
			'subject' => $subject,
		],$ext);
		return $this->post('alipay.trade.precreate',$params);
	}

	/**
	 * 统一收单交易支付接口
	 * @param $outTradeNo
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return mixed
	 * @throws \Exception
	 */
	public function pay($outTradeNo,$scene,$authCode,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'total_amount' => $amt,
			'subject' => $subject,
		],$ext);
		return $this->post('alipay.trade.create',$params);
	}

	/**
	 * 统一收单下单并支付页面
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.page.pay/
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return \stdClass
	 * @throws
	 */
	public function pagePay($outTradeNo,$body,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => 'FAST_INSTANT_TRADE_PAY',
		],$ext);
		return $this->post("alipay.trade.page.pay",$params);
	}

	/**
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return 提交表单HTML文本
	 */
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
	 * app支付接口
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.app.pay
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return 提交表单HTML文本
	 */
	public function appPay($outTradeNo,$body,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => 'QUICK_MSECURITY_PAY',
		],$ext);
		return $this->post("alipay.trade.app.pay",$params);
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
		$sysParams["notify_url"] = $this->config['notify_url'];
		$sysParams["return_url"] = $this->config['return_url'];

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
	 * @throws \Exception
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
	 * 支付结果后台通知处理
	 * @param $notify_data array|string 通知数据
	 * @param $callback callable 回调
	 * @return null
	 * @throws Exception
	 */
	public function onPaidNotify($data,callable $callback = null){
		if(true === $this->verifySignature($data)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$data] );
			}
		}else{
			throw new \Exception('Invalid paid notify data');
		}
	}

	/**
	 * 支付结果前台返回处理
	 * @see https://docs.open.alipay.com/203/107090/#s2
	 * @param $notify_data array|string 返回参数
	 * @param $callback callable 回调
	 * @return null
	 * @throws Exception
	 */
	public function onPaidReturn($data,callable $callback = null){
		if(true === $this->verifySignature($data)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$data] );
			}
		}else{
			throw new \Exception('Invalid paid return data');
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
	public function refundByOutTradeNo($outTradeNo,$amt,$requestNo,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'refund_amount' => $amt,
			'out_request_no' => $requestNo
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
	public function refundByTradeNo($tradeNo,$amt,$requestNo,$ext = []){
		$params = array_merge([
			'trade_no' => $tradeNo,
			'refund_amount' => $amt,
			'out_request_no' => $requestNo
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

	/**
	 * 退款交易查询
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.fastpay.refund.query/
	 */
	public function queryRefund($outRequestNo,$tradeNo = null,$outTradeNo = null,$orgPid = null){
		if(!$tradeNo && !$outTradeNo) throw new \Exception('TradeNo or OutTradeNo is required.');
		$params = ['out_request_no' => $outRequestNo];
		if($tradeNo) $params['trade_no'] = $tradeNo;
		if($outTradeNo) $params['out_trade_no'] = $outTradeNo;
		if($orgPid) $params['org_pid'] = $orgPid;
		return $this->post('alipay.trade.fastpay.refund.query',$params);
	}

	/**
	 * 统一收单交易结算接口
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.order.settle/
	 */
	public function settleOrder($outRequestNo, $tradeNo, $royalties){
		$params = [
			'out_request_no' => $outRequestNo,
			'trade_no'=>$tradeNo,
			'royalty_parameters' => $royalties
		];
		return $this->post('alipay.trade.order.settle',$params);
	}

	/**
	 * 统一收单交易关闭
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.close/
	 */
	public function close($tradeNo = null,$outTradeNo = null,$operatorId = null){
		if(!$tradeNo && !$outTradeNo) throw new \Exception('TradeNo or OutTradeNo is required.');
		$params = [];
		if($tradeNo) $params['trade_no'] = $tradeNo;
		if($outTradeNo) $params['out_trade_no'] = $outTradeNo;
		if($operatorId) $params['operator_id'] = $operatorId;
		return $this->post('alipay.trade.close',$params);
	}
	/**
	 * 统一收单交易撤销
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.cancel/
	 */
	public function cancel($tradeNo = null,$outTradeNo = null){
		if(!$tradeNo && !$outTradeNo) throw new \Exception('TradeNo or OutTradeNo is required.');
		$params = [];
		if($tradeNo) $params['trade_no'] = $tradeNo;
		if($outTradeNo) $params['out_trade_no'] = $outTradeNo;
		return $this->post('alipay.trade.cancel',$params);
	}


	private function post($method, $params) {
		$sysParams["app_id"] = $this->config['app_id'];
		$sysParams["version"] = $this->config['version'];
		$sysParams["format"] = $this->config['format'];
		$sysParams["sign_type"] = $this->config['sign_type'];
		$sysParams["method"] = $method;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["charset"] = $this->config['input_charset'];

		$params = ['biz_content'=>json_encode($params,JSON_UNESCAPED_UNICODE)];

		$sysParams["sign"] = $this->sign(array_merge($params, $sysParams), $this->config['sign_type']);

		$url = self::GATEWAY_OPENAPI . "?" . http_build_query($sysParams);

		$r = $this->httpClient->post($url,$this->config['input_charset'],$params);

		$jsonObj = json_decode($r);
		$sign = $jsonObj->sign;
		$node = str_replace('.','_',$method) . '_response';

		$signData = json_encode($jsonObj->$node,JSON_UNESCAPED_UNICODE);//注意这里一定要escape，否则中文会输出为unicode，导致验证签名错误
		$checkResult = $this->verify($signData, $sign);
		if (!$checkResult) {
			throw new \Exception("check sign Fail! [sign=" . $sign . ", signData=" . $signData . "]");
		}
		return $jsonObj->$node;
	}

	private function get($method, $params) {
		$sysParams["app_id"] = $this->config['app_id'];
		$sysParams["version"] = $this->config['version'];
		$sysParams["format"] = $this->config['format'];
		$sysParams["sign_type"] = $this->config['sign_type'];
		$sysParams["method"] = $method;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["charset"] = $this->config['input_charset'];

		$params = ['biz_content'=>json_encode($params,JSON_UNESCAPED_UNICODE)];

		$sysParams["sign"] = $this->sign(array_merge($params, $sysParams), $this->config['sign_type']);
		$tmp = array_merge($sysParams,$params);
		$url = self::GATEWAY_OPENAPI . "?" . http_build_query( $tmp);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$inputcharset = $this->config['input_charset'];
		$r = $this->httpClient->get($url,$inputcharset,$tmp);

		$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $inputcharset);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$resp = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new \Exception(curl_error($ch), 0);
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
		$node = str_replace('.','_',$method) . '_response';
		$signData = json_encode($jsonObj->$node,JSON_UNESCAPED_UNICODE);//注意这里一定要escape，否则中文会输出为unicode，导致验证签名错误
		$checkResult = $this->verify($signData, $sign);
		if (!$checkResult) {
			throw new Exception("check sign Fail! [sign=" . $sign . ", signData=" . $signData . "]");
		}

		return $jsonObj->$node;
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

	/**
	 * @param $data
	 * @return bool
	 * @throws Exception
	 */
	public function verifySignature($data){
		return $this->signer-> verify($data);
	}

	protected function getSignContent($params) {
		ksort($params);

		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
				// 转换成目标字符集
				$v = $this->characet($v, $this->config['input_charset']);

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}

	/**
	 * 数据签名
	 * @param $data
	 * @return string
	 */
	private function sign($data,$signType) {
		return $this->signer->sign($data,$signType);
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