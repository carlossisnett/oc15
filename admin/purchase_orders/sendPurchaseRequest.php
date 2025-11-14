<?php

require_once 'sap_service_layer.php';
require_once 'enviar_correo.php';

function sendPurchaseRequest ($poId){

try {

    $config =  require __DIR__ . '/../../configuracion.php';
    /*
    $path = realpath(__DIR__ . '/../../secrets.json');
    $secrets_file = file_get_contents($path);
    $secrets = json_decode($secrets_file);
    */

    $servername = $config['servername'];
    $username = $config['username'];
    $password = $config['password'];
    $dbname = $config['dbname'];
    $hostsap = $config['hostsap'];
    $puertosap = $config['puertosap'];
    $companydbsap = $config['companydbsap'];
    $usernamesap = $config['usernamesap'];
    $passwordsap = $config['passwordsap'];

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión a la base de datos
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//$data = settings->userdata();
//print_r($data);


    // Datos de la Purchase Request
    $sql = "SELECT a.*, u.codSAP, u.firstname, u.lastname FROM po_list a join users u on u.username = a.username where a.id = $poId";
    //$sql = "SELECT a.*, b.codSAP FROM po_list a inner join supplier_list b on a.supplier_id = b.id where a.id = $poId";
    
    $result = $conn->query($sql);
    $first_name = "";
    $last_name = "";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $poId = $row['id'];
            $poNo = $row['po_no'];
            $supplierId = $row['supplier_id'];
            $supplierCodSAP = $row['codSAP'];// $_SESSION['userdata']['codSAP'];
            $dateCreated = $row['date_created'];
            $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
            $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
            $notes = $row['notes'];
            $taxPercentage = $row['tax_percentage'];
            $discountPercentage = isset($row['discount_percentage']) ? $row['discount_percentage'] : 0;
            $discount_amount = isset($row['discount_amount']) ? $row['discount_amount'] : 0;
            $first_name = $row['firstname'];
            $last_name = $row['lastname'];               
            // Construir la solicitud de compra
            $purchaseRequest = [

                'DocStatus' => 'O',
                'DocDate' => $dateCreatedYMD,
                'RequriedDate' => $dateCreatedYMD,
                'DocDueDate' => $dateCreatedYMD,
                'TaxDate' => $dateCreatedYMD,
                'ReqType' => 171,
                'Requester' => $supplierCodSAP,
                'Comments' => $poNo . " " . $notes,
                'U_HNL_C_TIPO_DOC' => 'SOLICITUDCOMPRA',
                'DocumentLines' => [],
                'TotalDiscount' => $discount_amount,
                //'DiscountPercent' => $discountPercentage,
            ];
    
            // Recuperar los items correspondientes de la tabla order_items
            $sqlItems = "SELECT a.*, b.codSAP, p.codSAP as proveedor_SAP FROM order_items a inner join item_list b on a.item_id = b.id join proveedores p on a.proveedor_id = p.id  WHERE a.po_id = $poId";
            $resultItems = $conn->query($sqlItems);
    
            if ($resultItems->num_rows > 0) {
                while ($item = $resultItems->fetch_assoc()) {

                   // print $item['codSAP'];
                    $itemCode =  $item['codSAP']; //'S0000002';  // Código de item fijo según el requerimiento
                    $description = $item['description'];
                    $quantity = $item['quantity'];
                    $unitPrice = $item['unit_price'];
                    $codigo_marca = $item['codigo_marca'];
                    $codigo_departamento = $item['codigo_departamento'];
                    $url = $item['url'];
                    $proveedor_sap = $item['proveedor_SAP']; // Codigo de SAP del proveedor
    
                    // Determinar el grupo de IVA
                    $vatGroup = ($taxPercentage == 0) ? 'C0' : 'C1';
    
                    // Construir la línea del documento
                    $line = [
                        'ItemCode' => $itemCode,
                        'UnitPrice' => $unitPrice,
                        'U_Comentario' => $description,
                        'U_LP_EnlaceCompra' => $url,
                        'Quantity' => $quantity,
                        'TaxCode' => $vatGroup,
                        'RequiredDate' => $requiredDateYMD,
                        'CostingCode' => $codigo_marca,
                        'CostingCode2' => $codigo_departamento,
                        'LineVendor' => $proveedor_sap
                        //'VatGroup' => $vatGroup,
                        //'DiscPercent' => $discountPercentage
                    ];
    
                    // Agregar la línea al array de líneas del documento
                    $purchaseRequest['DocumentLines'][] = $line;
                }
            }


        }

    } else {
        echo "No se encontraron registros en la tabla po_list.";
    }
        
    $conn->close();

    // Inicializar el Service Layer y crear la Purchase Request
    $sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
    $result = $sap->createPurchaseRequest($purchaseRequest);

    $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    file_put_contents('purchase_request_output_1_1.json', $json);

    $xresult = json_encode($result);

    //print $xresult;

    // Obtener el número de la Purchase Request creada
    $url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $poId;
    $link_element = "<a href='$url_orden'>Ver Solicitud de Compra $poId</a>";
    $mensaje = "Error: La solicitud $poId hecha por $first_name $last_name no pudo ser enviada a SAP. $link_element";
    
    if (isset($result['DocEntry'])) {
        return 'Solicitud SAP creada exitosamente. Número de documento: ' . $result['DocNum'] . '|' . $result['DocNum'] . '|' . $result['DocEntry'];
    } else {
        enviar_email(['desarrollo@prensa.com', 'compras@prensa.com'],"Hubo un error al enviar solicitud de compra $poId a SAP por favor reenviar",$mensaje);
        return 'Error: No se pudo obtener el número del documento de SAP.';
    }

    //require_once(/enviar_correo)
   
    //enviar_email(['nelvir.mirabal@prensa.com'],'PruebaCompra','Este es un correo de prueba para verificar la funcionalidad.');
   
    // Cerrar sesión
    $sap->logout();
} catch (Exception $e) {
    return 'Error: ' . $e->getMessage();
}
}

function enviar_solicitud_inventario($solicitud_id){

    try {
        $config =  require __DIR__ . '/../../configuracion.php';
        $servername = $config['servername'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];
        $hostsap = $config['hostsap'];
        $puertosap = $config['puertosap'];
        $companydbsap = $config['companydbsap'];
        $usernamesap = $config['usernamesap'];
        $passwordsap = $config['passwordsap'];
        $ambiente = $config['ambiente'];

    if($ambiente == "azure"){
        echo "dentro de ambiente azure";
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);
    } else {
         $conn = new mysqli($servername, $username, $password, $dbname);
    }

   

    // Verifica la conexión a la base de datos
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    //$data = settings->userdata();
    //print_r($data);


        // Datos de la Purchase Request
        $sql = "SELECT a.*, u.codSAP, u.firstname, u.lastname FROM solicitud_de_inventario a join users u on u.username = a.username where a.id = $solicitud_id";
        //$sql = "SELECT a.*, b.codSAP FROM solicitud_de_inventario a inner join supplier_list b on a.supplier_id = b.id where a.id = $solicitud_id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $solicitud_id = $row['id'];
                $numero_solicitud = $row['numero_solicitud'];
                $supplierId = $row['supplier_id'];
                $supplierCodSAP = $row['codSAP']; //$_SESSION['userdata']['codSAP'];
                $dateCreated = $row['date_created'];
                $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
                $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
                $notes = $row['notes'];
                $first_name = $row['firstname'];
                $last_name = $row['lastname'];  
                //$taxPercentage = $row['tax_percentage'];
                //$discountPercentage = $row['discount_percentage'];
                
                // Construir la solicitud de compra
                $purchaseRequest = [

                    'DocStatus' => 'O',
                    'DocDate' => $dateCreatedYMD,
                    'RequriedDate' => $dateCreatedYMD,
                    'DocDueDate' => $dateCreatedYMD,
                    'TaxDate' => $dateCreatedYMD,
                    'ReqType' => 171,
                    'Requester' => $supplierCodSAP,
                    'Comments' => $numero_solicitud . " " . $notes,
                    'U_HNL_C_TIPO_DOC' => 'SALIDAINVENTARIO',
                    'DocumentLines' => []
                ];
        
                // Recuperar los items correspondientes de la tabla inventory_items
                $sqlItems = "SELECT a.*, b.codSAP FROM inventory_items a inner join item_list b on a.item_id = b.id WHERE a.solicitud_id = $solicitud_id";
                $resultItems = $conn->query($sqlItems);
        
                if ($resultItems->num_rows > 0) {
                    while ($item = $resultItems->fetch_assoc()) {

                    // print $item['codSAP'];
                        $itemCode =  $item['codSAP']; //'S0000002';  // Código de item fijo según el requerimiento
                        //$description = 'ALQUILER DE AUTOS';
                        $quantity = $item['quantity'];
                        //$unitPrice = $item['unit_price'];
                        $codigo_marca = $item['codigo_marca'];
                        $codigo_departamento = $item['codigo_departamento'];
        
                        // Determinar el grupo de IVA
                        //$vatGroup = ($taxPercentage == 0) ? 'C0' : 'C1';
        
                        // Construir la línea del documento
                        $line = [
                            'ItemCode' => $itemCode,
                            //'UnitPrice' => $unitPrice,
                            //'Dscription' => $description,
                            'Quantity' => $quantity,
                            //'TaxCode' => $vatGroup,
                            'RequiredDate' => $requiredDateYMD,
                            'CostingCode' => $codigo_marca,
                            'CostingCode2' => $codigo_departamento
                            //'VatGroup' => $vatGroup,
                            //'DiscPercent' => $discountPercentage
                        ];
        
                        // Agregar la línea al array de líneas del documento
                        $purchaseRequest['DocumentLines'][] = $line;
                    }
                }


            }

        } else {
            echo "No se encontraron registros en la tabla solicitud_de_inventario.";
        }
            
        $conn->close();

        // Inicializar el Service Layer y crear la Purchase Request
        $sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
        $result = $sap->createPurchaseRequest($purchaseRequest);

        $xresult = json_encode($result);

        $jsonData = json_encode($result, JSON_PRETTY_PRINT);

        // Save to a file
        file_put_contents('solicitud_inventario_result.json', $jsonData);



        //print $xresult;

        // Obtener el número de la Purchase Request creada

        // Obtener el número de la Purchase Request creada
        if(defined('base_url')){
            $url_orden = base_url . "admin/?page=inventario/view_si&id=" . $solicitud_id;
        } else {
            $url_orden = "http://10.0.1.170/finanzas/compras/ordenes_compra/" . "admin/?page=inventario/view_si&id=" . $solicitud_id;
        }
        
        $link_element = "<a href='$url_orden'>Ver Salida de Inventario $solicitud_id para reenviar</a>";
        $mensaje = "Error: La solicitud $solicitud_id hecha por $first_name $last_name no pudo ser enviada a SAP. $link_element";
        if (isset($result['DocEntry'])) {
            return 'Solicitud SAP creada exitosamente. Número de documento: ' . $result['DocNum'] . '|' . $result['DocNum'] . '|' . $result['DocEntry'];
        } else {
            enviar_email(['desarrollo@prensa.com', 'compras@prensa.com'],"Hubo un error al enviar solicitud de inventario $solicitud_id a SAP por favor reenviar",$mensaje);
            return 'Error: No se pudo obtener el número del documento.';
        }

        //require_once(/enviar_correo)
    
        //enviar_email(['nelvir.mirabal@prensa.com'],'PruebaCompra','Este es un correo de prueba para verificar la funcionalidad.');
    
        // Cerrar sesión
        $sap->logout();
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function crear_salida_de_mercancia($solicitud_id){
    
    try {
        $config =  require __DIR__ . '/../../configuracion.php';
        $servername = $config['servername'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];
        $hostsap = $config['hostsap'];
        $puertosap = $config['puertosap'];
        $companydbsap = $config['companydbsap'];
        $usernamesap = $config['usernamesap'];
        $passwordsap = $config['passwordsap'];
        $ambiente = $config['ambiente'];

    if($ambiente == "azure"){
        echo "dentro de ambiente azure";
        $conn = mysqli_init();
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);
    } else {
         $conn = new mysqli($servername, $username, $password, $dbname);
    }


    // Verifica la conexión a la base de datos
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    //$data = settings->userdata();
    //print_r($data);


        // Datos de la Purchase Request
        $sql = "SELECT a.*, u.codSAP, u.firstname, u.lastname FROM solicitud_de_inventario a join users u on u.username = a.username where a.id = $solicitud_id";
        //$sql = "SELECT a.*, b.codSAP FROM solicitud_de_inventario a inner join supplier_list b on a.supplier_id = b.id where a.id = $solicitud_id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $solicitud_id = $row['id'];
                $numero_solicitud = $row['numero_solicitud'];
                $supplierId = $row['supplier_id'];
                $supplierCodSAP = $row['codSAP']; //$_SESSION['userdata']['codSAP'];
                $dateCreated = $row['date_created'];
                $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
                $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
                $notes = $row['notes'];
                $first_name = $row['firstname'];
                $last_name = $row['lastname'];  
                //$taxPercentage = $row['tax_percentage'];
                //$discountPercentage = $row['discount_percentage'];
                
                // Construir la solicitud de compra
                $purchaseRequest = [

                    'DocStatus' => 'O',
                    'DocDate' => $dateCreatedYMD,
                    'RequriedDate' => $dateCreatedYMD,
                    'DocDueDate' => $dateCreatedYMD,
                    'TaxDate' => $dateCreatedYMD,
                    'ReqType' => 171,
                    'Requester' => $supplierCodSAP,
                    'Comments' => $numero_solicitud . " " . $notes,
                    'DocumentLines' => [],
                    'Reference2' => $solicitud_id
                ];
        
                // Recuperar los items correspondientes de la tabla inventory_items
                $sqlItems = "SELECT a.*, b.codSAP FROM inventory_items a inner join item_list b on a.item_id = b.id WHERE a.solicitud_id = $solicitud_id";
                $resultItems = $conn->query($sqlItems);
        
                if ($resultItems->num_rows > 0) {
                    while ($item = $resultItems->fetch_assoc()) {

                    // print $item['codSAP'];
                        $itemCode =  $item['codSAP']; //'S0000002';  // Código de item fijo según el requerimiento
                        //$description = 'ALQUILER DE AUTOS';
                        $quantity = $item['quantity'];
                        //$unitPrice = $item['unit_price'];
                        $codigo_marca = $item['codigo_marca'];
                        $codigo_departamento = $item['codigo_departamento'];
        
                        // Determinar el grupo de IVA
                        //$vatGroup = ($taxPercentage == 0) ? 'C0' : 'C1';
        
                        // Construir la línea del documento
                        $line = [
                            'ItemCode' => $itemCode,
                            //'UnitPrice' => $unitPrice,
                            //'Dscription' => $description,
                            'Quantity' => $quantity,
                            //'TaxCode' => $vatGroup,
                            'RequiredDate' => $requiredDateYMD,
                            'CostingCode' => $codigo_marca,
                            'CostingCode2' => $codigo_departamento
                            //'VatGroup' => $vatGroup,
                            //'DiscPercent' => $discountPercentage
                        ];
        
                        // Agregar la línea al array de líneas del documento
                        $purchaseRequest['DocumentLines'][] = $line;
                    }
                }


            }

        } else {
            echo "No se encontraron registros en la tabla solicitud_de_inventario.";
        }
            
        $conn->close();

        // Inicializar el Service Layer y crear la Purchase Request
        $sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
        $result = $sap->create_inventory_exit($purchaseRequest);

        $xresult = json_encode($result);

        $jsonData = json_encode($result, JSON_PRETTY_PRINT);

        // Save to a file
        file_put_contents('salida_de_mercancia_result.json', $jsonData);



        //print $xresult;

        // Obtener el número de la Purchase Request creada
        if(defined('base_url')){
            $url_orden = base_url . "admin/?page=inventario/view_si&id=" . $solicitud_id;
        } else {
            $url_orden = "http://10.0.1.170/finanzas/compras/ordenes_compra/" . "admin/?page=inventario/view_si&id=" . $solicitud_id;
        }
        
        $link_element = "<a href='$url_orden'>Ver Salida de Inventario $solicitud_id para reenviar</a>";
        $mensaje = "Error: La solicitud $solicitud_id hecha por $first_name $last_name no pudo ser enviada a SAP. $link_element";
        if (isset($result['DocEntry'])) {
            return $result;
        } else {
            enviar_email(['desarrollo@prensa.com', 'compras@prensa.com'],"Hubo un error al enviar solicitud de inventario $solicitud_id a SAP por favor reenviar",$mensaje);
            return 'Error: No se pudo obtener el número del documento.';
        }

        //require_once(/enviar_correo)
        //enviar_email(['nelvir.mirabal@prensa.com'],'PruebaCompra','Este es un correo de prueba para verificar la funcionalidad.');

        // Cerrar sesión
        $sap->logout();
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function get_proveedor($po_id, $conn){
    $query = $conn->query("SELECT o.*, p.codSAP FROM order_items o JOIN proveedores p ON p.id = o.proveedor_id where o.po_id = $po_id LIMIT 1;");
    if(gettype($query) == "boolean"){
        echo "";
    } else {
            $rows = $query->fetch_array();
            if(isset($rows)) {
                return $rows['codSAP'];
            }
    }
}

/*
Integer ->  ____
Crea un pedido de SAP cuando la solicitud de compra ya ha sido aprobada
*/

function create_purchase_order($poId){
    $aditional_expenses = new stdClass();
    try {

        $config =  require __DIR__ . '/../../configuracion.php';
        $servername = $config['servername'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];
        $hostsap = $config['hostsap'];
        $puertosap = $config['puertosap'];
        $companydbsap = $config['companydbsap'];
        $usernamesap = $config['usernamesap'];
        $passwordsap = $config['passwordsap'];
        $ambiente = $config['ambiente'];

   
    if($ambiente == "azure"){
            echo "dentro de ambiente azure";
            $conn = mysqli_init();
            mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
            mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);
    } else {
         $conn = new mysqli($servername, $username, $password, $dbname);
    }

    $proveedor_id = get_proveedor($poId, $conn);

    // Datos de la Purchase Request
    $sql = "SELECT a.*, u.codSAP, u.firstname, u.lastname FROM po_list a join users u on u.username = a.username where a.id = $poId";
    //$sql = "SELECT a.*, b.codSAP FROM po_list a inner join supplier_list b on a.supplier_id = b.id where a.id = $poId";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $poId = $row['id'];
            $poNo = $row['po_no'];
            $supplierId = $row['supplier_id'];
            $supplierCodSAP = $row['codSAP'];// $_SESSION['userdata']['codSAP'];
            $dateCreated = $row['date_created'];
            $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
            $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
            $notes = $row['notes'];
            $taxPercentage = isset($row['tax_percentage']) ? $row['tax_percentage'] : 0;
            $discountPercentage = isset($row['discount_percentage']) ? $row['discount_percentage'] : 0;
            $discount_amount = isset($row['discount_amount']) ? $row['discount_amount'] : 0;
            $owner_code = $row['codSAP'];
            $first_name = $row['firstname'];
            $last_name = $row['lastname'];


            
            
            // Construir la solicitud de compra
            $purchaseRequest = [
                'CardCode' => $proveedor_id,
                'DocStatus' => 'O',
                'DocDate' => $dateCreatedYMD,
                'RequriedDate' => $dateCreatedYMD,
                'DocDueDate' => $dateCreatedYMD,
                'TaxDate' => $dateCreatedYMD,
                'ReqType' => 171,
                'Requester' => $owner_code,
                'Comments' => $poNo . " " . $notes,
                'DocumentsOwner' => $owner_code,
                'OwnerCode' => $owner_code,
                'SalesPersonCode' => 26,
                'SlpCode' => 26,
                'DocumentLines' => [],
                'TotalDiscount' => $discount_amount,
                'DiscountPercent' => $discountPercentage,
                'DocumentAdditionalExpenses' => []
            ];

                
             if(isset($row['shipping_cost'])){
                $aditional_expenses->ExpenseCode = 8;
                $aditional_expenses->LineTotal = $row['shipping_cost'];
                $aditional_expenses->LineTotalSys = $row['shipping_cost'];
                $aditional_expenses->LineGross = $row['shipping_cost'];
                $aditional_expenses->LineGrossSys = $row['shipping_cost'];
                $aditional_expenses->VatGroup = "C0";
                $purchaseRequest['DocumentAdditionalExpenses'][0] = $aditional_expenses;
        }
    
            // Recuperar los items correspondientes de la tabla order_items
            $sqlItems = "SELECT a.*, b.codSAP, p.codSAP as proveedor_SAP FROM order_items a inner join item_list b on a.item_id = b.id join proveedores p on a.proveedor_id = p.id  WHERE a.po_id = $poId";
            $resultItems = $conn->query($sqlItems);
    
            if ($resultItems->num_rows > 0) {
                while ($item = $resultItems->fetch_assoc()) {

                // print $item['codSAP'];
                    $itemCode =  $item['codSAP']; //'S0000002';  // Código de item fijo según el requerimiento
                    $description = $item['description'];
                    $quantity = $item['quantity'];
                    $unitPrice = $item['unit_price'];
                    $codigo_marca = $item['codigo_marca'];
                    $codigo_departamento = $item['codigo_departamento'];
                    $url = $item['url'];
                    $proveedor_sap = $item['proveedor_SAP']; // Codigo de SAP del proveedor
                    $tax_percentage = $item['tax_percentage'];
                    $tax_amount = $item['tax_amount'];
                    
                    $vatGroup = '';
                    // Determinar el grupo de IVA

                    
                    if($tax_percentage > 0){
                        $vatGroup = "C1";
                    } else {
                        $vatGroup = "C0";
                    }
                        

                    /*
                    if($tax_percentage == 0){
                        $vatGroup = 'C0';
                    }
                    if($tax_percentage == 7){
                        $vatGroup = 'C1';
                    }
                        */
                        
                    //$vatGroup = ($tax_percentage == 0) ? 'C0' : 'C1';
    
                    // Construir la línea del documento
                    $line = [
                        'ItemCode' => $itemCode,
                        'UnitPrice' => $unitPrice,
                        'U_Comentario' => $description,
                        'U_LP_EnlaceCompra' => $url,
                        'Quantity' => $quantity,
                        'TaxCode' => $vatGroup,
                        'RequiredDate' => $requiredDateYMD,
                        'CostingCode' => $codigo_marca,
                        'CostingCode2' => $codigo_departamento,
                        'LineVendor' => $proveedor_sap,
                        'VatGroup' => $vatGroup
                        
                        
                        //'VatGroup' => $vatGroup,
                        //'DiscPercent' => $discountPercentage
                    ];

                    //var_dump($line);
    
                    // Agregar la línea al array de líneas del documento
                    $purchaseRequest['DocumentLines'][] = $line;
                }

            }


        }

    } else {
        echo "No se encontraron registros en la tabla po_list.";
    }

    //echo "Line count: " . count($purchaseRequest['DocumentLines']) . "\n";

        
    $conn->close();

    //echo(json_encode($purchaseRequest));

    // Inicializar el Service Layer y crear la Purchase Request

    $sap = new SAPServiceLayer($hostsap, $puertosap, $companydbsap, $usernamesap, $passwordsap);
    $response = $sap->createPurchaseOrder($purchaseRequest);

    $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    file_put_contents('purchase_order_output.json', $json);

    //$xresult = json_encode($result);

    //echo var_dump($response);
    //print $xresult;

    // Obtener el número de la Purchase Order creada
    $base_url = "https://oc15-d8d8asb3cvhzfxb5.canadacentral-01.azurewebsites.net/ordenes_compra/";
    $url_orden = $base_url . "admin/?page=purchase_orders/view_po&id=" . $poId;
    $link_element = "<a href='$url_orden'>Ver Pedido de Compra $poId para reenviar</a>";
    $mensaje = "Error: El pedido de compra $poId hecha por $first_name $last_name no pudo ser enviada a SAP. $link_element";
    $result_string = file_get_contents("purchase_order_output.json");
    if (isset($response['DocEntry'])) {
        return $response;
    } else {
        enviar_email(['desarrollo@prensa.com', 'compras@prensa.com'],"Hubo un error al enviar Pedido de compra $poId a SAP por favor reenviar", $mensaje . "\n \n" . $result_string);
        return 'Error: No se pudo obtener el número del documento.';
    }

    //require_once(/enviar_correo)

    //enviar_email(['nelvir.mirabal@prensa.com'],'PruebaCompra','Este es un correo de prueba para verificar la funcionalidad.');

    // Cerrar sesión
    $sap->logout();
} catch (Exception $e) {
    return 'Error: ' . $e->getMessage();
}

}


/*
function integrate_po ($po_id)
{

// Configuración de la conexión a la base de datos MySQL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ordenes_compra";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión a la base de datos
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Configuración del API Service Layer SAP Business One
$serviceLayerUrl = "xxxx";
$usernameSap = "xxx";
$passwordSap = "xxx";
$companyDb = "xxx";


// Autenticación con SAP Business One Service Layer
$sessionId = authenticateSap($serviceLayerUrl, $usernameSap, $passwordSap, $companyDb);
if (!$sessionId) {
    die("Error en la autenticación con el Service Layer de SAP.");
}

$response_integrate = integrate_request($serviceLayerUrl, $sessionId,$conn,$po_id); 

}

// Función para autenticarse con SAP Business One Service Layer
function authenticateSap($url, $user, $pass, $company) {
    $data = [
        'UserName' => $user,
        'Password' => $pass,
        'CompanyDB' => $company
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url . "/Login", false, $context);
    $response = json_decode($result, true);

    if (isset($response['SessionId'])) {
        return $response['SessionId'];
    } else {
        return false;
    }
}

// Autenticación con SAP Business One Service Layer
function integrate_request($serviceLayerUrl, $sessionId,$conn) 
{
// Recuperar los registros de la tabla po_list
$sql = "SELECT * FROM po_list where id = 14";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $poId = $row['id'];
        $poNo = $row['po_no'];
        $supplierId = $row['supplier_id'];
        $dateCreated = $row['date_created'];
        $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
        $notes = $row['notes'];
        $taxPercentage = $row['tax_percentage'];
        $discountPercentage = $row['discount_percentage'];

        // Construir la solicitud de compra
        $purchaseRequest = [
            'DocStatus' => 'O',
            'DocDate' => $dateCreatedYMD,
            'RequriedDate' => $dateCreatedYMD,
            'DocDueDate' => $dateCreatedYMD,
            'TaxDate' => $dateCreatedYMD,
            'ReqType' => 171,
            'Requester' => 1833,
            'ReqName' => 'MIRABAL, NELVIR',
            'Comments' => $poNo . " " . $notes,
            'DocumentLines' => []
        ];

        // Recuperar los items correspondientes de la tabla order_items
        $sqlItems = "SELECT * FROM order_items WHERE po_id = $poId";
        $resultItems = $conn->query($sqlItems);

        if ($resultItems->num_rows > 0) {
            while ($item = $resultItems->fetch_assoc()) {
                $itemCode = 'S0000002';  // Código de item fijo según el requerimiento
                $description = 'ALQUILER DE AUTOS';
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];

                // Determinar el grupo de IVA
                $vatGroup = ($taxPercentage == 0) ? 'C0' : 'C1';

                // Construir la línea del documento
                $line = [
                    'ItemCode' => $itemCode,
                    'UnitPrice' => $unitPrice,
                    //'Dscription' => $description,
                    'Quantity' => $quantity,
                    'TaxCode' => $vatGroup,
                    'RequiredDate' => $dateCreatedYMD,
                    'CostingCode' => 'GRL-0014',
                    'CostingCode2' => 'CC120001',
                    //'VatGroup' => $vatGroup,
                    //'DiscPercent' => $discountPercentage
                ];

                // Agregar la línea al array de líneas del documento
                $purchaseRequest['DocumentLines'][] = $line;
            }
        }

        // Llamada al API Service Layer para crear la solicitud de compra
        $createResult = createPurchaseRequest($serviceLayerUrl, $sessionId, $purchaseRequest);

        if ($createResult['success']) {
            echo "Solicitud de compra para PO $poNo creada exitosamente.<br>";
        } else {
            echo "Error al crear la solicitud de compra para PO $poNo: " . $createResult['error'] . "<br>";
        }
    }

} else {
    echo "No se encontraron registros en la tabla po_list.";
}

}
$conn->close();

// Función para crear una solicitud de compra en SAP Business One
function createPurchaseRequest($url, $sessionId, $data) {
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Cookie: B1SESSION=$sessionId"
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url . "/PurchaseRequests", false, $context);
    $response = json_decode($result, true);

    // Verificar si la creación fue exitosa
    if (isset($response['error'])) {
        return [
            'success' => false,
            'error' => isset($response['error']['message']['value']) ? $response['error']['message']['value'] : 'Error desconocido'
        ];
    } else {
        return ['success' => true];
    }
}

// Cerrar sesión en el Service Layer de SAP Business One
function logoutSap($url, $sessionId) {
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\nCookie: B1SESSION=$sessionId",
            'method' => 'POST',
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    file_get_contents($url . "/Logout", false, $context);
}

// Cerrar la sesión al final del script
logoutSap($serviceLayerUrl, $sessionId);
*/

?>