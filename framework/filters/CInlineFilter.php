<?php
class CInlineFilter extends CFilter
{
	public $name;
	public static function create($controller,$filterName)
	{
		if(method_exists($controller,'filter'.$filterName))
		{
			$filter=new CInlineFilter;
			$filter->name=$filterName;
			return $filter;
		}
	}
	public function filter($filterChain)
	{
		$method='filter'.$this->name;
		$filterChain->controller->$method($filterChain);
	}
}
