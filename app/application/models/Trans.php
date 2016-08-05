<?php

class TransModel extends Idiorm\DAO {

	/**
	 * 重写save方法，附加验证功能
	 *
	 * @return null
	 */
	public function save() {
		// if($this->is_dirty('flowno') && !filter_var($this->flowno, FILTER_VALIDATE_IP)) {
		// 	throw new Exception('Invalid flowno.');
		// }
		// if($this->is_dirty('filtime') && !filter_var($this->filtime, FILTER_VALIDATE_IP)) {
		// 	throw new Exception('Invalid datetime.');
		// }
		if($this->is_dirty('realamt') && !filter_var($this->realamt, FILTER_VALIDATE_FLOAT)) {
			throw new Exception('Invalid amount: '.$this->realamt);
		}
		return parent::save();
	}

}