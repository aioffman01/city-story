<?php
/**
 * userinfo_tbl.php
 * 
 * userinfo 테이블 관련 데이터베이스 처리를 담당하는 클래스입니다.
 * SQL 쿼리를 개별 페이지에 직접 작성하지 않고 이 클래스로 캡슐화하여 사용합니다.
 */

require_once dirname(__DIR__) . '/conf/db_config.php';

class userinfo_tbl {
    // 데이터베이스 연결 인스턴스를 보관할 변수
    private $db;

    /**
     * 클래스 생성자
     * db_config.php에 선언된 get_db_connection()을 통해 데이터베이스 연결을 획득합니다.
     */
    public function __construct() {
        $this->db = get_db_connection();
    }

    /**
     * 특정 사용자 아이디가 데이터베이스에 이미 존재하는지 여부를 확인합니다.
     * 
     * @param string $userId 검사할 사용자 ID
     * @return bool 존재하면 true, 없거나 실패 시 false 반환
     */
    public function existsUser($userId) {
        // 데이터베이스가 연결되어 있지 않은 경우 바로 실패 처리
        if (!$this->db) {
            return false;
        }

        // 아이디 존재 여부를 확인하기 위해 id 필드만 조회
        $sql = "SELECT id FROM userinfo WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt === false) {
            return false;
        }
        
        // 쿼리 실행
        $result = $this->db->execute($stmt, array($userId));
        if ($result === false) {
            return false;
        }
        
        // 검색된 행(row)의 수가 0보다 크면 아이디가 존재하는 것임
        return ($this->db->num_rows($result) > 0);
    }

    /**
     * 로그인 인증을 위해 입력받은 ID에 해당하는 사용자 정보를 가져옵니다.
     * 
     * @param string $userId 사용자 ID
     * @return array|null 사용자 정보 배열, 데이터가 없거나 실패 시 null 반환
     */
    public function getUserForAuth($userId) {
        if (!$this->db) {
            return null;
        }

        // 로그인 인증에 필요한 필드(아이디, 이름, 암호화된 비밀번호 등) 조회
        $sql = "SELECT id, user_id, name, password FROM userinfo WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt === false) {
            return null;
        }
        
        $result = $this->db->execute($stmt, array($userId));
        
        // 데이터가 존재하면 연관 배열 형식으로 반환
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_array($result);
        }
        
        return null;
    }

    /**
     * 사용자의 마지막 로그인 일시(last_login_at)를 현재 시간으로 갱신합니다.
     * 
     * @param int $id userinfo 테이블의 primary key (id)
     * @return bool 성공 시 true, 실패 시 false 반환
     */
    public function updateLastLogin($id) {
        if (!$this->db) {
            return false;
        }

        // last_login_at 컬럼을 현재 서버 시간(NOW())으로 업데이트
        $sql = "UPDATE userinfo SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt === false) {
            return false;
        }
        
        return $this->db->execute($stmt, array($id));
    }

    /**
     * 회원가입 처리를 위해 신규 회원 정보를 데이터베이스에 입력(INSERT)합니다.
     * 
     * @param string $userId 사용자 ID
     * @param string $name 이름
     * @param string $password 암호화된 비밀번호 해시
     * @param string $phone 전화번호
     * @param string $email 이메일
     * @param string $address 주소
     * @return bool 성공 시 true, 실패 시 false 반환
     */
    public function insertUser($userId, $name, $password, $phone, $email, $address) {
        if (!$this->db) {
            return false;
        }

        // 회원 정보 테이블에 신규 데이터를 추가하는 쿼리
        $sql = "INSERT INTO userinfo (user_id, name, password, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt === false) {
            return false;
        }
        
        // 파라미터를 바인딩하여 쿼리 실행
        $params = array($userId, $name, $password, $phone, $email, $address);
        return $this->db->execute($stmt, $params);
    }

    /**
     * 마지막 발생한 데이터베이스 관련 에러 메시지를 반환합니다.
     * 
     * @return string 에러 메시지
     */
    public function getErrorMsg() {
        return $this->db ? $this->db->errormsg() : '데이터베이스가 연결되지 않았습니다.';
    }
}
?>
