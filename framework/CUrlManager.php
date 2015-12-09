<?php
class CUrlManager {
	const GET_FORMAT = 'get';
	public $rules = array ();
	public $urlSuffix = '';
	public $caseSensitive = true;
	public $urlRuleClass = 'CUrlRule';
	private $_urlFormat = self::GET_FORMAT;
	private $_rules = array ();
	private $_baseUrl;
	protected function processRules() {
		//遍历自定义的请求匹配规则
		foreach ( $this->rules as $pattern => $route ) {
			//对每一个规则创建CUrlRule实例
			$this->_rules [] = $this->createUrlRule ( $route, $pattern );
		}
	}
	protected function createUrlRule($route, $pattern) {
		if (is_array ( $route ) && isset ( $route ['class'] ))
			return $route;
		else {
			//import第二个参数表示是否立即包含类文件。 如果为flase，则类文件仅在被使用时包含。 这个参数仅当使用一个类的路径 别名 时才会用到
			$urlRuleClass = Yii::import ( $this->urlRuleClass, true );
			//创建CUrlRule实例
			return new $urlRuleClass ( $route, $pattern );
		}
	}
	//类似于__construct()
	public function init() {
		$this->processRules ();
	}
	public function parseUrl($request) {
		//获取请求
		$rawPathInfo = $request->getPathInfo ();
		$pathInfo = $this->removeUrlSuffix ( $rawPathInfo, $this->urlSuffix );
		foreach ( $this->_rules as $i => $rule ) {
			if (($r = $rule->parseUrl ( $this, $pathInfo, $rawPathInfo )) !== false) {
				return $r;
			}
		}
		return $pathInfo;
	}
	//解析请求，将请求参数写入$_REQUEST
	public function parsePathInfo($pathInfo) {
		if ($pathInfo === '')
			return;
		$segs = explode ( '/', $pathInfo . '/' );
		$n = count ( $segs );
		for($i = 0; $i < $n - 1; $i += 2) {
			$key = $segs [$i];
			if ($key === '')
				continue;
			$value = $segs [$i + 1];
			if (($pos = strpos ( $key, '[' )) !== false && ($m = preg_match_all ( '/\[(.*?)\]/', $key, $matches )) > 0) {
				$name = substr ( $key, 0, $pos );
				for($j = $m - 1; $j >= 0; -- $j) {
					if ($matches [1] [$j] === '')
						$value = array (
							$value 
							);
					else
						$value = array (
							$matches [1] [$j] => $value 
							);
				}
				if (isset ( $_GET [$name] ) && is_array ( $_GET [$name] ))
					$value = CMap::mergeArray ( $_GET [$name], $value );
				$_REQUEST [$name] = $_GET [$name] = $value;
			} else {				
				$_REQUEST [$key] = $_GET [$key] = $value;
			}
		}
	}
	//去除请求后缀，如video/broadcast.html=>video/broadcast 
	public function removeUrlSuffix($pathInfo, $urlSuffix) {
		if ($urlSuffix !== '' && substr ( $pathInfo, - strlen ( $urlSuffix ) ) === $urlSuffix)
			return substr ( $pathInfo, 0, - strlen ( $urlSuffix ) );
		else
			return $pathInfo;
	}
}