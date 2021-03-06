<?php

class Bootstrap extends Yaf_Bootstrap_Abstract {

	protected function _initApp(Yaf_Dispatcher $dispatcher) {
		$request = $dispatcher->getRequest();
		if($GLOBALS['argc'] >= 2) {
			$request->setActionName($GLOBALS['argv'][1]);
		}
		if($GLOBALS['argc'] >= 3) {
			$request->setParam('args', array_slice($GLOBALS['argv'], 2));
		}
		$path = '/var/cjw/'.Yaf_Application::app()->environ();
		define('DATA_DIR', $path.'/database/');
		define('LOG_DIR', $path.'/log/');
		if(!is_dir(DATA_DIR)) {
			mkdir(DATA_DIR, 0777, true);
		}
		if(!is_dir(LOG_DIR)) {
			mkdir(LOG_DIR, 0777, true);
		}
		$config = Yaf_Application::app()->getConfig();
		Yaf_Registry::set('config', $config);
		$dispatcher->autoRender(false);
	}

	protected function _initLocker(Yaf_Dispatcher $dispatcher) {
		$appLocker = TEMP_PATH.'locker';
		if(file_exists($appLocker) && time() - file_get_contents($appLocker) < 1800) {
			throw new RuntimeException('程序运行中，请稍后重试');
		}
		file_put_contents($appLocker, time());
		register_shutdown_function(function() use($appLocker) {
			@unlink($appLocker);
		});
	}

}

?>