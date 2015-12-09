<?php
class CHttpRequest {
	private $_requestUri;
	private $_pathInfo;
	private $_scriptUrl;
	private $_hostInfo;
	private $_baseUrl;
	public function init() {
	}
	public function getUrl() {
		return $this->getRequestUri ();
	}
	public function getIsSecureConnection() {
		return isset ( $_SERVER ['HTTPS'] ) && ($_SERVER ['HTTPS'] == 'on' || $_SERVER ['HTTPS'] == 1) || isset ( $_SERVER ['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER ['HTTP_X_FORWARDED_PROTO'] == 'https';
	}
	public function getHostInfo($schema = '') {
		if ($this->_hostInfo === null) {
			if ($secure = $this->getIsSecureConnection ())
				$http = 'https';
			else
				$http = 'http';
			if (isset ( $_SERVER ['HTTP_HOST'] ))
				$this->_hostInfo = $http . '://' . $_SERVER ['HTTP_HOST'];
			else {
				$this->_hostInfo = $http . '://' . $_SERVER ['SERVER_NAME'];
				$port = $secure ? $this->getSecurePort () : $this->getPort ();
				if (($port !== 80 && ! $secure) || ($port !== 443 && $secure))
					$this->_hostInfo .= ':' . $port;
			}
		}
		if ($schema !== '') {
			$secure = $this->getIsSecureConnection ();
			if ($secure && $schema === 'https' || ! $secure && $schema === 'http')
				return $this->_hostInfo;
			
			$port = $schema === 'https' ? $this->getSecurePort () : $this->getPort ();
			if ($port !== 80 && $schema === 'http' || $port !== 443 && $schema === 'https')
				$port = ':' . $port;
			else
				$port = '';
			
			$pos = strpos ( $this->_hostInfo, ':' );
			return $schema . substr ( $this->_hostInfo, $pos, strcspn ( $this->_hostInfo, ':', $pos + 1 ) + 1 ) . $port;
		} else
			return $this->_hostInfo;
	}
	public function setHostInfo($value) {
		$this->_hostInfo = rtrim ( $value, '/' );
	}
	public function getBaseUrl($absolute = false) {
		if ($this->_baseUrl === null)
			$this->_baseUrl = rtrim ( dirname ( $this->getScriptUrl () ), '\\/' );
		return $absolute ? $this->getHostInfo () . $this->_baseUrl : $this->_baseUrl;
	}
	public function setBaseUrl($value) {
		$this->_baseUrl = $value;
	}
	public function getScriptUrl() {
		if ($this->_scriptUrl === null) {
			$scriptName = basename ( $_SERVER ['SCRIPT_FILENAME'] );
			if (basename ( $_SERVER ['SCRIPT_NAME'] ) === $scriptName)
				$this->_scriptUrl = $_SERVER ['SCRIPT_NAME'];
			elseif (basename ( $_SERVER ['PHP_SELF'] ) === $scriptName)
				$this->_scriptUrl = $_SERVER ['PHP_SELF'];
			elseif (isset ( $_SERVER ['ORIG_SCRIPT_NAME'] ) && basename ( $_SERVER ['ORIG_SCRIPT_NAME'] ) === $scriptName)
				$this->_scriptUrl = $_SERVER ['ORIG_SCRIPT_NAME'];
			elseif (($pos = strpos ( $_SERVER ['PHP_SELF'], '/' . $scriptName )) !== false)
				$this->_scriptUrl = substr ( $_SERVER ['SCRIPT_NAME'], 0, $pos ) . '/' . $scriptName;
			elseif (isset ( $_SERVER ['DOCUMENT_ROOT'] ) && strpos ( $_SERVER ['SCRIPT_FILENAME'], $_SERVER ['DOCUMENT_ROOT'] ) === 0)
				$this->_scriptUrl = str_replace ( '\\', '/', str_replace ( $_SERVER ['DOCUMENT_ROOT'], '', $_SERVER ['SCRIPT_FILENAME'] ) );
			else
				throw new CException ( Yii::t ( 'yii', 'CHttpRequest is unable to determine the entry script URL.' ) );
		}
		return $this->_scriptUrl;
	}
	public function setScriptUrl($value) {
		$this->_scriptUrl = '/' . trim ( $value, '/' );
	}
	public function getPathInfo() {
		if ($this->_pathInfo === null) {
			$pathInfo = $this->getRequestUri ();
			
			if (($pos = strpos ( $pathInfo, '?' )) !== false)
				$pathInfo = substr ( $pathInfo, 0, $pos );
			
			$pathInfo = $this->decodePathInfo ( $pathInfo );
			
			$scriptUrl = $this->getScriptUrl ();
			$baseUrl = $this->getBaseUrl ();
			if (strpos ( $pathInfo, $scriptUrl ) === 0)
				$pathInfo = substr ( $pathInfo, strlen ( $scriptUrl ) );
			elseif ($baseUrl === '' || strpos ( $pathInfo, $baseUrl ) === 0)
				$pathInfo = substr ( $pathInfo, strlen ( $baseUrl ) );
			elseif (strpos ( $_SERVER ['PHP_SELF'], $scriptUrl ) === 0)
				$pathInfo = substr ( $_SERVER ['PHP_SELF'], strlen ( $scriptUrl ) );
			else
				throw new CException ( Yii::t ( 'yii', 'CHttpRequest is unable to determine the path info of the request.' ) );
			
			if ($pathInfo === '/')
				$pathInfo = '';
			elseif ($pathInfo [0] === '/')
				$pathInfo = substr ( $pathInfo, 1 );
			
			if (($posEnd = strlen ( $pathInfo ) - 1) > 0 && $pathInfo [$posEnd] === '/')
				$pathInfo = substr ( $pathInfo, 0, $posEnd );
			
			$this->_pathInfo = $pathInfo;
		}
		return $this->_pathInfo;
	}
	protected function decodePathInfo($pathInfo) {
		$pathInfo = urldecode ( $pathInfo );
		
		// is it UTF-8?
		// http://w3.org/International/questions/qa-forms-utf-8.html
		if (preg_match ( '%^(?:
		   [\x09\x0A\x0D\x20-\x7E]            # ASCII
		 | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		 | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		 | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		 | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $pathInfo )) {
			return $pathInfo;
		} else {
			return utf8_encode ( $pathInfo );
		}
	}
	public function getRequestUri() {
		if ($this->_requestUri === null) {
			if (isset ( $_SERVER ['HTTP_X_REWRITE_URL'] )) // IIS
				$this->_requestUri = $_SERVER ['HTTP_X_REWRITE_URL'];
			elseif (isset ( $_SERVER ['REQUEST_URI'] )) {
				$this->_requestUri = $_SERVER ['REQUEST_URI'];
				if (! empty ( $_SERVER ['HTTP_HOST'] )) {
					if (strpos ( $this->_requestUri, $_SERVER ['HTTP_HOST'] ) !== false)
						$this->_requestUri = preg_replace ( '/^\w+:\/\/[^\/]+/', '', $this->_requestUri );
				} else
					$this->_requestUri = preg_replace ( '/^(http|https):\/\/[^\/]+/i', '', $this->_requestUri );
			} elseif (isset ( $_SERVER ['ORIG_PATH_INFO'] )) 			// IIS 5.0 CGI
			{
				$this->_requestUri = $_SERVER ['ORIG_PATH_INFO'];
				if (! empty ( $_SERVER ['QUERY_STRING'] ))
					$this->_requestUri .= '?' . $_SERVER ['QUERY_STRING'];
			} else
				throw new CException ( Yii::t ( 'yii', 'CHttpRequest is unable to determine the request URI.' ) );
		}
		
		return $this->_requestUri;
	}
	public function redirect($url, $terminate = true, $statusCode = 302) {
		if (strpos ( $url, '/' ) === 0 && strpos ( $url, '//' ) !== 0)
			$url = $this->getHostInfo () . $url;
		header ( 'Location: ' . $url, true, $statusCode );
		if ($terminate)
			Yii::app ()->end ();
	}
}
