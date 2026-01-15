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
        SELECT aprobadores.*, users.name, centro_costo.nombre_ccosto 
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
            echo "<h5>" . htmlspecialchars($row_2['name']) . "</h5>";
            echo "<ul>";
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

        // Print the department under this user
        echo "<li>" . htmlspecialchars($row_2['nombre_ccosto']) . " (" . htmlspecialchars($row_2['departamento']) . ")$label_text</li>";
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