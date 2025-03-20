<?php

class SAPServiceLayer
{
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
    $twoWeeksAgo = date("Y-m-d", strtotime("-2 weeks"));

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
        $requests_url = "Items?\$select=ItemCode,ItemName,InventoryItem,ItemWarehouseInfoCollection";

        $url = $base_url . $requests_url;

        $i = 0;

        
        while($i < 26){
        

        $url = $base_url . "/" . $requests_url;

        

        $response = $this->sendRequest("GET", $url);
        $file = fopen('all_items_' . strval($i) . '.json','w+');
        fwrite($file, json_encode($response));
        fclose($file);

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
        $connection = new mysqli("localhost", "root", "", "ordenes_compra_pruebas");
        while($i < 26){
        
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
        $myfile = fopen("items_stock.txt", "a") or die("Unable to open file!");
        //$txt = "itemcode: $itemcode, stock: $sum \n";
        fwrite($myfile, $fullquery);
        fclose($myfile);
            
        
        unlink('all_items_' . strval($i) . '.json');

        $requests_url = $response['odata.nextLink'];

        echo "Next URL: " . $requests_url . "\n";
        
        $i++;
    
        }
        
        
 
    }

}


?>
