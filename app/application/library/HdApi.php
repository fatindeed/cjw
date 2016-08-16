<?php

class HdApi {

	/**
	 * @ignore
	 */
	private static $instance;

	/**
	 * API配置信息
	 *
	 * @ignore
	 */
	private $api;

	/**
	 * 登录LSLogIn成功时，返回的Session串，有效期24小时
	 *
	 * @ignore
	 */
	private $token;

	/**
	 * @ignore
	 */
	private function __construct() {
		$this->api = Yaf_Registry::get('config')->api;
		if(empty($this->api->host)) {
			throw new RuntimeException('Unable to load api config');
		}
		$this->token = file_get_contents(TEMP_PATH.'token.key');
		if(empty($this->token)) {
			$this->login();
		}
	}

	/**
	 * 单例方法
	 *
	 * @return HdApi
	 */
	public static function getInstance() {
		self::$instance || self::$instance = new HdApi();
		return self::$instance;
	}

	/**
	 * Api接口调用
	 *
	 * 1. 登录接口 LSLogIn
	 *   - STORECODE Char[4]   门店代码(商场提供)。
	 *   - LOGINNAME Char[20]  设备号(商场提供)
	 *   - PASSWORD  Char[32]  密码(商场提供)
	 * 
	 * 2. 上传交易数据接口 ILSUPLOADTRANS
	 *   - SESSION   Char[255] 登录LSLogIn成功时，返回的Session串，有效期24小时。
	 *   - DEVNAME   Char[50]  设备号(商场提供)
	 *   - FLOWNO    Char[12]  交易流水
	 *   - FILTIME   Char[12]  交易发生时间
	 *   - REALAMT   Float     交易金额（实收金额，退货时，金额为负）
	 *
	 * @param string $apiName	接口名
	 * @param array $data		接口参数
	 * @return array
	 */
	private function apiRequest($apiName, $data) {
		$output = self::httpRequest($this->api->host.'/HDDataCenterSvr.dll/'.$apiName.'?'.http_build_query($data));
		if(substr($output, 0, 3) != '[\\]') {
			throw new RuntimeException('Invalid API response: '.$output);
		}
		$str = str_replace("\n", '&', trim(substr($output, 3)));
		parse_str($str, $response);
		if($response['FRESULT'] != 0) {
			// token expired
			if($response['FRESULT'] == 1) {
				$this->login();
				$response = $this->apiRequest($apiName, $data);
			}
			else {
				throw new RuntimeException('API error: '.mb_convert_encoding($response['FMSG'], 'UTF-8', 'GBK'), $response['FRESULT']);
			}
		}
		return $response;
	}

	/**
	 * 接口登录获取密钥，密钥有效期24小时
	 */
	public function login() {
		unset($this->token);
		$response = $this->apiRequest('LSLogin', array(
			'STORECODE' => $this->api->storecode,
			'LOGINNAME' => $this->api->devname,
			'PASSWORD' => $this->api->password
		));
		if($response['FDATA'] == 0) {
			throw new RuntimeException('Login Failed');
		}
		$this->token = $response['FDATA'];
		file_put_contents(TEMP_PATH.'token.key', $this->token);
	}

	/**
	 * 商户交易数据上传
	 *
	 * @param string $flowno	交易流水
	 * @param string $filtime	交易发生时间
	 * @param float $realamt	交易金额（实收金额，退货时，金额为负）
	 */
	public function uploadTrans($flowno, $filtime, $realamt) {
		$this->apiRequest('ILSUPLOADTRANS', array(
			'SESSION' => $this->token,
			'DEVNAME' => $this->api->devname,
			'FLOWNO' => $flowno,
			'FILTIME' => $filtime,
			'REALAMT' => $realamt,
		));
	}

	/**
	 * 发起HTTP请求
	 *
	 * @param string $apiName	请求URL
	 * @return string
	 */
	private static function httpRequest($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);
		$errno = curl_errno($ch);
		if($errno) {
			throw new RuntimeException(curl_error($ch).' ['.$url.']', $errno);
		}
		curl_close($ch);
		return $response;
	}

}

?>