<?php
@set_time_limit(0);

/**
 * Javascript를 합치기 전에 로드한다
 * 
 * @author mini
 */
Class Main {
	private $_files = array();
	const EOL = "\r\n";
	
	public function __contructor() {
	}
	
	public function run() {
		$this->loadList("../../js/filelist.txt");
		$this->merge();
	}
	
	/**
	 * merge할 파일을 추가
	 *
	 * @param string|array $url ,로 여러개를 입력할 수 있음
	 */
	public function addFile($url) {
		if (empty($url)) return;
		
		if (!empty($url) && is_array($url)) {
			$url = $this->_parseURL($url);
		}
		
		$this->_files = array_merge($this->_files, $url);
	}
	
	/**
	 * "," 로 엮은 String Parse
	 *
	 * @param string $str
	 * @return array
	 */
	private function _parseURL($str) {
		$list = explode(",", $str);
		
		// trim 처리
		foreach($list as $key => $val) {
			$list[$key] = trim($val);
		}
		
		return $list;
	}
	
	/**
	 * 파일에 기록된 목록을 불러온다
	 * 
	 * @param string $url 목록 파일이 있는 경로
	 */
	public function loadList($url) {
		$list = file($url);

		if (!empty($list) && is_array($list)) {
			$path = dirname($url);
			
			foreach ($list as $key => $val) {
				$list[$key] = $path.DIRECTORY_SEPARATOR.$val;
			}
			
			$this->_files = array_merge($this->_files, $list);
		}
	}
		
	/**
	 * PreMerge
	 */
	public function merge() {
		if (!count($this->_files)) return;
		$output = "// PreMerge Javascript ".date("Y/m/d H:i:s") . self::EOL;
		
		foreach ($this->_files as $key => $val) {
			$val = trim($val);
			$output .= "// PreMerge #{$key} : {$val}" . self::EOL;
			$output .= $this->_loadFile($val);
		}
		
		echo $output;
	}
	
	/**
	 * 파일을 불러온다
	 *
	 * @param string $url
	 */
	private function _loadFile($url) {
		return file_get_contents($url);
	}
}

$main = new Main();
$main->run();
?>