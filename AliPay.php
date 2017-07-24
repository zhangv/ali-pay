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
	<script>document.forms['alipaysubmit'].submit();</script>
	</body></html>
HTML;

	public function __construct($config){
		$this->config = $config;
	}

	/**
	 * 网关支付(网页版)
	 *
	 * @param $outTradeNo 商户订单号
	 * @param $subject 订单名称
	 * @param $price 付款金额
	 * @param $body  订单描述
	 * @param $showUrl 订单展示地址
	 * @param $receiveName 收货人姓名
	 * @param $receiveAddress 收货地址
	 * @param $receiveZip 收货人邮编
	 * @param $receivePhone 收货人电话
	 * @param $receiveMobile 收到人手机
	 * @param int $quantity 数量
	 * @param string $logisticsFee 物流费用
	 * @param string $logisticsType 物流类型
	 * @param string $logisticsPayment 物流支付方式
	 * @return 提交表单HTML文本
	 */
	public function payForm($outTradeNo, $subject, $price, $body, $showUrl = '', $receiveName = '', $receiveAddress = '', $receiveZip= '', $receivePhone ='', $receiveMobile = ''
		, $quantity = 1, $logisticsFee = "0.00", $logisticsType="EXPRESS", $logisticsPayment="SELLER_PAY"){
		$parameter = [
				"service" => "create_direct_pay_by_user",//"trade_create_by_buyer",
				"partner" => trim($this->config['partner']),
				"payment_type"	=> "1",//支付类型,必填，不能修改
				"notify_url"	=> $this->config['notify_url'], //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
				"return_url"	=> $this->config['return_url'],//页面跳转同步通知页面路径//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
				"seller_email"	=> $this->config['seller_email'],//卖家支付宝帐户//必填
				"out_trade_no"	=> $outTradeNo,//商户订单号//必填//商户网站订单系统中唯一订单号，必填
				"subject"	=> $subject, //订单名称//必填
				"price"	=> $price,//付款金额//必填
				"quantity"	=> $quantity,//商品数量//必填，建议默认为1，不改变值，把一次交易看成是一次下订单而非购买一件商品
				"logistics_fee"	=> $logisticsFee,//物流费用//必填，即运费
				"logistics_type"	=> $logisticsType,//物流类型//必填，三个值可选：EXPRESS（快递）、POST（平邮）、EMS（EMS）
				"logistics_payment"	=> $logisticsPayment,//物流支付方式//必填，两个值可选：SELLER_PAY（卖家承担运费）、BUYER_PAY（买家承担运费）
				"body"	=> $body, //订单描述
				"show_url"	=> $showUrl,//商品展示地址,不能放订单详情，因为没有权限
				"receive_name"	=> $receiveName,//收货人姓名
				"receive_address"	=> $receiveAddress,//收货人地址 //如：XX省XXX市XXX区XXX路XXX小区XXX栋XXX单元XXX号
				"receive_zip"	=> $receiveZip,//收货人邮编
				"receive_phone"	=> $receivePhone,//收货人电话号码
				"receive_mobile"	=> $receiveMobile,//收货人手机号码
				"_input_charset"	=> trim(strtolower($this->config['input_charset']))
		];
		$form = $this->buildRequestForm($parameter);
		return $form;
	}

	/**
	 * 网关支付(移动版)
	 * @param $outTradeNo string 商户订单号
	 * @param $subject string 订单名称
	 * @param $price string 总金额
	 * @param $merchantUrl string 操作中断返回地址
	 * @return string 提交表单HTML文本
	 */
	public function mobilepayForm($outTradeNo, $subject, $price, $merchantUrl){
		$format = "xml";//返回格式 //必填，不需要修改
		$v = "2.0";//必填，不需要修改
		$req_id = date('Ymdhis').rand(1000,9999);//请求号 //必填，须保证每次请求都是唯一
		//**req_data详细信息**
		//服务器异步通知页面路径
		$notify_url = $this->config['notify_url'];
		//页面跳转同步通知页面路径
		$call_back_url = $this->config['return_url'];
		//卖家支付宝帐户
		$seller_email = $this->config['seller_email']; //必填
		//付款金额
		$total_fee = $price;//必填
		//请求业务参数详细
		$req_data = "<direct_trade_create_req><notify_url>$notify_url</notify_url><call_back_url>$call_back_url</call_back_url><seller_account_name>$seller_email</seller_account_name><out_trade_no>$outTradeNo</out_trade_no><subject>$subject</subject><total_fee>$total_fee</total_fee><merchant_url>$merchantUrl</merchant_url></direct_trade_create_req>";//必填
		/************************************************************/
		//构造要请求的参数数组，无需改动
		$para_token = array(
				"service" => "alipay.wap.trade.create.direct",
				"partner" => trim($this->config['partner']),
				"sec_id" => trim($this->config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($this->config['input_charset']))
		);
		//建立请求
		$html_text = $this->buildRequestHttp($para_token);
		//URLDECODE返回的信息
		$html_text = urldecode($html_text);
		//解析远程模拟提交后返回的信息
		$para_html_text = $this->parseResponse($html_text);
		//获取request_token
		$request_token = $para_html_text['request_token'];
		/**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/
		//业务详细
		$req_data = "<auth_and_execute_req><request_token>$request_token</request_token></auth_and_execute_req>";//必填
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim($this->config['partner']),
				"sec_id" => trim($this->config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($this->config['input_charset']))
		);
		return $this->buildRequestFormMobile($parameter, 'get', '确认');
	}


	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	public function buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$data = $this->filter($para_temp);
		ksort($data);
		reset($data);
		//生成签名结果
		$mysign = $this->sign($data);
		$data['sign'] = $mysign;
		if($data['service'] != 'alipay.wap.trade.create.direct' && $data['service'] != 'alipay.wap.auth.authAndExecute') {
			$data['sign_type'] = strtoupper(trim($this->config['sign_type']));
		}
		return $data;
	}

	/**
	 * 该方法目前只有移动版会用到,所以hardcode移动版网关
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
	 * @param $para_temp 请求参数数组
	 * @return 支付宝处理结果
	 */
	private function buildRequestHttp($para_temp) {
		$request_data = $this->buildRequestPara($para_temp);
		$sResult = $this->httpPost(self::ALIPAY_MOBILE_GATEWAY,$request_data);
		return $sResult;
	}

	/**
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
	 * @param $para_temp 请求参数数组
	 * @param $file_para_name 文件类型的参数名
	 * @param $file_name 文件完整绝对路径
	 * @return 支付宝返回处理结果
	 */
	function buildRequestHttpInFile($para_temp, $file_para_name, $file_name) {
		$para = $this->buildRequestPara($para_temp);
		$para[$file_para_name] = "@".$file_name;
		$sResult = $this->httpPost(self::ALIPAY_MOBILE_GATEWAY,$para);
		return $sResult;
	}

	/**
	 * 解析远程模拟提交后返回的信息
	 * @param $str_text 要解析的字符串
	 * @return 解析结果
	 */
	function parseResponse($str_text) {
		//以“&”字符切割字符串
		$para_split = explode('&',$str_text);
		//把切割后的字符串数组变成变量与数值组合的数组
		foreach ($para_split as $item) {
			//获得第一个=字符的位置
			$nPos = strpos($item,'=');
			//获得字符串长度
			$nLen = strlen($item);
			//获得变量名
			$key = substr($item,0,$nPos);
			//获得数值
			$value = substr($item,$nPos+1,$nLen-$nPos-1);
			//放入数组中
			$para_text[$key] = $value;
		}

		if( ! empty ($para_text['res_data'])) {
			//解析加密部分字符串
			if($this->config['sign_type'] == '0001') {
				$para_text['res_data'] = rsaDecrypt($para_text['res_data'], $this->config['private_key_path']);
			}
			//token从res_data中解析出来（也就是说res_data中已经包含token的内容）
			$doc = new DOMDocument();
			$doc->loadXML($para_text['res_data']);
			$para_text['request_token'] = $doc->getElementsByTagName( "request_token" )->item(0)->nodeValue;
		}

		return $para_text;
	}

	/**
	 * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * PHP5+
	 * return 时间戳字符串
	 */
	function query_timestamp() {
		$url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->config['partner']))."&_input_charset=".trim(strtolower($this->config['input_charset']));
		$encrypt_key = "";

		$doc = new DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

		return $encrypt_key;
	}

	/**
	 * 退款(需要密码)
	 * 参考：http://doc.open.alipay.com/doc2/detail?spm=0.0.0.0.Fyw0eq&treeId=66&articleId=103600&docType=1
	 * 错误代码参考：http://wenku.baidu.com/view/520a0c6748d7c1c708a1456a.html###
	 * @param $refundDate
	 * @param $batchNo
	 * @param $detail
	 * @return 提交表单HTML文本
	 */
	public function refundForm($refundDate,$batchNo,$detail){
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
	 * 退款(不需要密码)
	 * 如报错:ILLEGAL_PARTNER_EXTERFACE - 需要确认有没有开通该接口
	 *
	 * @param $refundDate
	 * @param $batchNo
	 * @param $detail
	 * @return 提交表单HTML文本
	 */
	public function refund($refundDate,$batchNo,$detail){
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
				$mysign = $this->sign($para_sort);
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
			$signString = $this->getSignString();
			return $this->rsaVerify($params,$signString);
		} else {
			$sign = $this->sign($data);
			if ( preg_match("/true$/i",$responseTxt) && ($sign == $data['sign']) ) {
				return true;
			} else {
				return false;
			}
		}
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
			$sign = $this->sign($data);
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
	private function sign($data) {
		$data = $this->filter($data);
		ksort($data);
		reset($data);
		$stringSignTemp = $this->getSignString($data);
		$sign = '';
		switch ($this->config['sign_type']) {
			case 'RSA':
				$sign = $this->rsaSign($stringSignTemp);
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
	 * 过滤待签名数据，去掉sing、sing_type及空值
	 *
	 * @return array
	 */
	private function filter($data) {
		$para_filter = [];
		foreach($data as $key => $value){
			if($key =="sign"|| $key =="sign_type"|| empty($value)) continue;
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
	 * @param $data 待签名数据
	 * @return 签名结果
	 */
	private function rsaSign($data) {
		$priKey = file_get_contents($this->config['private_key_path']);
		$res = openssl_get_privatekey($priKey);
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		//base64编码
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
		$data["sign"] = $this->sign($data);
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