<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");
@set_time_limit(0);

/**
 * DB 자동생성
 * 
 * @package util
 */
class iiInstallDB {
	private $_tables = array();
	private $_sql = null;
	private $_ii = null;

	public function __construct() {
		$this->_ii = ii::singleton();
		$this->_sql = $this->_ii->getSQL();
	}

	/**
	 * 테이블 추가
	 * @param unknown_type $name
	 * @param unknown_type $query
	 * @param unknown_type $isGroup
	 */
	public function addTable($name, $query, $isGroup = false) {
		array_push($this->_tables, array($name, $query, $isGroup));
	}
	
	/**
	 * 시작
	 */
	public function patch() {
		$output = array();
		
		// 목록 만들기
		$arr = array();
		foreach($this->_tables as $key => $val):
			// 여러 형식이 있는 table 이라면				
			if ($val[2]) {
				// 이름 가져오기
				preg_match("/CREATE TABLE (.+)_ /i", $val[1], $name);
				
				$tmp = array();
				$tmp = $this->_sql->gets("SHOW TABLES LIKE '{$name[1]}_%'");
				
				if (!empty($tmp))
				foreach ($tmp as $key2 => $val2) {
					foreach ($val2 as $val3) {
						$arr[$val3] = array($val3, preg_replace("/CREATE TABLE (.+)_ /i", "CREATE TABLE {$val3} ", $val[1]));
					}
				}
			}
			else {
				$arr[$key] = $val;
			}
		endforeach;
	
		// DB설치
		foreach($arr as $key => $val) {
			// 변수 초기화
			if (isset($tablename)) unset($tablename);
			if (isset($data_new)) unset($data_new);
			if (isset($data_old)) unset($data_old);
			$query = "";
			
			$output_each = array(
				'name' => $val[0]
			);
	
			// 테이블이 있을때 대조해서 고치기
			preg_match("/CREATE TABLE (.*) \(/im", $val[1], $tablename);
			if ($this->_sql->gets("SHOW TABLES LIKE '{$tablename[1]}'")) {
				// tmp 글자 치환
				$temp_query = preg_replace("/CREATE TABLE (.*) \(/im", "CREATE TABLE tmp_\\1 (", $val[1]);
				
				// 기존에 tmp 테이블이 있으면 지우기
				$mat = array();
				preg_match("/CREATE TABLE (tmp_[^ ]+) \(/i", $temp_query, $mat);
				if ($this->_sql->get("SHOW TABLES LIKE '{$mat[1]}'")) {
					$this->_sql->query("DROP TABLE {$mat[1]}");
				}
				
				// tmp 테이블 생성
				$result = $this->_sql->query($temp_query);
				
				if ($result) {	
					$data_old = array();
					$data_new = array();

					// 테이블 정보 수집
					if (!function_exists('handleData')) {
						function handleData($data, & $target) {
							$target[$data['Field']] = $data;
						}
					}
					$this->_sql->gets("SHOW COLUMNS FROM {$tablename[1]}", array('handleData', &$data_old));
					$this->_sql->gets("SHOW COLUMNS FROM tmp_{$tablename[1]}", array('handleData', &$data_new));
	
					// diff
					foreach ($data_new as $key2 => $val2) {
						if (empty($val2['Type'])) $val2['Type'] = '';
						if (empty($val2['Key'])) $val2['Key'] = '';
						if (empty($val2['Extra'])) $val2['Extra'] = '';
						if (empty($val2['Default'])) $val2['Default'] = '';
						if (empty($val2['Null'])) $val2['Null'] = '';							
					
						foreach($val2 as $key3 => $val3) {	
							// 새로 생긴 컬럼
							if(empty($data_old[$key2]) || (!empty($data_old[$key2][$key3]) && $data_old[$key2][$key3] != $val3)) {
								$query_action = $query_pri = $query_def = $query_null = "";
								$query_action = !empty($data_old[$key2]['Field']) ? "CHANGE {$key2}" : "ADD";
								$query_null = ($val2['Null'] == 'NO') ? "not null" : "";
								$query_def = ($val2['Default'] !== '') ? "default '".$val2['Default']."'" : "";
								$query_pri = ($val2['Key']=="PRI") ? "PRIMARY KEY" : "";
								$query .= ",{$query_action} {$key2} {$val2['Type']} {$query_null} {$query_def} {$query_pri} {$val2['Extra']}";
	
								if (empty($query_pri) && !empty($val2['Key']) && empty($data_old[$key2]['Key'])) $query .= ",ADD KEY {$key2}({$key2})";
								break;
							}
						}
					}
	
					// 없어져야할 컬럼 확인
					foreach ($data_old as $key2=>$val2) {
						if (empty($data_new[$key2])) $query .= ",DROP {$key2}";
					}
					
					if ($query) {
						$query = substr($query, 1);
						$query = "ALTER TABLE {$tablename[1]} {$query}";
					}
	
					$this->_sql->query("DROP TABLE tmp_{$tablename[1]}");
				}
			}
	
			// 쿼리 - 기존에 있던것 수정
			if ($query) {				
				$result2 = $this->_sql->query($query, true);
				if (!$result2) {
					$output_each['error'] = "테이블 ALTER중 에러 발생";
				} else {
					$output_each['result'] = "ALTER : {$query}";
				}
				
				$query = "";
			}
	
			// 쿼리 - 새로 추가
			else if (!$this->_sql->get("SHOW TABLES LIKE '{$tablename[1]}'")) {
				$result = $this->_sql->query($val[1], false);
				if (!$result) {
					$output_each['error'] = "테이블 추가 중에 에러가 발생 했습니다. {$query}";
				} else {
					$output_each['result'] = "테이블 생성 완료";
				}
			}
	
			// 이미 있음
			else {
				$output_each['result'] = "변경된 내용이 없습니다";
			}
			
			$output[] = $output_each;
		}
		
		return $output;
	}
}
?>