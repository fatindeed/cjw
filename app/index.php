<?php

try {
	set_time_limit(0);
	error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
	set_error_handler('appErrorHandler', E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);

	ini_set('date.timezone', 'Asia/Shanghai');

	define('APP_PATH', __DIR__.DIRECTORY_SEPARATOR);
	define('DATA_DIR', '/var/cjw/database/');
	define('LOG_DIR', '/var/cjw/log/');
	define('TEMP_PATH', '/tmp/');

	if(!is_dir(DATA_DIR)) {
		mkdir(DATA_DIR, 0777, true);
	}
	if(!is_dir(LOG_DIR)) {
		mkdir(LOG_DIR, 0777, true);
	}
	$app = new Yaf_Application('/var/cjw/application.ini', getenv('APP_ENV'));
	$app->bootstrap() //call bootstrap methods defined in Bootstrap.php
		->run();
}
catch(Exception $e) {
	trigger_error('Error #'.$e->getCode().': '.$e->getMessage(), E_USER_ERROR);
}

function appErrorHandler($errno, $errstr) {
	$message = date('Y-m-d H:i:s')."\t".$errstr."\r\n";
	echo $message;
	file_put_contents(LOG_DIR.date('Ymd').'.txt', $message, FILE_APPEND);
}

?>