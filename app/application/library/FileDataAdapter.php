<?php

/**
 * 本地文件数据采集类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class FileDataAdapter extends DataAdapter {

	/**
	 * Vendor可配置参数
	 * vendor.data_dir = /path/to/dir/
	 *
	 * @ignore
	 */
	public function __construct($vendor) {
		parent::__construct($vendor);
	}

	/**
	 * 获取当日付款数据
	 *
	 * @return array
	 */
	protected function getCurrentData() {
		return $this->getPastData(date('Ymd'));
	}

	/**
	 * 获取历史付款数据
	 *
	 * @param string $date	日期
	 * @return array
	 */
	protected function getPastData($date) {
		$results = array();
		$dir = $this->vendor->data_dir.$date.DIRECTORY_SEPARATOR;
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
		$i = 0;
		while(true) {
			$filename = sprintf($dir.'pay%02s.dbf', $i++);
			if(!file_exists($filename)) break;
			$files[] = $filename;
		}
		return $files;
	}

}

?>