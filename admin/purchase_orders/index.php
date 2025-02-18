<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title">Mis Solicitudes de Compra</h3>
		<div class="card-tools">
			<a href="?page=purchase_orders/manage_po" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Crear Nuevo</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-hover table-striped">
				<colgroup>
						<col width="5%">
						<col width="10%">
						<col width="10%"> <!-- Marca -->
						<col width="8%"> <!-- Departamento -->
						<col width="20%">
						<col width="10%">
						<col width="10%">
						<col width="10%">
				</colgroup>
				<thead>
					<tr class="">
						<th>#</th>
						<th>Fecha Creación</th>
						<th># Solicitud de Compra</th>
						<th># SAP</th>
						<th>Solicitante</th>
						<th>Monto Total</th>
						<th>Estado</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					//echo "SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `po_list` po inner join `users` u on po.username = u.username where po.username = '" . $_SESSION['userdata']['username'] . "' order by unix_timestamp(po.date_updated) ";	
					
					//$qry = $conn->query("SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `po_list` po inner join `users` u on po.username = u.username order by unix_timestamp(po.date_updated) ");
						//$qry = $conn->query("SELECT po.*, s.name as sname FROM `po_list` po inner join `supplier_list` s on po.supplier_id = s.id order by unix_timestamp(po.date_updated) ");
						$strqry = "SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `po_list` po inner join `users` u on po.username = u.username where po.username = '" . $_SESSION['userdata']['username'] . "' order by unix_timestamp(po.date_updated)";
						$qry = $conn->query($strqry);
					
						while($row = $qry->fetch_assoc()):
							$row['item_count'] = $conn->query("SELECT * FROM order_items where po_id = '{$row['id']}'")->num_rows;
							if($row['total'] != null) {
								$row['total_amount'] = $row['total'];
							} else{
							$row['total_amount'] = $conn->query("SELECT sum(quantity * unit_price) as total FROM order_items where po_id = '{$row['id']}'")->fetch_array()['total'] + $row['tax_amount'] - $row['discount_amount'];
							}
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td class=""><?php echo date("M d,Y H:i",strtotime($row['date_created'])) ; ?></td>
							<td class=""><?php echo $row['po_no'] ?></td>
							<td class="text-center"><?php echo $row['SAPDocEntry'] ?></td>
							<td class=""><?php echo $row['sname'] ?></td>
							<td class="text-right"><?php echo number_format($row['total_amount'], 2) ?></td>
							<td>
								<?php 
									switch ($row['status']) {
										case '1':
											echo '<span class="badge badge-success">Aprobado</span>';
											break;
										case '2':
											echo '<span class="badge badge-danger">Rechazado</span>';
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
								  	<a class="dropdown-item" href="?page=purchase_orders/view_po&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> Ver</a>
				                    <!-- Botón Editar/Duplicar/Eliminar deshabilitado temporalmente -->
									<!-- <div class="dropdown-divider"></div>-->
				                    <!--<a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id']?>&duplicate=true"><span class="fa fa-edit text-primary"></span> Editar</a> -->
				                    <div class="dropdown-divider"></div>
									<a class="dropdown-item" href="?page=purchase_orders/manage_poid=<?php echo $row['id'] ?>&"><span class="fa fa-copy text-warning"></span> Duplicar
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