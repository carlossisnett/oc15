<?php

class SAPServiceLayer{
    private $serviceLayerUrl;
    private $companyDB;
    private $userName;
    private $password;
    private $sessionId;

    public function __construct($host, $puerto, $companyDB, $userName, $password)
    {
        $this->serviceLayerUrl = "https://{$host}:{$puerto}/b1s/v1";
        $this->companyDB = $companyDB;
        $this->userName = $userName;
        $this->password = $password;
        $this->login();
    }

    // Función para iniciar sesión en el Service Layer
    private function login()
    {
        $loginUrl = "{$this->serviceLayerUrl}/Login";

        //echo $loginUrl;

        $loginData = json_encode([
            "CompanyDB" => $this->companyDB,
            "UserName" => $this->userName,
            "Password" => $this->password
        ]);

        $response = $this->sendRequest('POST', $loginUrl, $loginData);

        if (isset($response['SessionId'])) {
            $this->sessionId = $response['SessionId'];
        } else {
            throw new Exception('Error al iniciar sesión en SAP: ' . json_encode($response));
        }
    }

    // Función para crear una Purchase Request
    public function createPurchaseRequest($purchaseRequestData)
    {
        $createUrl = "{$this->serviceLayerUrl}/PurchaseRequests";
        return $this->sendRequest('POST', $createUrl, json_encode($purchaseRequestData));
    }

    public function createPurchaseOrder($purchaseOrderData){
        $createUrl = "{$this->serviceLayerUrl}/PurchaseOrders";
        return $this->sendRequest('POST', $createUrl, json_encode($purchaseOrderData));
    }

    public function item_location($item_data){
        $createUrl = "{$this->serviceLayerUrl}/PurchaseOrders";
        return $this->sendRequest('POST', $createUrl, json_encode($item_data));
    }

    // Función genérica para enviar solicitudes al Service Layer
    private function sendRequest($method, $url, $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Error en cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    // Función para construir los encabezados de las solicitudes
    private function buildHeaders()
    {
        $headers = ['Content-Type: application/json'];
        if ($this->sessionId) {
            $headers[] = 'Cookie: B1SESSION=' . $this->sessionId;
        }
        $headers[] = 'Prefer: odata.maxpagesize=1000';
        return $headers;
    }

    // Función para cerrar sesión
    public function logout()
    {
        $logoutUrl = "{$this->serviceLayerUrl}/Logout";
        $this->sendRequest('POST', $logoutUrl);
    }

    public function get_old_open_purchase_requests(){
    // Base URL for the SAP Service Layer (replace with your actual URL)
    $base_url = $this->serviceLayerUrl;
    $requests_url = "/PurchaseRequests?\$filter=";
    #$requests_url = "/PurchaseRequests/\$metadata";

    // Calculate the date 2 weeks ago
    $twoWeeksAgo = date("Y-m-d", strtotime("-2 days"));

    // Build the query to filter open Purchase Requests older than 2 weeks
    $query = urlencode("DocumentStatus eq 'O' and CreationDate lt '$twoWeeksAgo'");
    echo "Query: " . $query . "\n";
    

    // Full URL with query
    $url = $base_url . $requests_url . $query;

    //echo "Request url: " . $url . "\n";

    // Send the GET request
    $response = $this->sendRequest("GET", $url);
    /*
    $file = fopen('response_sap.json','w+');
    fwrite($file, json_encode($response));
    fclose($file);
        */
    if (!$response || !isset($response['value'])) {
        throw new Exception("Failed to retrieve open purchase requests.");
     } else{
        return $response;
     }
    }

    public function get_my_item_location(){
    // Base URL for the SAP Service Layer (replace with your actual URL)
    $base_url = $this->serviceLayerUrl;
    $requests_url = "/BinLocations?\$filter=";
    #$requests_url = "/PurchaseRequests/\$metadata";

    // Calculate the date 2 weeks ago
    //$twoWeeksAgo = date("Y-m-d", strtotime("-2 days"));

    // Build the query to filter open Purchase Requests older than 2 weeks
    $query = urlencode("ItemCode eq 'ALP0000002' and WhsCode eq '02'");
    echo "Query: " . $query . "\n";
    

    // Full URL with query
    $url = $base_url . $requests_url . $query;

    //echo "Request url: " . $url . "\n";

    // Send the GET request
    $response = $this->sendRequest("POST", $url);

    var_dump($response);
    

    }

    public function get_close_purchase_requests(){
        // Base URL for the SAP Service Layer (replace with your actual URL)
        $base_url = $this->serviceLayerUrl;
        $requests_url = "/PurchaseRequests?\$filter=";
        #$requests_url = "/PurchaseRequests/\$metadata";
    
            // Calculate the date range for the past 1 months
        $start_date = date("Y-m-d", strtotime("-1 months")); // Two months ago
        $end_date = date("Y-m-d"); // Today

        // Build the query to filter closed purchase requests based on the date they were closed (UpdateDate)
        $query = urlencode("DocumentStatus eq 'C' and UpdateDate ge '$start_date' and UpdateDate le '$end_date'");
        echo "Query: " . $query . "\n";
    
        // Full URL with query
        $url = $base_url .  $requests_url . $query;
    
        echo "Request url: " . $url . "\n";
    
        // Send the GET request
        $response = $this->sendRequest("GET", $url);
            
        if (!$response || !isset($response['value'])) {
            throw new Exception("Failed to retrieve open purchase requests.");
         } else{
            return $response;
         }
        }

    public function get_purchase_requests_status($docEntries) {
            // Ensure the list of DocEntries is not empty
            if (empty($docEntries)) {
                throw new Exception("No purchase request IDs provided.");
            }
        
            // Convert array of IDs into a comma-separated string
            $docEntriesStr = implode(",", $docEntries);
        
            // Base URL for the SAP Service Layer
            $base_url = $this->serviceLayerUrl;
            $requests_url = "/PurchaseRequests?\$filter=";
        
            // Build the filter query
            $query = "DocNum in ($docEntriesStr)&\$select=DocEntry,DocNum,DocumentStatus,UpdateDate";
        
            // Construct the full API URL
            $url = $base_url . $requests_url . urlencode($query);
        
            // Send the GET request
            $response = $this->sendRequest("GET", $url);

            $file = fopen('response_status.json','w+');
            fwrite($file, json_encode($response));
            fclose($file);
        
            if (!$response || !isset($response['value'])) {
                throw new Exception("Failed to retrieve purchase requests status.");
            }
        
            return $response['value']; // Return the list of purchase requests with their status
        }

    public function get_items(){
        $base_url = $this->serviceLayerUrl;
        $requests_url = "Items";

        $url = $base_url . $requests_url;

        $i = 0;

        
        while($i < 26){
        

        $url = $base_url . "/" . $requests_url;

        

        $response = $this->sendRequest("GET", $url);
        /*
        $file = fopen('my_items_' . strval($i) . '.json','w+');
        fwrite($file, json_encode($response));
        fclose($file);
        */

        file_put_contents('my_items_' . strval($i) . '.json', json_encode($response, JSON_UNESCAPED_UNICODE));
        $requests_url = $response['odata.nextLink'];

        echo "Next URL: " . $requests_url . "\n";
        
        $i++;
    
        }
        
        
 
    }

    
    public function get_inventory(){
        $base_url = $this->serviceLayerUrl;
        $requests_url = "Items?\$select=ItemCode,ItemName,InventoryItem,ItemWarehouseInfoCollection";

        $url = $base_url . $requests_url;

        $i = 0;
        $connection = new mysqli("localhost", "root", "", "ordenes_compra");
        while($i < 25){
        
            $url = $base_url . "/" . $requests_url;

            

            $response = $this->sendRequest("GET", $url);
            $file = fopen('all_items_' . strval($i) . '.json','w+');
            fwrite($file, json_encode($response));
            fclose($file);

            $json_string = file_get_contents('all_items_' . $i . '.json');

            $data = json_decode($json_string, true);

            //print_r($data);

            $count = count($data['value']);
            $j = 0;

            

                               /* 
                               Example of the query I'm building:
                UPDATE item_list
                SET 
                    stock_actual = CASE 
                        WHEN codSAP = '1' THEN $sum
                        WHEN codSAP = '2' THEN $sum
                        WHEN codSAP = '3' THEN $sum
                        ELSE stock_actual
                    END
                WHERE codSAP IN ('1', '2', '3');

                */

            $sql_query = "UPDATE item_list SET stock_actual = CASE ";
            $case_query = "";
            $sql_query_end = "END WHERE codSAP IN (";
            while($j < $count){
                $itemcode = $data['value'][$j]['ItemCode'];
                $inventory_item = $data['value'][$j]['InventoryItem'];
                $warehouse_collection = $data['value'][$j]['ItemWarehouseInfoCollection'];

                $count_warehouse = count($warehouse_collection);
                $k = 0;
                $sum = 0;

                if($inventory_item == 'tYES'){
                    $inventory_item = true;
                }else {
                    $inventory_item = false;
                }

                if($inventory_item == true) {
                    while($k < $count_warehouse){
                        $quantity = $warehouse_collection[$k]['InStock'];
                        $sum += $quantity;
                        $k++;
                        }

                    $case_query = $case_query . "WHEN codSAP = '$itemcode' THEN $sum ";
                    $sql_query_end .= "'$itemcode'" . ", ";
                        
                    //$result = $connection->query("UPDATE item_list SET stock_actual = $sum WHERE codSAP = '$itemcode'");
                    

                    /*
                    $myfile = fopen("items_stock.txt", "a") or die("Unable to open file!");
                    $txt = "itemcode: $itemcode, stock: $sum \n";
                    fwrite($myfile, $txt);
                    fclose($myfile);

                    */

                }
                $j++;


            }
        $sql_query_end = substr($sql_query_end, 0, -2);
        $fullquery = $sql_query . $case_query . " ELSE stock_actual " . $sql_query_end . ");";
        $result = $connection->query($fullquery);
        //$myfile = fopen("items_stock.txt", "a") or die("Unable to open file!");
        //$txt = "itemcode: $itemcode, stock: $sum \n";
        //fwrite($myfile, $fullquery);
        //fclose($myfile);
            
        
        //unlink('all_items_' . strval($i) . '.json');

        $requests_url = $response['odata.nextLink'];

        echo "Next URL: " . $requests_url . "\n";
        
        $i++;
    
        }
        
        
 
    }

    public function get_purchase_orders(){
            
            $base_url = $this->serviceLayerUrl;
            $requests_url = "PurchaseOrders";
    
            $url = $base_url . $requests_url;
    
            $i = 24;
            $connection = new mysqli("localhost", "root", "", "ordenes_compra");
            while($i < 25){
            
                $url = $base_url . "/" . $requests_url;
    
                
    
                $response = $this->sendRequest("GET", $url);
                $file = fopen('all_purchase_orders_' . strval($i) . '.json','w+');
                fwrite($file, json_encode($response));
                fclose($file);
    
                $json_string = file_get_contents('all_purchase_orders_' . $i . '.json');
    
                $data = json_decode($json_string, true);
    
                //print_r($data);
    
                $count = count($data['value']);
                $j = 0;
    
    
                }

            
            
     
        }

    public function get_proveedor($po_id, $conn){
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

    public function create_purchase_order($poId){
        try {
            $conn = new mysqli("localhost", "root", "", "ordenes_compra_pruebas");
            $proveedor_id = $this->get_proveedor($poId, $conn);

        // Datos de la Purchase Request
        $sql = "SELECT a.*, u.codSAP FROM po_list a join users u on u.username = a.username where a.id = $poId";
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
                $taxPercentage = $row['tax_percentage'];
                $discountPercentage = $row['discount_percentage'];
                $owner_code = $row['codSAP'];
                
                // Construir la solicitud de compra
                $purchaseRequest = [
                    'CardCode' => $proveedor_id,
                    'DocStatus' => 'O',
                    'DocDate' => $dateCreatedYMD,
                    'RequriedDate' => $dateCreatedYMD,
                    'DocDueDate' => $dateCreatedYMD,
                    'TaxDate' => $dateCreatedYMD,
                    'ReqType' => 171,
                    'Requester' => 1888,
                    'Comments' => $poNo . " " . $notes . "5ta prueba",
                    'DocumentsOwner' => 1888,
                    'OwnerCode' => 1888,
                    'SalesPersonCode' => 26,
                    'SlpCode' => 26,
                    'DocumentLines' => []
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

        echo "Line count: " . count($purchaseRequest['DocumentLines']) . "\n";

            
        $conn->close();

        // Inicializar el Service Layer y crear la Purchase Request
        $response = $this->createPurchaseOrder($purchaseRequest);

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents('purchase_order_output.json', $json);

        //$xresult = json_encode($result);

        //echo var_dump($response);
        //print $xresult;

        // Obtener el número de la Purchase Request creada
        if (isset($xresult['DocEntry'])) {

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

}


?>
