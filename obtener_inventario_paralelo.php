<?php
exec("php obtener_inventario.php > /dev/null 2>&1 &");
echo "Started background task!";

?>