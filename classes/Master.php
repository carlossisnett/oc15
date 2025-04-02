<?php
require_once('../config.php');
require_once('../admin/purchase_orders/sendPurchaseRequest.php');
require_once('../admin/purchase_orders/enviar_correo.php');



Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_supplier(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				$v = addslashes(trim($v));
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `supplier_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Proveedor existe actualmente";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `supplier_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `supplier_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Nuevo proveedor guardado correctamente");
			else
				$this->settings->set_flashdata('success',"Proveedor actualizado con éxito.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_supplier(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `supplier_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Proveedor eliminado correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_item(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(isset($_POST['description'])){
			if(!empty($data)) $data .=",";
				$data .= " `description`='".addslashes(htmlentities($description))."' ";
		}
		$check = $this->conn->query("SELECT * FROM `item_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "El nombre del producto ya existe.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `item_list` set {$data} ";
		}else{
			$sql = "UPDATE `item_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Nuevo elemento guardado con éxito.");
			else
				$this->settings->set_flashdata('success',"Producto actualizado correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_item(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `item_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Producto eliminado correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function search_items(){
		extract($_POST);
		//$qry = $this->conn->query("SELECT * FROM item_list where `name` LIKE '%{$q}%'");
		$qry = $this->conn->query("SELECT id,codSAP, concat(codSAP, ' ' , `description`) as `description` FROM item_list where `description` LIKE '%{$q}%'");
		$qry_2 = $this->conn->query("SELECT id,codSAP, concat(codSAP, ' ' , `description`) as `description` FROM item_list where `codSAP` LIKE '%{$q}%'");
		$data = array();
		while($row = $qry->fetch_assoc()){
			$data[] = array("label"=>$row['description'],"id"=>$row['id'],"name"=>$row['codSAP']);
		}
		while($row = $qry_2->fetch_assoc()){
			$data[] = array("label"=>$row['description'],"id"=>$row['id'],"name"=>$row['codSAP']);
		}
		
		//$data = array_unique($data);
		return json_encode($data);
	}


	function get_stock(){
		extract($_POST);
		$qry = $this->conn->query("SELECT * FROM item_list where id = '{$item_id}'");
		//$data = array();
		$item_object = $qry->fetch_object();
		$stock = ["stock"=> $item_object->stock_actual];
		return json_encode($stock);
	}

	
	/*
	Esta funcion existe para solo buscar los items que son de inventario, es decir a diferencia de la funcion anterior no busca items que son servicios
	*/

	function search_inventory_items(){
		extract($_POST);
		//$qry = $this->conn->query("SELECT * FROM item_list where `name` LIKE '%{$q}%'");
		$qry = $this->conn->query("SELECT id,codSAP, concat(codSAP, ' ' , `description`) as `description` FROM item_list where `description` LIKE '%$q%' and `inventory_item` = 1");
		$qry_2 = $this->conn->query("SELECT id,codSAP, concat(codSAP, ' ' , `description`) as `description` FROM item_list where `codSAP` LIKE '%{$q}%'");
		$data = array();
		while($row = $qry->fetch_assoc()){
			$data[] = array("label"=>$row['description'],"id"=>$row['id'],"name"=>$row['codSAP']);
		}
		while($row = $qry_2->fetch_assoc()){
			$data[] = array("label"=>$row['description'],"id"=>$row['id'],"name"=>$row['codSAP']);
		}
		return json_encode($data);
	}
	function search_marca(){
		extract($_POST);
		$qry = $this->conn->query("SELECT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 1 and activo = 'Y' and `nombre_ccosto` LIKE '%{$q}%' order by `nombre_ccosto`");
		$data = array();
		while($row = $qry->fetch_assoc()){
			$data[] = array("label"=>$row['nombre_ccosto'],"id"=>$row['codigo_ccosto'],"name"=>$row['nombre_ccosto']);
		}
		return json_encode($data);
	}
	function search_departamento(){
		extract($_POST);
		
		$qry = $this->conn->query("SELECT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 2 and activo = 'Y' and `nombre_ccosto` LIKE '%{$q}%' order by `nombre_ccosto`");
		$data = array();
		while($row = $qry->fetch_assoc()){
			$data[] = array("label"=>$row['nombre_ccosto'],"id"=>$row['codigo_ccosto'],"name"=>$row['nombre_ccosto']);
		}
		return json_encode($data);
	}

	function edit_po(){
		extract($_POST);
		$data = "";

		
		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data_pruebas.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);
		
		 

		
		foreach($_POST as $k =>$v){
			if(in_array($k,array('discount_amount','tax_amount')))
				$v= str_replace(',','',$v);
		}


		if(isset($discount_amount) == false){
			$discount_amount = 0;
		}
		if(isset($discount_percentage) == false){
			$discount_percentage = 0;
		}
		if(isset($tax_amount) == false){
			$tax_amount = 0;
		}
		if(isset($tax_percentage) == false){
			$tax_percentage = 0;
		}

		if(isset($sub_total) == false){
			$sub_total = null;
		}
		if(isset($notes) == false){
			$notes = null;
		}
		if(isset($ruta_adjunto) == false){
			$ruta_adjunto = null;
		}


		$username = $_SESSION['userdata']['username'];

		$prepared = $this->conn->prepare("UPDATE po_list 
			SET required_date = ?, 
				username = ?, 
				discount_percentage = ?, 
				discount_amount = ?, 
				tax_percentage = ?, 
				tax_amount = ?, 
				notes = ?, 
				sub_total = ?, 
				total = ?, 
				ruta_adjunto = ? 
			WHERE id = ?");

		$prepared->bind_param("ssddddsddss", $required_date, $username, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $notes, $sub_total, $total, $ruta_adjunto, $id);
		$prepared->execute();

		
		$prepared = $this->conn->prepare("SELECT po_no FROM po_list where id = ?");
		$prepared->bind_param("s", $id);
		$prepared->execute();
		$result = $prepared->get_result();
		$row = $result->fetch_assoc();
		$po_no = $row['po_no'];
		$id = (int)$id;
		
		$supplier_id = (int)$supplier_id;

		#Si se creo la orden de compra entonces proceder a agregar los articulos a ella
		if(isset($row))
			for($x = 0; $x < count($item_id); $x++){
				// Solo modificamos los order item ids que ya existian en la base de datos, es decir no los que son default
				if($order_item_id[$x] != "default"){
					$price = (float)$unit_price[$x];
					$quantity = (float)$qty[$x];
					$prepared = $this->conn->prepare("UPDATE order_items 
					SET quantity = ?, 
						description = ?, 
						unit_price = ?, 
						po_id = ?, 
						item_id = ?, 
						codigo_marca = ?, 
						codigo_departamento = ?, 
						url = ?, 
						proveedor_id = ? 
					WHERE id = ?");
				
					$prepared->bind_param("dsdissssii", $quantity, $description[$x], $price, $id, $item_id[$x], $marca_id[$x], $departamento_id[$x], $url[$x], $supplier_id, $order_item_id[$x]);
					$prepared->execute();
				}
			}
			$resp['status'] = 'success';
			$resp['id'] = $id;
			$resp['po_no'] = $po_no;
				$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email(["carlos.sisnett@prensa.com"], "Orden de compra ha sido modificada", "Orden de compra ha sido modificada con exito.", "desarrollo@prensa.com");

					
					//if ($po_id != 233)
					//{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}

		
		/*
		else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}

		*/
		return json_encode($resp);
	}

	function save_po(){
		extract($_POST);
		$data = "";

		
		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data_pruebas.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);
		
		 

		
		foreach($_POST as $k =>$v){
			if(in_array($k,array('discount_amount','tax_amount')))
				$v= str_replace(',','',$v);
		}

		$po_no = "";
			while(true){
				$po_no = "PO-".(sprintf("%'.011d", mt_rand(1,99999999999)));
				$check = $this->conn->query("SELECT * FROM `po_list` where `po_no` = '{$po_no}'")->num_rows;
				if($check <= 0)
				break;
			}

		if(isset($discount_amount) == false){
			$discount_amount = 0;
		}
		if(isset($discount_percentage) == false){
			$discount_percentage = 0;
		}
		if(isset($tax_amount) == false){
			$tax_amount = 0;
		}
		if(isset($tax_percentage) == false){
			$tax_percentage = 0;
		}

		if(isset($sub_total) == false){
			$sub_total = null;
		}
		if(isset($notes) == false){
			$notes = null;
		}
		if(isset($ruta_adjunto) == false){
			$ruta_adjunto = null;
		}


		$username = $_SESSION['userdata']['username'];

		$prepared = $this->conn->prepare("INSERT INTO po_list(required_date, username, po_no, discount_percentage, discount_amount, tax_percentage, tax_amount, notes, sub_total, total, ruta_adjunto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

		$prepared->bind_param("sssddddsdds", $required_date, $username, $po_no, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $notes, $sub_total, $total, $ruta_adjunto);
		$prepared->execute();

		
		$prepared = $this->conn->prepare("SELECT id FROM po_list where po_no = ?");
		$prepared->bind_param("s", $po_no);
		$prepared->execute();
		$result = $prepared->get_result();
		$row = $result->fetch_assoc();
		$id = (int)$row['id'];
		$supplier_id = (int)$supplier_id;

		#Si se creo la orden de compra entonces proceder a agregar los articulos a ella
		if(isset($row)){
			for($x = 0; $x < count($item_id); $x++){
				$price = (float)$unit_price[$x];
				$quantity = (float)$qty[$x];
				$prepared = $this->conn->prepare("INSERT INTO order_items(quantity, description, unit_price, po_id, item_id, codigo_marca, codigo_departamento, url, proveedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$prepared->bind_param("dsdissssi", $quantity, $description[$x], $price, $id, $item_id[$x], $marca_id[$x], $departamento_id[$x], $url[$x], $supplier_id);
				$prepared->execute();
			}
			$resp['status'] = 'success';
			$resp['id'] = $id;
			$resp['po_no'] = $po_no;
			$ResultRequestSAP = sendPurchaseRequest($id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
				$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email2($id, $pos1DocEntry);

					
					//if ($po_id != 233)
					//{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}

		} else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
		/*
		if($save){
			$resp['status'] = 'success';
			$po_id = empty($id) ? $this->conn->insert_id : $id ;
			$resp['id'] = $po_id;
			$data = "";
			foreach($item_id as $k =>$v){
				if(!empty($data)) $data .=",";
				$data .= "('{$po_id}','{$v}','{$unit_price[$k]}','{$qty[$k]}','{$marca_id[$k]}','{$departamento_id[$k]}', '{$url[$k]}', '{$description[$k]}')";
			}
			if(!empty($data)){
				$this->conn->query("DELETE FROM `order_items` where po_id = '{$po_id}'");
				$save = $this->conn->query("INSERT INTO `order_items` (`po_id`,`item_id`,`unit_price`,`quantity`,codigo_marca,codigo_departamento,url,description) VALUES {$data} ");
				//echo "INSERT INTO `order_items` (`po_id`,`item_id`,`unit`,`unit_price`,`quantity`) VALUES {$data} ";
			}
			if(empty($id))
			{
				$ResultRequestSAP = sendPurchaseRequest($po_id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
				$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$po_id}'");
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email2($po_id, $pos1DocEntry);

					
					//if ($po_id != 233)
					//{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}
			}
			else
				$this->settings->set_flashdata('success',"Orden de compra actualizada correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
		*/

	/*
	Esta funcion devuelve un array con los departamentos que faltan por aprobar, es decir aquellos que tienen status 0 o 2
	
	Number -> Array
	330 -> 
	*/

	function departamentos_que_faltan_por_aprobar($po_id){
		$query = $this->conn->query("SELECT codigo_departamento from order_items where po_id = '{$po_id}' and (status = 0 or status = 2 or status is null)");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row; // Store each row in an array
		}
		return $rows;
	}

	/*
	Esta funcion devuelve un array con los departamentos que el usuario puede aprobar, es decir aquellos que tiene en la tabla aprobadores
	
	Number -> Array
	*/

	function departamentos_que_usuario_puede_aprobar($user_id){
		$query = $this->conn->query("SELECT departamento from aprobadores where user_id = '{$user_id}'");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row; // Store each row in an array
		}
		return $rows;
	}

	/*
	Retorna true si todos los elementos del subset se encuentran en mainArray, de lo contrario retorna false.
	Array, Array -> Boolean
	*/

	function containsAllElements($subset, $mainArray) {
		return empty(array_diff($subset, $mainArray));
	}

	/*
	Esta funcion determina si con la aprobacion del usuario recibido ya se puede aprobar la orden de compra
	esto se necesita porque hay ordenes de compra que requieren mas de 1 aprobacion al solicitar articulos
	para distintos departamentos

	Id de la solicitud de compra, Id del usuario que aprueba -> ___
	String, String -> ____
	*/

	function is_po_ready_to_be_approved($id, $user_id, $status){
			$departamentos_por_aprobar = $this->departamentos_que_faltan_por_aprobar($id);
			$departamentos_que_usuario_puede_aprobar = $this->departamentos_que_usuario_puede_aprobar($user_id);
			// Extract only the department values
			$departamentos = array_column($departamentos_que_usuario_puede_aprobar, 'departamento');

			// Convert to a string formatted for SQL
			$departamentos_sql = "'" . implode("', '", $departamentos) . "'";

			$hora_aprobacion = date('Y-m-d H:i:s');
			//$departamentos_string = "'" . implode("', '", $departamentos_que_usuario_puede_aprobar) . "'";

			$this->conn->query("UPDATE `order_items` 
								  SET aprobador_user_id = '{$user_id}', 
									  status = '{$status}', 
									  hora_aprobacion = '{$hora_aprobacion}' 
								  WHERE po_id = '{$id}' and codigo_departamento in ({$departamentos_sql})");

			if($this->containsAllElements(
				array_column($departamentos_por_aprobar, 'codigo_departamento'),
				array_column($departamentos_que_usuario_puede_aprobar, 'departamento')) == false){
				return false;
			} else {
				return true;
		}
	}

	function is_po_approved_by_this_departament($codigo_departamento, $po_id){
		$query = $this->conn->query("SELECT * from aprobaciones where departamento = '{$codigo_departamento}' and orden_compra_id = '{$po_id}' and estado = 1");
		$rows = $query->fetch_assoc();
		if($rows == 1){
			return true;
		} else{
			return false;
		}
	}

	function is_po_approved_by_this_approver($user_id, $po_id){
		$query = $this->conn->query("SELECT * from order_items where user_id = '{$user_id}' and po_id = '{$po_id}' and estado = 1");
		$rows = $query->fetch_assoc();
		// Se usa mayor que 0 en caso de que algun aprobador haya aprobado mas de 1 vez
		if($rows > 0){
			return true;
		} else{
			return false;
		}
	}

	function change_po_status(){
		extract($_POST);

		/*
		$jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		// Define the path to the external JSON file
		$filePath = 'po_status_data.json';
	
		// Write the JSON data to the file
		file_put_contents($filePath, $jsonData);
		*/

		$save = false;

		if($status == 0 or $status == 2){
			$save = $this->conn->query("UPDATE `po_list` set status = '{$status}' where id = '{$id}' ");
		}

		$approved = $this->is_po_ready_to_be_approved($id, $user_id, $status);

		if($approved == true) {
			$save = $this->conn->query("UPDATE `po_list` set status = '{$status}' where id = '{$id}' ");
		}

	
			$resp['status'] = 'success';
			if($approved == true and $status == 1) {
				$this->settings->set_flashdata('success',"Orden de compra aprobada correctamente.");
			} elseif ($status == 1  and $approved == false) {
				$this->settings->set_flashdata('success',"Orden de compra ha sido actualizada correctamente. Falta la aprobación de los otros departamentos.");
			} elseif ($status == 2 and $approved == false) {
			$this->settings->set_flashdata('success',"Estado de la orden de compra actualizado correctamente.");
			} elseif ($status == 0) {
				$this->settings->set_flashdata('success',"Estado de la orden de compra actualizado correctamente.");
			} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		
		return json_encode($resp);

	}
	
	function save_po_old(){
		extract($_POST);
		$data = "";

		
		/*
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);
		*/
		 

	
		foreach($_POST as $k =>$v){
			if(in_array($k,array('discount_amount','tax_amount')))
				$v= str_replace(',','',$v);
			if(!in_array($k,array('id','po_no')) && !is_array($_POST[$k])){
				$v = addslashes(trim($v));
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(!empty($po_no)){
			$check = $this->conn->query("SELECT * FROM `po_list` where `po_no` = '{$po_no}' ".($id > 0 ? " and id != '{$id}' ":""))->num_rows;
			if($this->capture_err())
				return $this->capture_err();
			if($check > 0){
				$resp['status'] = 'po_failed';
				$resp['msg'] = "El número de orden existe actualmente";
				return json_encode($resp);
				exit;
			}
		}else{
			$po_no ="";
			while(true){
				$po_no = "PO-".(sprintf("%'.011d", mt_rand(1,99999999999)));
				$check = $this->conn->query("SELECT * FROM `po_list` where `po_no` = '{$po_no}'")->num_rows;
				if($check <= 0)
				break;
			}
		}

		$username_ins = $_SESSION['userdata']['username'];

		$data .= ", po_no = '{$po_no}' ";
		$data .= ", username = '{$username_ins}' ";
		

		//echo $data;

		if(empty($id)){
			$sql = "INSERT INTO `po_list` set {$data} ";
		}else{
			$sql = "UPDATE `po_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$po_id = empty($id) ? $this->conn->insert_id : $id ;
			$resp['id'] = $po_id;
			$data = "";
			foreach($item_id as $k =>$v){
				if(!empty($data)) $data .=",";
				$data .= "('{$po_id}','{$v}','{$unit_price[$k]}','{$qty[$k]}','{$marca_id[$k]}','{$departamento_id[$k]}', '{$url[$k]}', '{$description[$k]}')";
			}
			if(!empty($data)){
				$this->conn->query("DELETE FROM `order_items` where po_id = '{$po_id}'");
				$save = $this->conn->query("INSERT INTO `order_items` (`po_id`,`item_id`,`unit_price`,`quantity`,codigo_marca,codigo_departamento,url,description) VALUES {$data} ");
				//echo "INSERT INTO `order_items` (`po_id`,`item_id`,`unit`,`unit_price`,`quantity`) VALUES {$data} ";
			}
			if(empty($id))
			{
				$ResultRequestSAP = sendPurchaseRequest($po_id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
				$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$po_id}'");
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email2($po_id, $pos1DocEntry);

					/*
					if ($po_id != 233)
					{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					*/
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}
			}
			else
				$this->settings->set_flashdata('success',"Orden de compra actualizada correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	function duplicate_po(){
		extract($_POST);
		$data = "";

		/*
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);
		*/
	
		foreach($_POST as $k =>$v){
			if(in_array($k,array('discount_amount','tax_amount')))
				$v= str_replace(',','',$v);
			if(!in_array($k,array('id','po_no')) && !is_array($_POST[$k])){
				$v = addslashes(trim($v));
				if(!empty($data)) $data .=",";
				if($k != 'original_id'){
				$data .= " `{$k}`='{$v}' ";
				}
			}
		}

		$po_no ="";
		while(true){
				$po_no = "PO-".(sprintf("%'.011d", mt_rand(1,99999999999)));
				$check = $this->conn->query("SELECT * FROM `po_list` where `po_no` = '{$po_no}'")->num_rows;
				if($check <= 0)
				break;
			}
		

		$username_ins = $_SESSION['userdata']['username'];

		$data .= ", po_no = '{$po_no}' ";
		$data .= ", username = '{$username_ins}' ";
		

		//echo $data;

		$sql = "INSERT INTO `po_list` set {$data} ";

		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$po_id = empty($id) ? $this->conn->insert_id : $id ;
			$resp['id'] = $po_id;
			$data = "";
			foreach($item_id as $k =>$v){
				if(!empty($data)) $data .=",";
				$data .= "('{$po_id}','{$v}','{$unit_price[$k]}','{$qty[$k]}','{$marca_id[$k]}','{$departamento_id[$k]}', '{$url[$k]}', '{$description[$k]}')";
			}

			if(!empty($data)){
				$this->conn->query("DELETE FROM `order_items` where po_id = '{$po_id}'");
				$save = $this->conn->query("INSERT INTO `order_items` (`po_id`,`item_id`,`unit_price`,`quantity`,codigo_marca,codigo_departamento,url,description) VALUES {$data} ");
				//echo "INSERT INTO `order_items` (`po_id`,`item_id`,`unit`,`unit_price`,`quantity`) VALUES {$data} ";
			}

			if(empty($id)){
				$ResultRequestSAP = sendPurchaseRequest($po_id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
				$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$po_id}'");
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email2($po_id, $pos1DocEntry);

					/*
					if ($po_id != 233)
					{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					*/
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}
			}
			else
				$this->settings->set_flashdata('success',"Orden de compra actualizada correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	function save_inventory_request(){
		extract($_POST);
		$data = "";

		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		 
		
	
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','numero_solicitud')) && !is_array($_POST[$k])){
				$v = addslashes(trim($v));
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(!empty($numero_solicitud)){
			$check = $this->conn->query("SELECT * FROM `solicitud_de_inventario` where `numero_solicitud` = '{$numero_solicitud}' ".($id > 0 ? " and id != '{$id}' ":""))->num_rows;
			if($this->capture_err())
				return $this->capture_err();
			if($check > 0){
				$resp['status'] = 'po_failed';
				$resp['msg'] = "El número de Solicitud de inventario existe actualmente";
				return json_encode($resp);
				exit;
			}
		}else{
			$numero_solicitud ="";
			while(true){
				$numero_solicitud = "SI-".(sprintf("%'.011d", mt_rand(1,99999999999)));
				$check = $this->conn->query("SELECT * FROM `solicitud_de_inventario` where `numero_solicitud` = '{$numero_solicitud}'")->num_rows;
				if($check <= 0)
				break;
			}
		}

		$username_ins = $_SESSION['userdata']['username'];

		$data .= ", numero_solicitud = '{$numero_solicitud}' ";
		$data .= ", username = '{$username_ins}' ";
		

		//echo $data;

		if(empty($id)){
			$sql = "INSERT INTO `solicitud_de_inventario` set {$data} ";
		}else{
			$sql = "UPDATE `solicitud_de_inventario` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$solicitud_id = empty($id) ? $this->conn->insert_id : $id ;
			$resp['id'] = $solicitud_id;
			$data = "";
			foreach($item_id as $k =>$v){
				if(!empty($data)) $data .=",";
				$data .= "('{$solicitud_id}','{$v}','{$qty[$k]}','{$marca_id[$k]}','{$departamento_id[$k]}')";
			}
			if(!empty($data)){
				$this->conn->query("DELETE FROM `inventory_items` where solicitud_id = '{$solicitud_id}'");
				$save = $this->conn->query("INSERT INTO `inventory_items` (`solicitud_id`,`item_id`,`quantity`,codigo_marca,codigo_departamento) VALUES {$data} ");
				//echo "INSERT INTO `inventory_items` (`solicitud_id`,`item_id`,`unit`,`unit_price`,`quantity`) VALUES {$data} ";
			}
			if(empty($id))
			{
				$ResultRequestSAP = enviar_solicitud_inventario($solicitud_id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Salida de inventario guardada correctamente $pos0Msj");
				$this->conn->query("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$solicitud_id}'");
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				//$this->guardar_adjunto($pos1DocEntry);
				try {
					$resultado = enviar_email_solicitud_inventario($solicitud_id, $pos1DocEntry);
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}
			}
			else
				$this->settings->set_flashdata('success',"Salida de inventario actualizada correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);

		
	}



function guardar_adjunto($po_no){

	$order = $this->conn->query("SELECT * FROM `po_list` where po_no = '{$po_no}'");
	$row = $order->fetch_array();
	$id = $row['id'];

	/*

	$jsonData = json_encode($row, JSON_PRETTY_PRINT);

	// Define the path to the external JSON file
	$filePath = 'order_data.json';

	// Write the JSON data to the file
	file_put_contents($filePath, $jsonData);

	*/

		//echo 'entre guardar_adjunto';
	/* prueba adjuntos */
	// Manejar archivo adjunto
   // Ruta local donde deseas guardar el archivo (NO usar una URL HTTP)
   $upload_dir = "../uploads/";
   //echo  $upload_dir;

   // Verifica si la carpeta de destino existe, si no, la crea
   if(!is_dir($upload_dir)){
	   mkdir($upload_dir, 0755, true); // Crea la carpeta con permisos 0755
   }

   // Generamos la fecha actual en formato ISO (YYYY-MM-DD)
   $fechaISO = date('Y-m-d');

   $file_dir = $upload_dir . $fechaISO . '_OCID_' . $id;
   //echo 'file_dir' . $file_dir;
   if(!is_dir($file_dir)){
	   mkdir($file_dir, 0755, true); // Crea la carpeta con permisos 0755
   }
   
//if(isset($_FILES['ruta_adjunto']) && $_FILES['ruta_adjunto']['error'] == 0){


		$i = 1;
	while($i < 11){
		if($_FILES['ruta_adjunto_' . strval($i)]['name'] != ""){
			// Nombre original del archivo
			$file_name = $_FILES['ruta_adjunto_' . strval($i)]['name'];
			//echo  $file_name;

			// Ruta temporal del archivo en el servidor
			$file_tmp = $_FILES['ruta_adjunto_' . strval($i)]['tmp_name'];

	

			// Construimos la ruta completa concatenando: carpeta + fechaISO + _OCID_ + id + nombre original
			$file_path = rtrim($file_dir, '/\\') . '/' . basename($file_name);
			//echo 'file_path' . $file_path;
			// Define la ruta completa donde se guardará el archivo
			//$file_path = $upload_dir . basename($file_name);

			// Mueve el archivo desde la ruta temporal a la carpeta local
			if(move_uploaded_file($file_tmp, $file_path)){
				//echo "Archivo subido exitosamente. Ruta: " . realpath($file_path);
			} else {
				//echo "Error al subir el archivo.";
			}
		}
		$i++;
	}
	$this->conn->query("UPDATE `po_list` SET ruta_adjunto  = '$file_dir' where id = $id");
}
	function delete_po(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `po_list` where unit_id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Orden de compra eliminada correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function get_price(){
		extract($_POST);
		 $qry = $this->conn->query("SELECT * FROM price_list where unit_id = '{$unit_id}'");
		 $this->capture_err();
		 if($qry->num_rows > 0){
			 $res = $qry->fetch_array();
			 switch($rent_type){
				 case '1':
					$resp['price'] = $res['monthly'];
					break;
				case '2':
					$resp['price'] = $res['quarterly'];
					break;
				case '3':
					$resp['price'] = $res['annually'];
					break;
			 }
		 }else{
			 $resp['price'] = "0";
		 }
		 return json_encode($resp);
	}

	function update_approver(){
		extract($_POST);
		$save = $this->conn->query("INSERT INTO `aprobadores` (`user_id`,`departamento`) VALUES ('{$user_id}','{$departamento_id}') ");
		if($save){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Aprobador guardado correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	
	function delete_img(){
		extract($_POST);
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown '.$path.' path';
		}
		return json_encode($resp);
	}
	
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_supplier':
		echo $Master->save_supplier();
	break;
	case 'delete_supplier':
		echo $Master->delete_supplier();
	break;
	case 'save_item':
		echo $Master->save_item();
	break;
	case 'delete_item':
		echo $Master->delete_item();
	break;
	case 'search_items':
		echo $Master->search_items();
	break;
	case 'search_inventory_items':
		echo $Master->search_inventory_items();
	break;
	case 'save_po':
		echo $Master->save_po();
	break;
	case 'duplicate_po':
		echo $Master->duplicate_po();
	break;
	case 'save_inventory_request':
		echo $Master->save_inventory_request();
	break;
	case 'delete_po':
		echo $Master->delete_po();
	break;
	case 'get_price':
		echo $Master->get_price();
		break;
	case 'search_marca':
		echo $Master->search_marca();
	break;
	case 'search_departamento':
		echo $Master->search_departamento();
	break;
	case 'get_stock':
		echo $Master->get_stock();
	break;
	case 'change_po_status':
		echo $Master->change_po_status();
	break;
	case 'update_approver':
		echo $Master->update_approver();
	break;
	case 'edit_po':
		echo $Master->edit_po();
	break;

	
	default:
		// echo $sysset->index();
		break;
}