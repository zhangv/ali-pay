<?php
namespace zhangv\alipay\service;

use zhangv\alipay\AliPay;

/**
 * 会员API
 * @license MIT
 * @author zhangv
 * @link https://opendocs.alipay.com/open/218/105329
 */
class OAuth extends AliPay {

	/**
	 * 获取授权令牌接口
	 * @param $code 用户授权后的授权码
	 */
	public function getAccessToken($code, $ext = []) {
		$params = array_merge([
			'grant_type' => 'authorization_code',
			'code' => $code,
		],$ext);
		return $this->post2('alipay.system.oauth.token',$params);
	}

	/**
	 * 刷新令牌
	 * @param $token 刷新令牌，上次换取访问令牌时得到
	 */
	public function refreshAccessToken($token, $ext = []) {
		$params = array_merge([
			'grant_type' => 'refresh_token',
			'refresh_token' => $token,
		],$ext);
		return $this->post2('alipay.system.oauth.token',$params);
	}

	/**
	 * 获取用户信息
	 */
	public function getUserInfo($authToken, $ext = []) {
		$params = array_merge([
			'auth_token' => $authToken,
		],$ext);
		return $this->post2('alipay.user.info.share',$params);
	}

}