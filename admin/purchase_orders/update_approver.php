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
<form id="approver-frm" action="" method="post">
<h4>Seleccione el departamento y el usuario que va a aprobar solicitudes de ese departamento </h4>
<select name="departamento_id" class="custom-select custom-select-sm rounded-0 select2" required>
								<option value="" selected disabled>-- Escoge un departamento --</option >
									<?php
									$marcas_query = $conn->query("SELECT DISTINCT codigo_ccosto, concat(codigo_ccosto, ' ' , `nombre_ccosto`) as `nombre_ccosto` FROM centro_costo where dimension_ccosto = 2 and activo = 'Y' order by `nombre_ccosto`");
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
    

</body>

<script>
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