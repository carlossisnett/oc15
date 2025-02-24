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
/*
$id_usuario = $_settings->userdata('id');
$qry = $conn->query("SELECT * from `users` where id = '$id_usuario' ");
$qry = $qry->fetch_array();
if($qry['codSAP'] == null){
  echo "<script> alert('ADVERTENCIA: Su usuario no tiene código de SAP, por favor contactar a desarrollo@prensa.com para que le asignen uno antes de realizar órdenes de compra') </script>";
}
  */
?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title"><?php echo isset($id) ? "Actualizar los detalles de la Salida de inventario": "Nueva Salida de Inventario" ?> </h3>
	</div>
	<div class="card-body">
		<form action="" id="po-form">
			<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
			<div class="row">
				<div class="col-md-6 form-group">
					<label for="po_no">Salida # <span class="po_err_msg text-danger"></span></label>
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
                value="<?php echo isset($required_date) ? $required_date : '' ?>" 
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
	<col width="5%">
    <col width="30%">
    <col width="30%">
    <col width="30%">
  </colgroup>
  <thead>
    <tr class="bg-navy disabled">
      <th class="px-1 py-1 text-center"></th>
      <th class="px-1 py-1 text-center">Cantidad</th>
	  <th class="px-1 py-1 text-center">Inventario</th>
      <th class="px-1 py-1 text-center">Artículo</th>
      <th class="px-1 py-1 text-center">Marca</th>
      <th class="px-1 py-1 text-center">Departamento</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    if(isset($id)):
      $order_items_qry = $conn->query("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `order_items` o 
        INNER JOIN item_list i ON o.item_id = i.id 
        INNER JOIN centro_costo ma ON o.codigo_marca = ma.codigo_ccosto
        INNER JOIN centro_costo de ON o.codigo_departamento = de.codigo_ccosto
        WHERE o.`po_id` = '$id' ");
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

	  <td class="align-middle p-0 text-center">
        <input type="number" class="text-center w-100 border-0" step="any" name="inventario[]" value="" disabled/>
      </td>
      <!--Campo Artículo-->
      <td class="align-middle p-1">
        <input type="hidden" name="item_id[]" value="<?php echo $row['item_id'] ?>">
        <input type="text" class="text-center w-100 border-0 item_id" value="<?php echo $row['nombre_item'] ?>" required/>
      </td>
      <!--Campo Marca-->
      <td class="align-middle p-1">
        <input type="hidden" name="marca_id[]" value="<?php echo $row['codigo_marca'] ?>">
        <input type="text" class="text-center w-100 border-0 marca_id" value="<?php echo $row['nombre_marca'] ?>" required/>
      </td>
      <!--Campo Departamento-->
      <td class="align-middle p-1">
        <input type="hidden" name="departamento_id[]" value="<?php echo $row['codigo_departamento'] ?>">
        <input type="text" class="text-center w-100 border-0 departamento_id" value="<?php echo $row['nombre_departamento'] ?>" required/>
      </td>
    </tr>
    <?php endwhile; endif; ?>
  </tbody>
  <tfoot>
    <tr>
      <th class="p-1 text-right" colspan="5">
        <span>
          <button class="btn btn btn-sm btn-flat btn-primary py-0 mx-1" type="button" id="add_row">Agregar Fila</button>
        </span>
      </th>
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
						<div class="col-md-6 form-group" hidden>
    						<label for="ruta_adjunto" class="control-label">Adjuntar archivo:</label>
    						<input type="file" id="ruta_adjunto" name="ruta_adjunto[]" class="form-control form-control-file" multiple> 
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

								<td class="align-middle p-0 text-center">
									<input type="number" class="text-center w-100 border-0" step="any" name="inventario[]" disabled/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]">
									<input type="text" class="text-left w-100 border-0 item_id" required/>
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
							</tr>
</table>
<div id="stock_store">

</div>
<script>
	function create_stock(id_item, stock_actual){
		let node = document.createElement("div");
		node.setAttribute('stock_actual', stock_actual.toString());
		let stock_store = document.getElementById('stock_store');
		stock_store.appendChild(node);
	}
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
					url:_base_url_+"classes/Master.php?f=search_inventory_items",
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
			start_loader();
			const guardarBoton = document.getElementById('guardar_boton');
			guardarBoton.disabled = true;
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_inventory_request",
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
						alert_toast("Su solicitud fue guardada exitosamente",'success');
						sleep(2000).then(() => { location.href = "./?page=inventario/view_si&id="+resp.id; });
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

	function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}
</script>