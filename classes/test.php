<?php
require_once "Master.php";
require_once '../admin/purchase_orders/sendPurchaseRequest.php';

$solicitud_id = 1324;
//$Master = new Master();
//$Master->notificar_a_aprobadores_inventario($id);
$id = 497;
//$response = create_purchase_order($id);

$response = crear_salida_de_mercancia($solicitud_id);
//$conn->query("UPDATE `solicitud_de_inventario` set SAPDocEntry = '{$response['DocEntry']}', SAPDocNum = '{$response['DocNum']}', estado_almacen = 4 where id = '{$solicitud_id}' ");



/*			
$ResultRequestSAP = enviar_solicitud_inventario($solicitud_id);
					$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
					$pos0Msj = $ArrayResultRequestSAP[0];
					$pos1DocEntry = $ArrayResultRequestSAP[1];
					$pos2DocNum = $ArrayResultRequestSAP[2];
					$this->settings->set_flashdata('success',"Salida de inventario guardada correctamente $pos0Msj");
					$this->conn->query("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$solicitud_id}'");

					*/
/*
$Master = new Master();
if($Master->aprueba_karol($id) == true) {
	echo "si aprueba karol";
} else{
	echo "no aprueba karol";
};
*/
//crear_salida_de_mercancia(338);

//$conn = $Master->conn;
//enviar_email3(516);
//$Master->enviar_pedido_a_sap(444);

//$id = 364;
/*

$ResultRequestSAP = enviar_solicitud_inventario($id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				echo("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");

*/
				/*
$ResultRequestSAP = sendPurchaseRequest($id);
					$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
					$pos0Msj = $ArrayResultRequestSAP[0];
					$pos1DocEntry = $ArrayResultRequestSAP[1];
					$pos2DocNum = $ArrayResultRequestSAP[2];
					// $this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
					echo("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");

					*/

					/*
					$user_id = 47;

					$query = $this->conn->query("SELECT departamento from aprobadores where user_id = '{$user_id}'");
					$rows = array(); // Initialize an empty array to store rows
					while ($row = $query->fetch_assoc()) {
						$rows[] = $row; // Store each row in an array
					}
					return $rows;
*/
?>