<?php
class CWebApplication extends CApplication {
	public $controllerNamespace;
	private $_controllerPath;
	private $_viewPath;
	private $_systemViewPath;
	private $_controller;
	public $controllerMap=array();
	public function processRequest() {//开始执行请求
		//获取urlManager组件，解析请求，得到controller/action这种格式的string,
		//并且将隐藏参数与请求的参数一一对应，匹配起来，写入$_REQUEST中
		$route = $this->getUrlManager ()->parseUrl ($this->getRequest());
		$this->runController ( $route );
	}
	public function getRequest() {//获取request组件
		return $this->getComponent ( 'request' );
	}
	protected function registerCoreComponents() {//注册核心组件
		parent::registerCoreComponents ();
	}
	//执行contronller
	public function runController($route) {
		if (($ca = $this->createController ( $route )) !== null) {
			list ( $controller, $actionID ) = $ca;
			$oldController = $this->_controller;
			$this->_controller = $controller;
			$controller->init ();//钩子,在执行action方法前调用，子类去实现
			$controller->run ( $actionID );//开始转入controller类中action方法的执行
			$this->_controller = $oldController;
		}
	}
	//创建controller类实例,从controller/action这种格式的string中解析出$controller, $actionID 
	public function createController($route, $owner = null) {
		if ($owner === null)
			$owner = $this;
		if (($route = trim ( $route, '/' )) === '')
			$route = $owner->defaultController;//默认的controller

		$route .= '/';
		while ( ($pos = strpos ( $route, '/' )) !== false ) {
			$id = substr ( $route, 0, $pos );
			if (! preg_match ( '/^\w+$/', $id ))
				return null;
			$id = strtolower ( $id );
			$route = ( string ) substr ( $route, $pos + 1 );
			if (! isset ( $basePath )) 			// first segment
			{
				$basePath = $owner->getControllerPath ();
				$controllerID = '';
			} else {
				$controllerID .= '/';
			}
			$className = ucfirst ( $id ) . 'Controller';
			$classFile = $basePath . DIRECTORY_SEPARATOR . $className . '.php';

			if (is_file ( $classFile )) {
				if (! class_exists ( $className, false ))
					require ($classFile);
				if (class_exists ( $className, false ) && is_subclass_of ( $className, 'CController' )) {
					$id [0] = strtolower ( $id [0] );
					return array (
							new $className ( $controllerID . $id, $owner === $this ? null : $owner ),
							$this->parseActionParams ( $route )
					);
				}
				return null;
			}
			$controllerID .= $id;
			$basePath .= DIRECTORY_SEPARATOR . $id;
		}
	}
	protected function parseActionParams($pathInfo) {
		if (($pos = strpos ( $pathInfo, '/' )) !== false) {
			$manager = $this->getUrlManager ();//再次获取urlManager,在上面第一次调用中已经导入。
			$manager->parsePathInfo ( ( string ) substr ( $pathInfo, $pos + 1 ) );
			$actionID = substr ( $pathInfo, 0, $pos );
			return $manager->caseSensitive ? $actionID : strtolower ( $actionID );
		} else
			return $pathInfo;
	}
	public function getControllerPath() {
		if ($this->_controllerPath !== null)
			return $this->_controllerPath;
		else
			return $this->_controllerPath = $this->getBasePath () . DIRECTORY_SEPARATOR . 'controllers';
	}
	//两个钩子,子类去实现
	public function beforeControllerAction($controller, $action) {
		return true;
	}
	public function afterControllerAction($controller, $action) {
	}
	protected function init() {
		parent::init ();
	}
}
