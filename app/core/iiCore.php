<?php
define("II_VERSION", "0.0.1");
@header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

/**
 * ii Framework Core
 * 
 * @author mini <i@iiwork.com>
 * @copyright Copyright (c) 2010, iiwork.com
 * @desc framework 사용전 반드시 include 해야할 파일 입니다
 * @desc singleton
 */
class ii {
	/**
	 * header
	 * @var string
	 */
	const HEADER_JSON = "json";
	const HEADER_XML = "xml";
	
	/**
	 * import할 때 directory를 구분하는 문자
	 * @var string
	 */
	const IMPORT_SEPERATOR = "___";
	
	/**
	 * debug mode
	 * @var bool
	 */
	public $debug = true;
	
	/**
	 * ii instance
	 * @var ii
	 */
	static private $_instance;
	
	/**
	 * import시에 중복 방지를 위한 멤버 변수
	 * getInstace를 이용할 시에 내용으로 instance가 담긴다
	 * @var array
	 * @see ii::import
	 */
	private $_import = array();
	
	/**
	 * DB Instance
	 * @var iiDB
	 */
	private $_sql = null;

	/**
	 * framework 설정 값
	 * @var unknown_type
	 */
	public $ip = "";
	public $referer = "";
	public $lang = "";
	public $charset = "UTF-8";
	public $http = "";
	public $filename = "";
	public $date = "";
	public $time = "";
	public $config = array();
	public $dir = ""; // framework root
	public $dir_self = ""; // 현재 이 Class를 사용하고 있는 최상위 주소
	public $dir_config = ""; // Service > Config root

	final private function __clone() {}
	
	/**
	 * Singleton instance 가져오기
	 * @return ii
	 */
	static public function & singleton() {
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c();
		}
		
		return self::$_instance;
	}
	
	/**
	 * 생성자
	 */
	final private function __construct() {
		// 버젼 검사
		if (version_compare(PHP_VERSION, '5.2.0', '<')) {
			throw new Exception('php 5.2.0  이상이 설치되어 있어야 합니다.');
		}

		// 디버깅 모드
		if ($this->debug) {
			error_reporting(E_ALL);
			ini_set('display_errors', false);
			ini_set('ignore_repeated_errors', true); 
			ini_set('ignore_repeated_source', true); 
			ini_set('html_errors', true);
		}
		
		// error handler 등록
		set_exception_handler(array($this, '_handleException'));
		set_error_handler(array($this, '_handleError'), error_reporting());
		register_shutdown_function(array($this, '_handleShutdown'));

		// 값 설정
		$this->ip = (empty($_SERVER['REMOTE_ADDR']) || preg_match("/[^0-9.]/", $_SERVER['REMOTE_ADDR'])) ? "unknown" : $_SERVER['REMOTE_ADDR'];		
		$this->referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
		$this->lang = !empty($_SERVER['HTTP_ACCESS_LANGUAGE']) ? $_SERVER['HTTP_ACCESS_LANGUAGE'] : "ko"; 
		$this->http = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$this->filename = basename($_SERVER['PHP_SELF']);
		
		// 경로 설정(directory_seperator를 무시하고 slash로 한다)
		$this->dir = str_replace("\\", "/", dirname(__FILE__)); // ii core
		define("II_PATH", $this->dir);
		$this->dir_self = str_replace("\\", "/", dirname($_SERVER['SCRIPT_FILENAME']));
		$this->dir_config = $this->dir_self . "../config"; // config app
	
		// 시간 설정
		if (function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Seoul');
		$this->time = time();
		$this->date = date('Y-m-d H:i:s', $this->time);
		
		// php 환경설정
		ini_set('arg_separator.output', '&amp;');
		ini_set('pcre.backtrack_limit', '200000');
		ini_set("auto_detect_line_endings", 1);
	}

	/**
	 * workaround
	 * @ignore
	 */
	private function ii() {
		if(version_compare(PHP_VERSION, "5.0.0", "<")){
			$this->__construct();
			register_shutdown_function(array($this, "__destruct"));          
		}
	}
	
	/**
	 * Class Loader
	 * ex) import("test, test2, db.test");
	 * 
	 * @param string $name 콤마로 여러개를 부를 수 있다
	 * @throws Exception
	 */
	public function import($name) {
		$names = array();
		if (preg_match("/[^a-z0-9_\.\/, ]/i", $name)) throw new Exception("class name에 올바르지 않은 문자가 있습니다 name:{$name}");
		
		if (strpos($name, ",") !== false) {			
			$names = explode(",", trim($name));
		} else {
			$names[] = $name;
		}
		
		foreach ($names as $val) {
			$val = $this->_getImportName($val);
			$dir = preg_match("/ii.+/", $val) ? $this->dir . "/" : "";
			
			// 기존에 import된 것이 없을 경우 import 한다.
			if (empty($this->_import[$val])) {
				require $dir . str_replace(self::IMPORT_SEPERATOR, "/", $val) . ".php";
				$this->_import[$val] = true;
			}
		}
	}
	
	/**
	 * import할 때 seperator를 치환한 이름을 반환한다
	 * @return string
	 */
	private function _getImportName($name) {
		return str_replace(array(".", "/", "\\"), self::IMPORT_SEPERATOR, trim($name)); // directory_seperator 변환하기			
	}
	
	/**
	 * Controller를 불러오고 실행한다
	 * @param string $name class 이름
	 */
	public function load($name) {
		$this->import($name);
		var_dump($name);
	}
	
	/**
	 * SQL Instance를 반환
	 * 초기화 해야 한다면 접속까지 함
	 * 
	 * @depends iiDB
	 * @depends db.iiMysql
	 * @desc 여러개의 instance가 생기면 안되기 때문에 ii에서 관리 한다.
	 * singleton방식이나 DIContainer방식이 PHP 복잡도를 증가 시키기 때문에 한 결정
	 */
	public function getSQL() {
		if ($this->_sql === null) {
			$this->import("db.iiMysql");
			$this->_sql = new iiMysql();
			$config = $this->config();			
			$this->_sql->connect($config['db']['hostname'], $config['db']['userid'], $config['db']['userpass'], $config['db']['dbname']);
		}
		
		return $this->_sql;
	}
	
	/**
	 * Config를 부른다
	 * Service/config 디렉토리의 config를 부릅니다.
	 * 
	 * @param string $name
	 * @param bool $isForceLoad 파일이 로드되어 있어도 다시 한번 부른다
	 * @return array
	 */
	public function config($name = "default", $isForceLoad = false) {
		if ($isForceLoad || empty($this->config[$name])) {						
			$this->config[$name] = parse_ini_file("{$this->dir_config}/{$name}.ini.php", true);
		}
		
		return $this->config[$name];
	}
	
	/**
	 * debugging message
	 * @param {...} 들어온 param을 모두 브라우져에 맞게 출력
	 */
	public function debug() {
		$output = "<div style='position:absolute;z-index:9999;border:3px solid red;padding:10px;line-height:1.5;font:11px verdana;' ondblclick='this.parentNode.removeChild(this);'><pre>";
		$message = print_r(func_get_args(), true);
		$message = htmlspecialchars($message);
		$output .= $message;
		$output .= "</pre></div>";
		echo $output;
	}
	
	public function print_r() {
		$output = "<pre>";
		$output .= htmlspecialchars(print_r(func_get_args(), true));
		$output .= "</pre>";
		echo $output;
	}
	
	/**
	 * Error Handler
	 * @ignore
	 * @param $msg
	 * @param $code
	 * @param $file
	 * @param $line
	 * @param $trace
	 */
	public function _error($msg, $code, $file, $line, $trace = null) {
		$this->import("iiStage");
		$stage = new iiStage();
		$stage->start();
		
		echo "<div style='position:absolute; top:0; left:0; background-color:#fff; padding:15px; border:3px solid #333;' ondblclick='this.style.display = \"none\";'>";
		echo "<hr /><span style='color:red;font-weight:bold;'>Error(!)</span> file <b>{$file}</b> lines <b>{$line}</b><br /><pre>{$msg}</pre>";
		if ($trace) echo "\n<pre>{$trace}</pre>";
		echo "</div>";
		
		$stage->end();
	}
	
	/**
	 * Error Hanlder
	 * @ignore
	 * @param Exception $e
	 */
	public function _handleException(Exception $e) {
		$this->_error($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
	}
	
	/**
	 * Error Handler
	 * @ignore
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @throws ErrorException
	 */
	public function _handleError($errno, $errstr, $errfile, $errline) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		return true;
	}
	
	/**
	 * Shutdown Handler
	 * @ignore
	 */
	public function _handleShutdown() {
		$isError = false;
		if ($error = error_get_last()) {
			switch($error['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$isError = true;
					break;
			}
		}

		// 세션 write 닫기
		if (class_exists('iiSession', false)) {
			@session_write_close();
		}		
		
		if ($isError) { // 에러일 때
			$this->_error($error['message'], $error['type'], $error['file'], $error['line']);
		} else { // 정상일 때
		
		}
	}

	/**
	 * http header 설정
	 * @param string $header 헤더 종류 선택 $this->[HEADER_JSON|HEADER_XML]
	 */
	public function header($header = null) {
		switch ($header) {
			case self::HEADER_XML:
				header("Content-type: text/xml; charset=\"{$this->charset}\"");
				header("Cache-Control: no-cache, must-revalidate"); 
				header("Pragma: no-cache");
				break;
				
			case self::HEADER_JSON:
			default:
				//TODO json으로 header를 설정하면 IE에서 text로 읽히는게 아니라 받아진다
//				header("Content-type: application/json; charset={$this->charset}"); 
				header("Content-type: text/javascript; charset={$this->charset}"); 
				header("Last-Modified: ".gmdate("D, d M Y H:i:s") . " GMT"); 
				header("Cache-Control: no-store, no-cache, must-revalidate"); 
				header("Cache-Control: post-check=0, pre-check=0", false); 
				header("Pragma: no-cache");
				break;
		}
	}
}
?>