<?php

/**
 * FTP数据采集类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class FtpDataAdapter extends DataAdapter {

	/**
	 * FTP实例
	 *
	 * @ignore
	 */
	private $ftp = false;

	/**
	 * Vendor可配置参数
	 * vendor.server = x.x.x.x
	 * vendor.user = xxxx
	 * vendor.passwd = xxxx
	 * vendor.daily_dir = /path/to/dir/
	 * vendor.past_dir = /path/to/dir/
	 *
	 * @ignore
	 */
	public function __construct($vendor) {
		parent::__construct($vendor);
		$this->ftp = ftp_connect($this->vendor->server);
		if(!$this->ftp) {
			throw new RuntimeException('FTP connect failed');
		}
		$result = ftp_login($this->ftp, $this->vendor->user, $this->vendor->passwd);
		if(!$result) {
			throw new RuntimeException('FTP login failed');
		}
		ftp_pasv($this->ftp, true);
	}

	/**
	 * @ignore
	 */
	public function __destruct() {
		ftp_close($this->ftp);
	}

	/**
	 * 获取当日付款数据
	 *
	 * @return array
	 */
	public function getCurrentData() {
		$results = array();
		$files = $this->findPayFiles($this->vendor->daily_dir);
		foreach($files as $file) {
			$records = parent::loadDbase($file);
			$results = array_merge($results, $records);
		}
		return $results;
	}

	/**
	 * 获取历史付款数据
	 *
	 * @param string $date	日期
	 * @return array
	 */
	public function getPastData($date) {
		$results = array();
		$dir = $this->vendor->past_dir.$date.DIRECTORY_SEPARATOR;
		$files = $this->findPayFiles($dir);
		foreach($files as $file) {
			$records = parent::loadDbase($file);
			$results = array_merge($results, $records);
		}
		return $results;
	}

	/**
	 * 查找目录中的付款数据库文件(pay??.dbf)
	 *
	 * @param string $dir	目录路径
	 * @return array
	 */
	private function findPayFiles($dir) {
		$files = array();
		$contents = ftp_nlist($this->ftp, $dir);
		$i = 0;
		while(true) {
			$remoteFile = sprintf($dir.'pay%02s.dbf', $i++);
			if(!in_array($remoteFile, $contents)) break;
			$localFile = TEMP_PATH.basename($remoteFile);
			$result = ftp_get($this->ftp, $localFile, $remoteFile, FTP_BINARY);
			if(!$result) {
				throw new RuntimeException('FTP transfer failed');
			}
			$files[] = $localFile;
		}
		return $files;
	}

}

?>