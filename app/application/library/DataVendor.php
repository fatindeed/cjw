<?php

use Idiorm\ORM as ORM;
use Idiorm\DAO as DAO;

/**
 * 数据来源类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class DataVendor {

	/**
	 * Vendor配置信息
	 *
	 * @ignore
	 */
	private $vendor;

	/**
	 * FTP实例
	 *
	 * @ignore
	 */
	private $ftp = false;

	/**
	 * @ignore
	 */
	public function __construct() {
		$this->vendor = Yaf_Registry::get('config')->vendor;
		switch($this->vendor->type) {
			case 'local':
				# code...
				break;
			case 'ftp':
				if(!is_dir($this->vendor->save_dir)) {
					mkdir($this->vendor->save_dir, 0777, true);
				}
				$this->ftp = ftp_connect($this->vendor->server);
				if(!$this->ftp) {
					throw new Exception('FTP connect failed');
				}
				$result = ftp_login($this->ftp, $this->vendor->user, $this->vendor->passwd);
				if(!$result) {
					throw new Exception('FTP login failed');
				}
				break;
			default:
				throw new Exception('Unable to load vendor config');
		}
	}

	/**
	 * @ignore
	 */
	public function __destruct() {
		if($this->ftp) ftp_close($this->ftp);
	}

	/**
	 * 初始化数据库
	 *
	 * @param string $month	归档月份，如为空则调用recent数据库
	 */
	public function init($month = null) {
		$connection_name = $month ? $month : ORM::DEFAULT_CONNECTION;
		$database_file = $month ? $month : 'recent';
		if(!ORM::get_config('connection_string', $connection_name)) {
			$database = Yaf_Registry::get('config')->database;
			ORM::configure('sqlite:'.$database->directory.$database_file.'.db', null, $connection_name);
			ORM::raw_execute('CREATE TABLE IF NOT EXISTS [trans] (
				[id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				[flowno] VARCHAR(12) UNIQUE NOT NULL,
				[filtime] VARCHAR(14) NOT NULL,
				[realamt] REAL NOT NULL'.($month ? '' : ', [status] INTEGER NOT NULL').'
			);', array(), $connection_name);
			ORM::raw_execute('CREATE INDEX IF NOT EXISTS [IDX_TRANS_FILTIME] ON [trans]([filtime] DESC);', array(), $connection_name);
		}
	}

	/**
	 * 查找历史付款数据库文件
	 *
	 * @param string $date	日期
	 * @return array
	 */
	public function scanPastFiles($date) {
		$dir = $this->vendor->past_dir.str_replace('-', '', $date).'/';
		$files = $this->find_pay_files($dir);
		foreach($files as $file) {
			$this->proc_pay_file($file);
		}
	}

	/**
	 * 下载历史付款数据库文件
	 *
	 * @param string $date	日期
	 * @return array
	 */
	public function downloadPastFiles($date, $save_dir) {
		$dir = $this->vendor->past_dir.str_replace('-', '', $date).'/';
		$files = $this->find_pay_files($dir);
		foreach($files as $file) {
			copy($file, $save_dir.$date.substr($file, -6));
		}
	}

	/**
	 * 查找今日付款数据库文件
	 *
	 * @return array
	 */
	public function scanDailyFiles() {
		$files = $this->find_pay_files($this->vendor->daily_dir);
		foreach($files as $file) {
			$this->proc_pay_file($file);
		}
	}

	/**
	 * 查找目录中的付款数据库文件(pay??.dbf)
	 *
	 * @param string $dir	目录路径
	 * @return array
	 */
	private function find_pay_files($dir) {
		$files = array();
		switch($this->vendor->type) {
			case 'local':
				$files = glob($dir.'pay??.dbf');
				break;
			case 'ftp':
				$contents = ftp_nlist($this->ftp, $dir);
				$i = 0;
				while(true) {
					$remote_path = sprintf($dir.'pay%02s.dbf', $i++);
					if(!in_array($remote_path, $contents)) break;
					$local_path = $this->vendor->save_dir.basename($remote_path);
					$result = ftp_get($this->ftp, $local_path, $remote_path, FTP_BINARY);
					if(!$result) {
						throw new Exception('FTP transfer failed');
					}
					$files[] = $local_path;
				}
				break;
			default:
				throw new Exception('Unable to load vendor config');
		}
		return $files;
	}

	/**
	 * 处理付款数据库文件
	 *
	 * @param string $file	数据库文件路径
	 */
	private function proc_pay_file($file) {
		$records = Dbase::load($file);
		foreach($records as $record) {
			$record = array_map('trim', $record);
			if($record['FPAYTYPE'] == 12) continue;
			$real_amt = $record['FPAYAMT'] + $record['FTIPS'];
			if($real_amt == 0) continue;
			// Time Patch for stat match
			// 01:23:45 will be saved as 06:01:23
			if($record['FTIME'] >= '000000' && $record['FTIME'] < '060000') {
				$record['FTIME'] = '06'.substr($record['FTIME'], 0, 4);
			}
			$flowno = Hex36::unpack($record['FTIME'], $record['FCHECK'], $record['FPAYTYPE']);
			$trans = DAO::factory('Trans')->where_equal('flowno', $flowno)->find_one();
			if($trans) {
				if(!$record['deleted']) continue; // 已存在记录且未取消交易，无需上传
				$flowno = substr($trans->flowno, 0, -1).Hex36::unpack(35 - $record['FPAYTYPE']);
				$real_amt = 0 - $trans->real_amt;
				$trans = DAO::factory('Trans')->where_equal('flowno', $flowno)->find_one();
				if($trans) continue; // 已存在对冲交易，无需重复上传
			}
			else {
				if($record['deleted']) continue; // 不存在记录且已取消交易，无需上传
			}
			$trans = DAO::factory('Trans')->create();
			$trans->flowno = $flowno; // 交易流水
			$trans->filtime = $record['FDATE'].$record['FTIME']; // 交易发生时间
			$trans->realamt = $real_amt; // 交易金额（实收金额，退货时，金额为负）
			$trans->status = 0;
			$trans->save();
		}
	}

}

?>