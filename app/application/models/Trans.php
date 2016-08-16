<?php

class TransModel {

	/**
	 * 数据连接ID
	 *
	 * @ignore
	 */
	private $connId = null;

	/**
	 * ID
	 *
	 * @ignore
	 */
	private $id = null;

	/**
	 * 交易流水
	 *
	 * @ignore
	 */
	public $flowno;

	/**
	 * 交易发生时间
	 *
	 * @ignore
	 */
	public $filtime;

	/**
	 * 交易金额
	 *
	 * @ignore
	 */
	public $realamt;

	/**
	 * 状态
	 *
	 * @ignore
	 */
	public $status;

	const STATUS_NEW = 0;
	const STATUS_UPLOADED = 1;
	const STATUS_CHECKED = 2;

	/**
	 * @param string $connId	归档月份，如为空则调用recent数据库
	 */
	public function __construct($connId = 'recent') {
		$this->connId = $connId;
	}

	/**
	 * 保存
	 *
	 * @return int	新增数据ID
	 */
	public function save() {
		$data = array(
			':flowno' => $this->flowno,
			':filtime' => $this->filtime,
			':realamt' => $this->realamt,
		);
		if($this->connId == 'recent') {
			$data[':status'] = $this->status;
		}
		$transDao = TransDao::getInstance($this->connId);
		if($this->id > 0) {
			$data[':id'] = $this->id;
			$transDao->update($data);
		}
		else {
			$this->id = $transDao->create($data);
		}
	}

	/**
	 * 删除
	 *
	 * @return null
	 */
	public function delete() {
		$transDao = TransDao::getInstance();
		return $transDao->delete(array(':id' => $this->id));
	}

	/**
	 * 撤销当前交易
	 *
	 * @return null
	 */
	public function offset() {
		if($this->status == self::STATUS_NEW) {
			return $this->delete();
		}
		$fPayType = Hex36::to10(substr($this->flowno, -1));
		$flowno = substr($this->flowno, 0, -1).Hex36::to36(35 - $fPayType);
		$transDao = TransDao::getInstance();
		$trans = $transDao->get(array(':flowno' => $flowno));
		if(empty($trans)) {
			$transDao->create(array(
				':flowno' => $flowno,
				':filtime' => $this->filtime,
				':realamt' => 0 - $this->realamt,
				':status' => self::STATUS_NEW
			));
		}
	}

	/**
	 * 上传交易数据
	 *
	 * @return bool
	 */
	public function upload() {
		if($this->status != self::STATUS_NEW) return false;
		if(floatval($this->realamt) == 0) return false;
		$hdApi = HdApi::getInstance();
		$hdApi->uploadTrans($this->flowno, $this->filtime, $this->realamt);
		$this->status = self::STATUS_UPLOADED;
		$this->save();
		return true;
	}

	/**
	 * 归档数据
	 *
	 * @return bool
	 */
	public function archive() {
		if($this->status != self::STATUS_UPLOADED) return false;
		$trans = new TransModel(substr($this->filtime, 0, 6));
		$trans->flowno = $this->flowno;
		$trans->filtime = $this->filtime;
		$trans->realamt = $this->realamt;
		$trans->save();
		if($this->id > 0) {
			return $this->delete();
		}
		else {
			return 0;
		}
	}

}