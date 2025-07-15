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

		//file_put_contents('log.txt', 'called search_items' . PHP_EOL, FILE_APPEND);
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
		Esta funcion retorna el nombre del proveedor de la orden de compra.
		Integer -> String
		Si no encuentra un proveedor, retorna un string vacio, de lo contrario retorna el nombre del proveedor
	*/

	function obtener_proveedor($po_id){
		$query = $this->conn->query("SELECT o.*, p.name FROM order_items o JOIN proveedores p ON p.id = o.proveedor_id where o.po_id = '$po_id' LIMIT 1;");
                    if(gettype($query) == "boolean"){
                        return "";
                    } else {
                    $rows = $query->fetch_array();
                    if(isset($rows)) {
                    $proveedor_id = $rows['proveedor_id'];
                    $name_proveedor = $rows['name'];
                    } else {
                        $name_proveedor = "";
                    }
                }
		return $name_proveedor;
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
		 $filePath = 'post_data_ediciones.json';
	 
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

		$prepared = $this->conn->prepare("SELECT po_no, username FROM po_list where id = ?");
		$prepared->bind_param("s", $id);
		$prepared->execute();
		$result = $prepared->get_result();
		$row = $result->fetch_assoc();
		$po_no = $row['po_no'];
		//$username = $row['username'];


		//$username = $_SESSION['userdata']['username'];

		$prepared = $this->conn->prepare("UPDATE po_list 
			SET required_date = ?, 
				discount_percentage = ?, 
				discount_amount = ?, 
				tax_percentage = ?, 
				tax_amount = ?, 
				notes = ?, 
				sub_total = ?, 
				total = ?
			WHERE id = ?");

		$prepared->bind_param("sddddsdds", $required_date, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $notes, $sub_total, $total, $id);
		$prepared->execute();

		
		
		$id = (int)$id;
		$supplier_id = (int)$supplier_id;

		#Si se creo la orden de compra entonces proceder a agregar los articulos a ella
		if(isset($row))
			for($x = 0; $x < count($item_id); $x++){
				// Solo modificamos los order item ids que ya existian en la base de datos, es decir no los que son default
				if($order_item_id[$x] != ""){
					if($delete[$x] == "false"){ 
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
					} else{
						$prepared = $this->conn->prepare("DELETE FROM order_items WHERE id = ?");
						$prepared->bind_param("i", $order_item_id[$x]);
						$prepared->execute();
					}
				} else{
					// Crea un nuevo order item
					$price = (float)$unit_price[$x];
					$quantity = (float)$qty[$x];
					$prepared = $this->conn->prepare("INSERT INTO order_items(quantity, description, unit_price, po_id, item_id, codigo_marca, codigo_departamento, url, proveedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
					$prepared->bind_param("dsdissssi", $quantity, $description[$x], $price, $id, $item_id[$x], $marca_id[$x], $departamento_id[$x], $url[$x], $supplier_id);
					$prepared->execute();
				}
			}

			$resp['status'] = 'success';
			$resp['id'] = $id;
			$resp['po_no'] = $po_no;
				//$this->guardar_adjunto($po_no);
				try {
					$resultado = enviar_email(["carlos.sisnett@prensa.com"], "Orden de compra $po_no ha sido modificada", "Orden de compra ha sido modificada con exito.", "desarrollo@prensa.com");

					
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

	function update_inventory_request(){
		extract($_POST);
		$data = "";

		
		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'data_edicion_inventario.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);
		
		 
		if(isset($notes) == false){
			$notes = null;
		}

		$username = $_SESSION['userdata']['username'];

		$prepared = $this->conn->prepare("UPDATE solicitud_de_inventario 
			SET required_date = ?, 
				notes = ?,
				notes_almacen = ?,
				estado_almacen = ?
			WHERE id = ?");

		$prepared->bind_param("sssss", $required_date, $notes, $notes_almacen, $estado_almacen, $id);
		$prepared->execute();

		
		$prepared = $this->conn->prepare("SELECT numero_solicitud FROM solicitud_de_inventario where id = ?");
		$prepared->bind_param("s", $id);
		$prepared->execute();
		$result = $prepared->get_result();
		$row = $result->fetch_assoc();
		$numero_solicitud = $row['numero_solicitud'];
		$id = (int)$id;
		
		//$supplier_id = (int)$supplier_id;

		#Si se creo la orden de compra entonces proceder a agregar los articulos a ella
		if(isset($row))
			for($x = 0; $x < count($item_id); $x++){
				// Solo modificamos los order item ids que ya existian en la base de datos, es decir no los que son default
				if($order_item_id[$x] != ""){
					if($delete[$x] == "false"){ 
					//$price = (float)$unit_price[$x];
					$quantity = (float)$qty[$x];
					$prepared = $this->conn->prepare("UPDATE inventory_items 
					SET quantity = ?, 
						solicitud_id = ?, 
						item_id = ?, 
						codigo_marca = ?, 
						codigo_departamento = ?
					
					WHERE id = ?");
				
					$prepared->bind_param("ddsssd", $quantity, $id, $item_id[$x], $marca_id[$x], $departamento_id[$x], $order_item_id[$x]);
					$prepared->execute();
					} else{
						$prepared = $this->conn->prepare("DELETE FROM inventory_items WHERE id = ?");
						$prepared->bind_param("i", $order_item_id[$x]);
						$prepared->execute();
					}
				} else{
					// Crea un nuevo order item
					//$price = (float)$unit_price[$x];
					$quantity = (float)$qty[$x];
					$prepared = $this->conn->prepare("INSERT INTO inventory_items(quantity, solicitud_id, item_id, codigo_marca, codigo_departamento) VALUES (?, ?, ?, ?, ?)");
					$prepared->bind_param("ddsss", $quantity, $id, $item_id[$x], $marca_id[$x], $departamento_id[$x]);
					$prepared->execute();
				}
			}

			$resp['status'] = 'success';
			$resp['id'] = $id;
			$resp['numero_solicitud'] = $numero_solicitud;
			$usuario_actualizador = $username;
			enviar_email_salida_de_mercancia_actualizacion($id, $usuario_actualizador, $estado_almacen);

					
					//if ($po_id != 233)
					//{$resultado = enviar_email2($po_id, $pos1DocEntry);}
					
					//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
					
					//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
					/*
				} catch (Exception $e) {
					echo "Error al enviar el correo: " . $e->getMessage();
				}
*/
		
		/*
		else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}

		*/
		return json_encode($resp);
	}

	/*
	Esta funcion verifica si se crearon las lineas de la solicitud de compra. Si tiene al menos 1 linea retorna true, de lo contrario retorna false
	*/

	function hay_lineas($id){
		$query = $this->conn->query("SELECT * FROM order_items where po_id = '{$id}'");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row; // Store each row in an array
		}
		if(count($rows) > 0){
			return true;
		} else{
			return false;
		}
	}

	function calcular_porcentaje_descuento($discount_amount, $sub_total){
		$porcentaje_descuento = ($discount_amount/$sub_total) * 100;
		return $porcentaje_descuento;
	}

	function log_message($message){
		$myFile = "log.txt"; 
		$log_file = fopen($myFile, 'a') or die("can't open file");
		fwrite($log_file, $message);
		fclose($log_file);
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


		if(is_numeric($discount_amount) == true and ($discount_amount > 0) == true){
			$discount_percentage = $this->calcular_porcentaje_descuento($discount_amount, $sub_total);
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


		// Calcular porcentaje de descuento aqui en su propia funcion


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
				
			if($this->hay_lineas($id) == false){
				$resp['status'] = 'failed';
				$resp['msg'] = "No se puede guardar la orden de compra porque no tiene líneas.";
				$this->settings->set_flashdata('failed',"Hubo un problema al insertar las líneas de la solicitud de compra, por favor crear una nueva.");
				return json_encode($resp);
			}

			$resp['status'] = 'success';
			$resp['id'] = $id;
			$resp['po_no'] = $po_no;

			$this->guardar_adjunto($po_no);

			if($this->es_pedido($id) == true){
				$this->conn->query("UPDATE `po_list` set pedido = 1, status = 3 where id = '{$id}' ");
				$this->notificar_a_aprobadores($id);
				enviar_email3($id);
				$this->settings->set_flashdata('success',"Orden de compra guardada correctamente");
			}

			else {
				$this->conn->query("UPDATE `po_list` set pedido = 0 where id = '{$id}' ");
				$ResultRequestSAP = sendPurchaseRequest($id);
					$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
					$pos0Msj = $ArrayResultRequestSAP[0];
					$pos1DocEntry = $ArrayResultRequestSAP[1];
					$pos2DocNum = $ArrayResultRequestSAP[2];
					$this->settings->set_flashdata('success',"Orden de compra guardada correctamente $pos0Msj");
					$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");

					try {
						$resultado = enviar_email2($id, $pos1DocEntry);
	
						
						//if ($po_id != 233)
						//{$resultado = enviar_email2($po_id, $pos1DocEntry);}
						
						//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
						
						//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
					} catch (Exception $e) {
						echo "Error al enviar el correo: " . $e->getMessage();
					}

			}
				#echo $Master->guardar_adjunto($pos2DocNum);
				//enviar_correo();
				
				

		} else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	/*
	Integer -> Array
	Retorna la lista de los departamentos de una solicitud de compra
	*/

	function departamentos_de_orden_de_compra($po_id){
		$query = $this->conn->query("SELECT codigo_departamento from order_items where po_id = '{$po_id}'");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row["codigo_departamento"]; // Store each row in an array
		}
		return $rows;
	}

	/*
		Retorna true si el departamento de la orden de compra esta en la lista de codes_to_check, false si no lo está
	*/

	function es_pedido($po_id){
		return true;

		/*
	
		$departamentos_de_solicitud = $this->departamentos_de_orden_de_compra($po_id);
		// 26 = usuario de Basilio Fernandez
		// 27 = usuario de Juan Planells
		// 133 = usuario de Anette Planells
		// 115 = usuario de Soodabeh Salence
		// 90 = usuario de Ramon Ali
		// 414 = usuario de Karol Saman

		$departamentos_que_usuario = array_column($this->departamentos_que_usuario_puede_aprobar(27), "departamento");
		$departamentos_usuario_2 = array_column($this->departamentos_que_usuario_puede_aprobar(133), "departamento");
		$departamentos_que_usuario_3 = array_column($this->departamentos_que_usuario_puede_aprobar(115), "departamento");
		$departamentos_que_usuario_4 = array_column($this->departamentos_que_usuario_puede_aprobar(26), "departamento");
		$departamentos_que_usuario_5 = array_column($this->departamentos_que_usuario_puede_aprobar(90), "departamento");
		$departamentos_que_usuario_6 = array_column($this->departamentos_que_usuario_puede_aprobar(414), "departamento");

		$todos_los_departamentos_gerentes = array_merge($departamentos_que_usuario, $departamentos_usuario_2, $departamentos_que_usuario_3, $departamentos_que_usuario_4, $departamentos_que_usuario_5, $departamentos_que_usuario_6);

		foreach ($departamentos_de_solicitud as $code) {
			if (in_array($code, $todos_los_departamentos_gerentes)) {
				return true;
			}
		}
		return false;

		*/
		
	}

		/*
	Integer -> Array
	Retorna la lista de los departamentos de una salida de inventario
	*/

	function departamentos_de_salida_de_inventario($si_id){
		$query = $this->conn->query("SELECT codigo_departamento from inventory_items where solicitud_id = '{$si_id}'");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row["codigo_departamento"]; // Store each row in an array
		}
		return $rows;
	}

	function es_salida_de_mercancia($si_id){
		// 47 = usuario de Carlos Sisnett
		// 8 = usuario de Nelvir Mirabal
		// 26 = usuario de Basilio Fernandez
		// 115 = usuario de Soodabeh Salence

		$departamentos_de_solicitud = $this->departamentos_de_salida_de_inventario($si_id);

		$departamentos_que_usuario = array_column($this->departamentos_que_usuario_puede_aprobar(115, "aprobacion"), "departamento");
		$departamentos_usuario_2 = array_column($this->departamentos_que_usuario_puede_aprobar(8, "aprobacion"), "departamento");
		$departamentos_usuario_3 = array_column($this->departamentos_que_usuario_puede_aprobar(47, "aprobacion"), "departamento");

		$todos_los_departamentos_gerentes = array_merge($departamentos_que_usuario, $departamentos_usuario_2, $departamentos_usuario_3);

		foreach ($departamentos_de_solicitud as $code) {
			if (in_array($code, $todos_los_departamentos_gerentes)) {
				return true;
			}
		}
		return false;

	}

	function pueden_aprobar_estos_gerentes($departamentos_por_aprobar){
		$sr_planells = $this->puede_este_usuario_aprobar_estos_departamentos(47, $departamentos_por_aprobar);
		$sra_planells = $this->puede_este_usuario_aprobar_estos_departamentos(8, $departamentos_por_aprobar);
		return $sr_planells || $sra_planells;
	}

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

	function departamentos_que_faltan_por_aprobar_inventario($si_id){
		$query = $this->conn->query("SELECT codigo_departamento from inventory_items where solicitud_id = '{$si_id}' and (status = 0 or status = 2 or status is null)");
		$rows = array(); // Initialize an empty array to store rows
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row; // Store each row in an array
		}
		return $rows;
	}

	/*
	Esta funcion devuelve un array con los departamentos que el usuario puede aprobar, es decir aquellos que tiene el usuario en la tabla aprobadores
	
	Number, String -> Array
	A la hora de aprobar solo el usuario del gerente general (sr planells) y de la sra planells puede aprobar cualquier departamento.
	A la hora de pasar departamentos a otro usuario se usa $tipo = "vacaciones" para que solo se pasen los departamentos del usuario que esta en la tabla aprobadores y
	no se pasen todos los departamentos en el caso que el sr o la sra planells se vayan de vacaciones
	133 = sra planells
	27 = sr planells
	*/

	function departamentos_que_usuario_puede_aprobar($user_id, $tipo){

		$query = "";
		if($tipo == "aprobacion" and ($user_id == 27 || $user_id == 133)){
			// El sr y la sra planells pueden aprobar todos los departamentos
			$query = $this->conn->query("SELECT departamento from aprobadores");
		} else {
			$query = $this->conn->query("SELECT departamento from aprobadores where user_id = '{$user_id}'");
		}

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
		// if gerente general approves consider it

		if($status == 0 or $status == 2 or $status == 3){
			return false;
		}

		$departamentos_por_aprobar = $this->departamentos_que_faltan_por_aprobar($id);
			$departamentos_que_usuario_puede_aprobar = $this->departamentos_que_usuario_puede_aprobar($user_id, "aprobacion");
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

	function is_solicitud_inventario_ready_to_be_approved($id, $user_id, $status){
		// if gerente general approves consider it

		if($status == 0 or $status == 2 or $status == 3){
			return false;
		}

		$departamentos_por_aprobar = $this->departamentos_que_faltan_por_aprobar_inventario($id);
			$departamentos_que_usuario_puede_aprobar = $this->departamentos_que_usuario_puede_aprobar($user_id, "aprobacion");
			// Extract only the department values
			$departamentos = array_column($departamentos_que_usuario_puede_aprobar, 'departamento');

			// Convert to a string formatted for SQL
			$departamentos_sql = "'" . implode("', '", $departamentos) . "'";

			$hora_aprobacion = date('Y-m-d H:i:s');
			//$departamentos_string = "'" . implode("', '", $departamentos_que_usuario_puede_aprobar) . "'";

			$this->conn->query("UPDATE `inventory_items` 
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

	/*
	Retorna true si el usuario puede aprobar el departamento recibido, false si no puede
	*/

	function puede_este_usuario_aprobar_este_departamento($user_id, $codigo_departamento){
		$query = $this->conn->query("SELECT * from aprobadores where user_id = '{$user_id}' and departamento = '{$codigo_departamento}' ");
		$rows = $query->fetch_assoc();
		if($rows == 1){
			return true;
		} else{
			return false;
		}
	}

	/*
	Retorna true si el usuario puede aprobar todos los departamentos recibidos, false si no puede
	*/

	function puede_este_usuario_aprobar_estos_departamentos($user_id, $departamentos){
		foreach($departamentos as $key => $value){
			if($this->puede_este_usuario_aprobar_este_departamento($user_id, $value) == false){
				return false;
			}
		}
		return true;
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
		$query = $this->conn->query("SELECT * from aprobaciones where user_id = '{$user_id}' and orden_compra_id = '{$po_id}' and estado = 1");
		$rows = $query->fetch_assoc();
		// Se usa mayor que 0 en caso de que algun aprobador haya aprobado mas de 1 vez
		if($rows > 0){
			return true;
		} else{
			return false;
		}
	}

	/*
	Esta funcion le notifica al solicitante, al aprobador, y a compras que la solicitud que el aprobador
	acaba de aprobar ha sido aprobada.

	Esta funcion recibe el id del usuario del aprobador y de la solicitud de compra
	*/

	function enviar_email_solicitud_aprobada($user_id, $po_id) {
		$user_query = $this->conn->query("SELECT * from users where id = '{$user_id}' ");
			$user_row = $user_query->fetch_assoc();

			$po_list_query = $this->conn->query("SELECT * from po_list where id = '{$po_id}' ");
			$po_list_row = $po_list_query->fetch_assoc();

			$solicitante_query = $this->conn->query("SELECT * from users where username = '{$po_list_row['username']}' ");
			$solicitante_row = $solicitante_query->fetch_assoc();
			$solicitante_email = $solicitante_row['email'];
			$to = [$user_row['email'], "compras@prensa.com", $solicitante_email, "carlos.sisnett@prensa.com"];
			
			$firstname = $user_row['firstname'];
			$lastname = $user_row['lastname'];
			$nombre_completo = $firstname . " " . $lastname;
			$no_sap = $po_list_row['SAPDocEntry'];
			$url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $po_id;
			$subject = "Solicitud de compra $no_sap ha sido aprobada por $nombre_completo";
			$link_element = "<a href='$url_orden'>Ver Solicitud de Compra $no_sap</a>";
			$body = "La orden de compra $no_sap ha sido aprobada por $nombre_completo.  <br> $link_element";
			enviar_email($to, $subject, $body, "Desarrollo Prensa");
			// Utilizar la plantilla que utilizamos cuando se crea una orden de compra aqui:
	}

	/*
	String -> Array || Boolean
	Dada el id de una orden de compra esta funcion retorna el ids de los aprobadores que pueden aprobar la orden de compra, si no hay aprobador retorna false
	*/

	function determinar_aprobadores($po_id){
		$query = $this->conn->query("SELECT codigo_departamento FROM order_items where po_id = $po_id;");
		if(gettype($query) == "boolean"){
			echo "";
			return false;
		}
	   while($row = $query->fetch_assoc()) {
			   $departamentos[] = $row['codigo_departamento'];
		   }
		//echo $rows;
		//$codigo_departamento = $rows['codigo_departamento'];
		$codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

		$aprobadores = array();
		foreach($departamentos as $departamento){
			$query_2 = $this->conn->query("SELECT user_id FROM aprobadores where departamento = '{$departamento}'");
			while ($row = $query_2->fetch_assoc()) {
				$aprobadores[] = $row['user_id'];
			}
		}

		return array_unique($aprobadores);

		/*
	
	   $aprobador = $this->conn->query("SELECT user_id FROM aprobadores 
	 	WHERE departamento IN ({$codigo_departamento_list})");

	 	$lista_aprobadores = array(); // Initialize an empty array to store rows
	   
		if($aprobador->num_rows == 0){
			echo "";
			return false;
		} else if($aprobador->num_rows > 0){
			$lista_aprobadores = $aprobador->fetch_array();
			return $lista_aprobadores;
		}
			*/
}

/*
	String -> Array || Boolean
	Dada el id de una orden de compra esta funcion retorna el ids de los aprobadores que pueden aprobar la orden de compra, si no hay aprobador retorna false
	*/

	function determinar_aprobadores_inventario($si_id){
		$query = $this->conn->query("SELECT codigo_departamento FROM inventory_items where solicitud_id = $si_id;");
		if(gettype($query) == "boolean"){
			echo "";
			return false;
		}
	   while($row = $query->fetch_assoc()) {
			   $departamentos[] = $row['codigo_departamento'];
		   }
		//echo $rows;
		//$codigo_departamento = $rows['codigo_departamento'];
		$codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

		$aprobadores = array();
		foreach($departamentos as $departamento){
			$query_2 = $this->conn->query("SELECT user_id FROM aprobadores where departamento = '{$departamento}'");
			while ($row = $query_2->fetch_assoc()) {
				$aprobadores[] = $row['user_id'];
			}
		}

		return array_unique($aprobadores);

	}

	/*
	Esta funcion le envia un email a los aprobadores cuando una solicitud ya esta lista para aprobar (estado 3)
	String -> Boolean

	Retorna true si la funcion fue exitosa, false si no se pudo enviar el email
	*/

	function notificar_a_aprobadores($po_id){
		$aprobadores = $this->determinar_aprobadores($po_id);
		if($aprobadores == false){
			enviar_email(["desarrollo@prensa.com"], "Solicitud $po_id no tiene aprobador", "Solicitud $po_id no tiene aprobador, por favor asignar uno al departamento que le corresponde y notificarle al aprobador que la solicitud está lista para aprobar", "Desarrollo Prensa");
			return false;
		}
		$to = array();
		foreach($aprobadores as $key => $value){
			$user_query = $this->conn->query("SELECT * from users where id = $value ");
			$user_row = $user_query->fetch_assoc();
			$to[] = $user_row['email'];
		}
		$to[] = "desarrollo@prensa.com";

		
	
		$po_list_query = $this->conn->query("SELECT * from po_list where id = '{$po_id}' ");
		$po_list_row = $po_list_query->fetch_assoc();
		$date_created = $po_list_row['date_created'];
		$proveedor = $this->obtener_proveedor($po_id);
		$username = $po_list_row['username'];

		$solicitante_query = $this->conn->query("SELECT * from users where username = '{$username}'");
		$solicitante_row = $solicitante_query->fetch_assoc();
		$firstname = $solicitante_row['firstname'];
		$lastname = $solicitante_row['lastname'];
		$nombre_completo = $firstname . " " . $lastname;

		//$numero_sap = $po_list_row['SAPDocEntry'];

		$title = "Solicitud de compra N° $po_id necesita su aprobación - $nombre_completo";
		$url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $po_id;
		$url_todas_ordenes = base_url . "admin/?page=all_purchase_orders";
		$todas_solicitudes = "<a href='$url_todas_ordenes'>Ver todas las Solicitudes de Compra pendiente por aprobación</a>";
		$body = "La solicitud de compra N° $po_id hecha por $nombre_completo necesita su aprobación. <br> Proveedor: $proveedor <br> <a href='$url_orden'>Ver Solicitud de Compra</a> <br>";
		
		enviar_email($to, $title, $body, "Desarrollo Prensa");
	}




	function notificar_a_aprobadores_inventario($si_id){
		$aprobadores = $this->determinar_aprobadores_inventario($si_id);
		if($aprobadores == false){
			enviar_email(["desarrollo@prensa.com"], "Salida de inventario $si_id no tiene aprobador", "Salida de inventario $si_id no tiene aprobador, por favor asignar uno al departamento que le corresponde y notificarle al aprobador que la solicitud está lista para aprobar", "Desarrollo Prensa");
			return false;
		}
		$to = array();
		foreach($aprobadores as $key => $value){
			$user_query = $this->conn->query("SELECT * from users where id = $value ");
			$user_row = $user_query->fetch_assoc();
			$to[] = $user_row['email']; 
		}

		$to[] = "desarrollo@prensa.com";
	
		$po_list_query = $this->conn->query("SELECT * from solicitud_de_inventario where id = '{$si_id}' ");
		$po_list_row = $po_list_query->fetch_assoc();
		//$numero_sap = $po_list_row['SAPDocEntry'];

		$title = "Salida de inventario $si_id necesita su aprobación";
		$url_orden = base_url . "admin/?page=inventario/view_si&id=" . $si_id;
		$url_todas_ordenes = base_url . "admin/?page=all_inventario";
		$todas_solicitudes = "<a href='$url_todas_ordenes'>Ver todas las Salidas de Inventario pendientes por aprobación</a>";
		$body = "La Salida de inventario $si_id necesita su aprobación. <br> <a href='$url_orden'>Ver Salida de inventario $si_id</a> <br>";
		
		enviar_email($to, $title, $body, "Desarrollo Prensa");
		return true;
	}



	function change_si_status(){
		extract($_POST);

		
		$jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		// Define the path to the external JSON file
		$filePath = 'si_status_data.json';
	
		// Write the JSON data to the file
		file_put_contents($filePath, $jsonData);
		

		if($status == 0 or $status == 2 or $status == 3){
			$save = $this->conn->query("UPDATE `solicitud_de_inventario` set status = '{$status}' where id = '{$id}' ");
		}

		$approved = $this->is_solicitud_inventario_ready_to_be_approved($id, $user_id, $status);

		// Cuando almacen mueve la salida de inventario a lista por aprobar no se sigue el flujo de aprobacion
		if($status != 3){
			$save_2 = $this->conn->query("INSERT INTO `aprobaciones_inventario` (user_id, solicitud_inventario_id, estado) VALUES ('{$user_id}', '{$id}', '{$status}') ");
			if($approved == true) {
				$save = $this->conn->query("UPDATE `solicitud_de_inventario` set status = '{$status}' where id = '{$id}' ");
				// Para que inventario pueda hacer modificaciones si es necesario antes de mandar a SAP,  no se envia a SAP hasta que ellos lo hagan manualmente
				//$response = crear_salida_de_mercancia($id);
				//$this->conn->query("UPDATE `solicitud_de_inventario` set SAPDocEntry = '{$response['DocEntry']}', SAPDocNum = '{$response['DocNum']}' where id = '{$id}' ");
				enviar_email_salida_de_inventario_aprobada($id);
			};
			
			
			if($save_2){
				$resp['status'] = 'success';
				if($approved == true) {
					$this->settings->set_flashdata('success',"salida de inventario aprobada correctamente.");
				} elseif ($status == 1  and $approved == false) {
					$this->settings->set_flashdata('success',"salida de inventario ha sido actualizada correctamente. Falta la aprobación de los otros departamentos.");
				} elseif ($status = 2 and $approved = false) {
				$this->settings->set_flashdata('success',"Estado de la salida de inventario actualizado correctamente.");
				}
				
			}
			else{
				$resp['status'] = 'failed';
				$resp['error'] = $this->conn->error;
			}

	} else{
		$this->notificar_a_aprobadores_inventario($id);
		$resp['status'] = 'success';
		$this->settings->set_flashdata('success',"Estado de la orden de compra actualizado correctamente.");
	}
		
		return json_encode($resp);

	}

	function change_po_status(){
		extract($_POST);

		
		$jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		// Define the path to the external JSON file
		$filePath = 'po_status_data.json';
	
		// Write the JSON data to the file
		file_put_contents($filePath, $jsonData);
		

		if($status == 0 or $status == 2 or $status == 3){
			$save = $this->conn->query("UPDATE `po_list` set status = '{$status}' where id = '{$id}' ");
		}

		$approved = $this->is_po_ready_to_be_approved($id, $user_id, $status);

		// Cuando compras mueve la orden de compra a lista por aprobar no se sigue el flujo de aprobacion
		if($status != 3){
			$save_2 = $this->conn->query("INSERT INTO `aprobaciones` (user_id, orden_compra_id, estado) VALUES ('{$user_id}', '{$id}', '{$status}') ");
			if($approved == true) {
				$save = $this->conn->query("UPDATE `po_list` set status = '{$status}' where id = '{$id}' ");
				$response = create_purchase_order($id);
				$this->conn->query("UPDATE `po_list` set SAPDocEntry = '{$response['DocEntry']}', SAPDocNum = '{$response['DocNum']}' where id = '{$id}' ");
				// cambiar correo que se envia:
				enviar_email_orden_de_compra_aprobada($id);
			};
			
			
			if($save_2){
				$resp['status'] = 'success';
				$resp['id'] = $id;
				if($approved == true) {
					$this->settings->set_flashdata('success',"Orden de compra aprobada correctamente.");
				} elseif ($status == 1  and $approved == false) {
					$this->settings->set_flashdata('success',"Orden de compra ha sido actualizada correctamente. Falta la aprobación de los otros departamentos.");
				} elseif ($status = 2 and $approved = false) {
				$this->settings->set_flashdata('success',"Estado de la orden de compra actualizado correctamente.");
				}
				
			}
			else{
				$resp['status'] = 'failed';
				$resp['error'] = $this->conn->error;
			}

	} else{
		$this->notificar_a_aprobadores($id);
		$resp['status'] = 'success';
		$this->settings->set_flashdata('success',"Estado de la orden de compra actualizado correctamente.");
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
			if(empty($id)){
				if($this->es_salida_de_mercancia($solicitud_id)){
					$this->conn->query("update `solicitud_de_inventario` set status = 3, salida_de_mercancia = 1 where id = '{$solicitud_id}'");
					enviar_email_salida_de_mercancia($solicitud_id); // notifica a almacen que se ha creado la salida de mercancia
					$this->notificar_a_aprobadores_inventario($solicitud_id); // notifica a los aprobadores que tienen una nueva salida por aprobar
					$this->settings->set_flashdata('success',"Salida de inventario guardada correctamente");
				} else{

					$ResultRequestSAP = enviar_solicitud_inventario($solicitud_id);
					$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
					$pos0Msj = $ArrayResultRequestSAP[0];
					$pos1DocEntry = $ArrayResultRequestSAP[1];
					$pos2DocNum = $ArrayResultRequestSAP[2];
					$this->settings->set_flashdata('success',"Salida de inventario guardada correctamente $pos0Msj");
					$this->conn->query("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$solicitud_id}'");
					try {
						$resultado = enviar_email_solicitud_inventario($solicitud_id, $pos1DocEntry);
						//$resultado = enviar_email(['nelvir.mirabal@prensa.com','nelvir.mirabal@prensa.com'], '2','3');
						
						//echo $resultado; // Salida: Correo enviado para PO ID: 123 con SAP: SAP456789
					} catch (Exception $e) {
						echo "Error al enviar el correo: " . $e->getMessage();
					}
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

	function enviar_solicitud_de_inventario_a_sap(){
		extract($_POST);
		$data = "";

		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		 try{
			$ResultRequestSAP = enviar_solicitud_inventario($solicitud_id);
			$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
			$pos0Msj = $ArrayResultRequestSAP[0];
			$pos1DocEntry = $ArrayResultRequestSAP[1];
			$pos2DocNum = $ArrayResultRequestSAP[2];
			$this->settings->set_flashdata('success',"Salida de inventario guardada correctamente $pos0Msj");
			$this->conn->query("update `solicitud_de_inventario` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$solicitud_id}'");

		 }
		 catch (Exception $e) {
			$resp['status'] = 'failed';
			$resp['err'] = $e->getMessage();
			$this->settings->set_flashdata('failed',"Error al enviar la solicitud de inventario a SAP. ".$e->getMessage());
			return json_encode($resp);
		 }
		 $resp['status'] = 'success';
		 $resp['msg'] = "Salida de inventario enviada correctamente $pos0Msj";
		
		return json_encode($resp);
	}



	function enviar_salida_de_mercancia_a_sap(){
		extract($_POST);
		$data = "";

		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		 try{
			$response = crear_salida_de_mercancia($solicitud_id);
			$this->conn->query("UPDATE `solicitud_de_inventario` set SAPDocEntry = '{$response['DocEntry']}', SAPDocNum = '{$response['DocNum']}', estado_almacen = 4 where id = '{$solicitud_id}' ");
			$this->settings->set_flashdata('success',"Salida de inventario enviada correctamente");
		 }
		 catch (Exception $e) {
			$resp['status'] = 'failed';
			$resp['err'] = $e->getMessage();
			$this->settings->set_flashdata('failed',"Error al enviar la solicitud de inventario a SAP. ".$e->getMessage());
			return json_encode($resp);
		 }
		 $resp['status'] = 'success';
		 $resp['msg'] = "Salida de inventario enviada correctamente";
		
		return json_encode($resp);
	}


	function enviar_solicitud_de_compra_a_sap(){
		extract($_POST);
		$data = "";

		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		 try{
				$ResultRequestSAP = sendPurchaseRequest($id);
				$ArrayResultRequestSAP = explode("|",$ResultRequestSAP);
				$pos0Msj = $ArrayResultRequestSAP[0];
				$pos1DocEntry = $ArrayResultRequestSAP[1];
				$pos2DocNum = $ArrayResultRequestSAP[2];
				$this->settings->set_flashdata('success',"Solicitud de compra enviada correctamente a SAP: $pos1DocEntry");
				$this->conn->query("update `po_list` set SAPDocEntry = '{$pos1DocEntry}',  SAPDocNum = '{$pos2DocNum}' where id = '{$id}'");

		 }
		 catch (Exception $e) {
			$resp['status'] = 'failed';
			$resp['err'] = $e->getMessage();
			$this->settings->set_flashdata('failed',"Error al enviar la solicitud de compra a SAP. ".$e->getMessage());
			return json_encode($resp);
		 }
		 $resp['status'] = 'success';
		 $resp['msg'] = "Orden de compra enviada correctamente $pos0Msj";
		 $resp['id'] = $id;

		 return json_encode($resp);
	}

	function enviar_pedido_a_sap(){
		extract($_POST);
		$data = "";

		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		 try{
			$response = create_purchase_order($id);
			$this->conn->query("UPDATE `po_list` set SAPDocEntry = '{$response['DocEntry']}', SAPDocNum = '{$response['DocNum']}' where id = '{$id}' ");
			$resp['status'] = 'success';
			$resp['msg'] = "Orden de compra enviada correctamente a SAP: {$response['DocEntry']}";
			$this->settings->set_flashdata('success',"Orden de compra enviada correctamente a SAP: {$response['DocEntry']}");

		 }catch (Exception $e) {
			$resp['status'] = 'failed';
			$resp['err'] = $e->getMessage();
			$this->settings->set_flashdata('failed',"Error al enviar la Pedido de compra a SAP. ".$e->getMessage());
			return json_encode($resp);
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
		
		 // Encode the $_POST array into JSON
		 $jsonData = json_encode($_POST, JSON_PRETTY_PRINT);

		 // Define the path to the external JSON file
		 $filePath = 'post_data.json';
	 
		 // Write the JSON data to the file
		 file_put_contents($filePath, $jsonData);

		if(isset($exclusivo_compras) == false){
			$exclusivo_compras = false;
		}


		if($exclusivo_compras == "on"){
			$exclusivo_compras = true;
		}

		if(isset($exclusivo_inventario) == false){
			$exclusivo_inventario = false;
		}

		if($exclusivo_inventario == "on"){
			$exclusivo_inventario = true;
		}

		
		
		$save = $this->conn->query("INSERT INTO `aprobadores` (`user_id`,`departamento`, `exclusivo_compras`, `exclusivo_inventario`) VALUES ('{$user_id}','{$departamento_id}', '{$exclusivo_compras}', '{$exclusivo_inventario}') ");
		if($save){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Aprobador guardado correctamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function update_approver_vacation(){
		extract($_POST);
		$departamentos_usuario = $this->departamentos_que_usuario_puede_aprobar($gerente_id, "vacaciones");
		$departamentos = array_column($departamentos_usuario, 'departamento');
		foreach($departamentos as $key => $value){
			$save = $this->conn->query("INSERT INTO `aprobadores` (`user_id`,`departamento`) VALUES ('{$user_id}','{$value}') ");
		}
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
	case 'change_si_status':
		echo $Master->change_si_status();
	break;
	case 'update_approver':
		echo $Master->update_approver();
	break;
	case 'edit_po':
		echo $Master->edit_po();
	break;

	case 'update_inventory_request':
		echo $Master->update_inventory_request();
	break;

	case 'enviar_solicitud_de_inventario_a_sap':
		echo $Master->enviar_solicitud_de_inventario_a_sap();
	break; 

	case 'enviar_salida_de_mercancia_a_sap':
		echo $Master->enviar_salida_de_mercancia_a_sap();
	break;

	case 'enviar_solicitud_de_compra_a_sap':
		echo $Master->enviar_solicitud_de_compra_a_sap();
	break;

	case 'enviar_pedido_a_sap':
		echo $Master->enviar_pedido_a_sap();
	break;

	case 'update_approver_vacation':
		echo $Master->update_approver_vacation();
	break;
	
	default:
		// echo $sysset->index();
		break;
}