<?php
if (!defined('II_VERSION')) throw new Exception("Class를 직접 사용할 수 없습니다.");

/**
 * @fileoverview
 * DB patch
 * TODO 향후 이 파일이 권한 없이 실행되서는 안된다 
 */
Class installDB extends iiController {
	public function run($params) {
		if (!empty($params) && in_array("debug", $params)) {
			$this->setDebug();
		}
		
		$this->ii->import("util.iiInstallDB");
		$this->install = new iiInstallDB();
		$this->config = $this->ii->config();
		$this->table = $this->config['table'];
		
		$this->install->addTable("member", "
		CREATE TABLE {$this->table['member']} (
			no MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			
			# 필수정보
			email varchar(255) NOT NULL DEFAULT '', #이메일
			pass varchar(255) NOT NULL DEFAULT '', #비밀번호 / sha1
			nickname varchar(255) NOT NULL DEFAULT '', #닉네임
			
			# 추가정보
			age SMALLINT UNSIGNED NOT NULL DEFAULT 0, #나이
			gender int(1) NOT NULL DEFAULT 1, #성별 m:1, w:2
			job varchar(255) NOT NULL DEFAULT '', #직업
			
			admit_email int(1) NOT NULL DEFAULT 0, #수신동의
			check_email int(1) NOT NULL DEFAULT 0, #메일인증
		
			count_login mediumint UNSIGNED not null default 0, #로그인 회수	
			lock_login int(1) not null default 0,			#로그인 실패 회수
			history_admin text,								#관리용 기록
			ip varchar(255) not null default '',			#로그인 아이피
			ip_join varchar(255) not null default '',		#가입 아이피
			key_find varchar(255) not null default '',		#비밀번호찾기/메일인증키
			key_login varchar(255) not null default '',		#자동로그인키
			date DATETIME not null default 0,			#날짜 - 가입
			date_login DATETIME not null default 0,		#날짜 - 마지막로그인
		
			KEY date(date),
			KEY email(email),
			KEY admit_email(admit_email)
		) TYPE=MyISAM
		");
		
		$this->install->addTable("asset", "
		CREATE TABLE {$this->table['asset']} (
			no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			
			# FK
			member_no MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,	
			
			# 필수정보
			name varchar(255) NOT NULL DEFAULT '', #계정이름
			category enum('미분류', '예금', '대출', '적금', '증권', '펀드', '보험', '연금', '부동산', '자동차', '카드', '기타소득', '기타지출', '기타자산') NOT NULL DEFAULT '미분류', #계정종류
			owner varchar(255) NOT NULL DEFAULT '', #소유자명
			is_estimate int(1) NOT NULL DEFAULT 0, #추정 계정 여부(결산 제외, 소득 집계나 변동 추이를 확인홰 보는 용도의 계정임)
			is_del int(1) NOT NULL DEFAULT 0, #삭제 여부
			
			# 추가정보
			rate FLOAT(5,3) NOT NULL DEFAULT 0, #이율
			comment text, #비고
			history text, #정보 변경 기록(nosql 형식으로 쌓임)
			date DATETIME not null default 0, #등록된 날짜
				
			KEY member_no(member_no),
			KEY is_del(is_del),
			KEY is_estimate(is_estimate)
		) TYPE=MyISAM
		");
		
		$this->install->addTable("asset_data", "
		CREATE TABLE {$this->table['asset_data']} (
			no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			
			# FK
			asset_no INT UNSIGNED NOT NULL DEFAULT 0,
			snapshot_no INT UNSIGNED NOT NULL DEFAULT 0,
			
			# 필수정보
			balance INT NOT NULL DEFAULT 0, #잔액
			comment text, #비고
			date DATETIME not null default 0, #등록된 날짜
		
			KEY asset_no(asset_no),
			KEY snapshot_no(snapshot_no)
		) TYPE=MyISAM
		");
		
		$this->install->addTable("asset_snapshot", "
		CREATE TABLE {$this->table['asset_snapshot']} (
			no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			
			# FK
			member_no MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
			
			# 필수정보
			data text, #요약정보(serialized)
			comment text, #비고
			date DATETIME not null default 0, #등록된 날짜
		
			KEY member_no(member_no)
		) TYPE=MyISAM
		");
		
		$this->install->addTable("asset_detail", "
		CREATE TABLE {$this->table['asset_detail']} (
			no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			
			# FK
			asset_no INT UNSIGNED NOT NULL DEFAULT 0,
			
			# 필수정보
			name varchar(255) NOT NULL DEFAULT '', #이름
			category varchar(255) NOT NULL DEFAULT '', #구분
			price INT NOT NULL DEFAULT 0, #발생 금액
			comment text, #코멘트
			date DATETIME not null default 0, #등록된 날짜
		
			KEY asset_no(asset_no),
			KEY category(category)
		) TYPE=MyISAM
		");		
		
		$result = $this->install->patch();
		$this->complete($result);
	}
}
?>