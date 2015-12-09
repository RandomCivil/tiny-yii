<?php
abstract class CAction extends CComponent
{
	private $_id;
	private $_controller;
	public function __construct($controller,$id)
	{
		$this->_controller=$controller;
		$this->_id=$id;
	}
	public function getController()
	{
		return $this->_controller;
	}
	public function getId()
	{
		return $this->_id;
	}
	//运行带有请求参数的对象。 这个方法通过CController::runAction()内部调用
	public function runWithParams($params)
	{
		$method=new ReflectionMethod($this, 'run');
		if($method->getNumberOfParameters()>0)
			return $this->runWithParamsInternal($this, $method, $params);
		else
			return $this->run();
	}
	//执行一个带有命名参数的对象的方法
	protected function runWithParamsInternal($object, $method, $params)
	{
		$ps=array();
		foreach($method->getParameters() as $i=>$param)
		{
			$name=$param->getName();
			if(isset($params[$name]))
			{
				if($param->isArray())
					$ps[]=is_array($params[$name]) ? $params[$name] : array($params[$name]);
				elseif(!is_array($params[$name]))
				$ps[]=$params[$name];
				else
					return false;
			}
			elseif($param->isDefaultValueAvailable())
			$ps[]=$param->getDefaultValue();
			else
				return false;
		}
		$method->invokeArgs($object,$ps);//反射，执行
		return true;
	}
}
