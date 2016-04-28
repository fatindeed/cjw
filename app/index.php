<?php

try {
	set_time_limit(0);
	error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
	ini_set('date.timezone', 'Asia/Shanghai');

	define('APP_PATH', __DIR__.DIRECTORY_SEPARATOR);
	define('APP_LOCKER', APP_PATH.'locker.txt');
	if(file_exists(APP_LOCKER) && time() - file_get_contents(APP_LOCKER) < 3600) {
		exit('App can only run once in an hour.');
	}
	file_put_contents(APP_LOCKER, time());
	if(in_array(gethostname(), array('vgfcx', 'James-HP'))) {
		$env = 'dev';
	}
	else {
		$env = 'prod';
	}
	$app = new Yaf_Application(APP_PATH.'conf/application.ini', $env);
	$app->bootstrap() //call bootstrap methods defined in Bootstrap.php
		->run();
}
catch(Exception $e) {
	echo $e->getMessage()."\r\n";
}
@unlink(APP_LOCKER);

?>