<?php

/**
 * 数据采集接口
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
abstract class DataAdapter {

	/**
	 * Vendor配置信息
	 *
	 * @ignore
	 */
	protected $vendor;

	/**
	 * @ignore
	 */
	protected $today;

	/**
	 * @ignore
	 */
	public function __construct($vendor) {
		$this->vendor = $vendor;
		$this->today = date('Ymd');
	}

	/**
	 * 获取付款数据
	 *
	 * @param string $date	日期
	 * @return array
	 */
	public function getData($date) {
		if($date < $this->today) {
			return $this->getPastData($date);
		}
		else {
			return $this->getCurrentData();
		}
	}

	/**
	 * 获取当日付款数据
	 *
	 * @return array
	 */
	abstract protected function getCurrentData();

	/**
	 * 获取历史付款数据
	 *
	 * @param string $date	日期
	 * @return array
	 */
	abstract protected function getPastData($date);

	/**
	 * 读取dbase数据库
	 *
	 * @param string $filename	数据库文件路径
	 * @return array
	 */
	protected static function loadDbase($filename) {
		$results = array();
		$db = dbase_open($filename, 0);
		if(empty($db)) {
			throw new RuntimeException('Failed to open dbase:'.$filename);
		}
		$record_numbers = dbase_numrecords($db);
		for($i = 1; $i <= $record_numbers; $i++) {
			$results[] = dbase_get_record_with_names($db, $i);
		}
		return $results;
	}
}

?>