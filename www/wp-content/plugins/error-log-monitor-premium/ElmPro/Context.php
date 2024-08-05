<?php
class ElmPro_Context {
	protected $parameters = array();

	public function snapshot() {
		return $this->parameters;
	}
}