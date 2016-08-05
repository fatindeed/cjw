<?php

class HdApi {

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
	public function __construct() {
		$this->api = Yaf_Registry::get('config')->api;
		if(empty($this->api->host)) {
			throw new Exception('Unable to load api config');
		}
		$this->token = file_get_contents(TEMP_PATH.'token');
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
	 * @return string
	 */
	private function apiRequest($apiName, $data) {
		$output = self::httpRequest($this->api->host.'/HDDataCenterSvr.dll/'.$apiName.'?'.http_build_query($data));
		if(substr($output, 0, 3) != '[\\]') {
			throw new Exception('Invalid API response: '.$output);
		}
		$str = str_replace("\n", '&', trim(substr($output, 3)));
		parse_str($str, $response);
		if($response['FRESULT'] != 0) {
			// token expired
			if($response['FRESULT'] == 1) {
				unset($this->token);
				$this->login();
				$response = $this->apiRequest($apiName, $data);
			}
			else {
				throw new Exception('API error: '.mb_convert_encoding($response['FMSG'], 'UTF-8', 'GBK'), $response['FRESULT']);
			}
		}
		return $response;
	}

	/**
	 * 接口登录获取密钥，密钥有效期24小时
	 */
	public function login() {
		$response = $this->apiRequest('LSLogin', array(
			'STORECODE' => $this->api->storecode,
			'LOGINNAME' => $this->api->devname,
			'PASSWORD' => $this->api->password
		));
		if($response['FDATA'] == 0) {
			throw new Exception('Login Failed');
		}
		$this->token = $response['FDATA'];
		file_put_contents(TEMP_PATH.'token', $this->token);
	}

	/**
	 * 商户交易数据上传
	 *
	 * @param object $trans	交易数据
	 * @param int $deleted	是否已删除
	 */
	public function uploadTrans($trans) {
		if($trans->status == 1) return false;
		if(floatval($trans->realamt) == 0) return false;
		while(empty($this->token)) {
			$this->login();
		}
		$response = $this->apiRequest('ILSUPLOADTRANS', array(
			'SESSION' => $this->token,
			'DEVNAME' => $this->api->devname,
			'FLOWNO' => $trans->flowno,
			'FILTIME' => $trans->filtime,
			'REALAMT' => $trans->realamt,
		));
		$trans->status = 1;
		$trans->save();
		return true;
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
			throw new Exception($url.' connect failed: '.curl_error($ch), $errno);
		}
		curl_close($ch);
		return $response;
	}

}

?>