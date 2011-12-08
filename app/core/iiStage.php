<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

/**
 * 화면 출력
 * Stage는 API 출력이 아닌 view 출력일 때 사용 합니다. (화면 캐시 등에 활용 가능)
 * 
 * @author mini
 */
class iiStage {
	private $_isStart = false; // 시작 여부
	private $_isObstart = true; // Obstart 사용 여부
	
	public function __construct() {
		$this->ii = ii::singleton();
	}

	/**
	 * Stage 시작
	 * 
	 * @param bool $isObstart Obstart 사용 여부
	 * @param string $content_type Content-Type
	 */
	public function start($isObstart = true, $content_type = "text/html") {
		if ($this->_isStart) {
			return false;
		}
		
		$this->_isObstart = $isObstart;
		$head = "<meta http-equiv=\"Content-Type\" content=\"{$content_type}; charset=utf-8\" />\n";
	
		// ob_start 적용
		if ($this->_isObstart) {
			ob_start();
		}
		
		// 출력
		echo 
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', "\n",
			'<html xmlns="http://www.w3.org/1999/xhtml">', "\n",
			"<head>\n",
			$head,
			"</head>\n",
			"<body>\n";

		$this->_isStart = true;
	}
	
	/**
	 * Stage 종료
	 */
	public function end() {
		if ($this->_isStart) {
			echo "\n</body>\n</html>";
		}
		
		// DB 커넥션이 열려 있으면 닫기
		if (class_exists('iiDB')) {
			$sql = $this->ii->getSQL();
			$sql->close();
		}

		// ob_end 적용
		if ($this->_isObstart) {
			@ob_end_flush(); // cache 설정시 오류날 수 있음
		}
	}
}
?>