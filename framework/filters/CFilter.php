<?php
class CFilter extends CComponent {
	public function filter($filterChain) {
		//前置，后置方法
		if ($this->preFilter ( $filterChain )) {
			$filterChain->run ();
			$this->postFilter ( $filterChain );
		}
	}
	//钩子
	public function init() {
	}
	protected function preFilter($filterChain) {
		return true;
	}
	protected function postFilter($filterChain) {
	}
}