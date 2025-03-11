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

	public function login(){
		extract($_POST);


		require_once 'validar_ldap.php';
    	$ldap_response = json_decode(validar_ldap($username, $password), true);

    	if ($ldap_response['status'] !== 'success') {
        	// Retorna el error de LDAP si la validación falla
			//echo json_encode($ldap_response);
			//return json_encode($ldap_response);
			return json_encode(array('status'=>'incorrect'));
    	}
	

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

