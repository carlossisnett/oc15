<?php
if(!defined('DB_SERVER')){
    require_once("../initialize.php");
}
require_once("../initialize.php");
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            
            //$this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            $this->conn = mysqli_init();
            mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
            mysqli_real_connect($this->conn, $this->host, $this->username, $this->password, $this->database, 3306, NULL, MYSQLI_CLIENT_SSL);
            
            if (!$this->conn) {
                echo 'Cannot connect to database server';
                exit;
            }            
        }    
        
    }
    public function __destruct(){
        $this->conn->close();
    }
}
?>