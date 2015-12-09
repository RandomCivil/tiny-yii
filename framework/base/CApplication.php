<?php
abstract class CApplication extends CModule {
	private $_id;
	private $_basePath;
	abstract public function processRequest();
	public function __construct($config = null) {
		if (is_string ( $config ))
			$config = require ($config);
		Yii::setApplication ( $this );//保存整个app实例
		if (isset ( $config ['basePath'] )) {
			$this->setBasePath ( $config ['basePath'] );
			unset ( $config ['basePath'] );
		} else
			$this->setBasePath ( 'protected' );
		//设置别名,后面就可以用application表示basePath了
		Yii::setPathOfAlias ( 'application', $this->getBasePath () );
		//钩子，模块 预 初始化时执行，子类实现。不过这时，配置还没有写入框架
		$this->preinit ();
		$this->registerCoreComponents ();
		//父类实现
		$this->configure ( $config );
		//加载静态应用组件
		$this->preloadComponents ();
		//这才开始初始化模块
		$this->init ();
	}
	protected function registerCoreComponents() {
		$components = array (
				'request' => array (
						'class' => 'CHttpRequest'
				),
				'urlManager' => array (
						'class' => 'CUrlManager'
				)
		);

		$this->setComponents ( $components );//父类实现
	}
	public function run() {
		$this->processRequest ();
	}
	public function getId() {
		if ($this->_id !== null)
			return $this->_id;
		else
			return $this->_id = sprintf ( '%x', crc32 ( $this->getBasePath () . $this->name ) );
	}
	public function setId($id) {
		$this->_id = $id;
	}
	public function getBasePath() {
		return $this->_basePath;
	}
	public function setBasePath($path) {
		if (($this->_basePath = realpath ( $path )) === false || ! is_dir ( $this->_basePath ))
			return;
	}
	public function getDb() {
		return $this->getComponent ( 'db' );//父类实现
	}
	public function getUrlManager() {
		return $this->getComponent ( 'urlManager' );
	}
	public function getController() {
		return null;
	}
	public function getBaseUrl($absolute = false) {
		return $this->getRequest ()->getBaseUrl ( $absolute );
	}
}
