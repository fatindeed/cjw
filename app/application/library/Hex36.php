<?php

/**
 * Math 通用类
 *
 * @package	cjw
 * @author	James Zhu
 * @version	1.0
 */
class Hex36 {

	/**
	 * @ignore
	 */
	private static $charArr = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

	/**
	 * 十进制数转换成36进制数
	 *
	 * @param int $number	要转换的十进制值
	 * @return string
	 */
	public static function to36($number) {
		$num = intval($number);
		if($num < 0) return false;
		$char = '';
		do {
			$key = $num % 36;
			$char = self::$charArr[$key].$char;
			$num = floor(($num - $key) / 36);
		}
		while($num > 0);  
		return $char;
	}

	/**
	 * 36进制数转换成十进制数
	 *
	 * @param string $filename	要转换的36进制值
	 * @return int
	 */
	public static function to10($str) {
		$sum = 0;
		$len = strlen($str);
		for($i = 0; $i < $len; $i++){
			$index = array_search($str[$i], self::$charArr);
			$sum += $index * pow(36, $len - $i - 1);
		}
		return $sum;  
	}

	/**
	 * 将传入参数分别转换为36进制并合并结果
	 *
	 * @param int $number $number $...	要转换的十进制值
	 * @return string
	 */
	public static function unpack() {
		$result = '';
		$args = func_get_args();
		foreach($args as $arg) {
			$result .= self::to36($arg);
		}
		return $result;
	}

}

?>