<?php
abstract class CModule extends CComponent {
	public $preload = array ();
	public $behaviors = array ();
	private $_id;
	private $_parentModule;
	private $_basePath;
	private $_modulePath;
	private $_params;
	private $_modules = array ();
	private $_moduleConfig = array ();
	private $_components = array ();
	private $_componentConfig = array ();
	//重写是为了方便直接用 application.组件 这种方式直接获取组件
	public function __get($name) {
		if ($this->hasComponent ( $name ))
			return $this->getComponent ( $name );
		else
			return parent::__get ( $name );
	}
	public function __isset($name) {
		if ($this->hasComponent ( $name ))
			return $this->getComponent ( $name ) !== null;
		else
			return parent::__isset ( $name );
	}
	public function hasComponent($id) {
		return isset ( $this->_components [$id] ) || isset ( $this->_componentConfig [$id] );
	}
	public function setImport($aliases)
	{
		foreach($aliases as $alias)
			Yii::import($alias);
	}
	public function getComponent($id, $createIfNull = true) {
		if (isset ( $this->_components [$id] ))
			return $this->_components [$id];
		else if (isset ( $this->_componentConfig [$id] ) && $createIfNull) {
			$config = $this->_componentConfig [$id];
			$component = Yii::createComponent ( $config );//YiiBase,返回组件实例
			$component->init ();//钩子，调用子类重写的init方法
			//将组件写入数组保存，并返回
			return $this->_components [$id] = $component;
		}
	}
	public function setComponent($id, $component, $merge = true) {
		//组件写入数组保存
		if (isset ( $this->_componentConfig [$id] ) && $merge) {
			
			$this->_componentConfig [$id] = self::mergeArray ( $this->_componentConfig [$id], $component );
		} else {
			
			$this->_componentConfig [$id] = $component;
		}
	}
	public static function mergeArray($a, $b) {
		$args = func_get_args ();
		$res = array_shift ( $args );
		while ( ! empty ( $args ) ) {
			$next = array_shift ( $args );
			foreach ( $next as $k => $v ) {
				if (is_integer ( $k ))
					isset ( $res [$k] ) ? $res [] = $v : $res [$k] = $v;
				elseif (is_array ( $v ) && isset ( $res [$k] ) && is_array ( $res [$k] ))
					$res [$k] = self::mergeArray ( $res [$k], $v );
				else
					$res [$k] = $v;
			}
		}
		return $res;
	}
	public function setComponents($components, $merge = true) {
		foreach ( $components as $id => $component )
			$this->setComponent ( $id, $component, $merge );
	}
	//子类CApplication调用，用来为模块指定配置
	public function configure($config) {
		if (is_array ( $config )) {
			foreach ( $config as $key => $value )
				$this->$key = $value;
		}
	}
	protected function preloadComponents() {
		foreach ( $this->preload as $id )
			$this->getComponent ( $id );
	}
	//又是两个钩子
	protected function preinit() {
	}
	protected function init() {
	}
}
