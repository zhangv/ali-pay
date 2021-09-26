<?php
namespace zhangv\alipay;
use Exception;
use stdClass;
use zhangv\alipay\util\HttpClient;
use zhangv\alipay\util\Signer;

/**
 * Class AliPay
 */
class AliPay {
	const SIGNTYPE_RSA = 'RSA', SIGNTYPE_RSA2 = 'RSA2';
	const SCENE_BARCODE = 'barcode', //当面付条码支付场景
		SCENE_SECURITYCODE = 'security_code'; //当面付刷脸支付场景
	const PRODUCTCODE_FACETOFACE = 'FACE_TO_FACE_PAYMENT', //当面付产品
		PRODUCTCODE_CYCLE_PAY_AUTH ='CYCLE_PAY_AUTH', //周期扣款产品
		PRODUCTCODE_GENERAL_WITHHOLDING= 'GENERAL_WITHHOLDING', //代扣产品
		PRODUCTCODE_PRE_AUTH_ONLINE = 'PRE_AUTH_ONLINE', //支付宝预授权产品
		PRODUCTCODE_PRE_AUTH = 'PRE_AUTH',//新当面资金授权产品
		PRODUCTCODE_QUICK_WAP_PAY=  'QUICK_WAP_PAY',//无线快捷支付产品
		PROCUCTCODE_QUICK_MSECURITY_PAY = 'QUICK_MSECURITY_PAY' //无线快捷支付产品（APP）
	;
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
		if(empty($this->config['public_key']) || empty($this->config['private_key'])) throw new Exception('Public key and private key are required');
		$alipayRootCert = (!empty($this->config['alipay_root_cert']))?$this->config['alipay_root_cert']:null;
		if(!$this->signer) 
			$this->signer = new Signer(
				$this->config['public_key'],$this->config['private_key'],$this->config['alipay_public_key'],
				$alipayRootCert,$this->config['input_charset']
		);
		return $this->signer;
	}

	/**
	 * 统一收单交易创建接口
	 * @param $outTradeNo
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return mixed
	 * @throws Exception
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
	 * @throws Exception
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
	 * @param $ext
	 * @return mixed
	 * @throws Exception
	 */
	public function pay($outTradeNo,$authCode, $subject,$amt,
	                    $scene = AliPay::SCENE_BARCODE,$product_code = AliPay::PRODUCTCODE_FACETOFACE, $ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'scene' => $scene,
			'auth_code' => $authCode,
			'total_amount' => $amt,
			'subject' => $subject,
			'product_code' => $product_code,
		],$ext);
		return $this->post("alipay.trade.pay",$params);
	}

	/**
	 * 统一收单交易支付接口
	 * 当面付 读取用户二维码、条码、声波码支付
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.pay/
	 * @param string $outTradeNo 商户订单号。
	 * 由商家自定义，64个字符以内，仅支持字母、数字、下划线且需保证在商户端不重复
	 * @param $scene string 支付场景。枚举值：
	 *      bar_code：当面付条码支付场景；
	 *      security_code：当面付刷脸支付场景，对应的auth_code为fp开头的刷脸标识串；
	 *      周期扣款或代扣场景无需传入，协议号通过agreement_params参数传递；
	 *      支付宝预授权和新当面资金授权场景无需传入，授权订单号通过 auth_no字段传入。
	 *      默认值为bar_code。
	 * @param string $authCode 支付授权码。
	 * @param $amt
	 * @param string $subject
	 * @param string $body
	 * @param array $ext
	 * @return mixed
	 * @throws Exception
	 */
	public function posPay($outTradeNo, $scene, $authCode, $amt, $subject = '', $body = '', $ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'scene' => $scene,
			'auth_code' => $authCode,
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => self::PRODUCTCODE_FACETOFACE,
		],$ext);
		return $this->post("alipay.trade.pay",$params);
	}

	/**
	 * 统一收单下单并支付页面
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.page.pay/
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return stdClass
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
	 * @param $totalAmt
	 * @param array $ext
	 * @return string
	 */
	public function wapPay($outTradeNo,$body,$subject,$totalAmt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $totalAmt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => self::PRODUCTCODE_QUICK_WAP_PAY,
		],$ext);
		return $this->buildForm("alipay.trade.wap.pay",$params);
	}

	/**
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return string 字符串
	 */
	public function jsPay($outTradeNo,$body,$subject,$amt,$ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => self::PRODUCTCODE_QUICK_WAP_PAY,
		],$ext);
		return $this->buildString("alipay.trade.wap.pay",$params);
	}

	/**
	 * app支付接口
	 * @link https://docs.open.alipay.com/api_1/alipay.trade.app.pay
	 * @param $outTradeNo
	 * @param $body
	 * @param $subject
	 * @param $amt
	 * @param array $ext
	 * @return string
	 */
	public function appPay($outTradeNo,$body,$subject,$amt,$product_code = AliPay::PROCUCTCODE_QUICK_MSECURITY_PAY, $ext = []){
		$params = array_merge([
			'out_trade_no' => $outTradeNo,
			'timeout_express' => '90m',
			'total_amount' => $amt,
			'body' => $body,
			'subject' => $subject,
			'product_code' => $product_code,
		],$ext);
		return $this->buildInfo("alipay.trade.app.pay",$params);
	}

	/**
	 * 支付结果后台通知处理
	 * @param array|string $data 通知数据
	 * @param callable|null $callback 回调
	 * @return null
	 * @throws Exception
	 */
	public function onPaidNotify($data,callable $callback = null){
		if(true === $this->signer->verify($data)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$data] );
			}
		}else{
			throw new Exception('Invalid paid notify data');
		}
	}

	/**
	 * 支付结果前台返回处理
	 * @see https://docs.open.alipay.com/203/107090/#s2
	 * @param array|string $data 返回参数
	 * @param callable|null $callback 回调
	 * @return null
	 * @throws Exception
	 */
	public function onPaidReturn($data,callable $callback = null){
		if(true === $this->signer->verify($data)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$data] );
			}
		}else{
			throw new Exception('Invalid paid return data');
		}
	}

	/**
	 * 交易查询(参数二选一,out_trade_no如果同时存在优先取trade_no)
	 * @param string $outTradeNo 商户订单号
	 * @param string $tradeNo 支付宝交易号
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
	 * @param string $outTradeNo
	 * @param string $amt
	 * @param string $requestNo
	 * @return stdClass
	 * @throws Exception
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
	 * @param string $tradeNo
	 * @param string $amt
	 * @param string $requestNo
	 * @return stdClass
	 * @throws Exception
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
	 * @param string $outTradeNo
	 * @return mixed
	 * @throws Exception
	 */
	public function queryByOutTradeNo($outTradeNo){
		$params = ['out_trade_no'=>$outTradeNo];
		return $this->post('alipay.trade.query',$params);
	}

	/**
	 * 查询交易(根据支付订单号)
	 * @param string $tradeno
	 * @return mixed
	 * @throws Exception
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
		if(!$tradeNo && !$outTradeNo) throw new Exception('TradeNo or OutTradeNo is required.');
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
		if(!$tradeNo && !$outTradeNo) throw new Exception('TradeNo or OutTradeNo is required.');
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
		if(!$tradeNo && !$outTradeNo) throw new Exception('TradeNo or OutTradeNo is required.');
		$params = [];
		if($tradeNo) $params['trade_no'] = $tradeNo;
		if($outTradeNo) $params['out_trade_no'] = $outTradeNo;
		return $this->post('alipay.trade.cancel',$params);
	}


	/**
	 * 创建APP支付所需要的字符串格式+签名
	 * @param $para_temp 请求参数数组
	 * @return string
	 */
	protected function buildInfo($apiName,$params) {
		$params["alipay_sdk"] = 'alipay-sdk-java-dynamicVersionNo';
		return $this->buildString($apiName,$params);
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp 请求参数数组
	 * @return string
	 */
	protected function buildForm($apiName,$params) {
		$allparams = $this->buildParams($apiName,$params);
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
	 * 生成用于jsapi、app的字符串
	 * ref: https://myjsapi.alipay.com/alipayjsapi/util/pay/tradePay.html
	 * @param $params array 请求参数数组
	 * @return string
	 */
	protected function buildString($apiName,$params) {
		$allparams = $this->buildParams($apiName,$params);
		ksort($allparams);
		$finals = [];
		foreach($allparams as $k => $v){
			$finals[] = ("{$k}=". urlencode($v));
		}
		return implode('&',$finals);
	}

	private function commonParams($method){
		$sysParams["app_id"] = $this->config['app_id'];
		$sysParams["version"] = $this->config['version'];
		$sysParams["format"] = $this->config['format'];
		$sysParams["sign_type"] = $this->config['sign_type'];
		$sysParams["method"] = $method;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["charset"] = $this->config['input_charset'];
		return $sysParams;
	}

	/**
	 * 添加公共请求参数并添加签名信息
	 *
	 */
	private function buildParams($apiName,$params){
		$common = $this->commonParams($apiName);
		$bizContent = ['biz_content'=>json_encode($params,JSON_UNESCAPED_UNICODE)];
		$all = array_merge($bizContent, $common);
		$all["sign"] = $this->signer->sign($all, $this->config['sign_type']);
		return $all;
	}

	protected function post($method, $params) {
		$common = $this->commonParams($method);
		$url = self::GATEWAY_OPENAPI . "?" . http_build_query($common);
		$params = $this->buildParams($method,$params);
		$r = $this->httpClient->post($url,$this->config['input_charset'],$params);
		$jsonObj = json_decode($r);
		$sign = $jsonObj->sign;
		$node = str_replace('.','_',$method) . '_response';

		$signData = json_encode($jsonObj->$node,JSON_UNESCAPED_UNICODE);//注意这里一定要escape，否则中文会输出为unicode，导致验证签名错误
		$checkResult = $this->signer->verify($signData, $sign);
		if (!$checkResult) {
			throw new Exception("check sign Fail! [sign=" . $sign . ", signData=" . $signData . "]");
		}
		return $jsonObj->$node;
	}

	protected function get($method, $params) {
		$allParams = $this->buildParams($method,$params);
		$url = self::GATEWAY_OPENAPI . "?" . http_build_query( $allParams);
		$inputcharset = $this->config['input_charset'];
		$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $inputcharset);
		
		$r = $this->httpClient->get($url,$inputcharset,$allParams,$headers);

		$jsonObj = json_decode($r);
		$sign = $jsonObj->sign;
		$node = str_replace('.','_',$method) . '_response';
		$signData = json_encode($jsonObj->$node,JSON_UNESCAPED_UNICODE);//注意这里一定要escape，否则中文会输出为unicode，导致验证签名错误
		$checkResult = $this->signer->verify($signData, $sign);
		if (!$checkResult) {
			throw new Exception("check sign Fail! [sign=" . $sign . ", signData=" . $signData . "]");
		}

		return $jsonObj->$node;
	}


	/**
	 * 验证退款异步通知参数合法性
	 *
	 * @return boolean
	 */
	public function verifyRefundNotify($data) {
		$verified = $this->signer->verify($data);
		$responseTxt = 'true';
		if( !empty( $data['notify_id'] ) ) {
			$responseTxt = $this->getResponseByNotifyId($data['notify_id']);
		}
		return ( preg_match("/true$/i",$responseTxt) && $verified );
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
		$verifyUrl = $transport == 'https'?self::HTTPS_VERIFY_URL:self::HTTP_VERIFY_URL;
		$verifyUrl = "{$verifyUrl}partner={$partner}&notify_id={$notifyId}";
		$responseTxt = $this->httpClient->get($verifyUrl);
		return $responseTxt;
	}

}