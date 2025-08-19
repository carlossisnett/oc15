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
    <h4>Lista de departamentos con sus aprobadores:</h4>
									<?php
									$marcas_query = $conn->query("SELECT aprobadores.*, users.name, centro_costo.nombre_ccosto FROM aprobadores join users on users.id = aprobadores.user_id join centro_costo on centro_costo.codigo_ccosto = aprobadores.departamento order by users.id");
									while($row_2 = $marcas_query->fetch_assoc()):
									?>
                                    <p> <?php  echo($row_2['nombre_ccosto']); echo(" "); echo($row_2['departamento']); echo(" "); echo($row_2['name']);?> </p>
								<?php  endwhile;  ?>

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