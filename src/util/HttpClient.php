<?php
/**
 * @license MIT
 * @author zhangv
 */
namespace zhangv\alipay\util;

class HttpClient{

	const GET = 'get',POST = 'post', DELETE = 'delete',PUT = 'put';
	private $instance = null;
	private $errNo = null;
	private $info = null;
	private $timeout = 1;

	public function __construct($timeout = 1) {
		$this->initInstance($timeout);
	}

	public function initInstance($timeout){
		if(!$this->instance) {
			$this->instance = curl_init();
			if ($timeout < 1) {
				curl_setopt($this->instance, CURLOPT_TIMEOUT_MS, intval($timeout * 1000));
			} else {
				curl_setopt($this->instance, CURLOPT_TIMEOUT, intval($timeout));
			}
			curl_setopt($this->instance, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->instance, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($this->instance, CURLOPT_SSL_VERIFYPEER, false);
		}
	}

	public function get($url, $inputcharset, $params = array(),$headers = array(),$opts = array()) {
		$ch = curl_init();
		if($params && count($params) > 0) $url .= '?' . http_build_query($params);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$headers[] = 'content-type: application/x-www-form-urlencoded;charset=' . $inputcharset;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$resp = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new \Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new \Exception($resp, $httpStatusCode);
			}
		}
		curl_close($ch);
		return $resp;
	}

	public function post($url, $inputcharset, $params = array(),$headers = array(),$opts = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$postBodyString = "";
		$encodeArray = Array();
		$postMultipart = false;
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
			$headers[] = 'content-type: multipart/form-data;charset=' . $inputcharset . ';boundary=' . $this->getMillisecond();
		} else {
			$headers[] = 'content-type: application/x-www-form-urlencoded;charset=' . $inputcharset;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$resp = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new \Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new \Exception($resp, $httpStatusCode);
			}
		}
		curl_close($ch);
		return $resp;
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

	private function execute() {
		$result = curl_exec($this->instance);
		$this->errNo = curl_errno($this->instance);
		$this->info = curl_getinfo($this->instance);
		return $result;
	}

	public function setOpt($optArray) {
		if (!$this->instance)	return;
		if (!is_array($optArray))	throw new \Exception("Argument is not an array!");
		curl_setopt_array($this->instance, $optArray);
	}

	public function getInfo(){
		return $this->info;
	}
}