<?php
class CInlineAction extends CAction
{
	//执行该动作
	public function run()
	{
		$method='action'.$this->getId();
		$this->getController()->$method();
	}
	//执行带提供的请求的参数的动作
	public function runWithParams($params)
	{
		$methodName='action'.$this->getId();//拼接action方法
		$controller=$this->getController();
		$method=new ReflectionMethod($controller, $methodName);//反射
		if($method->getNumberOfParameters()>0)//方法参数个数>0
			return $this->runWithParamsInternal($controller, $method, $params);
		else
			return $controller->$methodName();
	}
}
