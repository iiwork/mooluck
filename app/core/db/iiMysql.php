<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");
ii::singleton()->import("iiDB");

/**
 * MySQL adapter 
 * @author mini
 * @class iiMysql
 */
class iiMysql extends iiDB {
	public $init_query;
	
	/**
	 * DB 접속
	 * @see core/iiDB::connect()
	 * @throws Exception
	 */
	public function connect($host = "localhost", $id, $pass, $db = '') {
		$this->host = $host;
		$this->id = $id;
		$this->pass = $pass;
		$this->db = $db;
		
		$this->sql = mysql_connect($host, $id, $pass, $this->no != 0 ? true : false);
		if (!$this->sql) {
			throw new Exception('DB 연결에 실패했습니다 '.mysql_error());
		}
		
		// 초기 쿼리
		if ($this->init_query) {
			$this->query($this->init_query);
		}
		
		// db 선택
		$this->select_db($db);
	}
	
	/**
	 * DB 선택
	 * @see core/iiDB::select_db()
	 * @throws Exception
	 */
	public function select_db($name) {
		if (!mysql_select_db($name, $this->sql)) {
			throw new Exception('DB 선택에 실패했습니다 '.mysql_error());
		}
	}
	
	/**
	 * DB 연결끊기
	 * @see core/iiDB::close()
	 */
	public function close() {
		if (!empty($this->sql)) { 
			if (mysql_close($this->sql)) {
				unset($this->sql);
			}
		}
	}
	
	/**
	 * 쿼리 실행
	 * @see core/iiDB::query()
	 * @throws Exception
	 */
	public function query($query, $isIgnoreError = false) {
		$query = trim($query);		
		
		if ($isIgnoreError) {
			$q = @mysql_query($query, $this->sql);
		} else {
			$q = mysql_query($query, $this->sql);	
		}		
		
		if ($q === false) {
			$errno = mysql_errno();
			$msg = mysql_error();
			
			// 언어 변경
			$msg = iconv('EUC-KR', 'UTF-8//IGNORE', $msg);

			// 쿼리가 여러줄이면 한줄로 바꿈
			$query = str_replace(array("\r\n", "\n"), array("\n", " "), $query);
			
			// 테이블 이름 뽑기
			$mat = array();
			$result = false;
			if (strpos($msg, '.MY') !== false) preg_match("/'(.+)\.MY'/i", $msg, $mat);
			
			// 테이블 복구
			switch ($errno) {
				case 145:
					$result = $this->query("REPAIR TABLE {$mat[1]}");
					break;
			}

			if ($result) {
				$this->query($query);
			} else if (!$isIgnoreError) {
				throw new Exception($msg."\n\nquery: {$query}");
			} else {
				return $q;
			}
		} else {
			return $q;
		}
	}
	
	/**
	 * 결과 갯수 구하기
	 * @see core/iiDB::count()
	 */
	public function count($sql) {
		return mysql_num_rows($sql);
	}
	
	/**
	 * Fetch
	 * @see core/iiDB::fetch()
	 */
	public function fetch($sql) {
		return mysql_fetch_assoc($sql);
	}
	
	/**
	 * Columns 목록 가져오기
	 * @see core/iiDB::getColumns()
	 */
	public function getColumns($table) {
		$data = array();
		$output = array();
		$data = $this->gets("SHOW COLUMNS FROM {$table}");
		
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				if (isset($val['Field'])) array_push($output, $val['Field']);
			}			
		}


		if (!empty($output)) {
			return $output;
		} else {
			return false;
		}		
	}

	/**
	 * Escape String
	 * @see core/iiDB::escape()
	 */
	public function escape($str) {
		if (is_array($str)) {
			foreach ($str as $key => $val) {
				$str[$key] = $this->escape($str[$key]);
			}
			
			return $str;
		} else {
			return mysql_real_escape_string($str);
		}
	}

	/**
	 * 마지막 primary key 값 가져오기
	 * @see core/iiDB::getInsertId()
	 */
	public function getInsertId() {
		return mysql_insert_id();
	}
}
?>