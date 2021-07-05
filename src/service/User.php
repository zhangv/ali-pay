<?php
namespace zhangv\alipay\service;

use zhangv\alipay\AliPay;

/**
 * 会员API
 * @license MIT
 * @author zhangv
 * @link https://opendocs.alipay.com/apis/api_2/alipay.user.info.share
 */
class User extends AliPay {

	/**
	 * 会员授权信息查询
	 */
	public function infoShare($authToken, $ext = []) {
		$params = array_merge([
			'auth_token' => $authToken,
		],$ext);
		return $this->post('alipay.user.info.share',$params);
	}

	/**
	 * 身份认证初始化
	 */
	public function certifyInitialize($authToken,$bizCode,$identityParam,$merchantConfig,$faceContrastPicture = '', $ext = []) {
		$params = array_merge([
			'outer_order_no' => $authToken,
			'biz_code' => $bizCode,
			'identity_param' => $identityParam,
			'merchant_config' => $merchantConfig,
			'face_contrast_picture' => '',
		],$ext);
		return $this->post('alipay.user.certify.open.initialize',$params);
	}

	/**
	 * 身份认证开始
	 */
	public function certify($certifyId, $ext = []) {
		$params = array_merge([
			'certify_id' => $certifyId,
		],$ext);
		return $this->post('alipay.user.certify.open.certify',$params);
	}

	/**
	 * 身份认证记录查询
	 */
	public function certifyQuery($certifyId, $ext = []) {
		$params = array_merge([
			'certify_id' => $certifyId,
		],$ext);
		return $this->post('alipay.user.certify.open.query',$params);
	}

}