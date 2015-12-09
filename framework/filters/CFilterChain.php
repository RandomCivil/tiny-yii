<?php
//过滤器列表
class CFilterChain extends CList {
	public $controller;
	public $action;
	public $filterIndex = 0;
	public function __construct($controller, $action) {
		$this->controller = $controller;
		$this->action = $action;
	}
	//创建过滤器列表
	public static function create($controller, $action, $filters) {
		$chain = new CFilterChain ( $controller, $action );
		$actionID = $action->getId ();
		foreach ( $filters as $filter ) {
			if (is_string ( $filter )) 			// filterName [+|- action1 action2]
			{
				if (($pos = strpos ( $filter, '+' )) !== false || ($pos = strpos ( $filter, '-' )) !== false) {
					$matched = preg_match ( "/\b{$actionID}\b/i", substr ( $filter, $pos + 1 ) ) > 0;
					if (($filter [$pos] === '+') === $matched)
						$filter = CInlineFilter::create ( $controller, trim ( substr ( $filter, 0, $pos ) ) );
				} else
					$filter = CInlineFilter::create ( $controller, $filter );
			} elseif (is_array ( $filter )) 			// array('path.to.class [+|- action1, action2]','param1'=>'value1',...)
			{
				$filterClass = $filter [0];
				unset ( $filter [0] );
				//开始解析过滤器配置
				if (($pos = strpos ( $filterClass, '+' )) !== false || ($pos = strpos ( $filterClass, '-' )) !== false) {
					preg_match ( "/\b{$actionID}\b/i", substr ( $filterClass, $pos + 1 ), $a );
					$matched = preg_match ( "/\b{$actionID}\b/i", substr ( $filterClass, $pos + 1 ) ) > 0;
					//如果是filterName+action，创建一个过滤器,否则忽略
					if (($filterClass [$pos] === '+') === $matched) {
						//解析出过滤器的类名
						$filterClass = trim ( substr ( $filterClass, 0, $pos ) );
					} else
						continue;
				}
				$filter ['class'] = $filterClass;
				$filter = Yii::createComponent ( $filter );
			}
			
			if (is_object ( $filter )) {
				$filter->init ();
				$chain->add ( $filter );//list添加过滤器
			}
		}
		return $chain;
	}
	public function run() {
		if ($this->offsetExists ( $this->filterIndex )) {//过滤器列表个数不为0
			//取出过滤器实例
			$filter = $this->itemAt ( $this->filterIndex ++ );
			$filter->filter ( $this );
		} else
			$this->controller->runAction ( $this->action );
	}
}