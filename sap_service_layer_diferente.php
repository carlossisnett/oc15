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

}


?>
