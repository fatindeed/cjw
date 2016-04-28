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
		$config = Yaf_Application::app()->getConfig();
		Yaf_Registry::set('config', $config);
		Yaf_Loader::getInstance()->registerLocalNameSpace(array('Idiorm'));
		$dispatcher->autoRender(false);
		if(!is_dir($config->database->directory)) {
			mkdir($config->database->directory, 0777, true);
		}
		DataVendor::init();
	}

}

?>