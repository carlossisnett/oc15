<?php
ini_set('memory_limit', '1024M');
require_once 'sap_service_layer_diferente.php';

$hostSAP = 'sap-bo-srvl-mtdtech.skyinone.net';
$puertoSAP = '50000';
#$companyDBSAP = 'SBO_C184_DB2_TST2'; // para hacer pruebas
$companyDBSAP = 'SBO_C184_DB2_PRD';
$userNameSAP = 'SAPABO\\ef82f11a-65d9-44a3';
$passwordSAP = 'Sky0ne2020.';

$sap = new SAPServiceLayer($hostSAP, $puertoSAP, $companyDBSAP, $userNameSAP, $passwordSAP);
$sap->get_inventory();


?>