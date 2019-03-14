<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 2018/5/7
 * Time: 11:51
 */

use zhangv\alipay\AliPay;
use PHPUnit\Framework\TestCase;

class AliPayTest extends TestCase{
	/** @var  AliPay */
	private $aliPay;
	/** @var  array */
	private $config;

	public function setUp(){
		$this->config = include __DIR__ .'/../demo/config.php';
		$this->aliPay = new AliPay($this->config);
	}

	/**
	 * @test
	 */
	public function verifySignature(){
		$data = [
			'charset' => 'UTF-8',
            'out_trade_no' => 'MT20190312000521',
			'method' => 'alipay.trade.wap.pay.return',
            'total_amount' => '1.00',
            'sign' => 'MrUTCywo2zqy2jY3FIBaZy3hcn6qQcPukUFxCX4wxtAFtqzs8BVUw9KcTbsOX3t/Ui+4pHceFZJveKgtk9W9fs6jHjcn/LCosfn2woRbpkdmHdkmE85ZIkilON3ziXYcVzSIoMmbL344EWupduZ0lolEqplPLEefMBbstHqnWYOG7HFNW6P7Ck/5Tv93SLe3Qe65HX7LL4VzaU6gKhWdOORF35L+DgddZVAZOx70IDVidkTlQAJ9osYyuU0LWsmnEshp1d6awJTYD9zwzhdh7Vc05I/DGUL5tugZkEEW/GE1YwUtTava0af+NR4w7WtdhJDvW44+FB1oCsmDQSrkhA==',
			'trade_no' => '2019031222001404980593219193',
            'auth_app_id' => '2018071260534977',
            'version' => '1.0',
            'app_id' => '2018071260534977',
            'sign_type' => 'RSA2',
			'seller_id' => '2088912169453589',
            'timestamp' => '2019-03-12 17:01:39'
		];

		$r = $this->aliPay->verifySignature($data);
		$this->assertTrue($r);
	}

	/**
	 * @test
	 */
	public function query(){
		$r = $this->aliPay->queryByOutTradeNo('MT20190312000525');
		var_dump($r);
	}

}
