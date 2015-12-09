<?php
class CUrlRule {
	public $urlSuffix;
	public $defaultParams = array ();
	public $route;
	public $routePattern;
	public $pattern;
	public $template;
	public $params = array ();
	//根据自定义规则构建匹配参数的正则表达式。
	public function __construct($route, $pattern) {
		if (is_array ( $route )) {
			foreach ( array (
					'urlSuffix',
					'caseSensitive',
					'defaultParams',
			) as $name ) {
				if (isset ( $route [$name] ))
					$this->$name = $route [$name];
			}
			if (isset ( $route ['pattern'] ))
				$pattern = $route ['pattern'];
			$route = $route [0];
		}
		$this->route = trim ( $route, '/' );
		
		$tr2 ['/'] = $tr ['/'] = '\\/';
		$tr ['.'] = '\\.';
		
		// if (strpos ( $route, '<' ) !== false && preg_match_all ( '/<(\w+)>/', $route, $matches2 )) {
		// 	print_r($route);
		// 	foreach ( $matches2 [1] as $name )
		// 		$this->references [$name] = "<$name>";
		// }
		
		$this->hasHostInfo = ! strncasecmp ( $pattern, 'http://', 7 ) || ! strncasecmp ( $pattern, 'https://', 8 );
		
		if (preg_match_all ( '/<(\w+):?(.*?)?>/', $pattern, $matches )) {
			$tokens = array_combine ( $matches [1], $matches [2] );
			foreach ( $tokens as $name => $value ) {
				if ($value === '')
					$value = '[^\/]+';
				$tr ["<$name>"] = "(?P<$name>$value)";
				//取出自定义规则中隐藏的参数,保存
				if (isset ( $this->references [$name] ))
					$tr2 ["<$name>"] = $tr ["<$name>"];
				else
					$this->params [$name] = $value;
			}
		}
		$p = rtrim ( $pattern, '*' );
		$this->append = $p !== $pattern;
		$p = trim ( $p, '/' );
		$this->template = preg_replace ( '/<(\w+):?.*?>/', '<$1>', $p );
		$this->pattern = '/^' . strtr ( $this->template, $tr ) . '\/';
		//合成匹配的正则表达式
		if ($this->append)
			$this->pattern .= '/u';
		else
			$this->pattern .= '$/u';
	}
	//根据正则表达式和请求，将隐藏参数与请求参数一一匹配，保存$_REQUEST
	public function parseUrl($manager, $pathInfo, $rawPathInfo) {
		if ($this->urlSuffix !== null) {
			$pathInfo = $manager->removeUrlSuffix ( $rawPathInfo, $this->urlSuffix );
		}
		$pathInfo .= '/';
		if (preg_match ( $this->pattern, $pathInfo, $matches )) {
			foreach ( $this->defaultParams as $name => $value ) {
				if (! isset ( $_GET [$name] ))
					$_REQUEST [$name] = $_GET [$name] = $value;
			}
			$tr = array ();
			foreach ( $matches as $key => $value ) {
				if (isset ( $this->references [$key] ))
					$tr [$this->references [$key]] = $value;
				elseif (isset ( $this->params [$key] ))
					$_REQUEST [$key] = $_GET [$key] = $value;
			}
			if ($pathInfo !== $matches [0]) //如果还有另外的请求参数
				$manager->parsePathInfo ( ltrim ( substr ( $pathInfo, strlen ( $matches [0] ) ), '/' ) );
			return $this->route;
		} else
			return false;
	}
}