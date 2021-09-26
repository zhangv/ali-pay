<?php

namespace zhangv\alipay\util;
use zhangv\alipay\AliPay;
use zhangv\alipay\util\CertUtils;

/**
 * 签名/验签辅助类
 *
 * @package zhangv\alipay\util
 * @author zhangv
 * @license MIT
 */
class Signer{

	private $publicKey,$privateKey;
	private $alipayPublicKey;
	private $charset;
	private $certMode = false; //是否证书模式
	private $alipayRootCertSN = null;
	private $appCertSN = null;

	/**
	 * RSA模式初始化
	 * @param publicKey 应用公钥字符串
	 * @param privateKey 应用私钥字符串
	 * @param alipayPublicKey 支付宝公钥字符串
	 */
	public function __construct($publicKey,$privateKey,$alipayPublicKey,$charset = 'UTF-8',$alipayRootCert = null){
		if($alipayRootCert){
			$this->certMode = true;
			$cu = new CertUtils();
			if(is_file($privateKey)) $this->privateKey = file_get_contents($privateKey);
			else $this->privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($privateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
	
			$this->publicKey = $cu->getPublicKey($publicKey);
			$this->alipayPublicKey = $cu->getPublicKey($alipayPublicKey);
			$this->alipayRootCertSN = $cu->getRootCertSN($alipayRootCert);
			$this->appCertSN = $cu->getCertSN($this->publicKey); //todo test
		}else{
			if(is_file($publicKey)) $this->publicKey = file_get_contents($publicKey);
			else $this->publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
	
			if(is_file($privateKey)) $this->privateKey = file_get_contents($privateKey);
			else $this->privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($privateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
	
			if(is_file($alipayPublicKey)) $this->alipayPublicKey = file_get_contents($alipayPublicKey);
			else $this->alipayPublicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($alipayPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";	
		}
		
		$this->charset = $charset;
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
	 * 数据签名
	 * @param $data
	 * @param $signType
	 * @return string
	 */
	public function sign($data,$signType) {
		if($this->certMode === true){
			$data = array_merge($data,[
				'alipay_root_cert_sn' => $this->alipayRootCertSN,
				'app_cert_sn' => $this->appCertSN
			]);
		}
		ksort($data);
		reset($data);
		$stringSignTemp = $this->createQueryString($data);
		$sign = null;
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
				throw new \Exception('Not supported sign type - '.$signType);
		}
		return $sign;
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
			if($key == 'sign_type') continue;
			//TODO: 生活号异步通知会保留sign_type参数参与验签。
			else $para_filter[$key] = $value;
		}
		return $para_filter;
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
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	function characet($data, $targetCharset) {
		if (!empty($data)) {
			$fileType = $this->charset;
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
			}
		}
		return $data;
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

	/**
	 * RSA签名
	 * @param $data string 待签名数据
	 * @param $signtype string 签名方式 RSA | RSA2
	 * @return string 签名结果
	 * @throws Exception
	 */
	private function rsaSign($data,$signtype = AliPay::SIGNTYPE_RSA) {
		$res = $this->privateKey;
		$sign = null;
		if(AliPay::SIGNTYPE_RSA2 == $signtype){
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($data, $sign, $res);
		}
//		openssl_free_key($res);
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * @param $data
	 * @return bool
	 * @throws Exception
	 */
	public function verify($data, $sign = null){
		$signType = $data['sign_type'];
		if(!$sign) $sign = $data['sign'];

		if($this->certMode === true){
			$data = array_merge($data,[
				'alipay_root_cert_sn' => $this->alipayRootCertSN,
				'app_cert_sn' => $this->appCertSN
			]);
		}
		
		$params = $this->filter($data); //过滤待签名数据
		ksort($params);
		$urlstring = $this->createQueryString($params);
		$result = false;
		if($signType == AliPay::SIGNTYPE_RSA2){
			$result = (bool)openssl_verify($urlstring, base64_decode($sign), $this->alipayPublicKey, OPENSSL_ALGO_SHA256);
		}elseif($signType == AliPay::SIGNTYPE_RSA){
			$result = (bool)openssl_verify($urlstring, base64_decode($sign), $this->alipayPublicKey);
		}
		return $result;
	}

}