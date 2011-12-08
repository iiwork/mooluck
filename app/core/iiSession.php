<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

/**
 * DB Session
 */
class iiSession {
	private $lifehour;
	private $table;

	public function __construct($table = 'iiSession', $lifehour = 24) {
		$this->ii = ii::singleton();
		$this->sql = $this->ii->getSQL();
		
		// 기본값
		if (!empty($table)) $this->table = $table;
		if (!empty($lifehour)) $this->lifehour = $lifehour;
		session_cache_expire($this->lifehour * 3600);
	}

	public function start($id = '') {
		session_set_save_handler(array(&$this, 'open'), array(&$this, 'close'), array(&$this, 'read'), array(&$this, 'write'), array(&$this, 'destroy'), array(&$this, 'gc'));

		// debug 모드이면 시작할 때 테이블 설치
		if ($this->ii->debug) {
			$this->install();
		}
		
		// 아이디 지정
		if (!empty($id)) session_id($id);
		
		if (session_start() !== true) {
			throw new Exception('세션 시작에 실패했습니다');
		}
	}

	public function open($save_path, $session_name) {
		return true;
	}

	public function close() {
		return true;
	}

	//TODO ip를 검사할지는 고민해 봐야 함
	public function read($id) {
		$id = $this->sql->escape($id);
		$data = $this->sql->get("SELECT data FROM {$this->table} WHERE id='{$id}' LIMIT 1");
		return !empty($data) ? $data : false;
	}

	public function write($id, $data) {
		$id = $this->sql->escape($id);
		$data = $this->sql->escape($data);
		$result = false;
		
		return $this->sql->query("
			INSERT INTO {$this->table} (id, data, ip, date) VALUES ('{$id}', '{$data}', '{$this->ii->ip}', '{$this->ii->date}')
			ON DUPLICATE KEY UPDATE data = '{$data}', date = '{$this->ii->date}', ip = '{$this->ii->ip}'
		");
	}

	public function destroy($id) {
		return $this->sql->query("DELETE FROM {$this->table} WHERE id='{$id}'");
	}

	public function gc($maxlifetime = '') {
		return !!$this->sql->query("DELETE FROM {$this->table} WHERE date < '" .date("Y-m-d H:i:s", (time() - ($this->lifehour * 3600))). "'");
	}
	
	/**
	 * 세션 테이블 생성
	 */
	public function install() {
		return $this->sql->query("CREATE TABLE IF NOT EXISTS {$this->table} (
				id varchar(255) BINARY not null default '',		#세션ID
				data text,										#내용
				ip varchar(255) not null default '',			#아이피
				date DATETIME not null default 0,				#날짜 - 생성

				PRIMARY KEY (id),
				KEY date(date)
			) TYPE=MyISAM");
	}
}
?>