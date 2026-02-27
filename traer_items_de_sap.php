<?php

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

    
        $logFile = __DIR__ . "/rutinas.log"; // log file in the same folder
        $message = date("Y-m-d H:i:s") . " - empezo a correr traer_items_de_sap.php \n";

        file_put_contents($logFile, $message, FILE_APPEND);
        

    $sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
    $purchase_orders = $sap->get_new_items();

            $logFile = __DIR__ . "/rutinas.log"; // log file in the same folder
        $message = date("Y-m-d H:i:s") . " - terminamos de correr traer_items_de_sap.php \n";

        file_put_contents($logFile, $message, FILE_APPEND);

?>