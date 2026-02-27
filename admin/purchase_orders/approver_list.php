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