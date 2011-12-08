<?php
/**
 * 회원
 * 
 * @class Member
 */
class Member {
	/**
	 * @var String 회원번호 쿠키 이름
	 */
	const COOKIE_NO = "ii_no";
	
	/**
	 * @var String 회원인증코드 쿠키 이름
	 */
	const COOKIE_AUTH = "ii_auth";
	
	/**
	 * @var String 고유 인증키, 인증키를 토대로 hash를 생성 합니다. 노출되어서는 안됩니다.
	 */
	const AUTH_KEY = "TEST";
	
	/**
	 * @constructor
	 */
	public function __construct() {
		$this->ii = ii::singleton();
		$this->sql = $this->ii->connect();
		$this->config = $this->ii->config();
		$this->_bIsLogged = false;
	}
	
	/**
	 * 회원 정보를 가져 옵니다
	 *
	 * @param integer $no 값을 비우면 현재 로그인된 사용자의 정보를 가져 옵니다
	 * @return Boolean|Array 회원 정보를 반환
	 * @throws Exception
	 */
	public function getMember($no = null) {
		// 현재 사용자의 번호를 구함
		if ($no === null && $this->isLogin()) {
			$no = (int)$_COOKIE[self::COOKIE_NO];
			$data = $this->_loggedMemberData;
		}
		
		if (empty($no)) {
			throw new Exception("회원 번호를 가져올 수 없습니다");
		}
		
		// 회원 정보 가져오기
		$data = $this->get("SELECT * FROM {$this->config['table']['member']} WHERE no = {$no}");
		
		if (empty($data)) {
			throw new Exception("존재하지 않는 회원 정보 입니다");
		}
		
		$data = $this->_parseMember($data);
		return $data;
	}
	
	/**
	 * 로그인 정보를 비교하여 현재 로그인 되어 있는지 확인
	 * 
	 * @return Boolean 로그인 여부를 반환
	 */
	public function checkLogin() {
		if ($this->_bIsLogged) {
			return true;
		}
		
		$no = (int)$_COOKIE[self::COOKIE_NO];
		$auth = $_COOKIE[self::COOKIE_AUTH];
		
		if (empty($_COOKIE[self::COOKIE_NO])) {
			return false;
		}

		// 회원 정보를 가져 옴
		$data = $this->getMember($no);
		
		// 로그인 정보를 비교
		
	}
	
	/**
	 * 인증키를 생성 합니다
	 * 
	 * @param integer $no 회원번호
	 * @param string $key DB에 저장된 인증키
	 * @return string auth_key
	 */
	private function _makeAuthKey($no, $key) {
		return sha1(md5($no . "|" . $key . "|" . self::AUTH_KEY));
	}
	
	private function _parseMember($data) {
		return $data;		
	}
}
?>