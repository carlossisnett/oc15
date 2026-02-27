<?php
exec("php traer_proveedores_de_sap.php > /dev/null 2>&1 &");
exec("php traer_items_de_sap.php > /dev/null 2>&1 &");
exec("php obtener_inventario.php > /dev/null 2>&1 &");
exec("php ./admin/purchase_orders/reenviar_a_sap.php > /dev/null 2>&1 &");
echo "En proceso de sincronización por favor espere 15 mins";

?>