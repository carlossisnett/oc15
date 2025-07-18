<?php

require 'SendPurchaseRequest.php';
require_once 'enviar_correo.php';

 $config =  require __DIR__ . '/../../configuracion.php';
    /*
    $path = realpath(__DIR__ . '/../../secrets.json');
    $secrets_file = file_get_contents($path);
    $secrets = json_decode($secrets_file);
    */

    $servername = $config['servername'];
    $username = $config['username'];
    $password = $config['password'];
    $dbname = $config['dbname'];
    $hostsap = $config['hostsap'];
    $puertosap = $config['puertosap'];
    $companydbsap = $config['companydbsap'];
    $usernamesap = $config['usernamesap'];
    $passwordsap = $config['passwordsap'];

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión a la base de datos
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
    // Me excluyo (carlos.sisnett) porque yo hago muchas solicitudes de prueba y no queremos que se reenvien a SAP
    $sql = "SELECT po.*, u.username FROM po_list po join users u on po.username = u.username WHERE po.pedido = true and status = 1 and po.SAPDocEntry and u.username <> 'carlos.sisnett' IS NULL AND po.date_created >= '2025-06-10 00:00:00';";

    $result = $conn->query($sql);

    foreach($result as $row) {

        $poId = $row['id'];
        $reenvios = $row['reenvios_a_sap'];
        //$supplier_id = $row['supplier_id'];
       // $user_id = $row['user_id'];
       // $status = $row['status'];
        //$codSAP = $row['codSAP'];
       // $firstname = $row['firstname'];
        
        echo($poId);

        // Nos aseguramos de no re enviar a SAP mas de 3 veces
        if($reenvios > 3){
            continue;
        }

        
        try {
            $ResultRequestSAP = sendPurchaseRequest($poId);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
                $conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$poId}'");
            enviar_email(["desarrollo@prensa.com"], "Orden $poId reenviada a SAP", "Orden $poId reenviada a SAP", "Desarrollo Prensa");
            actualizar_reenvios_po($poId, $conn);
        }

        catch (Exception $e) {
                enviar_email(["desarrollo@prensa.com"], "Error al reenviar la Orden $poId  a SAP", "Se intento reenviar la Orden $poId a SAP y no se pudo.", "Desarrollo Prensa");
                actualizar_reenvios_po($poId, $conn);
		}
                
    }

    $sql = "SELECT si.*, u.username FROM solicitud_de_inventario si join users u on si.username = u.username WHERE si.SAPDocEntry IS NULL and u.username <> 'carlos.sisnett' AND date_created >= '2025-06-10 00:00:00' and salida_de_mercancia IS NULL;";

     $result = $conn->query($sql);

    foreach($result as $row) {

        $id = $row['id'];
        $numero_solicitud = $row['numero_solicitud'];
        $reenvios = $row['reenvios_a_sap'];
        //$supplier_id = $row['supplier_id'];
       // $user_id = $row['user_id'];
       // $status = $row['status'];
        //$codSAP = $row['codSAP'];
       // $firstname = $row['firstname'];
        

        if($reenvios > 3){
            continue;
        }

        echo($id);
        try {
            $ResultRequestSAP = enviar_solicitud_inventario($id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
                $conn->query("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");
            enviar_email(["desarrollo@prensa.com"], "Salida de inventario $numero_solicitud reenviada a SAP", "Salida de inventario $numero_solicitud reenviada a SAP", "Desarrollo Prensa");
            actualizar_reenvios_si($id, $conn);
        }

        catch (Exception $e) {
                enviar_email(["desarrollo@prensa.com"], "Error al reenviar la Salida de inventario $numero_solicitud a SAP", "Se intento reenviar la Salida de inventario $numero_solicitud a SAP y no se pudo.", "Desarrollo Prensa");
                actualizar_reenvios_si($id, $conn);
            }
                

    }

function actualizar_reenvios_po($poId, $conn){
    $sql = "SELECT reenvios_a_sap from po_list WHERE id = $poId";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $reenvios = $row['reenvios_a_sap'];
    if($reenvios == null){
        $reenvios = 1;
		$sql = "UPDATE po_list SET reenvios_a_sap = $reenvios WHERE id = $poId";
    	$conn->query($sql);
    } elseif ($reenvios < 3) {
        $reenvios = $reenvios + 1;
		$sql = "UPDATE po_list SET reenvios_a_sap = $reenvios WHERE id = $poId";
    	$conn->query($sql);
    }
}

function actualizar_reenvios_si($id, $conn){
    $sql = "SELECT reenvios_a_sap from solicitud_de_inventario WHERE id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $reenvios = $row['reenvios_a_sap'];
    if($reenvios == null){
        $reenvios = 1;
		$sql = "UPDATE solicitud_de_inventario SET reenvios_a_sap = $reenvios WHERE id = $id";
    	$conn->query($sql);
    } elseif ($reenvios < 3) {
        $reenvios = $reenvios + 1;
		$sql = "UPDATE solicitud_de_inventario SET reenvios_a_sap = $reenvios WHERE id = $id";
    	$conn->query($sql);
    }
}



?>