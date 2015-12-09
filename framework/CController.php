<?php
class CController {
	protected $db;
	public $defaultAction = 'index';
	private $_id;
	private $_action;
	public function __construct($id, $module = null) {
		$this->_id = $id;
	}
	public function init() {
	}
	//过滤方法,子类重写
	public function filters() {
		return array ();
	}
	public function run($actionID) {
		//创建action实例
		if (($action = $this->createAction ( $actionID )) !== null) {
			$parent = Yii::app ();
			if ($parent->beforeControllerAction ( $this, $action )) {
				$this->runActionWithFilters ( $action, $this->filters () );
				$parent->afterControllerAction ( $this, $action );
			}
		}
	}
	public function refresh($terminate = true, $anchor = '') {
		$this->redirect ( Yii::app ()->getRequest ()->getUrl () . $anchor, $terminate );
	}
	public function redirect($url, $terminate = true, $statusCode = 302) {
		Yii::app ()->getRequest ()->redirect ( $url, $terminate, $statusCode );
	}
	//如果controller里面有filter
	public function runActionWithFilters($action, $filters) {
		if (empty ( $filters ))
			$this->runAction ( $action );
		else {
			$priorAction = $this->_action;
			$this->_action = $action;
			CFilterChain::create ( $this, $action, $filters )->run ();
			$this->_action = $priorAction;
		}
	}
	public function runAction($action) {
		$priorAction = $this->_action;
		$this->_action = $action;
		if ($this->beforeAction ( $action )) {
			if ($action->runWithParams ( $this->getActionParams () ) === false)
				$this->invalidActionParams ( $action );
			else
				$this->afterAction ( $action );
		}
		$this->_action = $priorAction;
	}
	//渲染视图
	public function render($view, $data = array()) {
		if (isset ( $data ))
			extract ( $data );
		include VIEWS_DIR . "/" . $this->_id . "/" . $view . ".php";
	}
	public function renderFile($file, $data = array()) {
		if (isset ( $data ))
			extract ( $data );
		include VIEWS_DIR . "/" . $file;
	}
	//跳转到另一个controller/action,不过浏览器的地址没有变
	public function forward($route) {
		if (strpos ( $route, '/' ) === false)
			$this->run ( $route );
		else {
			//不在同一个controller里面，重新创建
			Yii::app ()->runController ( $route );
		}
	}
	public function getActionParams() {
		return $_GET;
	}
	public function createAction($actionID) {
		if ($actionID === '')
			$actionID = $this->defaultAction;
		if (method_exists ( $this, 'action' . $actionID ) && strcasecmp ( $actionID, 's' ))
			return new CInlineAction ( $this, $actionID );
	}
	public function getAction() {
		return $this->_action;
	}
	public function setAction($value) {
		$this->_action = $value;
	}
	public function getId() {
		return $this->_id;
	}
	//两个钩子
	protected function beforeAction($action) {
		return true;
	}
	protected function afterAction($action) {
	}
}
