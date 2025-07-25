<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$host = 'localhost';
$dbname = 'ordenes_compra_pruebas';
$user = 'root'; 
$password = '';


$authToken = '1000.c84653f756dcbdf4cba1d40a3119bf41.41746c0b9b390c011c8763c9e0dd0718';
$organization_id = '867918838';

// Crear conexión MySQL usando PDO
$conn = new mysqli($host, $user, $password, $dbname);
$query_sql = "SELECT * FROM item_list where id = 9";
$result = $conn->query($query_sql);

$all_codigos = '';

// valores del csv:
$item_id = '';
$item_name = 'alcohol';
$sku = 'alcohol_203';
$upc = '';
$mpn = '';
$ean = '';
$isbn = '';
$es_un_articulo_retornable = 'false';
$brand = '';
$manufacturer = '';
$description = '';
$rate = 'USD 3.34';
$account = 'Ventas';
$account_code = '';
$peso_del_paquete = '0';
$longitud_del_paquete = '0';
$ancho_del_paquete = '0';
$alto_del_paquete = '0';
$dimension_unit = 'cm';
$weight_unit = 'kg';
$is_receivable_service = '';
$tax_name = 'ITBMS';
$tax_percentage = '7';
$tax_type = 'ItemAmount';
$purchase_tax_name = 'ITBMS';
$purchase_tax_percentage = '7';
$purchase_tax_type = 'ItemAmount';
$product_type = 'goods';
$source = '1';
$reference_id = '';
$last_sync_time = '';
$status = 'Active';
$usage_unit = 'unidad';
$purchase_rate = 'USD 4.55';
$purchase_account = 'Costes de productos vendidos';
$purchase_account_code = '';
$purchase_description = '';
$inventory_account = 'Activo de inventario';
$inventory_account_code = '';
$metodo_de_valoracion_de_inventario = 'wac';
$reorder_point = '2';
$vendor = '';
$warehouse_name = 'Corporación La Prensa';
$opening_stock = '';
$opening_stock_value = '';
$stock_on_hand = '';
$item_type = 'Inventory';
$enable_bin_tracking = 'false';
$is_combo_product = 'false';
$marca = '';
$departamento = '';
$cf_grupo_del_articulo = '';
$cf_ubicacion_almacen = '02-26-1-D-0';
$cf_articulo_comprable = 'false';
$cf_articulo_inventariable = 'true';
$cf_articulo_vendible = 'true';
$cf_estado = '';  //Usar el de zoho
$cf_stock_maximo = '5';
$cf_id_oracle = '001121065';
$cf_subgrupo_del_articulo = 'OTROS MATERIALES';
$cf_seccion_del_articulo = "N\/A";



$newRow = ['', 'alcohol', 'alcohol_1', '','','','','false','','','','USD 3.34', 'Ventas',''];

$my_row = [$item_id, $item_name, $sku, $upc, $mpn, $ean, $isbn, $es_un_articulo_retornable,
 $brand, $manufacturer, $description, $rate, $account, $account_code, $peso_del_paquete,
$longitud_del_paquete, $ancho_del_paquete, $alto_del_paquete, $dimension_unit, $weight_unit,
$is_receivable_service, $tax_name, $tax_percentage, $tax_type, $purchase_tax_name,
$purchase_tax_percentage, $purchase_tax_type, $product_type, $source,
$reference_id, $last_sync_time, $status, $usage_unit, $purchase_rate,
$purchase_account, $purchase_account_code, $purchase_description, $inventory_account,
$inventory_account_code, $metodo_de_valoracion_de_inventario, $reorder_point, $vendor,
$warehouse_name, $opening_stock, $opening_stock_value, $stock_on_hand, $item_type,
$enable_bin_tracking, $is_combo_product, $marca, $departamento, $cf_grupo_del_articulo,
$cf_ubicacion_almacen, $cf_articulo_comprable, $cf_articulo_inventariable,
$cf_articulo_vendible, $cf_estado, $cf_stock_maximo, $cf_id_oracle,
$cf_subgrupo_del_articulo, $cf_seccion_del_articulo];




// Open the file in append mode
$file = fopen('articulos.csv', 'a');

if ($file === false) {
    die('Error opening the file.');
}

// Add the new row to the CSV
fputcsv($file, $my_row);

// Close the file
fclose($file);

foreach($result as $row) {
    echo " cod_sap: " . $row['codSAP'];
    //$all_codigos .= "'" . $row['codSAP'] . "', ";
}

//campos opcionales:
// "purchase_account_id" => "2"
// "tax_percentage" => "2
//"inventory_account_id" => "2",

// campos que faltan:
// itemsgroupcode
//vat_group
//ubicacion_almacen
//purchase_item boolean
//sales_item boolean
//inventory_item boolean
//purchase_unit
//active boolean
//stock_maximo
//id_oracle
//sub_grupo
//seccion



$data = [
    "name" => $row['description'],
    "sku" => $row['codSAP'],
    "product_type" => "goods",
    "item_type" => "inventory",
    "purchase_rate" => $row['price'],
    "reorder_level" => $row['minimal_stock'],
    "unit" => $row['purchase_unit']
];

/*
$url = "https://www.zohoapis.com/books/v3/items?organization_id=$organization_id";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Zoho-oauthtoken $authToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 201) {
    echo $response;
} else {
    echo "http code: $http_code \n";
    echo $response;
}
  */  


?>