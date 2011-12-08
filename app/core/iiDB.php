<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

/**
 * DB Interface
 * @author mini <i@iiwork.com>
 * @abstract
 */
abstract class iiDB {
	const TYPE_INSERT = 'insert';
	const TYPE_UPDATE = 'update';
	
	static private $instance = array(); 

	/**
	 * 접속시 자동 쿼리 지정
	 * @var String $init_query sql query
	 */
	public $init_query;

	/**
	 * DB Instance no
	 * 생성자에서 지정한 DB Instance no
	 * @var int $no
	 */
	protected $no;		
	
	/**
	 * DB 접속정보
	 * @var String $host
	 */
	protected $host;
	protected $id;
	protected $pass;
	protected $db;
	protected $sql;
	
	/**
	 * @ignore
	 * @param int DB Instance no
	 */
	public function __construct($no = 0) {
		$this->no = $no;
	}

	/**
	 * 연결이 되어 있는지 검사한다
	 * @param bool $isBreak exception 호출 여부
	 * @return bool 연결이 되어 있다면 true를 리턴
	 * @throws Exception
	 */
	public function isConnected($isBreak = false) {
		if (!isset($this->sql)) {
			if ($isBreak) {
				throw new Exception('DB 연결이 필요합니다'); 
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	
	/**
	 * DB 접속
	 * @param string $host
	 * @param string $id
	 * @param string $pass
	 * @param string $db
	 */
	abstract public function connect($host = "localhost", $id, $pass, $db = '');	
	
	/**
	 * DB 선택
	 * @param string $name
	 */
	abstract public function select_db($name);
	
	/**
	 * DB 연결 끊기
	 */
	abstract public function close();
	
	/**
	 * SQL Query 실행
	 * @param string $query
	 * @param bool $isIgnoreError
	 * @return link SQL Link
	 */
	abstract public function query($query, $isIgnoreError = false);
	
	/**
	 * Query 결과 수를 준다. 실패시 False 리턴
	 * @param link $sql
	 * @return int
	 */
	abstract public function count($sql);
	
	/**
	 * SQL Fetch
	 * @param link $sql
	 * @return array column이 key로 된 배열을 리턴한다
	 */
	abstract public function fetch($sql);
	
	/**
	 * Column들을 가져온다
	 * @param string $table
	 * @return array
	 */
	abstract public function getColumns($table);

	/**
	 * escape string
	 * @param string $str
	 */
	abstract public function escape($str);
	
	/**
	 * 마지막 insert된 primary key 값을 가져 옵니다1
	 * @return string
	 */
	abstract public function getInsertId();
	
	/**
	 * 쿼리문을 통해 자료를 갖고 온다
	 * <code>
	 * function test($data, $args) {
	 * 	foreach ($args as $key => $val) {
	 * 		echo "{$key}: {$val}";
	 * 	}
	 * }
	 * $sql->gets('SELECT * FROM test', array('test', array('name'->'john'));
	 *
	 * results:
	 * name: john
	 * </code> 
	 * 
	 * @param string $query
	 * @param function $func null인 경우 배열로 리턴된다
	 * @return array 자료가 없을 경우 false를 리턴한다
	 * @desc $func 에 array를 넣는다면 첫번째가 함수명, 두번째가 두번째 인자로 넘어온다
	 * @desc 미리 fetch해서 넣는 과정이 있으므로 퍼포먼스 문제가 있을 수 있음.
	 */
	public function gets($query, $func = null) {
		$q = $this->query($query);
		$output = array();
		$data = array();
		
		// 결과가 있으면
		if ($this->count($q)) {
			while($data = $this->fetch($q)) {
				// listener 호출
				if ($func != null) {
					if (!is_array($func)) {
						$func($data);
					} else {
						$func[0]($data, $func[1]);
					}
				} else {
					if (isset($data['no'])) {
						$output[$data['no']] = $data;
					} else {
						$output[] = $data;
					}
				}
			}
			
			return ($func == null) ? $output : false;
		} else {
			return false;
		}
	}
	
	/**
	 * 한개의 자료만 있다고 생각되는 경우 단순화하여 갖고 온다
	 * @param string $query
	 * @return mixed column이 한개일 경우 값만, 아닌 경우 key를 column 으로 가져온다
	 * @desc column이 한개일 경우 문제가 생길 수 있음
	 */
	public function get($query) {
		$data = $this->gets($query);
		
		if (empty($data) || count($data) > 1) return false;
		$val = current($data);
		
		return (count($val) > 1) ? $val : end($val);
	}

	/**
	 * 쿼리문 생성
	 * <code>
	 * echo $sql->makeQuery(array("test" => "value"), $sql->TYPE_INSERT);
	 * 
	 * results:
	 * ('test') VALUES ('value')
	 * </code>
	 * @param array $target key는 colunm name, value는 값 조합의 배열
	 * @param string $type iiDB::[TYPE_INSERT|TYPE_UPDATE]
	 * @return string 생성된 쿼리문
	 * @throws Exception
	 */
	public function makeQuery($target, $type) {
		if (!is_array($target) || empty($target)) throw new Exception('변수가 올바르지 않습니다');
		
		// escape
		$this->escape($target);

		$values = '';
		foreach ($target as $key => $val) {
			if ($val === null) {
				unset($target[$key]);
				continue;
			}
			
			switch ($type) {
				case self::TYPE_INSERT:
					$values .= (is_string($val) === true) ? ",'{$val}'" : ",{$val}";
					break;

				case self::TYPE_UPDATE:
					$values .= (is_string($val) === true) ? ",{$key}='{$val}'" : ",{$key}={$val}";
					break;
			}
		};

		switch ($type) {
			case self::TYPE_INSERT:
				return "(".implode(",", array_keys($target)).") VALUES (".substr($values, 1).")";
				break;

			case self::TYPE_UPDATE:
				return substr($values, 1);
				break;
		}
	}	
}
?>