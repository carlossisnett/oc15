<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `solicitud_de_inventario` where id = '{$_GET['id']}' ");
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

		/* Firefox */
		/*input[type=number] {
		-moz-appearance: textfield;
		}
		[name="tax_percentage"],[name="discount_percentage"]{
			width:5vw;
		}*/
</style>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title"><?php echo isset($id) ? "": "" ?> </h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-flat btn-success" id="print" type="button"><i class="fa fa-print"></i> Imprimir</button>
            <!-- Botón Editar deshabilitado temporalmente -->
            <!--<a class="btn btn-sm btn-flat btn-primary" href="?page=purchase_orders/manage_po&id=<?php echo $id ?>">Editar</a>-->
		    <a class="btn btn-sm btn-flat btn-default" href="?page=purchase_orders">Volver</a>
        </div>
	</div>
	<div class="card-body" id="out_print">
        <div class="row">
        <div class="col-6 d-flex align-items-center">
            <div>
                <p class="m-0"><?php echo $_settings->info('company_name') ?></p>
                <p class="m-0"><?php echo $_settings->info('company_email') ?></p>
                <p class="m-0"><?php echo $_settings->info('company_address') ?></p>
            </div>
        </div>
        <div class="d-flex justify-content-center align-items-center" style="height: 30vh;">
        <div class="text-center">
        <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="" style="height: 100px; " class="img-responsive">
        <h2><b>Salida de Inventario</b></h2>
        </div>
        </div>


        </div>
        <div class="row mb-2">
            <div class="col-4">
                <p class="m-0"><b>Solicitante</b></p>
                <?php 
                $sup_qry = $conn->query("SELECT * FROM users where username = '{$username}'");
                //$sup_qry = $conn->query("SELECT * FROM supplier_list where id = '{$supplier_id}'");
                $supplier = $sup_qry->fetch_array();
                ?>
                <div>
                    <p class="m-0"><?php echo $supplier['name'] ?></p>
                    <p class="m-0"><?php echo $supplier['username'] ?></p>
                    <p class="m-0"><?php echo $supplier['email'] ?></p>
                </div>
            </div>
            <div class="col-4 row">
                <div class="col-4">
                    <p  class="m-0"><b># Solicitud:</b></p>
                    <p><b><?php echo $numero_solicitud ?></b></p>
                </div>
                <div class="col-4">
                    <p  class="m-0"><b># SAP:</b></p>
                    <p><b><?php echo $SAPDocEntry ?></b></p>
                </div>
                <div class="col-4">
                    <p  class="m-0"><b>Fecha de Creación</b></p>
                    <p><b><?php echo date("Y-m-d",strtotime($date_created)) ?></b></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped table-bordered" id="item-list">
                    <colgroup>
                            <col width="10%">
							<col width="30%">
							<col width="30%">
							<col width="30%">
                    </colgroup>
                    <thead>
                            <th class="bg-navy disabled text-light px-1 py-1 text-center">Cantidad</th>
                            <th class="px-1 py-1 text-center">Artículo</th>
								<th class="px-1 py-1 text-center">Marca</th>
								<th class="px-1 py-1 text-center">Departamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(isset($id)):
                        //$order_items_qry = $conn->query("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento FROM `order_items` o inner join item_list i on o.item_id = i.id where o.`po_id` = '$id' ");
                        $order_items_qry = $conn->query("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
                            FROM `order_items` o 
                            inner join item_list i on o.item_id = i.id 
                            inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
                            inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
                            where o.`po_id` = '$id' ");
                        
                        $sub_total = 0;
                        while($row = $order_items_qry->fetch_assoc()):
                     
                        ?>
                        <tr class="po-item" data-id="">
                            <!--<td class="align-middle p-0 text-center"><?php echo $row['quantity'] ?></td>-->
                            <!--<td class="align-middle p-1"><?php echo $row['unit'] ?></td>-->
                            <!--<td class="align-middle p-1"><?php echo $row['name'] ?></td>-->
                            <!--<td class="align-middle p-1 item-description"><?php echo $row['description'] ?></td>-->
                            <!--<td class="align-middle p-1"><?php echo number_format($row['unit_price']) ?></td>-->
                            <!--<td class="align-middle p-1 text-right total-price"><?php echo number_format($row['quantity'] * $row['unit_price']) ?></td>-->
                            <!--Campo Cantidad-->
								<td class="align-middle p-0 text-center">
									<input type="number" class="text-center w-100 border-0" step="any" name="qty[]" readonly="readonly" value="<?php echo $row['quantity'] ?>"/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]" value="<?php echo $row['item_id'] ?>">
									<input type="text" class="text-center w-100 border-0 item_id" readonly="readonly"  value="<?php echo $row['nombre_item'] ?>" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="marca_id[]" value="<?php echo $row['codigo_marca'] ?>">
									<input type="text" class="text-center w-100 border-0 marca_id" readonly="readonly"  value="<?php echo $row['nombre_marca'] ?>" required/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="departamento_id[]" value="<?php echo $row['codigo_departamento'] ?>">
									<input type="text" class="text-center w-100 border-0 departamento_id" readonly="readonly" value="<?php echo $row['nombre_departamento'] ?>" required/>
								</td>

                        </tr>
                        <?php endwhile;endif; ?>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
                <div class="row">
                    <div class="col-6">
                        <label for="notes" class="control-label">Notas</label>
                        <p><?php echo isset($notes) ? $notes : '' ?></p>
                    </div>
                    <div class="col-6">
                        <label for="status" class="control-label">Estado</label>
                        <br>
                        <?php 
                        switch($status){
                            case 1:
                                echo "<span class='py-2 px-4 btn-flat btn-success'>Aprobada</span>";
                                break;
                            case 2:
                                echo "<span class='py-2 px-4 btn-flat btn-danger'>Negada</span>";
                                break;

                            case 3:
                                echo "<span class='py-2 px-4 btn-flat btn-success'>Cerrada</span>";
                                break;
                            default:
                                echo "<span class='py-2 px-4 btn-flat btn-secondary'>Pendiente</span>";
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<script>
	$(function(){
        $('#print').click(function(e){
            e.preventDefault();
            start_loader();
            var _h = $('head').clone()
            var _p = $('#out_print').clone()
            var _el = $('<div>')
                _p.find('thead th').attr('style','color:black !important')
                _el.append(_h)
                _el.append(_p)
                
            var nw = window.open("","","width=1200,height=950")
                nw.document.write(_el.html())
                nw.document.close()
                setTimeout(() => {
                    nw.print()
                    setTimeout(() => {
                        end_loader();
                        nw.close()
                    }, 300);
                }, 200);
        })
    })
</script>