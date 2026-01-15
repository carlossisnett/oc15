<?php

GLOBAL $conn;
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn ->query("SELECT * from `po_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<style>
    span.select2-selection.select2-selection--single {
        border-radius: 0;
        padding: 0.25rem 0.5rem;
        padding-top: 0.25rem;
        padding-right: 0.5rem;
        padding-bottom: 0.25rem;
        padding-left: 0.5rem;
        height: auto;
    }
	/* Chrome, Safari, Edge, Opera */
		input::-webkit-outer-spin-button,
		input::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
		}
		/*
		 Firefox 
		input[type=number] {
		-moz-appearance: textfield;
		}
		[name="tax_percentage"],[name="discount_percentage"]{
			width:5vw;
		}*/
</style>

<?php
$id_usuario = $_settings->userdata('id');
$qry = $conn->query("SELECT * from `users` where id = '$id_usuario' ");
$qry = $qry->fetch_array();
if($qry['codSAP'] == null){
  echo "<script> alert('ADVERTENCIA: Su usuario no tiene código de SAP, por favor contactar a desarrollo@prensa.com para que le asignen uno antes de realizar órdenes de compra') </script>";
}
?>
<div class="card card-outline card-info">
	<div class="card-header">
		<!-- <h3 class="card-title" id="title"><?php echo isset($id) ? "Actualizar los detalles de la solicitud orden de compra": "Nueva solicitud de compra" ?> </h3> -->
		<h3 class="card-title" id="title"> Nueva solicitud de compra </h3>
	</div>
	<div class="card-body">
		
		<form action="" id="po-form">
			<input type="hidden" id="id_element" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
			<div class="row">
			<div class="col-md-6 form-group">
					<label for="supplier_id">Proveedor</label>
					<select name="supplier_id" id="supplier_id" class="custom-select custom-select-sm rounded-0 select2" required>
						<option value="" selected <?php /* echo !isset($supplier_id) ? "selected" :'' */ ?>></option>
						<?php 

						// Solo corre cuando ya tenemos una orden de compra creada:
						if(isset($id)){
							
						$query = $conn->query("SELECT o.*, p.name FROM order_items o JOIN proveedores p ON p.id = o.proveedor_id where o.po_id = '{$_GET['id']}' LIMIT 1;");
						if(gettype($query) == "boolean"){
							echo "";
						} else {
						$rows = $query->fetch_array();
						if(isset($rows)) {
						$proveedor_id = $rows['proveedor_id'];
						$name_proveedor = $rows['name'];
						} else {
							$name_proovedor = "";
						}
					}
						}
							
							$supplier_qry = $conn->query("SELECT * FROM `proveedores` order by `name` asc");
							while($row = $supplier_qry->fetch_assoc()):
							
						?>
						<option value="<?php  echo $row['id']  ?>" <?php  echo isset($proveedor_id) && $proveedor_id == $row['id'] ? 'selected' : ''  ?> <?php  echo $row['status'] == 0? 'disabled' : ''  ?>><?php  echo($row['codSAP']); echo(" "); echo($row['name']);  ?></option>
						<?php  endwhile;  ?>
					</select>
				</div>
				<div class="col-md-6 form-group">
					<label for="po_no">Solicitud # <span class="po_err_msg text-danger"></span></label>
					<input type="text" class="form-control form-control-sm rounded-0" id="po_no" name="po_no" value="<?php echo isset($po_no) ? $po_no : '' ?>" disabled>
					 <!--<small><i>Deja este espacio en blanco para generar automáticamente al guardar.</i></small>-->
				</div>
							</div>
			<div class="row">
				<div class="col-md-6 form-group">
            <label for="required_date">Fecha necesaria <span class="text-danger">*</span></label>
            <input 
                type="date" 
                class="form-control form-control-sm rounded-0" 
                id="required_date" 
                name="required_date" 
                value="<?php echo date('Y-m-d'); ?>" 
    			min="<?php echo date('Y-m-d'); ?>" 
                required 
                title="Fecha en la que requiere el producto o servicio"
            >
        </div>
			</div>
			<div class="col-md-12">
			<table class="table table-striped table-bordered" id="item-list">
						<colgroup>
							<col width="5%">
							<col width="5%">
							<col width="15%">
							<col width="15%">
							<col width="15%">
							<col width="15%">
							<col width="15%">
							<col width="7.5%">
							<col width="7.5%">
						</colgroup>
						<thead>
							<tr class="bg-navy disabled">
								<th class="px-1 py-1 text-center"></th>
								<th class="px-1 py-1 text-center">Cantidad</th>
								<th class="px-1 py-1 text-center">Búsqueda del Artículo</th>
								<th class="px-1 py-1 text-center">Descripción (opcional)</th>
								<th class="px-1 py-1 text-center">Marca</th>
								<th class="px-1 py-1 text-center">Departamento</th>
								<th class="px-1 py-1 text-center">Enlace🌐 (opcional)</th>
								<th class="px-1 py-1 text-center">Precio</th>
								<th class="px-1 py-1 text-center">Total</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							if(isset($id)):
							//$order_items_qry = $conn->query("SELECT o.*,i.name, i.description, i.codSAP FROM `order_items` o inner join item_list i on o.item_id = i.id where o.`po_id` = '$id' ");
							$order_items_qry = $conn->query("SELECT o.*,i.name, o.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
                            FROM `order_items` o 
                            inner join item_list i on o.item_id = i.id 
                            inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
                            inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
                            where o.`po_id` = '$id' ");
							echo $conn->error;
							while($row = $order_items_qry->fetch_assoc()):
								$marca_id = $row['codigo_marca'];
								$departamento_id = $row['codigo_departamento'];
								$order_item_id = $row['id'];
							?>
							<tr class="po-item" data-id="">
								<input type="hidden" name="order_item_id[]" value="<?php echo $order_item_id ?>">
								<input type="hidden" name="delete[]" value="false">

								<!--Botón Remover Item-->
								<td class="align-middle p-1 text-center">
									<button class="btn btn-sm btn-danger py-0" type="button" onclick="rem_item($(this))"><i class="fa fa-times"></i></button>
								</td>
								<!--Campo Cantidad-->
								<td class="align-middle p-0 text-center">
									<input type="number" min="0.01" class="text-center w-100 border-0" step="any" name="qty[]" value="<?php echo $row['quantity'] ?>" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]" value="<?php echo $row['item_id'] ?>" required>
									<input type="text" placeholder="Escriba el nombre o el código del artículo para buscar" class="text-center w-100 border-0 item_id" value="<?php echo $row['nombre_item'] ?>" required/>
								</td>

								<td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" name="description[]" value="<?php echo isset($row['description']) ? ($row['description']) : "" ?>" />
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
								<select name="marca_id[]" class="custom-select custom-select-sm rounded-0 select2" required>
								
									<?php
									$marcas_query = $conn->query("SELECT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 1 and activo = 'Y' order by `nombre_ccosto`");
									while($row_2 = $marcas_query->fetch_assoc()):
									?>
								<option value="<?php  echo $row_2['codigo_ccosto']  ?>" <?php  echo isset($marca_id) && $marca_id == $row_2['codigo_ccosto'] ? 'selected' : ''  ?>> <?php  echo($row_2['nombre_ccosto']);?> </option>
									
								<?php  endwhile;  ?>
								</select>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">

								<select name="departamento_id[]" class="custom-select custom-select-sm rounded-0 select2" required>
									<?php
									$departamentos_query = $conn->query("SELECT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 2 and activo = 'Y' order by `nombre_ccosto`");
									while($row_2 = $departamentos_query->fetch_assoc()):
									?>
								<option value="<?php  echo $row_2['codigo_ccosto']  ?>" <?php  echo isset($departamento_id) && $departamento_id == $row_2['codigo_ccosto'] ? 'selected' : ''  ?>> <?php  echo($row_2['nombre_ccosto']);?> </option>
								<?php  endwhile;  ?>
								</td>
								<td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" name="url[]" value="<?php echo isset($row['url']) ? ($row['url']) : "" ?>" />
								</td>
								<td class="align-middle p-1">
									<input type="number" step="any" min="0.01" class="text-right w-100 border-0" name="unit_price[]"  value="<?php echo ($row['unit_price']) ?>" required/>
								</td>
								<td class="align-middle p-1 text-right total-price"><?php echo number_format($row['quantity'] * $row['unit_price']) ?></td>
							</tr>
							<?php endwhile;endif; ?>
						</tbody>
						<tfoot>
							<tr class="bg-lightblue">
								<tr>
									<th class="p-1 text-right" colspan="8"><span><button class="btn btn btn-sm btn-flat btn-primary py-0 mx-1" type="button" id="add_row">Agregar Fila</button></span> Sub Total</th>
									<th class="p-1 text-right">
									<input type="text" name="sub_total" id="sub_total" class="w-100 border-0 text-right" readonly>
									</th>
								</tr>
								<tr>
									<th class="p-1 text-right" colspan="8">Descuento (%)
									<input type="number" step="any" name="discount_percentage" class="border-light text-right" value="<?php echo isset($discount_percentage) ? $discount_percentage : null ?>">
									</th>
									<th class="p-1"><input type="text" class="w-100 border-0 text-right" value="<?php echo isset($discount_amount) ? $discount_amount : null ?>" name="discount_amount"></th>
								</tr>
								<tr>
									<th class="p-1 text-right" colspan="8">Impuestos (%)
									<!--<input type="number" step="any" name="tax_percentage" class="border-light text-right" value="<?php echo isset($tax_percentage) ? $tax_percentage : null ?>">-->
									<input type="number" step="any" name="tax_percentage" class="border-light text-right" 
        							value="<?php echo isset($tax_percentage) && $tax_percentage != 0 ? $tax_percentage : '' ?>">
									</th>
									<th class="p-1"><input type="text" class="w-100 border-0 text-right" value="<?php echo isset($tax_amount) ? $tax_amount : null ?>" name="tax_amount"></th>
								</tr>
								<tr>
									<th class="p-1 text-right" colspan="8">Total</th>
									<th class="p-1 text-right"><input type="text" class="w-100 border-0 text-right" name="total" id="total" readonly></th>
								</tr>
							</tr>
						</tfoot>
					</table>
					<div class="row">
						<div class="col-md-6">
							<label for="notes" class="control-label">Notas</label>
							<textarea maxlength="235" name="notes" id="notes" cols="10" rows="4" class="form-control rounded-0"><?php echo isset($notes) ? $notes : '' ?></textarea>
										<div id="char-count">0 / 230</div>
						</div>
						<div class="col-md-6">
							<label for="status" class="control-label">Estado</label>
							<select name="status" id="status" class="form-control form-control-sm rounded-0" disabled>
								<option value="0" <?php echo isset($status) && $status == 0 ? 'selected': '' ?>>Pendiente</option>
								<option value="1" <?php echo isset($status) && $status == 1 ? 'selected': '' ?>>Aprobado</option>
								<option value="2" <?php echo isset($status) && $status == 2 ? 'selected': '' ?>>Negado</option>
							</select>
						</div>
					</div>
			<div class="row">
                <div class="col-md-6">
				<div class="pdf-container">
				<label for="ruta_adjunto" class="control-label">
								Adjuntar archivos (Solo PDF):
							</label>
                <?php
				$number_of_files = 1;
                    if(isset($ruta_adjunto)){
                        $files = scandir($ruta_adjunto);
                        $files = array_diff($files, array('.', '..')); // Remove . and ..
                        //$only_files = array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'pdf'); // Filter only PDFs
							//echo "<label>Adjuntos</label>";
							//$number_of_files = 1;
							foreach ($files as $file) {
								echo '<div style="display: flex; align-items: center; gap: 10px;">
									<a id="link_adjunto_' . $number_of_files . '" href="' . $ruta_adjunto . '/' . $file . '" target="_blank">' . $file . '</a>
									<input hidden="true" type="file"  id="ruta_adjunto_' . $number_of_files . '" name="ruta_adjunto_' . $number_of_files . '" accept="application/pdf" class="form-control form-control-file" style="flex: 1; height: 40px;">
									<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(' . $number_of_files . ')">
										Borrar 🗑️
									</button>
								</div><br>';

								$number_of_files++;
							}
						
                    }
                        ?>
                    
				
				
						
						
							<?php 

							if($number_of_files == 1) {
								$i = 1;
								echo '
									<div id="contenedor_adjunto_' . $i . '" style="display: flex; align-items: center; gap: 10px;">
									<input type="file" id="ruta_adjunto_' . $i . '" name="ruta_adjunto_' . $i . '" accept="application/pdf" class="form-control form-control-file" style="flex: 1; height: 40px;">
									<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(' . $i . ')">
										Borrar 🗑️
									</button>
									</div>
									';
								
								for($i = 2; $i <= 10; $i++){
									echo '
									<div id="contenedor_adjunto_' . $i . '" style="display: flex; align-items: center; gap: 10px;" hidden="true">
									<input type="file" id="ruta_adjunto_' . $i . '" name="ruta_adjunto_' . $i . '" accept="application/pdf" class="form-control form-control-file" style="flex: 1; height: 40px;">
									<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(' . $i . ')">
										Borrar 🗑️
									</button>
									</div>
									';
								}
									
							} else {
								//$i = 1;
								
								/*if(isset($number_of_files) == false){
								echo '
									<div id="contenedor_adjunto_' . $i . '" style="display: flex; align-items: center; gap: 10px;">
									<input type="file" id="ruta_adjunto_' . $i . '" name="ruta_adjunto_' . $i . '" accept="application/pdf" class="form-control form-control-file" style="flex: 1; height: 40px;">
									<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(' . $i . ')">
										Borrar 🗑️
									</button>
									</div>
									';
							}*/
									for($i = $number_of_files; $i <= 10; $i++){
										echo '
										<div id="contenedor_adjunto_' . $i . '" style="display: flex; align-items: center; gap: 10px;" hidden="true">
										<input type="file" id="ruta_adjunto_' . $i . '" name="ruta_adjunto_' . $i . '" accept="application/pdf" class="form-control form-control-file" style="flex: 1; height: 40px;">
										<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(' . $i . ')">
											Borrar 🗑️
										</button>
										</div>
										';
									}
								}
						
							?>
							</div>
							
							<div> <button id="otro_adjunto" class="btn btn-flat btn-default"> Agregar otro adjunto </button> </div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
	<div class="card-footer">
		<button class="btn btn-flat btn-primary" form="po-form" id="guardar_boton">Guardar</button>
		<a class="btn btn-flat btn-default" href="?page=purchase_orders">Cancelar</a>
		<button><a href="?page=purchase_orders/manage_po_compras&id=<?php echo $id ?>&edit=true"> Modo avanzado</a> </button>
	</div>
</div>
<table class="d-none" id="item-chlone">
	<tr class="po-item" data-id="">
		<td class="align-middle p-1 text-center">
			<button class="btn btn-sm btn-danger py-0" type="button" onclick="rem_item($(this))"><i class="fa fa-times"></i></button>
		</td>
		<td class="align-middle p-0 text-center">
			<input type="number" class="text-center w-100 border-0" step="any" name="qty[]"/>
		</td>
		<td class="align-middle p-1">
			<input type="text" class="text-center w-100 border-0" name="unit[]"/>
		</td>
		<td class="align-middle p-1">
			<input type="hidden" name="item_id[]">
			<input type="text" class="text-center w-100 border-0 item_id" required/>
		</td>
		<td class="align-middle p-1 item-description"></td>
		<td class="align-middle p-1 item-url"> <input type="text" name="url[]" class="text-center w-100 border-0 item_url"/></td>
		<td class="align-middle p-1">
			<input type="number" step="any" class="text-right w-100 border-0" name="unit_price[]" value="0"/>
		</td>
		<td class="align-middle p-1 text-right total-price">0</td>
	</tr>
</table>
<table class="d-none" id="item-clone">
<tr class="po-item" data-id="">
<input type="hidden" name="delete[]" value="false">
<input type="hidden" name="order_item_id[]" value="">
								<!--Botón Remover Item-->
								<td class="align-middle p-1 text-center">
									<button class="btn btn-sm btn-danger py-0" type="button" onclick="rem_item($(this))"><i class="fa fa-times"></i></button>
									
								</td>
								
								<!--Campo Cantidad-->
								<td class="align-middle p-0 text-center">
									<input type="number" min="0.01" class="text-center w-100 border-0" step="any" name="qty[]" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]">
									<input type="text" placeholder="Escriba el nombre o el código del artículo para buscar" class="text-left w-100 border-0 item_id" required/>
								</td>

								<td class="align-middle p-1">
									<input type="text" id="description_input" name="description[]" class="text-left w-100 border-0" />
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
								<select name="marca_id[]" class="custom-select custom-select-sm rounded-0 select2" required>
								<option value="" selected disabled>-- Escoge una marca --</option>
									<?php
									$marcas_query = $conn->query("SELECT DISTINCT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 1 and activo = 'Y' order by `nombre_ccosto`");
									while($row_2 = $marcas_query->fetch_assoc()):
									?>
								<option value="<?php  echo $row_2['codigo_ccosto']  ?>"> <?php  echo($row_2['nombre_ccosto']);?> </option>
								<?php  endwhile;  ?>
									</select>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
								<select name="departamento_id[]" class="custom-select custom-select-sm rounded-0 select2" required>
								<option value="" selected disabled>-- Escoge un departamento --</option >
									<?php
									$marcas_query = $conn->query("SELECT DISTINCT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 2 and activo = 'Y' order by `nombre_ccosto`");
									while($row_2 = $marcas_query->fetch_assoc()):
									?>
								<option value="<?php  echo $row_2['codigo_ccosto']  ?>"> <?php  echo($row_2['nombre_ccosto']);?> </option>
								<?php  endwhile;  ?>
									</select>
								</td>
								<td class="align-middle p-1">
									<input type="text" name="url[]" class="text-left w-100 border-0" />
								</td>
								<td class="align-middle p-1">
									<input type="number" step="any" min="0.01" class="text-right w-100 border-0" name="unit_price[]" required>
								</td>
								<td class="align-middle p-1 text-right total-price">0</td>
							</tr>
</table>

<script>
const textarea = document.getElementById("notes");
const counter = document.getElementById("char-count");
const maxLength = 235;

// Update counter on page load
counter.textContent = `${textarea.value.length} / ${maxLength}`;

textarea.addEventListener("input", function() {
  const currentLength = this.value.length;
  
  // Enforce max length
  if (currentLength > maxLength) {
    this.value = this.value.substring(0, maxLength);
  }
  
  counter.textContent = `${this.value.length} / ${maxLength}`;
});

	function rem_item(_this){
		if(es_editado()){
			let row = _this.closest('tr'); 
    		row.find('input[name="delete[]"]').val("true"); // Change value to "true"
    		row.hide(); // Hide the row
		} else{
		_this.closest('tr').remove()
		}
	}
	function calculate(){
		var _total = 0
		$('.po-item').each(function(){
			var qty = $(this).find("[name='qty[]']").val()
			var unit_price = $(this).find("[name='unit_price[]']").val()
			var row_total = 0;
			if(qty > 0 && unit_price > 0){
				row_total = parseFloat(qty) * parseFloat(unit_price)
			}
			$(this).find('.total-price').text(parseFloat(row_total).toLocaleString('en-US'))
		})
		$('.total-price').each(function(){
			var _price = $(this).text()
				_price = _price.replace(/\,/gi,'')
				_total += parseFloat(_price)
		})
		var discount_perc = 0
		if($('[name="discount_percentage"]').val() > 0){
			discount_perc = $('[name="discount_percentage"]').val()
		}
		var discount_amount = Math.round(_total * (discount_perc))/100;
		$('[name="discount_amount"]').val(parseFloat(discount_amount).toLocaleString("en-US"))
		var tax_perc = 0
		if($('[name="tax_percentage"]').val() > 0){
			tax_perc = $('[name="tax_percentage"]').val()
		}
		var tax_amount = Math.round((_total - discount_amount) * (tax_perc))/100;
		$('[name="tax_amount"]').val(parseFloat(tax_amount).toLocaleString("en-US"))
		$('#sub_total').val(parseFloat(_total).toLocaleString("en-US"))
		$('[name="total"]').val(parseFloat(_total - discount_amount + tax_amount).toLocaleString("en-US"))
	}

	function calculate_amount(){
		var _total = 0
		$('.po-item').each(function(){
			var qty = $(this).find("[name='qty[]']").val()
			var unit_price = $(this).find("[name='unit_price[]']").val()
			var row_total = 0;
			if(qty > 0 && unit_price > 0){
				row_total = parseFloat(qty) * parseFloat(unit_price)
			}
			$(this).find('.total-price').text(parseFloat(row_total).toLocaleString('en-US'))
		})
		$('.total-price').each(function(){
			var _price = $(this).text()
				_price = _price.replace(/\,/gi,'')
				_total += parseFloat(_price)
		})

		discount_amount = 0;
		if($('[name="discount_amount"]').val() != 0){
			discount_amount = parseFloat(document.querySelector('input[name="discount_amount"]').value);
			discount_percentage = Math.round(((discount_amount / _total) * 100));
			document.querySelector('input[name="discount_percentage"]').value = discount_percentage;
		}

		
		if($('[name="discount_amount"]').val() === "" ){
			document.querySelector('input[name="discount_percentage"]').value = null;
		}

		tax_amount = 0;
		if($('[name="tax_amount"]').val() != 0){
			tax_amount = parseFloat(document.querySelector('input[name="tax_amount"]').value);
			tax_percentage = Math.round((tax_amount / (_total - discount_amount)) * 100);
			document.querySelector('input[name="tax_percentage"]').value = tax_percentage;
		}

		if($('[name="tax_amount"]').val() === "" ){
			document.querySelector('input[name="tax_percentage"]').value = null;
		}

		$('#sub_total').text(parseFloat(_total).toLocaleString("en-US"))
		total_amount = _total - discount_amount + tax_amount;
		$('[name="total"]').val(parseFloat(total_amount).toLocaleString("en-US"))
	}

	function _autocomplete(_item){
		_item.find('.item_id').autocomplete({
			source:function(request, response){
					if(request.term.length < 3){
						return;
					}


				$.ajax({
					url:_base_url_+"classes/Master.php?f=search_items",
					method:'POST',
					data:{q:request.term},
					dataType:'json',
					error:err=>{
						console.log(err)
					},
					success:function(resp){
						response(resp)
					}
				})
			},
			select:function(event,ui){
				console.log(ui)
				_item.find('input[name="item_id[]"]').val(ui.item.id)
				_item.find('.item-description').text(ui.item.description)
				_item.find('.item_id').data('selected', true); // Marca como válido
				_item.find('.item_id').css('background-color', 'white');
			},
        change: function (event, ui) {
            if (!ui.item) {
                // Si no se seleccionó un valor válido del autocomplete
                _item.find('input[name="item_id[]"]').val("");
                _item.find('.item-description').text("");
                _item.find('.item_id').data('selected', false); // Marca como inválido
				_item.find('.item_id').css('background-color', 'yellow');
				_item.find('.item_id').val("");
                alert("Por favor, selecciona un artículo válido de la lista.");
            }
        }
		})
	}

	

	function _autocompleteMarca(_item){

_item.find('.marca_id').autocomplete(
	{
	select:function(event,ui){
		console.log(ui)
		_item.find('input[name="marca_id[]"]').val(ui.item.id)
		_item.find('.marca-description').text(ui.item.description)
	},
	change: function (event, ui) {
            if (!ui.item) {
                // Si no se seleccionó un valor válido del autocomplete
                _item.find('input[name="marca_id[]"]').val("");
                _item.find('.marca-description').text("");
                _item.find('.marca_id').data('selected', false); // Marca como inválido
                alert("Por favor, selecciona un artículo válido de la lista.");
            }
        }
});

}


function _autocompleteDepartamento(_item){

_item.find('.departamento_id').autocomplete({
source:function(request, response){
$.ajax({
	url:_base_url_+"classes/Master.php?f=search_departamento",
	method:'POST',
	data:{q:request.term},
	dataType:'json',
	error:err=>{
		console.log(err)
	},
	success:function(resp){
		response(resp)
	}
})
},
select:function(event,ui){
console.log(ui)
_item.find('input[name="departamento_id[]"]').val(ui.item.id)
_item.find('.departamento-description').text(ui.item.description)
},
change: function (event, ui) {
            if (!ui.item) {
                // Si no se seleccionó un valor válido del autocomplete
                _item.find('input[name="departamento_id[]"]').val("");
                _item.find('.departamento-description').text("");
                _item.find('.departamento_id').data('selected', false); // Marca como inválido
                alert("Por favor, selecciona un artículo válido de la lista.");
            }
        }
});

}

let adjunto = <?php echo($number_of_files + 1);?>;

document.getElementById("otro_adjunto").addEventListener("click", function(event){
  event.preventDefault()
  let adjunto_element = document.getElementById('contenedor_adjunto_' + adjunto.toString());
	adjunto_element.hidden = false;
	adjunto = adjunto + 1;
});

/*

document.getElementById("boton_borrar_adjunto").addEventListener("click", function(event){
  event.preventDefault()
});
*/

const deleteButtons = document.querySelectorAll('.boton-borrar');

deleteButtons.forEach(button => {
  button.addEventListener('click', function(event) {
    event.preventDefault() 
  });
});


function erase_adjunto(id){
	let adjunto_element = document.getElementById('ruta_adjunto_' + id.toString());
	let link_adjunto = document.getElementById('link_adjunto_' + id.toString());
	if(link_adjunto != null){
		link_adjunto.hidden = true;
		adjunto_element.hidden = false;
	} else{
		adjunto_element.value = "";
	}
}

function es_duplicado(){
	const queryString = window.location.search;
	if(queryString.includes("duplicate=true") == true){
		return true;
	} else{
			return false;
		}
}

function es_editado(){
	const queryString = window.location.search;
	if(queryString.includes("edit=true") == true){
		return true;
	} else{
			return false;
		}
}

var proceder_sin_adjunto = false;

	$(document).ready(function(){
/*

		$('.select2').select2({
    dropdownParent: $('body'),
    dropdownAutoWidth: true,
    width: '100%',
    positionDropdown: 'below'  // Ensures it tries to open downward
});

*/

		if(es_duplicado() == true){
			document.getElementById("title").innerText = "Duplicar Solicitud de Compra";
			const elem = document.getElementById("id_element");
			elem.name = "original_id";
		}
		$('#add_row').click(function(){
			var tr = $('#item-clone tr').clone()
			$('#item-list tbody').append(tr)
    		tr.find('input[name="order_item_id[]"]').val("true");
			//tr.find('input[name="order_item_id[]"]').val('NEW_VALUE').attr('value', 'default');  
			_autocomplete(tr);
			//_autocompleteMarca(tr);
			//_autocompleteDepartamento(tr);
			tr.find("input, select").val(""); // Clear input/select values
    		tr.find(".select2").removeClass("select2-hidden-accessible").removeAttr("data-select2-id").show(); // Reset select2
    		tr.find(".select2-container").remove(); // Remove old select2 container

			tr.find(".select2").select2({
				width: "100%", // Make sure it resizes properly
				allowClear: true, // Enables clearing the selection
				placeholder: "-- Seleccione una opción --" // Keeps placeholder
    		});

			tr.find('[name="qty[]"],[name="unit_price[]"]').on('input keypress',function(e){
				calculate()
			})
			$('#item-list tfoot').find('[name="discount_percentage"],[name="tax_percentage"]').on('input keypress',function(e){
				calculate()
			})

			$('#item-list tfoot').find('[name="discount_amount"],[name="tax_amount"]').on('input keyup change blur paste keypress',function(e){
				calculate_amount()
			})
		})
		if($('#item-list .po-item').length > 0){
			$('#item-list .po-item').each(function(){
				var tr = $(this);
				_autocomplete(tr);
				//_autocompleteMarca(tr);
				//_autocompleteDepartamento(tr);
				tr.find('[name="qty[]"],[name="unit_price[]"]').on('input keypress',function(e){
					calculate()
				})
				$('#item-list tfoot').find('[name="discount_percentage"],[name="tax_percentage"]').on('input keypress',function(e){
					calculate()
				})
				$('#item-list tfoot').find('[name="discount_amount"],[name="tax_amount"]').on('input keyup change blur paste keypress',function(e){
				calculate_amount()
			})
				tr.find('[name="qty[]"],[name="unit_price[]"]').trigger('keypress')
			})
		}else{
		$('#add_row').trigger('click')
		}
        $('.select2').select2({placeholder:"Por favor selecciona aquí",width:"relative"})
		$('#po-form').submit(function(e){
			e.preventDefault();
			let adjunto_1 = document.getElementById('ruta_adjunto_1');
			let adjunto_2 = document.getElementById('ruta_adjunto_2');
			let adjunto_3 = document.getElementById('ruta_adjunto_3');
			let adjunto_4 = document.getElementById('ruta_adjunto_4');
			let adjunto_5 = document.getElementById('ruta_adjunto_5');
			let adjunto_6 = document.getElementById('ruta_adjunto_6');
			let adjunto_7 = document.getElementById('ruta_adjunto_7');
			let adjunto_8 = document.getElementById('ruta_adjunto_8');
			let adjunto_9 = document.getElementById('ruta_adjunto_9');
			let adjunto_10 = document.getElementById('ruta_adjunto_10');
			if(es_editado() == false && proceder_sin_adjunto == false && adjunto_1.value == "" && adjunto_2.value == "" && adjunto_3.value == "" && adjunto_4.value == "" && adjunto_5.value == "" && adjunto_6.value == "" && adjunto_7.value == "" && adjunto_8.value == "" && adjunto_9.value == "" && adjunto_10.value == ""){
					window.confirm("Por favor adjunte un archivo para guardar la solicitud");
					// Si el usuario responde afirmativo entonces proceder_sin_adjunto = true y se procede con el resto de la funcion
					if(proceder_sin_adjunto == false){
						return;
					}
				}
			

            var _this = $(this)
			$('.err-msg').remove();
			$('[name="po_no"]').removeClass('border-danger')
			if($('#item-list .po-item').length <= 0){
				alert_toast(" Agregue al menos 1 elemento en la lista.",'warning')
				return false;
			}
			let rawPrice = $('#total').val().replace(/,/g, ''); // Remove commas
			$('#total').val(rawPrice); // Set the value without commas before submitting
			start_loader();
			const guardarBoton = document.getElementById('guardar_boton');
			if(es_editado() == false){
				guardarBoton.disabled = true;
			}

			$function_name = 'save_po';
			if(es_editado() == true){
				$function_name = 'edit_po';
			}
			$.ajax({
				url:_base_url_+"classes/Master.php?f=" + $function_name,
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("Ocurrió un error",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.href = "./?page=purchase_orders/view_po&id="+resp.id;
					}else if((resp.status == 'failed' || resp.status == 'po_failed') && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body").animate({ scrollTop: 0 }, "fast");
                            end_loader()
							if(resp.status == 'po_failed'){
								$('[name="po_no"]').addClass('border-danger').focus()
							}
                    }else{
						alert_toast("Ocurrió un error",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})

        
	})
</script>