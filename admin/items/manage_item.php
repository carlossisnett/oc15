
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
		<h3 class="card-title">Agregar Artículo</h3>
	</div>
	<div class="card-body">
		<form action="" id="articulo-form">
			<div class="container-fluid">
				<div class="form-group">
					<label for="codSAP" class="control-label">Código SAP <span class="text-danger">*</span></label>
					<input type="text" name="codSAP" id="codSAP" class="form-control rounded-0" required>
					<small class="form-text text-muted">Ingrese el código SAP del artículo</small>
				</div>
				<div class="form-group">
					<label for="description" class="control-label">Nombre del Artículo <span class="text-danger">*</span></label>
					<textarea rows="3" name="description" id="description" class="form-control rounded-0" required></textarea>
					<small class="form-text text-muted">Ingrese el nombre o descripción del artículo</small>
				</div>
				<div class="form-group">
					<label for="inventory_item" class="control-label">¿Se guardará en inventario? <span class="text-danger">*</span></label>
					<select name="inventory_item" id="inventory_item" class="form-control rounded-0" required>
						<option value="">Seleccione una opción</option>
						<option value="1">Sí</option>
						<option value="0">No</option>
					</select>
				</div>
				<div class="form-group">
					<label for="purchase_item" class="control-label">¿Se puede comprar este artículo de otras empresas? <span class="text-danger">*</span></label>
					<select name="purchase_item" id="purchase_item" class="form-control rounded-0" required>
						<option value="">Seleccione una opción</option>
						<option value="1">Sí</option>
						<option value="0">No</option>
					</select>
				</div>
				<div class="form-group">
					<label for="sales_item" class="control-label">¿Este artículo será vendido por nuestra empresa? <span class="text-danger">*</span></label>
					<select name="sales_item" id="sales_item" class="form-control rounded-0" required>
						<option value="">Seleccione una opción</option>
						<option value="1">Sí</option>
						<option value="0">No</option>
					</select>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary">Guardar Artículo</button>
					<button type="reset" class="btn btn-secondary">Limpiar Formulario</button>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
    $(function(){
        $('#articulo-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			$('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_articulo",
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
						alert_toast("Artículo guardado correctamente",'success');
						setTimeout(function(){
							$('#articulo-form')[0].reset();
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

