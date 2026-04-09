<?php

GLOBAL $conn;
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
		
	/* Toggle Switch Styles */
	.toggle-switch {
		position: relative;
		display: inline-block;
		width: 50px;
		height: 24px;
		margin-left: 10px;
		vertical-align: middle;
	}

	.toggle-switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	.toggle-slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #ccc;
		transition: .4s;
		border-radius: 24px;
	}

	.toggle-slider:before {
		position: absolute;
		content: "";
		height: 18px;
		width: 18px;
		left: 3px;
		bottom: 3px;
		background-color: white;
		transition: .4s;
		border-radius: 50%;
	}

	input:checked + .toggle-slider {
		background-color: #28a745;
	}

	input:checked + .toggle-slider:before {
		transform: translateX(26px);
	}

	.super-firma-label,
	.gerente-label {
		display: inline-block;
		margin-left: 5px;
		font-size: 14px;
		font-weight: normal;
	}
	.approver-user-toggles {
		display: inline-flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 0.75rem 1.25rem;
		margin-left: 0.5rem;
		vertical-align: middle;
	}
</style>

<body>

<!-- Toggle button for approver list -->
<button id="toggleApproverList" class="btn btn-primary mb-3" type="button">
    <i class="fas fa-list"></i> Mostrar/Ocultar Lista de Aprobadores
</button>

<!-- Approver list section (hidden by default) -->
<div id="approverListSection" style="display: none; margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
    <h4>Lista de aprobadores y sus departamentos:</h4>
    <?php
    $marcas_query = $conn->query("
        SELECT aprobadores.*, users.name, users.super_firma, users.is_gerente, centro_costo.nombre_ccosto 
        FROM aprobadores 
        JOIN users ON users.id = aprobadores.user_id 
        JOIN centro_costo ON centro_costo.codigo_ccosto = aprobadores.departamento 
        ORDER BY users.id
    ");

    $current_user = null;
    while($row_2 = $marcas_query->fetch_assoc()):
        // When we hit a new user, print a header
        if ($current_user !== $row_2['user_id']) {
            // Close the previous list if not the first user
            if ($current_user !== null) {
                echo "</ul>";
            }
            
            $user_super_firma = (int)($row_2['super_firma'] ?? 0);
            $checked_sf = $user_super_firma === 1 ? 'checked' : '';
            $super_firma_status = $user_super_firma === 1 ? 'Activada' : 'Desactivada';
            $user_is_gerente = (int)($row_2['is_gerente'] ?? 0);
            $checked_g = $user_is_gerente === 1 ? 'checked' : '';
            $gerente_status = $user_is_gerente === 1 ? 'Sí' : 'No';

            echo "<h5 style='display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;'>" . htmlspecialchars($row_2['name']) . "
                    <span class='approver-user-toggles'>
                    <span>
                    <label class='toggle-switch'>
                        <input type='checkbox' class='toggle-super-firma'
                               data-user-id='" . $row_2['user_id'] . "'
                               data-user-name='" . htmlspecialchars($row_2['name']) . "'
                               {$checked_sf}>
                        <span class='toggle-slider'></span>
                    </label>
                    <span class='super-firma-label'>Super Firma: <span class='super-firma-status'>{$super_firma_status}</span></span>
                    </span>
                    <span>
                    <label class='toggle-switch'>
                        <input type='checkbox' class='toggle-is-gerente'
                               data-user-id='" . $row_2['user_id'] . "'
                               data-user-name='" . htmlspecialchars($row_2['name']) . "'
                               {$checked_g}>
                        <span class='toggle-slider'></span>
                    </label>
                    <span class='gerente-label'>Gerente: <span class='gerente-status'>{$gerente_status}</span></span>
                    </span>
                    </span>
                  </h5>";
            echo "<ul style='list-style: none; padding-left: 0;'>";
            $current_user = $row_2['user_id'];
        }
        
        // Build label text
        $labels = [];
        if ($row_2['exclusivo_inventario'] == 1) {
            $labels[] = "Exclusivo Inventario";
        }
        if ($row_2['exclusivo_compras'] == 1) {
            $labels[] = "Exclusivo Compras";
        }
        $label_text = !empty($labels) ? " [" . implode(", ", $labels) . "]" : "";

        // Print the department under this user with delete button
        echo "<li style='margin-bottom: 8px;'>";
        //echo "• ";
        echo "<button class='btn btn-sm btn-danger delete-approver' 
                data-user-id='" . $row_2['user_id'] . "' 
                data-departamento='" . htmlspecialchars($row_2['departamento']) . "' 
                data-user-name='" . htmlspecialchars($row_2['name']) . "' 
                data-dept-name='" . htmlspecialchars($row_2['nombre_ccosto']) . "' 
                style='padding: 1px 6px; margin-right: 5px;' 
                title='Eliminar aprobador'>
                <i class='fas fa-times'></i>
              </button>";
        echo htmlspecialchars($row_2['nombre_ccosto']) . " (" . htmlspecialchars($row_2['departamento']) . ")$label_text";
        echo "</li>";
    endwhile;

    // Close the last <ul>
    if ($current_user !== null) {
        echo "</ul>";
    }
    ?>
</div>

<?php
// Query to find if there are any temporary approvers
$query = $conn->query("SELECT * FROM aprobadores WHERE temporal = 1 LIMIT 1");

if ($query && $query->num_rows > 0) {
    // Get the first temporary approver (you can loop if needed)
    $row = $query->fetch_assoc();

    $original_id = $row['aprobador_original'];
    $temporal_id = $row['user_id'];

    // Get names (assuming you have a `users` table)
    $original_user = $conn->query("SELECT name FROM users WHERE id = {$original_id}")->fetch_assoc()['name'] ?? 'Usuario Original';
    $temporal_user = $conn->query("SELECT name FROM users WHERE id = {$temporal_id}")->fetch_assoc()['name'] ?? 'Usuario Temporal';
    ?>
      <h4> Retorno de aprobador </h4>
    <form id="retornar_aprobador" action="" method="post">
        <input type="hidden" name="original_id" value="<?= htmlspecialchars($original_id) ?>">
        <input type="hidden" name="temporal_id" value="<?= htmlspecialchars($temporal_id) ?>">

        <p>¿Desea retornar las aprobaciones que <?= htmlspecialchars($temporal_user) ?> hacia por <strong><?= htmlspecialchars($original_user) ?></strong>?</p>

        <button type="submit" class="btn btn-danger btn-block" style="font-size: 16px; width: 300px">
            Sí, retornar aprobaciones a <?= htmlspecialchars($original_user) ?>
        </button>
    </form>
<br><br><br><br><br>
    <?php
}
?>

<form id="transferir_aprobador" action="" method="post">
<h4>Transferir aprobaciones de un usuario a otro </h4>
Seleccione el usuario origen y el usuario que aprobara en su lugar
<br>
Antiguo aprobador:

<select name="antiguo_aprobador_id" class="custom-select custom-select-sm rounded-0 select2" required>
                <option value="" selected disabled>-- Escoge un usuario --</option >
                  <?php
                  $marcas_query = $conn->query("SELECT id, concat(firstname, ' ', lastname) as `name` FROM users order by `name`");
                  while($row_2 = $marcas_query->fetch_assoc()):
                  ?>
                <option value="<?php  echo $row_2['id']  ?>"> <?php  echo($row_2['name']);?> </option>
                <?php  endwhile;  ?>
                </select>

Nuevo Aprobador:

<select name="nuevo_aprobador_id" class="custom-select custom-select-sm rounded-0 select2" required>
                <option value="" selected disabled>-- Escoge un usuario --</option >
                  <?php
                  $marcas_query = $conn->query("SELECT id, concat(firstname, ' ', lastname) as `name` FROM users order by `name`");
                  while($row_2 = $marcas_query->fetch_assoc()):
                  ?>
                <option value="<?php  echo $row_2['id']  ?>"> <?php  echo($row_2['name']);?> </option>
                <?php  endwhile;  ?>
                </select>

--<b>Atención</b> -- Este nuevo aprobador lo hará permanente o temporalmente?
<select name="type_transfer" >
<option value=""  >-- Escoge una opción--</option >
<option value="permanente"  > Permanente (el antiguo aprobador ya no aprobará estos departamentos)</option >
<option value="temporal" > Temporal (por vacaciones, licencia, etc) </option >
</select>
<br><br>
<button type="submit" class="btn btn-danger btn-block" style="font-size: 16px; width: 300px">Enviar</button>
</form>
<br><br><br><br> <br><br><br>
<form id="approver-frm" action="" method="post">
<h4>Seleccione el departamento y el usuario que va a aprobar solicitudes de ese departamento </h4>
<select name="departamento_id" class="custom-select custom-select-sm rounded-0 select2" required>
								<option value="" selected disabled>-- Escoge un departamento --</option >
									<?php
									$marcas_query = $conn->query("SELECT DISTINCT codigo_ccosto, nombre_ccosto FROM centro_costo where dimension_ccosto = 2 and activo = 'Y' order by `nombre_ccosto`");
									while($row_2 = $marcas_query->fetch_assoc()):
									?>
								<option value="<?php  echo $row_2['codigo_ccosto']  ?>"> <?php  echo($row_2['nombre_ccosto']);?> </option>
								<?php  endwhile;  ?>
									</select>


<select name="user_id" class="custom-select custom-select-sm rounded-0 select2" required>
                <option value="" selected disabled>-- Escoge un usuario --</option >
                  <?php
                  $marcas_query = $conn->query("SELECT id, concat(firstname, ' ', lastname) as `name` FROM users order by `name`");
                  while($row_2 = $marcas_query->fetch_assoc()):
                  ?>
                <option value="<?php  echo $row_2['id']  ?>"> <?php  echo($row_2['name']);?> </option>
                <?php  endwhile;  ?>
                </select>
                <label> Aprobará ordenes de compra solamente </label>
                <input type="checkbox" name="exclusivo_compras" /> <br>
                <label> Aprobará salidas de inventario solamente </label>
                <input type="checkbox" name="exclusivo_inventario" />
<button type="submit" class="btn btn-danger btn-block" style="font-size: 16px;">Enviar</button>

</form>

<p>
Lista de departamentos sin aprobadores:
                  </p>

<?php
  $departamentos_sin_aprobador = $conn->query("SELECT DISTINCT 
    codigo_ccosto, 
    CONCAT(codigo_ccosto, ' ', nombre_ccosto) AS nombre_ccosto
FROM 
    centro_costo
WHERE 
    dimension_ccosto = 2 
    AND activo = 'Y'
    AND codigo_ccosto NOT IN (
        SELECT departamento 
        FROM aprobadores
    )
ORDER BY 
    nombre_ccosto;");
    ?>

    <ul>
    <?php while($row = $departamentos_sin_aprobador->fetch_assoc()): ?>
        <li><?php echo $row['nombre_ccosto']; ?></li>
    <?php endwhile; ?>
</ul>
    


<form id="approver-vacation-frm" action="" method="post" hidden>
<h4> Seleccione el usuario que aprobará las solicitudes del gerente que se va de vacaciones </h4>
<select name="user_id" class="custom-select custom-select-sm rounded-0 select2" required>
								<option value="" selected disabled>-- Escoge el usuario que va a aprobar --</option >
                                <?php
                  $marcas_query = $conn->query("SELECT id, concat(firstname, ' ', lastname) as `name` FROM users order by `name`");
                  while($row_2 = $marcas_query->fetch_assoc()):
                  ?>
                <option value="<?php  echo $row_2['id']  ?>"> <?php  echo($row_2['name']);?> </option>
                <?php  endwhile;  ?>
                </select>
									</select>


<select name="gerente_id" class="custom-select custom-select-sm rounded-0 select2" required>
                <option value="" selected disabled>-- Escoge el gerente que se va de vacaciones --</option >
                  <?php
                  $marcas_query = $conn->query("SELECT id, concat(firstname, ' ', lastname) as `name` FROM users order by `name`");
                  while($row_2 = $marcas_query->fetch_assoc()):
                  ?>
                <option value="<?php  echo $row_2['id']  ?>"> <?php  echo($row_2['name']);?> </option>
                <?php  endwhile;  ?>
                </select>
<button type="submit" class="btn btn-danger btn-block" style="font-size: 16px;">Enviar</button>

</form>

</body>

<script>
// Toggle approver list visibility
$('#toggleApproverList').click(function() {
    $('#approverListSection').slideToggle(300);
});

// Handle delete approver button click
$(document).on('click', '.delete-approver', function(e) {
    e.preventDefault();
    
    var userId = $(this).data('user-id');
    var departamento = $(this).data('departamento');
    var userName = $(this).data('user-name');
    var deptName = $(this).data('dept-name');
    var button = $(this);
    
    if(confirm('¿Está seguro que desea dejar de permitir que ' + userName + ' apruebe el departamento ' + deptName + '?')) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_approver",
            data: { user_id: userId, departamento: departamento },
            method: 'POST',
            dataType: 'json',
            error: function(err) {
                console.log(err);
                alert_toast("Ocurrió un error", 'error');
            },
            success: function(resp) {
                if(typeof resp == 'object' && resp.status == 'success') {
                    alert_toast("Aprobador eliminado correctamente.", 'success');
                    // Remove the list item from the DOM
                    button.closest('li').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else if(resp.status == 'failed' && !!resp.msg) {
                    alert_toast(resp.msg, 'error');
                } else {
                    alert_toast("Ocurrió un error", 'error');
                    console.log(resp);
                }
            }
        });
    }
});

// Handle toggle is_gerente switch change
$(document).on('change', '.toggle-is-gerente', function(e) {

    var userId = $(this).data('user-id');
    var userName = $(this).data('user-name');
    var checkbox = $(this);
    var originalState = checkbox.prop('checked');

    if(confirm('¿Está seguro que desea cambiar el estado de Gerente para ' + userName + '?')) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=toggle_is_gerente",
            data: { user_id: userId },
            method: 'POST',
            dataType: 'json',
            error: function(err) {
                console.log(err);
                alert_toast("Ocurrió un error", 'error');
                checkbox.prop('checked', !originalState);
            },
            success: function(resp) {
                if(typeof resp == 'object' && resp.status == 'success') {
                    alert_toast(resp.msg, 'success');
                    var statusText = resp.new_value == 1 ? 'Sí' : 'No';
                    checkbox.closest('h5').find('.gerente-status').text(statusText);
                } else if(resp.status == 'failed' && !!resp.msg) {
                    alert_toast(resp.msg, 'error');
                    checkbox.prop('checked', !originalState);
                } else {
                    alert_toast("Ocurrió un error", 'error');
                    console.log(resp);
                    checkbox.prop('checked', !originalState);
                }
            }
        });
    } else {
        checkbox.prop('checked', !originalState);
    }
});

// Handle toggle super firma switch change
$(document).on('change', '.toggle-super-firma', function(e) {
    
    var userId = $(this).data('user-id');
    var userName = $(this).data('user-name');
    var checkbox = $(this);
    var originalState = checkbox.prop('checked');
    
    if(confirm('¿Está seguro que desea cambiar el estado de Super Firma para ' + userName + '?')) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=toggle_super_firma",
            data: { user_id: userId },
            method: 'POST',
            dataType: 'json',
            error: function(err) {
                console.log(err);
                alert_toast("Ocurrió un error", 'error');
                // Revert checkbox state on error
                checkbox.prop('checked', !originalState);
            },
            success: function(resp) {
                if(typeof resp == 'object' && resp.status == 'success') {
                    alert_toast(resp.msg, 'success');
                    
                    // Update status text
                    var statusText = resp.new_value == 1 ? 'Activada' : 'Desactivada';
                    checkbox.closest('h5').find('.super-firma-status').text(statusText);
                } else if(resp.status == 'failed' && !!resp.msg) {
                    alert_toast(resp.msg, 'error');
                    // Revert checkbox state on error
                    checkbox.prop('checked', !originalState);
                } else {
                    alert_toast("Ocurrió un error", 'error');
                    console.log(resp);
                    // Revert checkbox state on error
                    checkbox.prop('checked', !originalState);
                }
            }
        });
    } else {
        // User cancelled, revert the checkbox
        checkbox.prop('checked', !originalState);
    }
});

$('#approver-vacation-frm').submit(function(e){
    e.preventDefault()
    $.ajax({
				url:_base_url_+"classes/Master.php?f=update_approver_vacation",
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
			
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						alert_toast("Aprobador actualizado correctamente.",'success');
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
					
                        console.log(resp)
					}
				}
			})
  })

  

  $('#retornar_aprobador').submit(function(e){
    e.preventDefault()
    $.ajax({
				url:_base_url_+"classes/Master.php?f=retornar_aprobador",
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
			
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						alert_toast("Aprobador actualizado correctamente.",'success');
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
					
                        console.log(resp)
					}
				}
			})
  })


  $('#transferir_aprobador').submit(function(e){
    e.preventDefault()
    $.ajax({
				url:_base_url_+"classes/Master.php?f=transfer_approver",
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
			
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						alert_toast("Aprobador actualizado correctamente.",'success');
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
					
                        console.log(resp)
					}
				}
			})
  })

  $('#approver-frm').submit(function(e){
    e.preventDefault()
    $.ajax({
				url:_base_url_+"classes/Master.php?f=update_approver",
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
			
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						alert_toast("Aprobador actualizado correctamente.",'success');
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
					
                        console.log(resp)
					}
				}
			})
  })
  </script>