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
$query_sql = "SELECT * FROM item_list where id > 3 and id < 27";
$result = $conn->query($query_sql);

$all_codigos = '';

// valores del csv:





// Open the file in append mode
$file = fopen('articulos_upload.csv', 'a');

if ($file === false) {
    die('Error opening the file.');
}

$grupos_json = file_get_contents('./grupos_de_items.json');
$secciones_json = file_get_contents('./secciones_de_items.json');
$subgrupos_json = file_get_contents('./subgrupos_de_items.json');

// Decode the JSON string into an associative array
$grupos = json_decode($grupos_json, true);
$secciones = json_decode($secciones_json, true);
$subgrupos = json_decode($subgrupos_json, true);

echo "grupo #1: " . $grupos["122"];


foreach($result as $row) {
    echo " cod_sap: " . $row['codSAP'];
    //$all_codigos .= "'" . $row['codSAP'] . "', ";

    $item_id = '';
    $item_name = $row['description'];
    $sku = $row['codSAP'];
    $upc = '';
    $mpn = '';
    $ean = '';
    $isbn = '';
    $es_un_articulo_retornable = 'false';
    $brand = '';
    $manufacturer = '';
    $description = '';
    $rate = 'USD 0.00';
    $account = '';
    $account_code = '';
    $peso_del_paquete = '0';
    $longitud_del_paquete = '0';
    $ancho_del_paquete = '0';
    $alto_del_paquete = '0';
    $dimension_unit = 'cm';
    $weight_unit = 'kg';
    $is_receivable_service = '';
    $tax_name =  '';//$row['sales_vat_group'] == "V1" ? 'ITBMS' : "EXCENTO";
    $tax_percentage = '';//$row['sales_vat_group'] == "V1" ? '7' : "0";
    $tax_type = ''; //'ItemAmount';
    $purchase_tax_name = $row['purchase_vat_group'] == "C1" ? 'ITBMS' : "EXCENTO";
    $purchase_tax_percentage = $row['purchase_vat_group'] == "C1" ? '7' : "0";
    $purchase_tax_type = 'ItemAmount';
    $product_type = $row['inventory_item'] == 1 ? 'goods' : 'service';
    $source = '1';
    $reference_id = '';
    $last_sync_time = '';
    $status = 'Active'; // Por ahora nada mas vamos a subir a Zoho los que son active.
    $usage_unit = $row['purchase_unit'];
    $purchase_rate = 'USD ' . $row['price'];
    $purchase_account = 'Costes de productos vendidos';
    $purchase_account_code = '';
    $purchase_description = '';
    $inventory_account = $row['inventory_item'] == 1 ? 'Activo de inventario' : '';
    $inventory_account_code = '';
    $metodo_de_valoracion_de_inventario = 'wac';
    $reorder_point = isset($row['minimal_stock']) ? $row['minimal_stock'] : '' ;
    $vendor = '';
    $warehouse_name = 'Corporación La Prensa';
    $opening_stock = '';
    $opening_stock_value = '';
    $stock_on_hand = '';
    $item_type = $row['inventory_item'] == 1 ? 'Inventory' : 'Purchases';
    $enable_bin_tracking = $row['inventory_item'] == 1 ? 'true' : 'false';
    $is_combo_product = 'false';
    $marca = '';
    $departamento = '';
    $nombre_del_grupo = '';
    if(isset($row['itemsgroupcode']) == true){
        $nombre_del_grupo = $grupos[$row['itemsgroupcode']] . " " .  $row['itemsgroupcode'];
    }
    $cf_grupo_del_articulo = $nombre_del_grupo;
    $cf_ubicacion_almacen = isset($row['ubicacion_almacen']) ? $row['ubicacion_almacen'] : '';
    $cf_articulo_comprable = $row['purchase_item'] == 1 ? 'true' : 'false';
    $cf_articulo_inventariable = $row['inventory_item'] == 1 ? 'true' : 'false';
    $cf_articulo_vendible = $row['sales_item'] == 1 ? 'true' : 'false';
    $cf_estado = $row['active'] == 1 ? 'activo' : 'inactivo';;  //Usar el de zoho
    $cf_stock_maximo = isset($row['maximal_stock']) ? $row['maximal_stock'] : '';
    $cf_id_oracle = isset($row['id_oracle']) ? $row['id_oracle'] : '';
    $nombre_del_subgrupo = '';
    if(isset($row['sub_grupo']) == true){
        $nombre_del_subgrupo = $subgrupos[$row['sub_grupo']] . " " .  $row['sub_grupo'];
    }
    $cf_subgrupo_del_articulo = $nombre_del_subgrupo;
    $nombre_de_seccion = '';
    if(isset($row['seccion']) == true){
        $nombre_de_seccion = $secciones[$row['seccion']] . " " .  $row['seccion'];
    }
    $cf_seccion_del_articulo = $nombre_de_seccion;



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

    fputcsv($file, $my_row);
}

fclose($file);

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