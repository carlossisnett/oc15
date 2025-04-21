<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title">Salidas de inventario</h3>
		<div class="card-tools">
			<a href="?page=purchase_orders/manage_si" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Crear Nuevo</a>
		</div>
	</div>
	<div class="card-body">

	<?php
	require_once "view_functions.php";
	$query = "SELECT * FROM `solicitud_de_inventario` WHERE status = 3";
	$result = $conn->query($query);
	$ids = array();
	$rows_to_display = array();
	$username = $_SESSION['userdata']['username'];
	
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			if (puede_aprobar($_SESSION['userdata']['id'], $row['id'], $conn)) {
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
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Fecha Creación</th>
						<th># Solicitud de Compra</th>
						<th># SAP</th>
						<th>Solicitante</th>
						<th>Estado</th>
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
						<td class="text-center"><?php echo $i++; ?></td>
						<td><?php echo date("M d,Y H:i", strtotime($row['date_created'])); ?></td>
						<td><?php echo $row['numero_solicitud']; ?></td>
						<td class="text-center"><?php echo $row['SAPDocEntry']; ?></td>
						<td><?php echo $row['username']; ?></td> <!-- Could replace with real name if needed -->
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
	<h4> Todas las Salidas de Inventario</h4>
	<br>
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-hover table-striped">
				<colgroup>
						<col width="5%">
						<col width="10%">
						<col width="10%"> <!-- Marca -->
						<col width="8%"> <!-- Departamento -->
						<col width="10%">
						<col width="10%">
						<col width="10%">
				</colgroup>
				<thead>
					<tr class="">
						<th>#</th>
						<th>Fecha Creación</th>
						<th># Salida de Inventario</th>
						<th># SAP</th>
						<th>Solicitante</th>
						<th>Estado</th>
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
							<td class="text-center"><?php echo $i++; ?></td>
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
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("¿Estás seguro de eliminar esta orden de forma permanente?","delete_rent",[$(this).attr('data-id')])
		})
		$('.view_details').click(function(){
			uni_modal("Reservaton Details","purchase_orders/view_details.php?id="+$(this).attr('data-id'),'mid-large')
		})
		$('.renew_data').click(function(){
			_conf("Are you sure to renew this rent data?","renew_rent",[$(this).attr('data-id')]);
		})
		$('.table th,.table td').addClass('px-1 py-0 align-middle')
		$('.table').dataTable();
	})
	function delete_rent($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_rent",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
	function renew_rent($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=renew_rent",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>