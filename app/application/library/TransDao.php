<?php

/**
 * TransDao 营业数据操作类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class TransDao {

	/**
	 * @ignore
	 */
	private static $instances = array();

	/**
	 * @ignore
	 */
	private $connId;

	/**
	 * dbh实例
	 *
	 * @ignore
	 */
	private $dbh;

	/**
	 * @ignore
	 */
	private static $fields = array();

	/**
	 * 初始化数据库
	 *
	 * @param string $connId	归档月份，如为空则调用recent数据库
	 * @return TransDao
	 */
	private function __construct($connId = 'recent') {
		$this->connId = $connId;
		$ddl = false;
		$filename = DATA_DIR.$connId.'.db';
		$ddl = !file_exists($filename);
		$this->dbh = new PDO('sqlite:'.$filename);
		if($ddl) {
			$this->dbh->query('CREATE TABLE IF NOT EXISTS [trans] (
				[id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				[flowno] VARCHAR(12) UNIQUE NOT NULL,
				[filtime] VARCHAR(14) NOT NULL,
				[realamt] REAL NOT NULL'.
				($connId == 'recent' ? ', [status] INTEGER NOT NULL' : '').
			');');
			$this->dbh->query('CREATE INDEX IF NOT EXISTS [IDX_TRANS_FILTIME] ON [trans]([filtime] DESC);');
		}
		return $this;
	}

	/**
	 * 单例方法
	 *
	 * @param string $connId	归档月份，如为空则调用recent数据库
	 * @return TransDao
	 */
	public static function getInstance($connId = 'recent') {
		self::$instances[$connId] || self::$instances[$connId] = new TransDao($connId);
		if($connId == 'recent') {
			self::$fields = array('flowno', 'filtime', 'realamt', 'status');
		}
		else {
			self::$fields = array('flowno', 'filtime', 'realamt');
		}
		return self::$instances[$connId];
	}

	/**
	 * 新增数据
	 *
	 * @param array $params	数据
	 * @return int 新记录ID
	 */
	public function create($params) {
		$sth = $this->dbh->prepare('INSERT INTO trans ('.implode(',', self::$fields).') VALUES (:'.implode(',:', self::$fields).')');
		$sth->execute($params);
		return $this->dbh->lastInsertId();
	}

	/**
	 * 更新数据
	 *
	 * @param array $params	数据
	 * @return int 影响行数
	 */
	public function update($params) {
		if(!is_numeric($params[':id']) || $params[':id'] <= 0) {
			throw new RuntimeException('Missing id when updating trans');
		}
		$sql = 'UPDATE trans SET';
		$conditions = self::getConditions($params);
		if(count($conditions) == 0) {
			return false;
		}
		$sql .= implode(',', $conditions).' WHERE id = :id';
		$sth = $this->dbh->prepare($sql);
		$sth->execute($params);
		return $sth->rowCount();
	}

	/**
	 * 删除数据
	 *
	 * @param array $params	数据
	 * @return int 影响行数
	 */
	public function delete($params) {
		$sth = $this->dbh->prepare('DELETE FROM trans WHERE id = :id');
		$sth->execute($params);
		return $sth->rowCount();
	}

	/**
	 * 查找数据
	 *
	 * @param array $params	搜索条件
	 * @return array 结果集
	 */
	public function find($params, $fetch_argument = PDO::FETCH_CLASS) {
		$sql = 'SELECT ';
		if(isset($params['select_expr'])) {
			$sql .= $params['select_expr'];
			unset($params['select_expr']);
		}
		else {
			$sql .= '*';
		}
		$sql .= ' FROM trans';
		$conditions = self::getConditions($params);
		if(count($conditions) > 0) {
			$sql .= ' WHERE'.implode(' AND', $conditions);
		}
		if(isset($params['group_expr'])) {
			$sql .= ' GROUP BY '.$params['group_expr'];
			unset($params['group_expr']);
		}
		if(isset($params['order_expr'])) {
			$sql .= ' ORDER BY '.$params['order_expr'];
			unset($params['order_expr']);
		}
		if(isset($params['limit_expr'])) {
			$sql .= ' LIMIT '.$params['limit_expr'];
			unset($params['limit_expr']);
		}
		$sth = $this->dbh->prepare($sql);
		$sth->execute($params);
		if($fetch_argument == PDO::FETCH_CLASS) {
			return $sth->fetchAll(PDO::FETCH_CLASS, 'TransModel', array($this->connId));
		}
		else {
			return $sth->fetchAll($fetch_argument);
		}
	}

	/**
	 * 根据条件获取一条数据
	 *
	 * @param array $params	搜索条件
	 * @return TransModel
	 */
	public function get($params) {
		$params['limit_expr'] = '1';
		$results = $this->find($params);
		return count($results) > 0 ? current($results) : false;
	}

	private static function getConditions(&$params) {
		$conditions = array();
		foreach(self::$fields as $field) {
			if(isset($params[':'.$field])) {
				$conditions[] = ' '.$field.' = :'.$field;
			}
		}
		if(isset($params[':fildate'])) {
			$conditions[] = ' filtime >= "'.$params[':fildate'].'000000" AND filtime <= "'.$params[':fildate'].'235959"';
			unset($params[':fildate']);
		}
		return $conditions;
	}
}

?>