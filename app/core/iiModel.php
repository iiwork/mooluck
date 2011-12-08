<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

Class iiModel {
	private $ii = null;
	private $sql = null;
	
	public function __construct() {
		$this->ii = ii::getInstance();
		$config = $this->ii->config();
		$adapter = (empty($config['db']) || empty($config['db']['adapter'])) ? "iiMysql" : $config['db']['adapter'];
		$this->ii->import("db.{$adapter}");
		$this->sql = new $adapter();
	}
}
?>