<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title">Mis Salidas de Inventario</h3>
		<div class="card-tools">
			<a href="?page=inventario/manage_si" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Crear Nuevo</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-hover table-striped">
				<colgroup>
						<col width="10%">
						<col width="10%">
						<col width="20%"> 
						<col width="10%">
						<col width="20%">
						<col width="15%">
						<col width="15%">
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
					//echo "SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `po_list` po inner join `users` u on po.username = u.username where po.username = '" . $_SESSION['userdata']['username'] . "' order by unix_timestamp(po.date_updated) ";	
					
					//$qry = $conn->query("SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `po_list` po inner join `users` u on po.username = u.username order by unix_timestamp(po.date_updated) ");
						//$qry = $conn->query("SELECT po.*, s.name as sname FROM `po_list` po inner join `supplier_list` s on po.supplier_id = s.id order by unix_timestamp(po.date_updated) ");
						$strqry = "SELECT si.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname FROM `solicitud_de_inventario` si inner join `users` u on si.username = u.username where si.username = '" . $_SESSION['userdata']['username'] . "' order by unix_timestamp(si.date_created) desc";
						$qry = $conn->query($strqry);
					
						while($row = $qry->fetch_assoc()):
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
				                    <!-- Botón Editar/Duplicar/Eliminar deshabilitado temporalmente -->
									<!-- <div class="dropdown-divider"></div>-->
				                    <!--<a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Editar</a>
				                    <div class="dropdown-divider"></div>
									<a class="dropdown-item" href="?page=purchase_orders/duplicate_po&action=duplicate&id=<?php echo $row['id'] ?>"><span class="fa fa-copy text-warning"></span> Duplicar
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