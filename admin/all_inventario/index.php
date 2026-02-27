<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title">Salidas de inventario</h3>
		<div class="card-tools">
			<a href="?page=inventario/manage_si" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Crear Nuevo</a>
		</div>
	</div>
	<div class="card-body">

	<?php
	require_once "./views/view_functions.php";
	$query = "SELECT * FROM `solicitud_de_inventario` WHERE status = 3";
	$result = $conn->query($query);
	$ids = array();
	$rows_to_display = array();
	$username = $_SESSION['userdata']['username'];
	$user_id = $_SESSION['userdata']['id'];
	
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			if (puede_aprobar_inventario($_SESSION['userdata']['id'], $row['id'], $conn)) {
				$ids[] = $row['id'];
				$rows_to_display[] = $row; // save full row for table later
			}
		}
	} else {
		echo "<h5>Su usuario $username no tiene salidas de inventario por aprobar</h5>";
	}
	?>
	
	<?php if (count($rows_to_display) > 0): ?>
		<h5>Las siguientes solicitudes requieren su aprobación</h5>
		<div class="container-fluid">
			<table class="table table-hover table-striped">
				<colgroup>
					<col width="5%">
					<col width="10%">
					<col width="10%">
					<col width="8%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>ID referencia</th>
						<th>Fecha Creación</th>
						<th># Salida de Inventario</th>
						<th># SAP</th>
						<th>Solicitante</th>
						<th>Estado</th>
						<th>Estado en Almacen</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					foreach ($rows_to_display as $row):
						/*
						$prov_name = $conn->query("SELECT pro.name FROM proveedores pro JOIN inventory_items o ON pro.id = o.proveedor_id JOIN solicitud_de_inventario p ON p.id = o.solicitud_id WHERE p.id = '{$row['id']}' LIMIT 1")->fetch_assoc();
						$row['proveedor_name'] = $prov_name ? $prov_name["name"] : "";

						*/
	
						$row['item_count'] = $conn->query("SELECT * FROM inventory_items WHERE solicitud_id = '{$row['id']}'")->num_rows;
						/*
						$row['total_amount'] = $row['total'] ?? (
							$conn->query("SELECT SUM(quantity * unit_price) AS total FROM inventory_items WHERE solicitud_id = '{$row['id']}'")->fetch_array()['total'] 
							+ $row['tax_amount'] - $row['discount_amount']
						);
						*/
					?>
					<tr>
						<td class="text-center"><?php echo $row['id']; ?></td>
						<td><?php echo date("M d,Y H:i", strtotime($row['date_created'])); ?></td>
						<td><?php echo $row['numero_solicitud']; ?></td>
						<td class="text-center"><?php echo $row['SAPDocEntry']; ?></td>
						<td><?php echo $row['username']; ?></td> <!-- Could replace with real name if needed -->
						<td id="status_<?php echo $row['id']; ?>" class="status">
							<?php
							switch ($row['status']) {
								case '1':
									echo '<span class="badge badge-success">Aprobado</span>';
									break;
								case '2':
									echo '<span class="badge badge-danger">Rechazado</span>';
									break;
								case '3':
									echo '<b> Listo para aprobar </b>';
									break;
								default:
									echo '<span class="badge badge-secondary">Pendiente</span>';
									break;
							}
							?>
						</td>
						<td>
							
						<?php
							switch ($row['estado_almacen']) {
								case '1':
									echo '<span class="badge badge-success">Entregado</span>';
									break;
								case '2':
									echo '<span class="badge badge-danger">Rechazado</span>';
									break;
								case '3':
									echo '<b> Listo para entregar </b>';
									break;
								case '4':
											echo '<b> Enviado a SAP </b>';
											break;
								default:
									echo "";
									break;
								}
							?>
					
					</td>
						<td align="center">
							<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
								Acción
								<span class="sr-only">Toggle Dropdown</span>
							</button>
							<div class="dropdown-menu" role="menu">
								<a class="dropdown-item" href="?page=inventario/view_si&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> Ver</a>
								<div class="dropdown-divider"></div>
									 <?php if($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3): ?>
				                    <a class="dropdown-item" href="?page=inventario/manage_si&id=<?php echo $row['id']?>&edit=true"><span class="fa fa-edit text-primary"></span> Editar</a>
									<?php endif ?>
									<div class="dropdown-divider"></div>
									<?php if($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3): ?>
				                    <a id="aprobar_<?php echo $row['id'] ?>" class="dropdown-item cambiar_estado_aprobar" href="#"><span class="fa fa-check text-success"></span> Aprobar</a>
									<div class="dropdown-divider"></div>
									<?php endif ?>
									<?php if($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3): ?>
				                    <a id="rechazar_<?php echo $row['id'] ?>" class="dropdown-item cambiar_estado_aprobar" href="#"><span class="fa fa-ban text-danger"></span> Rechazar</a>
									<?php endif ?>
								
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

	<br>
	<br>

<button id="boton_pendientes" type="button" class="btn btn-success">Pendientes</button>
<button id="boton_historial" type="button" class="btn btn-outline-secondary">Historial</button>

<?php
// Estas son las solicitudes que almacen todavia tiene que atender:
render_solicitud_inventario_table($conn);
?>

<br>
<br>

	
	<?php
	render_historial_inventario($conn);
	?>
	<br>
	<!-- this is hidden is not shown right now -->
		<div class="container-fluid" hidden>
        <div class="container-fluid">
			<table class="table table-hover table-striped">
				<colgroup>
						<col width="5%">
						<col width="10%">
						<col width="10%"> <!-- Marca -->
						<col width="8%"> <!-- Departamento -->
						<col width="9%">
						<col width="10%">
						<col width="9%">
						<col width="9%">
						<col width="5%">
				</colgroup>
				<thead>
					<tr class="">
						<th>ID referencia</th>
						<th>Fecha Creación</th>
						<th># Salida de Inventario</th>
						<th># SAP</th>
						<th>Solicitante</th>
						<th>Departamento</th>
						<th>Estado</th>
						<th>Estado en Almacen</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					
					$i = 1;
					//echo "SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `solicitud_de_inventario` po inner join `users` u on po.username = u.username where po.username = '" . $_SESSION['userdata']['username'] . "' order by unix_timestamp(po.date_updated) ";	
					
					//$qry = $conn->query("SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `solicitud_de_inventario` po inner join `users` u on po.username = u.username order by unix_timestamp(po.date_updated) ");
						//$qry = $conn->query("SELECT po.*, s.name as sname FROM `solicitud_de_inventario` po inner join `supplier_list` s on po.supplier_id = s.id order by unix_timestamp(po.date_updated) ");
						$strqry = "SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `solicitud_de_inventario` po inner join `users` u on po.username = u.username " . "order by unix_timestamp(po.date_created) desc";
						$qry = $conn->query($strqry);
					
						while($row = $qry->fetch_assoc()):
							/*
							En orden de compra si se sabe el proveedor, en salida de inventario no guardamos el proveedor
							$prov_name = $conn->query("SELECT pro.name from proveedores pro join inventory_items o on pro.id = o.proveedor_id join solicitud_de_inventario p on p.id = o.solicitud_id where p.id = '{$row['id']}' LIMIT 1")->fetch_assoc();
							if($prov_name != null){
								$row['proveedor_name'] = $prov_name["name"];
							} else {
								$row['proveedor_name'] = "";
							}
								*/

							$row['item_count'] = $conn->query("SELECT * FROM inventory_items where solicitud_id = '{$row['id']}'")->num_rows;
							/*
							if($row['total'] != null) {
								$row['total_amount'] = $row['total'];
							} else{
							$row['total_amount'] = $conn->query("SELECT sum(quantity * unit_price) as total FROM inventory_items where po_id = '{$row['id']}'")->fetch_array()['total'] + $row['tax_amount'] - $row['discount_amount'];
							}
							*/
					?>
						<tr>
							<?php
							$id_solicitud = $row['id'];
							if ($row['salida_de_mercancia']) {
								echo '<td class="text-center"><b>' . $id_solicitud . '</b></td>';
							} else {
								echo '<td class="text-center">' . $id_solicitud . '</td>';
							}
							?>
							
							<td class=""><?php echo date("M d,Y H:i",strtotime($row['date_created'])) ; ?></td>
							<td class=""><?php echo $row['numero_solicitud'] ?></td>
							<td class="text-center"><?php echo $row['SAPDocEntry'] ?></td>
							<td class=""><?php echo $row['sname'] ?></td>
							<td>
								<?php 
									switch ($row['status']) {
										case '1':
											echo '<span class="badge badge-success">Aprobado</span>';
											break;
										case '2':
											echo '<span class="badge badge-danger">Rechazado</span>';
											break;
										case '3':
											echo '<b> Listo para aprobar </b>';
											break;
										default:
											echo '<span class="badge badge-secondary">Pendiente</span>';
											break;
									}
								?>
							</td>
							<td>
								<?php
									switch ($row['estado_almacen']) {
										case '1':
											echo '<span class="badge badge-success">Entregado</span>';
											break;
										case '2':
											echo '<span class="badge badge-danger">Rechazado</span>';
											break;
										case '3':
											echo '<b> Listo para entregar </b>';
											break;
										case '4':
											echo '<b> Enviado a SAP </b>';
											break;
										default:
											echo '';
											break;
										}
									?>
							</td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Acción
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
								  <div class="dropdown-menu" role="menu">
								  	<a class="dropdown-item" href="?page=inventario/view_si&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> Ver</a>
									  <div class="dropdown-divider"></div>
									 <?php if($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3): ?>
				                    <a class="dropdown-item" href="?page=inventario/manage_si&id=<?php echo $row['id']?>&edit=true"><span class="fa fa-edit text-primary"></span> Editar</a>
									<?php endif ?>
			
									<!--
									</a><div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Eliminar</a>-->
				                  </div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>

		</div>
	</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnPendientes = document.getElementById("boton_pendientes");
    const btnHistorial = document.getElementById("boton_historial");
    const divPendientes = document.getElementById("solicitudes_pendientes");
    const divHistorial = document.getElementById("historial_inventario");
	divHistorial.style.display = "none";

    btnPendientes.addEventListener("click", function() {
        divPendientes.style.display = "block";
        divHistorial.style.display = "none";
        btnPendientes.classList.remove("btn-outline-secondary");
        btnPendientes.classList.add("btn-success");
        btnHistorial.classList.remove("btn-success");
        btnHistorial.classList.add("btn-outline-secondary");
    });

    btnHistorial.addEventListener("click", function() {
        divPendientes.style.display = "none";
        divHistorial.style.display = "block";
        btnHistorial.classList.remove("btn-outline-secondary");
        btnHistorial.classList.add("btn-success");
        btnPendientes.classList.remove("btn-success");
        btnPendientes.classList.add("btn-outline-secondary");
    });
});


$(document).ready(function(){
	$('.cambiar_estado_aprobar').on('click',function(e){
	e.preventDefault();
	let id = $(this).attr('id');
	let _this = $(this);
	console.log(id);
	let proceder = false;
	let status = id.split('_')[0];
	let real_id = id.split('_')[1];
	let status_number = 0;
	  if(status == "aprobar"){
            proceder = window.confirm("¿Desea aprobar la salida de inventario?");
			status_number = 1;
        }
        if(status == "rechazar"){
            proceder = window.confirm("¿Desea rechazar la salida de inventario?");
			status_number = 2;
        }
    if(proceder == true){
		console.log("proceder = true");
		  $.ajax({
				url:_base_url_+"classes/Master.php?f=" + 'change_si_status',
				data: {status: status_number, id: real_id, user_id: <?php echo $user_id ?>},
                cache: false,
                contentType: "application/x-www-form-urlencoded",
                processData: true,
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
						alert_toast("Estado cambiado correctamente.",'success');
						if(status == 'aprobar'){
							$("#status_"+real_id).html('<span class="badge badge-success">Aprobado</span>')
						} else{
							$("#status_"+real_id).html('<span class="badge badge-danger">Rechazado</span>')
						}
						
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
    }

})

})
</script>