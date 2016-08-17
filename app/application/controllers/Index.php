<?php

use Idiorm\DAO as DAO;

class IndexController extends Yaf_Controller_Abstract {

	/**
	 * @ignore
	 */
	private $dataAdapter;

	/**
	 * @ignore
	 */
	public function init() {
		$vendor = Yaf_Registry::get('config')->vendor;
		if(!class_exists($vendor->adapter)) {
			throw new RuntimeException('Invalid data adapter - '.$vendor->adapter);
		}
		$this->dataAdapter = new $vendor->adapter($vendor);
	}

	public function indexAction() {
		$now = time();
		$time = $this->getLastDay(true);
		while($time <= $now) {
			$dateStr = date('Ymd', $time);
			$this->process($dateStr);
			$time += 86400;
		}
		$count = $this->upload();
		// check data
		$transDao = TransDao::getInstance();
		$dateArr = $transDao->find(array('select_expr' => 'substr(filtime, 1, 8) AS date', ':status' => TransModel::STATUS_UPLOADED, 'group_expr' => 'date'), PDO::FETCH_COLUMN);
		if(is_array($dateArr) && count($dateArr) > 0) {
			$lastDay = date('Ymd', $this->getLastDay(true));
			foreach($dateArr as $date) {
				if($date < $lastDay) {
					$this->check($date);
				}
			}
			$count += $this->upload();
		}
		trigger_error('任务完成'.($count > 0 ? '，本次更新'.$count.'条交易记录' : ''), E_USER_NOTICE);
	}

	public function fixAction($args) {
		if(!is_array($args) || count($args) == 0 || !preg_match('/^\d{8}$/', $args[0])) {
			$cmd = 'cjwfix';
			echo "usage: $cmd YYYYMMDD".PHP_EOL;
			echo "   ie: $cmd ".date('Ymd').PHP_EOL;
			return false;
		}
		$date = $args[0];
		$this->process($date, true);
		// skip exit when called by self::check()
		if(isset($args[1]) && $args[1] === true) {
			return true;
		}
		$count = $this->upload();
		trigger_error($date.'数据已修复'.($count > 0 ? '，本次更新'.$count.'条交易记录' : ''), E_USER_NOTICE);
	}

	public function statAction($args) {
		if(!is_array($args) || count($args) == 0 || !preg_match('/^\d{6}$/', $args[0])) {
			// $cmd = 'php '.implode(' ', $GLOBALS['argv']);
			$cmd = 'cjwstat';
			echo "usage: $cmd YYYYMM".PHP_EOL;
			echo "   ie: $cmd ".date('Ym').PHP_EOL;
			return false;
		}
		$month = $args[0];
		$transDao = TransDao::getInstance($month);
		$dataArr = $transDao->find(array('select_expr' => 'substr(filtime, 1, 8) AS date, total(realamt) AS total', 'group_expr' => 'date'), PDO::FETCH_ASSOC);
		$content = "Date\t\tAmount\n";
		if(is_array($dataArr) && count($dataArr) > 0) {
			foreach($dataArr as $data) {
				$content .= $data['date']."\t".$data['total']."\n";
			}
		}
		echo $content;
	}

	private function getLastDay($timestamp = false) {
		$transDao = TransDao::getInstance();
		$trans = $transDao->get(array('order_expr' => 'filtime DESC'));
		if($trans) {
			$date = substr($trans->filtime, 0, 4).'-'.substr($trans->filtime, 4, 2).'-'.substr($trans->filtime, 6, 2);
		}
		else {
			$date = date('Y-m-01');
		}
		return $timestamp ? strtotime($date) : $date;
	}

	/**
	 * 处理某日营业数据
	 *
	 * @param string $date	日期
	 * @param bool $checkMode	核对模式
	 * @return array
	 */
	private function process($date, $checkMode = false) {
		$transDao = TransDao::getInstance();
		$records = $this->dataAdapter->getData($date);
		foreach($records as $record) {
			$record = array_map('trim', $record);
			if($record['FPAYTYPE'] == 12) continue;
			$realamt = $record['FPAYAMT'] + $record['FTIPS'];
			if($realamt == 0) continue;
			// Time patch begin # 01:23:45 will be saved as 06:01:23
			if($record['FTIME'] >= '000000' && $record['FTIME'] < '060000') {
				$record['FTIME'] = '06'.substr($record['FTIME'], 0, 4);
			}
			// Time patch end
			$flowno = Hex36::unpack($record['FTIME'], $record['FCHECK'], $record['FPAYTYPE']);
			$trans = $transDao->get(array(':flowno' => $flowno));
			// 已存在数据
			if($trans) {
				// 未取消无需上传
				if(!$record['deleted']) continue;
				$trans->offset();
			}
			// 不存在数据
			else {
				// 已取消无需上传
				if($record['deleted']) continue;
				$trans = new TransModel();
				$trans->flowno = $flowno;
				$trans->filtime = $record['FDATE'].$record['FTIME'];
				$trans->realamt = $realamt;
				$trans->status = TransModel::STATUS_NEW;
				$trans->save();
			}
		}
		// 归档数据处理
		if($checkMode) {
			$archiveDao = TransDao::getInstance(substr($date, 0, 6));
			$archiveArr = $archiveDao->find(array(':fildate' => $date));
			foreach($archiveArr as $archive) {
				$trans = $transDao->get(array(':flowno' => $archive->flowno));
				if($trans) {
					$trans->status = TransModel::STATUS_UPLOADED;
					$trans->save();
				}
				else {
					$archive->status = TransModel::STATUS_UPLOADED;
					$archive->offset();
				}
			}
		}
	}

	/**
	 * 上传营业数据
	 *
	 * @return int	上传数据条数
	 */
	private function upload() {
		$count = 0;
		$transDao = TransDao::getInstance();
		$transArr = $transDao->find(array(':status' => TransModel::STATUS_NEW));
		foreach($transArr as $trans) {
			if($trans->upload()) $count++;
		}
		return $count;
	}

	/**
	 * 检查某日营业数据
	 *
	 * @param string $date	日期
	 * @return null
	 */
	private function check($date) {
		$totalamt = 0;
		$records = $this->dataAdapter->getData($date);
		foreach($records as $record) {
			if($record['deleted']) continue;
			$record = array_map('trim', $record);
			if($record['FPAYTYPE'] == 12) continue;
			$realamt = $record['FPAYAMT'] + $record['FTIPS'];
			if($realamt == 0) continue;
			$totalamt += $realamt;
		}
		$transDao = TransDao::getInstance();
		$data = $transDao->find(array('select_expr' => 'SUM(realamt)', ':fildate' => $date, ':status' => TransModel::STATUS_UPLOADED), PDO::FETCH_COLUMN);
		// 已上传数据与pastdata中统计结果一致
		if(bccomp($totalamt, $data[0], 3) === 0) {
			$transArr = $transDao->find(array(':fildate' => $date, ':status' => TransModel::STATUS_UPLOADED));
			foreach($transArr as $trans) {
				$trans->archive();
			}
			// upload to cloud later
			file_put_contents(LOG_DIR.'stat.txt', "$date\t$totalamt\r\n", FILE_APPEND);
		}
		else {
			$this->fixAction(array($date, true));
		}
	}

}

?>