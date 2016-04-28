<?php

/**
 * Dbase 通用类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class Dbase {

	/**
	 * 是否已加载dbase扩展
	 *
	 * @ignore
	 */
	private static $ext_loaded;

	/**
	 * 读取dbase数据库
	 *
	 * @param string $filename	数据库文件路径
	 * @return array
	 */
	public static function load($filename) {
		if(!isset(self::$ext_loaded)) {
			self::$ext_loaded = extension_loaded('dbase');
		}
		if(self::$ext_loaded) {
			$results = array();
			$db = dbase_open($filename, 0);
			if(empty($db)) return false;
			$record_numbers = dbase_numrecords($db);
			for($i = 1; $i <= $record_numbers; $i++) {
				$results[] = dbase_get_record_with_names($db, $i);
			}
		}
		else {
			$results = self::get_records($filename);
		}
		return $results;
	}

	/**
	 * dbase扩展无法加载时的替代方法
	 *
	 * @param string $filename	数据库文件路径
	 * @return array
	 */
	private static function get_records($filename) {
		$fp = fopen($filename, 'r');
		$results = array();
		$fields = array();
		$buf = fread($fp, 32);
		$header = unpack('VRecordCount/vFirstRecord/vRecordLength', substr($buf, 4, 8));
		$goon = true;
		$unpackString = '';
		while($goon && !feof($fp)) { // read fields: 
			$buf = fread($fp, 32);
			if(substr($buf, 0, 1) == chr(13)) {
				$goon = false; // end of field list 
			}
			else { 
				$field = unpack('a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec', substr($buf, 0, 18));
				$unpackString .= "A$field[fieldlen]$field[fieldname]/";
				array_push($fields, $field);
			}
		} 
		fseek($fp, $header['FirstRecord']+1);// move back to the start of the first record (after the field definitions) 
			for($i = 1; $i <= $header['RecordCount']; $i++) { 
			$buf = fread($fp, $header['RecordLength']);
			$record = unpack($unpackString, $buf);
			$results[] = $record;
		} //raw record 
		fclose($fp);
		return $results;
	}

}

?>