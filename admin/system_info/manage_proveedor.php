<?php
require_once('../../config.php');
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
</style>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Agregar Proveedor</h3>
	</div>
	<div class="card-body">
		<form action="" id="proveedor-form">
			<div class="container-fluid">
				<div class="form-group">
					<label for="name" class="control-label">Nombre Proveedor</label>
					<input type="text" name="name" id="name" class="form-control rounded-0" required>
				</div>
				<div class="form-group">
					<label for="codSAP" class="control-label">Código SAP</label>
					<input type="text" name="codSAP" id="codSAP" class="form-control rounded-0" required>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary">Guardar Proveedor</button>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
    $(function(){
        $('#proveedor-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			$('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_proveedor",
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
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						alert_toast("Proveedor guardado correctamente",'success');
						setTimeout(function(){
							$('#proveedor-form')[0].reset();
						}, 1000);
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body").animate({ scrollTop: 0 }, "fast");
                    }else{
						alert_toast("Ocurrió un error",'error');
                        console.log(resp)
					}
                    end_loader()
				}
			})
		})
	})
</script>
