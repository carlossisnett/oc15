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

<style>
        /* Basic modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }
        .modal input {
            width: 100%;
            padding: 10px;
            font-size: 18px;
        }
        .close {
            cursor: pointer;
            color: red;
            font-size: 20px;
			align-self: flex-end;
        }
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
		<h3 class="card-title"><?php echo isset($id) ? "Actualizar los detalles de la solicitud orden de compra": "Nueva solicitud de compra" ?> </h3>
	</div>
	<div class="card-body">
		<form action="" id="po-form">
			<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
			<div class="row">
				<div class="col-md-6 form-group">
					<label for="po_no">Solicitud # <span class="po_err_msg text-danger"></span></label>
					<input type="text" class="form-control form-control-sm rounded-0" id="po_no" name="po_no" value="<?php echo isset($po_no) ? $po_no : '' ?>" disabled>
					 <!--<small><i>Deja este espacio en blanco para generar automáticamente al guardar.</i></small>-->
				</div>
				<div class="col-md-6 form-group">
            <label for="required_date">Fecha necesaria <span class="text-danger">*</span></label>
            <input 
                type="date" 
                class="form-control form-control-sm rounded-0" 
                id="required_date" 
                name="required_date" 
                value="<?php echo isset($required_date) ? $required_date : date('Y-m-d'); ?>" 
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
								<th class="px-1 py-1 text-center">Artículo</th>
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
							$order_items_qry = $conn->query("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
                            FROM `order_items` o 
                            inner join item_list i on o.item_id = i.id 
                            inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
                            inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
                            where o.`po_id` = '$id' ");
							echo $conn->error;
							while($row = $order_items_qry->fetch_assoc()):
							?>
							<tr class="po-item" data-id="">
								<!--Botón Remover Item-->
								<td class="align-middle p-1 text-center">
									<button class="btn btn-sm btn-danger py-0" type="button" onclick="rem_item($(this))"><i class="fa fa-times"></i></button>
								</td>
								<!--Campo Cantidad-->
								<td class="align-middle p-0 text-center">
									<input type="number" class="text-center w-100 border-0" step="any" name="qty[]" value="<?php echo $row['quantity'] ?>"/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]" value="<?php echo $row['item_id'] ?>">
									<input type="text" class="text-center w-100 border-0 item_id" value="<?php echo $row['nombre_item'] ?>" required/>
								</td>

								<td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" name="descripcion[]" value="<?php echo isset($row['descripcion']) ? ($row['descripcion']) : "" ?>" />
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="marca_id[]" value="<?php echo $row['codigo_marca'] ?>">
									<input type="text" class="text-center w-100 border-0 marca_id" value="<?php echo $row['nombre_marca'] ?>" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="departamento_id[]" value="<?php echo $row['codigo_departamento'] ?>">
									<input type="text" class="text-center w-100 border-0 departamento_id" value="<?php echo $row['nombre_departamento'] ?>" required/>
								</td>
								<td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" name="url[]" value="<?php echo isset($row['url']) ? ($row['url']) : "" ?>" />
								</td>
								<td class="align-middle p-1">
									<input type="number" step="any" class="text-right w-100 border-0" name="unit_price[]"  value="<?php echo ($row['unit_price']) ?>"/>
								</td>
								<td class="align-middle p-1 text-right total-price"><?php echo number_format($row['quantity'] * $row['unit_price']) ?></td>
							</tr>
							<?php endwhile;endif; ?>
						</tbody>
						<tfoot>
							<tr class="bg-lightblue">
								<tr>
									<th class="p-1 text-right" colspan="8"><span><button class="btn btn btn-sm btn-flat btn-primary py-0 mx-1" type="button" id="add_row">Agregar Fila</button></span> Sub Total</th>
									<th class="p-1 text-right" id="sub_total">0</th>
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
							<textarea name="notes" id="notes" cols="10" rows="4" class="form-control rounded-0"><?php echo isset($notes) ? $notes : '' ?></textarea>
						</div>
						<div class="col-md-6">
							<label for="status" class="control-label">Estado</label>
							<select name="status" id="status" class="form-control form-control-sm rounded-0" disabled>
								<option value="0" <?php echo isset($status) && $status == 0 ? 'selected': '' ?>>Pendiente</option>
								<option value="1" <?php echo isset($status) && $status == 1 ? 'selected': '' ?>>Aprobado</option>
								<option value="2" <?php echo isset($status) && $status == 2 ? 'selected': '' ?>>Negado</option>
							</select>
						</div>
						<div class="col-md-6 form-group">
    						<label for="ruta_adjunto" class="control-label">
								Adjuntar archivos:
							</label>
							<div id="contenedor_adjunto_1" style="display: flex; align-items: center; gap: 10px;">
								<input type="file" id="ruta_adjunto_1" name="ruta_adjunto_1" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(1)">
									Borrar 🗑️
								</button>
								</div>

							<div id="contenedor_adjunto_2" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_2" name="ruta_adjunto_2" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(2)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_3" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_3" name="ruta_adjunto_3" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(3)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_4" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_4" name="ruta_adjunto_4" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(4)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_5" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_5" name="ruta_adjunto_5" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(5)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_6" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_6" name="ruta_adjunto_6" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(6)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_7" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_7" name="ruta_adjunto_7" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(7)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_8" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_8" name="ruta_adjunto_8" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(8)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_9" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_9" name="ruta_adjunto_9" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(9)">
									Borrar 🗑️
								</button>
								</div>

								<div id="contenedor_adjunto_10" style="display: flex; align-items: center; gap: 10px;" hidden="true">
								<input type="file" id="ruta_adjunto_10" name="ruta_adjunto_10" class="form-control form-control-file" style="flex: 1; height: 40px;">
								
								<button class="btn btn-flat btn-default boton-borrar" style="height: 40px; display: flex; align-items: center; justify-content: center;" onclick="erase_adjunto(10)">
									Borrar 🗑️
								</button>
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
								<!--Botón Remover Item-->
								<td class="align-middle p-1 text-center">
									<button class="btn btn-sm btn-danger py-0" type="button" onclick="rem_item($(this))"><i class="fa fa-times"></i></button>
								</td>
								<!--Campo Cantidad-->
								<td class="align-middle p-0 text-center">
									<input type="number" class="text-center w-100 border-0" step="any" name="qty[]"/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]">
									<input type="text" class="text-left w-100 border-0 item_id" required/>
								</td>

								<td class="align-middle p-1">
									<input type="text" id="description_input" name="description[]" class="text-left w-100 border-0" />
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="marca_id[]">
									<input type="text" class="text-left w-100 border-0 marca_id" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="departamento_id[]">
									<input type="text" class="text-left w-100 border-0 departamento_id" required/>
								</td>
								<td class="align-middle p-1">
									<input type="text" name="url[]" class="text-left w-100 border-0" />
								</td>
								<td class="align-middle p-1">
									<input type="number" step="any" class="text-right w-100 border-0" name="unit_price[]">
								</td>
								<td class="align-middle p-1 text-right total-price">0</td>
							</tr>
</table>

<script>
	function rem_item(_this){
		_this.closest('tr').remove()
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
		$('#sub_total').text(parseFloat(_total).toLocaleString("en-US"))
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
			},
        change: function (event, ui) {
            if (!ui.item) {
                // Si no se seleccionó un valor válido del autocomplete
                _item.find('input[name="item_id[]"]').val("");
                _item.find('.item-description').text("");
                _item.find('.item_id').data('selected', false); // Marca como inválido
                alert("Por favor, selecciona un artículo válido de la lista.");
            }
        }
		})
	}

	function _autocompleteMarca(_item){

_item.find('.marca_id').autocomplete({
	source:function(request, response){
		$.ajax({
			url:_base_url_+"classes/Master.php?f=search_marca",
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
		_item.find('input[name="marca_id[]"]').val(ui.item.id)
		_item.find('.marca-description').text(ui.item.description)
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
}
});

}

let adjunto = 2;

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
	adjunto_element.value = "";
}

	$(document).ready(function(){
		$('#add_row').click(function(){
			var tr = $('#item-clone tr').clone()
			$('#item-list tbody').append(tr)
			_autocomplete(tr);
			_autocompleteMarca(tr);
			_autocompleteDepartamento(tr);
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
				var tr = $(this)
				_autocomplete(tr)
				tr.find('[name="qty[]"],[name="unit_price[]"]').on('input keypress',function(e){
					calculate()
				})
				$('#item-list tfoot').find('[name="discount_percentage"],[name="tax_percentage"]').on('input keypress',function(e){
					calculate()
				})
				tr.find('[name="qty[]"],[name="unit_price[]"]').trigger('keypress')
			})
		}else{
		$('#add_row').trigger('click')
		}
        $('.select2').select2({placeholder:"Por favor selecciona aquí",width:"relative"})
		$('#po-form').submit(function(e){
			e.preventDefault();
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
			guardarBoton.disabled = true;
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_po",
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