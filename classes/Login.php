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

	public function logToFile($message) {
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
				/*
				// Como no existe una contraseña en la base de datos, entonces se utiliza la contraseña que el usuario da para setiar la contraseña
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE username = ?");
				$stmt->bind_param("ss", $password_hash, $username);
				$stmt->execute();
				return true;
				*/
				return false;
				//Se retorna false porque no se puede loguear si no hay una contraseña en la base de datos, el usuario debe crear una contraseña primero
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

	public function reset_password(){
		require "../admin/purchase_orders/enviar_correo.php";
		extract($_POST);
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0){
			$random_int = mt_rand(1000000,9999999999);
			$recovery_id = (string)$random_int;
			$last_recovery_at = date("Y-m-d H:i:s");

			$stmt = $this->conn->prepare("UPDATE users SET recovery_id = ?, last_recovery_at = ? WHERE email = ?");
			$stmt->bind_param("sss", $recovery_id, $last_recovery_at, $email);
			$stmt->execute();

			$body = "Para recuperar la contraseña, haga click en el siguiente enlace: <a href='".base_url."admin/change_password.php?recovery_id=$recovery_id'> ".base_url."admin/change_password.php?recovery_id=$recovery_id  </a>";

			enviar_email([$email], "Recuperación de contraseña", $body, "Sistema de Solicitudes de Compra");
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

	public function change_password(){
		extract($_POST);
		$prepared = $this->conn->prepare("SELECT * FROM users WHERE recovery_id = ? AND email = ?");
            $prepared->bind_param("ss", $recovery_id, $email);
            $prepared->execute();
            $result = $prepared->get_result();
		    $row = $result->fetch_array();

			if($row["last_recovery_at"] != null){
				$last_recovery_at = strtotime($row["last_recovery_at"]);
				$current_time = strtotime(date("Y-m-d H:i:s"));
				$difference = $current_time - $last_recovery_at;
				$minutes = $difference / 60;
				if($minutes > 60){
					// Solo se puede cambiar la contraseña durante una hora después de haber hecho la solicitud
					$resp['status'] = 'the recovery code has expired';
				} else{
					$password_hash = password_hash($password, PASSWORD_DEFAULT);
					$stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE recovery_id = ? AND email = ?");
					$stmt->bind_param("sss", $password_hash, $recovery_id, $email);
					$stmt->execute();
					$resp['status'] = 'success';
				}
			}
		return json_encode($resp);
            

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
	case 'reset_password':
		echo $auth->reset_password();
		break;
	case 'change_password':
		echo $auth->change_password();
		break;
	default:
		echo $auth->index();
		break;
}

