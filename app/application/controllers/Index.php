<?php

use Idiorm\DAO as DAO;

class IndexController extends Yaf_Controller_Abstract {

	public function indexAction() {
		// prepare database
		$dataVendor = new DataVendor();
		$today = date('Y-m-d');
		$date = $this->getLastDay();
		while($date < $today) {
			$dataVendor->scanPastFiles($date);
			$date = date('Y-m-d', strtotime($date) + 86400);
		}
		$dataVendor->scanDailyFiles();
		unset($dataVendor);
		// upload
		$count = 0;
		$hdApi = new HdApi();
		$transList = DAO::factory('Trans')->where_equal('status', '0')->find_many();
		foreach($transList as $trans) {
			if($hdApi->uploadTrans($trans)) {
				$count++;
			}
		}
		// optimize database
		$transList = DAO::factory('Trans')->where_lt('filtime', str_replace('-', '', $this->getLastDay()).'000000')->where_equal('status', '1')->order_by_asc('filtime')->find_many();
		foreach($transList as $trans) {
			$month = substr($trans->filtime, 0, 6);
			DataVendor::init($month);
			$alt_trans = DAO::factory('Trans', $month)->create();
			$alt_trans->flowno = $trans->flowno;
			$alt_trans->filtime = $trans->filtime;
			$alt_trans->realamt = $trans->realamt;
			$alt_trans->save();
			$trans->delete();
		}
		trigger_error('任务完成'.($count > 0 ? '，本次上传'.$count.'条交易记录' : ''), E_USER_NOTICE);
	}

	public function statAction() {
		$args = $this->getRequest()->getParam('args');
		if(!is_array($args) || count($args) == 0) {
			$cmd = 'php '.implode(' ', $GLOBALS['argv']);
			echo "usage: $cmd YYYYMM".PHP_EOL;
			echo "   ie: $cmd ".date('Ym').PHP_EOL;
			return false;
		}
		$month = $args[0];
		DataVendor::init($month);
		$results = DAO::factory('Trans', $month)->raw_query('SELECT substr(filtime, 1, 8) AS date, total(realamt) AS total FROM trans GROUP BY date')->find_many();
		echo "Date\t\tAmount\n";
		foreach($results as $stat) {
			echo $stat->date."\t".$stat->total."\n";
		}
	}

	public function downloadAction() {
		define('SAVE_DIR', APP_PATH.'downloads'.DIRECTORY_SEPARATOR);
		if(!is_dir(SAVE_DIR)) {
			mkdir(SAVE_DIR, 0777, true);
		}
		$dataVendor = new DataVendor();
		$month = date('Ym');
		$totalDays = date('t'); // Number of days in the given month
		for($i = 1; $i <= $totalDays; $i++) {
			$dataVendor->downloadPastFiles($month.sprintf('%02s', $i), SAVE_DIR);
		}
		unset($dataVendor);
	}

	public function fixAction($args) {
		echo date('Y-m-d H:i:s')."\t";
		// prepare database
		if(!is_array($args) && count($args) == 0) {
			throw new Exception('命令不正确。正确的命令格式：php index.php fix '.date('Ymd'));
		}
		$date = $args[0];
		if(strlen($date) != 8) {
			throw new Exception('日期必须为8位数字。正确的命令格式：php index.php fix '.date('Ymd'));
		}
		$dataVendor = new DataVendor();
		$dataVendor->scanPastFiles($date);
		// upload
		$month = substr($date, 0, 6);
		DataVendor::init($month);
		$counter = 0;
		$hdApi = new HdApi();
		$transList = DAO::factory('Trans')->where_gt('filtime', $date.'000000')->where_lt('filtime', ($date + 1).'000000')->order_by_asc('filtime')->find_many();
		foreach($transList as $trans) {
			$alt_trans = DAO::factory('Trans', $month)->where_equal('flowno', $trans->flowno)->find_one();
			if($alt_trans) {
				$trans->delete();
				continue;
			}
			$hdApi->uploadTrans($trans);
			$trans->status = 1;
			$trans->save();
			$counter++;
		}
		echo '任务完成'.($counter > 0 ? '，本次上传'.$counter.'条交易记录' : '')."\r\n";
	}

	private function getLastDay() {
		$trans = DAO::factory('Trans')->order_by_desc('filtime')->find_one();
		if($trans) {
			return substr($trans->filtime, 0, 4).'-'.substr($trans->filtime, 4, 2).'-'.substr($trans->filtime, 6, 2);
		}
		else {
			return date('Y-m-01');
		}
	}

}

?>