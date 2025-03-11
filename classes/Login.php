<?php
require_once '../config.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		ini_set('display_error', 1);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}

	private function logToFile($message) {
		$logFile = "log.txt";
		$timestamp = date("Y-m-d H:i:s"); // Current timestamp
		$logMessage = "[$timestamp] $message" . PHP_EOL; // Format log entry
	
		// Append log message to the file
		file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
	}

	public function validate_password($username, $password){
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0){
			$user = $result->fetch_assoc();
			if($user['password'] == null){
				// Como no existe una contraseña en la base de datos, entonces se utiliza la contraseña que el usuario da para setiar la contraseña
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE username = ?");
				$stmt->bind_param("ss", $password_hash, $username);
				$stmt->execute();
				return true;
			} else {
				// Si ya existe una contraseña en la base de datos, entonces hay que verificarla
				if(password_verify($password, $user['password'])){
					return true;
				} else {
					return false;
				}
			}
		} else {
			// Usuario no existe asi que hay que retornar false
			return false;
		}
	}


	public function login(){
		extract($_POST);


		//require_once 'validar_ldap.php';
    	//$ldap_response = json_decode(validar_ldap($username, $password), true);

    	if ($this->validate_password($username, $password) == false) {
        	// Retorna el error de LDAP si la validación falla
			//echo json_encode($ldap_response);
			//return json_encode($ldap_response);
			
			return json_encode(array('status'=>'incorrect'));
    	} else {
	

			//$qry = $this->conn->query("SELECT * from users where username = '$username' and password = md5('$password') ");
			$qry = $this->conn->query("SELECT * from users where username = '$username'");
			//$qry = $this->conn->query("SELECT * from users where id = 3");
			if($qry->num_rows > 0){
				foreach($qry->fetch_array() as $k => $v){
					if(!is_numeric($k) && $k != 'password'){
						$this->settings->set_userdata($k,$v);
						//echo $k;
						//echo $v;
					}

				}
				$this->settings->set_userdata('login_type',1);
				//$this->settings->set_userdata('avatar','C:\xampp\htdocs\finanzas\compras\ordenes_compra\uploads\1630999200_avatar5.png');
			return json_encode(array('status'=>'success'));
			}else{
			return json_encode(array('status'=>'incorrect','last_qry'=>"SELECT * from users where username = '$username'"));
		//	return json_encode(array('status'=>'incorrect','last_qry'=>"SELECT * from users where username = '$username' and password = md5('$password') "));
			}
		}
	}
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_user(){
		extract($_POST);
		$qry = $this->conn->query("SELECT * from clients where email = '$email' and password = md5('$password') ");
		if($qry->num_rows > 0){
			foreach($qry->fetch_array() as $k => $v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('login_type',1);
		$resp['status'] = 'success';
		}else{
		$resp['status'] = 'incorrect';
		}
		if($this->conn->error){
			$resp['status'] = 'failed';
			$resp['_error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'login_user':
		echo $auth->login_user();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	default:
		echo $auth->index();
		break;
}

