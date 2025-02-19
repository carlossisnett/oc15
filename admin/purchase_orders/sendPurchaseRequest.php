<?php

require_once 'sap_service_layer.php';  


function sendPurchaseRequest ($poId)
{

try {
    // Datos de configuración
    $hostSAP = 'sap-bo-srvl-mtdtech.skyinone.net';
    $puertoSAP = '50000';
   

    //$companyDBSAP = 'SBO_C184_DB2_PRD';

    $companyDBSAP = 'SBO_C184_DB2_TST2';
  
     if ($_SESSION['userdata']['codSAP'] == '1833')
     {
        $companyDBSAP = 'SBO_C184_DB2_TST2';}

   
    $userNameSAP = 'SAPABO\\ef82f11a-65d9-44a3';
    $passwordSAP = 'Sky0ne2020.';


// Configuración de la conexión a la base de datos MySQL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ordenes_compra_pruebas";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión a la base de datos
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//$data = settings->userdata();
//print_r($data);


    // Datos de la Purchase Request
    $sql = "SELECT a.* FROM po_list a where a.id = $poId";
    //$sql = "SELECT a.*, b.codSAP FROM po_list a inner join supplier_list b on a.supplier_id = b.id where a.id = $poId";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $poId = $row['id'];
            $poNo = $row['po_no'];
            $supplierId = $row['supplier_id'];
            $supplierCodSAP = $_SESSION['userdata']['codSAP'];//$row['codSAP'];
            $dateCreated = $row['date_created'];
            $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
            $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
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
                'Requester' => $supplierCodSAP,
                'Comments' => $poNo . " " . $notes,
                'U_HNL_C_TIPO_DOC' => 'SOLICITUDCOMPRA',
                'DocumentLines' => []
            ];
    
            // Recuperar los items correspondientes de la tabla order_items
            $sqlItems = "SELECT a.*, b.codSAP FROM order_items a inner join item_list b on a.item_id = b.id WHERE a.po_id = $poId";
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
        echo "No se encontraron registros en la tabla po_list.";
    }
        
    $conn->close();

    // Inicializar el Service Layer y crear la Purchase Request
    $sap = new SAPServiceLayer($hostSAP, $puertoSAP, $companyDBSAP, $userNameSAP, $passwordSAP);
    $result = $sap->createPurchaseRequest($purchaseRequest);

    $xresult = json_encode($result);

    //print $xresult;

    // Obtener el número de la Purchase Request creada
    if (isset($result['DocEntry'])) {
        return 'Solicitud SAP creada exitosamente. Número de documento: ' . $result['DocNum'] . '|' . $result['DocNum'] . '|' . $result['DocEntry']  ;
    } else {
        return 'Error: No se pudo obtener el número del documento.';
    }

    //require_once(/enviar_correo)
   
    //enviar_email(['nelvir.mirabal@prensa.com'],'PruebaCompra','Este es un correo de prueba para verificar la funcionalidad.');
   
    // Cerrar sesión
    $sap->logout();
} catch (Exception $e) 
{
    return 'Error: ' . $e->getMessage();
}
}

function enviar_solicitud_inventario($solicitud_id){

    try {
        // Datos de configuración
        $hostSAP = 'sap-bo-srvl-mtdtech.skyinone.net';
        $puertoSAP = '50000';
    
        $companyDBSAP = 'SBO_C184_DB2_TST2';
        //$companyDBSAP = 'SBO_C184_DB2_PRD';
    
        
        if ($_SESSION['userdata']['codSAP'] == '1833')
        {
            $companyDBSAP = 'SBO_C184_DB2_TST2';
        }

        

    
        $userNameSAP = 'SAPABO\\ef82f11a-65d9-44a3';
        $passwordSAP = 'Sky0ne2020.';


    // Configuración de la conexión a la base de datos MySQL
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ordenes_compra_pruebas";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verifica la conexión a la base de datos
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    //$data = settings->userdata();
    //print_r($data);


        // Datos de la Purchase Request
        $sql = "SELECT a.* FROM solicitud_de_inventario a where a.id = $solicitud_id";
        //$sql = "SELECT a.*, b.codSAP FROM solicitud_de_inventario a inner join supplier_list b on a.supplier_id = b.id where a.id = $solicitud_id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $solicitud_id = $row['id'];
                $numero_solicitud = $row['numero_solicitud'];
                $supplierId = $row['supplier_id'];
                $supplierCodSAP = $_SESSION['userdata']['codSAP'];//$row['codSAP'];
                $dateCreated = $row['date_created'];
                $dateCreatedYMD = date('Y-m-d', strtotime($row['date_created']));
                $requiredDateYMD = date('Y-m-d', strtotime($row['required_date']));
                $notes = $row['notes'];
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
        $sap = new SAPServiceLayer($hostSAP, $puertoSAP, $companyDBSAP, $userNameSAP, $passwordSAP);
        $result = $sap->createPurchaseRequest($purchaseRequest);

        $xresult = json_encode($result);

        //print $xresult;

        // Obtener el número de la Purchase Request creada
        if (isset($result['DocEntry'])) {
            return 'Solicitud SAP creada exitosamente. Número de documento: ' . $result['DocNum'] . '|' . $result['DocNum'] . '|' . $result['DocEntry']  ;
        } else {
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