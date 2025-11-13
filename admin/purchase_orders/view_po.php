<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `po_list` where id = '{$_GET['id']}' ");
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
        <h2><b>Solicitud de Compra</b></h2>
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
                <div class="col-3">
                    <p  class="mb-2"><b># Solicitud:</b></p>
                    <p><b><?php echo $po_no ?></b></p>
                </div>
                <div class="col-3">
                    <p  class="mb-2"><b># SAP:</b></p>
                    <?php
                    
                    if($SAPDocEntry == null && $pedido == false){
                        echo "<a href='' onclick='enviar_solicitud_a_sap()'> Reenviar a SAP </a>";
                        // Solo podemos enviar pedidos a SAP si el estado es 1 (Aprobada):
                    } elseif($SAPDocEntry == null && $pedido == true && $status == 1){
                        echo "<a href='' onclick='enviar_pedido_a_sap()'> Reenviar a SAP </a>";
                    } elseif($_settings->userdata('type') == 2){
                        echo "<b> $SAPDocEntry </b>";
                        echo "<a href='' onclick='enviar_pedido_a_sap()'> Reenviar a SAP </a>";
                        
                    }else {
                        echo "<p><b> $SAPDocEntry </b></p>";
                    }
                    
                    ?>
                </div>
                <div class="col-3">
                    <p  class="mb-2"><b>Fecha de Creación</b></p>
                    <p><b><?php echo date("Y-m-d",strtotime($date_created)) ?></b></p>
                </div>
                <div class="col-3">
                    <p  class="mb-2"><b>Proveedor</b></p>
                    <?php
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
                    ?>
                    <p><b><?php echo isset($name_proveedor) ? $name_proveedor : "" ?></b></p>
                    </div>
                </div>
                <div class="col-4 row">
                <div class="col-3">
                <p  class="mb-2"><b> Estado</b></p>
                <?php
                if($status == 1){
                    echo "<span class='py-2 px-4 btn-flat btn-success'>Aprobada</span>";
                } else if($status == 2){
                    echo "<span class='py-2 px-4 btn-flat btn-danger'>Rechazada</span>";
                } else if($status == 3){
                    echo "<b>Listo para aprobar</b>";
                } else if ($status == 0) {
                    echo "<span class='py-2 px-4 btn-flat btn-secondary'>Pendiente</span>";
                }
                ?>
               
            </div>
            <?php 
            require_once __DIR__ . '/../views/view_functions.php';
            $user_id = $_settings->userdata('id');
            cambiar_estado_para_aprobador($id, $user_id, $conn, $status);
            cambiar_estado_para_compras($id, $user_id, $conn, $status);
            ?>
            </div>
            </div>
            </div>
        </div>
                    <?php
                    
                    $historial = $conn->query("SELECT u.name , a.estado, a.hora_creacion FROM aprobaciones a JOIN users u ON u.id = a.user_id where orden_compra_id = '{$_GET['id']}'");
                    if(gettype($historial) == "boolean"){
                        echo "";
                    } else {
                        if ($historial && $historial->num_rows > 0) {
                            echo "Historial de la orden de compra <br>";
                            echo "<ul>";
                        
                            while ($row = $historial->fetch_assoc()) {
                                echo "<li>" . htmlspecialchars($row['name']) . " " . describir_estado($row['estado']) . " el " . date("Y-m-d H:i:s", strtotime($row['hora_creacion'])) . "</li>";
                            }
                        
                            echo "</ul>";
                    echo "</ul>";
                    }
                }
                    ?>
       
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped table-bordered" id="item-list">
                    <colgroup>
                            <col width="5%">
							<col width="15%">
                            <col width="15%">
							<col width="15%">
							<col width="20%">
                            <col width="15%">
							<col width="7.5%">
							<col width="7.5%">
                    </colgroup>
                    <thead>
                            <th class="bg-navy disabled text-light px-1 py-1 text-center">Cantidad</th>
                            <th class="px-1 py-1 text-center">Artículo</th>
                            <th class="px-1 py-1 text-center">Descripción</th>
								<th class="px-1 py-1 text-center">Marca</th>
								<th class="px-1 py-1 text-center">Departamento</th>
                                <th class="px-1 py-1 text-center">Enlace🌐</th>
								<th class="px-1 py-1 text-center">Precio</th>
								<th class="px-1 py-1 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        //require "view_functions.php";
                        if(isset($id)):
                        
                            $prepared = $conn->prepare("SELECT o.*,i.name, o.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
                            FROM `order_items` o 
                            inner join item_list i on o.item_id = i.id 
                            inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
                            inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
                            where o.`po_id` = ? ");
                            $prepared->bind_param("i", $id);
                            $prepared->execute();
                            $result = $prepared->get_result();
                            //$row = $result->fetch_assoc();
                        
                        $sub_total = 0;
                        while($row = $result->fetch_assoc()):
                            $sub_total += ($row['quantity'] * $row['unit_price']);
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
									<input type="text" class="text-center w-100 border-0" step="any" name="qty[]" readonly="readonly" value="<?php
 echo special_format($row['quantity']) ?>"/>
								</td>
								<!--Campo oculto item_id-->
								<td class="align-middle p-1">
									<input type="hidden" name="item_id[]" value="<?php echo $row['item_id'] ?>">
									<input type="text" class="text-center w-100 border-0 item_id" readonly="readonly"  value="<?php echo $row['nombre_item'] ?>" required/>
								</td>

                                <td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" readonly="readonly" name="description[]" value="<?php echo isset($row['description']) ? htmlspecialchars($row['description']) : "" ?>"/>
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
                                <td class="align-middle p-1">
									<input type="text" class="text-center w-100 border-0" readonly="readonly" name="url[]" value="<?php echo isset($row['url']) ? ($row['url']) : "" ?>" />
								</td>
								<td class="align-middle p-1">
									<input type="text" step="any" class="text-right w-100 border-0" name="unit_price[]" readonly="readonly" value="<?php echo special_format($row['unit_price']) ?>"/>
								</td>
								<td class="align-middle p-1 text-right total-price"><?php echo special_format($row['quantity'] * $row['unit_price']) ?></td>
                        </tr>
                        <?php endwhile;endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-lightblue">
                            <tr>
                                <th class="p-1 text-right" colspan="7">Sub Total</th>
                                <th class="p-1 text-right" id="sub_total"><?php echo(special_format($sub_total)) ?></th>
                            </tr>
                            <tr>
                                <th class="p-1 text-right" colspan="7">Descuento (<?php echo isset($discount_percentage) ? $discount_percentage : 0 ?>%)
                                </th>
                                <th class="p-1 text-right"><?php echo isset($discount_amount) ? special_format($discount_amount) : 0 ?></th>
                            </tr>
                            <tr>
                                <th class="p-1 text-right" colspan="7">Impuestos Incluidos (<?php echo isset($tax_percentage) ? $tax_percentage : 0 ?>%)</th>
                                <th class="p-1 text-right"><?php echo isset($tax_amount) ? special_format($tax_amount): 0 ?></th>
                            </tr>
                            <tr>
                                <th class="p-1 text-right" colspan="7">Total</th>
                                <th class="p-1 text-right" id="total"><?php echo isset($total) ? special_format($total) : special_format($sub_total - $discount_amount + $tax_amount)  ?></th>
                            </tr>
                        </tr>
                    </tfoot>
                </table>
                <div class="row">
                    <div class="col-6">
                        <label for="notes" class="control-label">Notas</label>
                        <p><?php echo isset($notes) ? $notes : '' ?></p>
                    </div>
                </div>
                <div class="row">
                <div class="col-md-12">
                <?php
                    if(isset($ruta_adjunto)){
                        $files = scandir($ruta_adjunto);
                        $files = array_diff($files, array('.', '..')); // Remove . and ..
                        //$only_files = array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'pdf'); // Filter only PDFs
                    }
                        ?>

                        <div class="pdf-container">
                            <?php
                            if($ruta_adjunto == null){
                                echo "<label>No hay adjuntos</label> <br>";
                            } else {
                                echo "<label>Adjuntos</label><br>";
                                foreach ($files as $file) {
                                    echo '<a href="' . $ruta_adjunto . '/' . $file . '" target="_blank">' . $file . '</a><br>';
                                }
                            } ?>
                        </div>
                    </div>
                        
                </div>
            </div>
        </div>
	</div>
</div>
<table class="d-none" id="item-clone">
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
        <td class="align-middle p-1">
			<input type="text" class="text-center w-100 border-0" name="url[]" value="<?php echo isset($row['url']) ? ($row['url']) : "" ?>" />
		</td>
		<td class="align-middle p-1">
			<input type="number" step="any" class="text-right w-100 border-0" name="unit_price[]" value="0"/>
		</td>
		<td class="align-middle p-1 text-right total-price">0</td>
	</tr>
</table>
<script>
function enviar_solicitud_a_sap(){
    $.ajax({
				url:_base_url_+"classes/Master.php?f=" + 'enviar_solicitud_de_compra_a_sap',
				data: {id: <?php echo $_GET['id'] ?>, user_id: <?php echo $user_id ?>},
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
                        alert_toast("",'success');
                        end_loader();
                        setTimeout(() => {
                            location.href = "./?page=purchase_orders/view_po&id="+<?php echo $_GET['id'] ?>;
                        }, 2000);
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

function enviar_pedido_a_sap(){
    $.ajax({
				url:_base_url_+"classes/Master.php?f=" + 'enviar_pedido_a_sap',
				data: {id: <?php echo $_GET['id'] ?>, user_id: <?php echo $user_id ?>},
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
                        alert_toast("",'success');
                        end_loader();
                        setTimeout(() => {
                            location.href = "./?page=purchase_orders/view_po&id="+<?php echo $_GET['id'] ?>;
                        }, 2000);
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
    $('select').on('change', function(e){
        let proceder = false;
        if(this.value == 0){
            proceder = window.confirm("¿Desea cambiar el estado de la solicitud de compra a Pendiente?");
        }
        if(this.value == 1){
            proceder = window.confirm("¿Desea aprobar la solicitud de compra?");
        }
        if(this.value == 2){
            proceder = window.confirm("¿Desea rechazar la solicitud de compra?");
        }
        if(this.value == 3){
            proceder = window.confirm("¿Desea cambiar el estado de la solicitud de compra a 'Lista para aprobar'?");
        }
    if(proceder == true){
        $.ajax({
				url:_base_url_+"classes/Master.php?f=" + 'change_po_status',
				data: {status: this.value, id: <?php echo $_GET['id'] ?>, user_id: <?php echo $user_id ?>},
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
						location.href = "./?page=purchase_orders/view_po&id="+<?php echo $_GET['id'] ?>;
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
});

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