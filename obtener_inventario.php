<?php
ini_set('memory_limit', '1024M');
require_once 'sap_service_layer_diferente.php';


$config =  require __DIR__ . '/configuracion.php';
$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

    $hostsap = $config['hostsap'];
    $puertosap = $config['puertosap'];
    $companydbsap = $config['companydbsap'];
    $usernamesap = $config['usernamesap'];
    $passwordsap = $config['passwordsap'];

$sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
$sap->get_inventory();


?>