<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

abstract class iiController {
	/**
	 * JSONP용 Callback Function Name 
	 * @var string
	 */
	const JSONP_CALLBACK_NAME = "callback";
	private $_isDebug = false;
	private $_isCallback = false;
	public $ii = null;
	
	public function __construct() {
		$this->ii = ii::singleton();
		
		// debug 모드일 경우 plain text로 결과가 나올 수 있도록 설정
		if (!empty($_REQUEST['debug'])) {
			$this->_isDebug = true;
		}
		
		if (!empty($_REQUEST[self::JSONP_CALLBACK_NAME])) {
			$this->_isCallback = true;
		}
	}
	
	/**
	 * Controller가 시작될 때 실행될 부분
	 * @example /api/testModule/test/p/1031/1111
	 * rewrite module -> /api/?testModule/test/p/1031/1111
	 * front controller -> /api/testModule/test.php?1031/1111
	 * 
	 * @param {Array} $params URI parameter 부분 순서대로 0 부터 들어온다
	 */
	abstract public function run($params);
	
	/**
	 * debug 상태로 임의로 만들어 준다
	 */
	public function setDebug() {
		$this->_isDebug = true;		
	}
	
	/**
	 * 결과가 error일 경우
	 * @param string $msg 에러 메세지
	 * @param array $data 넘길 에러 데이터
	 * @param integer $code 에러 Code
	 */
	public function error($msg, $data = null, $code = null) {
		$output = array(
			'error' => array(
				'msg' => $msg,
				'code' => $code,
				'data' => $data
			)
		);
		
		$this->_printData($output);		
	}
	
	/**
	 * 결과가 성공일 경우
	 * @param array $data 결과 데이터
	 */
	public function complete($data = null) {
		$output = array(
			'result' => $data
		);
		
		$this->_printData($output);
	}
	
	private function _printData($data) {
		$this->_start();
		
		// debug 모드일 경우 plain text로 출력
		if ($this->_isDebug) {
			$this->ii->print_r($data);
		} else {
			echo json_encode($data);
		}

		$this->_end();
	}
	
	private function _start() {
		// debug 모드일 경우 plain text로 결과가 나올 수 있도록 설정
		if ($this->_isDebug) {
			$this->ii->import("iiStage");
			$this->stage = new iiStage();
			$this->stage->start();
		} else {
			$this->ii->header();
		}
		
		if ($this->_isCallback) {
			echo "{$_REQUEST[self::JSONP_CALLBACK_NAME]}(";
		}
	}
	
	/**
	 * 종료 처리
	 */
	private function _end() {
		if ($this->_isDebug) {
			$this->stage->end();
		}
		
		if ($this->_isCallback) {
			echo ")";
		}
	}
}
?>