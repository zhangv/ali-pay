<?php
namespace zhangv\alipay\service;

use zhangv\alipay\AliPay;

/**
 * 店铺API
 * @license MIT
 * @author zhangv
 * @link https://opendocs.alipay.com/apis/api_3/
 */
class Shop extends AliPay {

	/**
	 * 修改门店信息
	 */
	public function modifyShop($shopId, $ext = []) {
		$params = array_merge([
			'shop_id' => $shopId,
		],$ext);
		return $this->post('alipay.offline.market.shop.modify',$params);
	}

	/**
	 * 类目配置查询
	 */
	public function categoryQuery($categoryId,$opRole, $ext = []) {
		$params = array_merge([
			'category_id' => $categoryId,
			'op_role' => $opRole,
		],$ext);
		return $this->post('alipay.offline.market.shop.category.query',$params);
	}

	/**
	 * 查询单个门店信息
	 */
	public function queryShop($shopId, $opRole, $ext = []) {
		$params = array_merge([
			'shop_id' => $shopId,
			'op_role' => $opRole,
		],$ext);
		return $this->post('alipay.offline.market.shop.querydetail',$params);
	}

	/**
	 * 业务流水批量查询
	 */
	public function batchQuery($bizType, $opRole, $ext = []) {
		$params = array_merge([
			'biz_type' => $bizType,
			'op_role' => $opRole,
		],$ext);
		return $this->post('alipay.offline.market.applyorder.batchquery',$params);
	}

}