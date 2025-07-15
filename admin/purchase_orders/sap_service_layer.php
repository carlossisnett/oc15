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

    public function getPurchaseRequest($purchaseRequestData){
        $base_url = $this->serviceLayerUrl;
        $requests_url = "/PurchaseRequests?\$filter=";
        $query = urlencode("DocEntry eq 26631");
        $url = $base_url . $requests_url . $query;
        return $this->sendRequest('GET', $url, null);
    }

    public function createPurchaseOrder($purchaseOrderData){
        $createUrl = "{$this->serviceLayerUrl}/PurchaseOrders";
        return $this->sendRequest('POST', $createUrl, json_encode($purchaseOrderData));
    }

    public function create_inventory_exit($Data){
        $createUrl = "{$this->serviceLayerUrl}/InventoryGenExits";
        return $this->sendRequest('POST', $createUrl, json_encode($Data));
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
        $headers[] = 'Prefer: odata.maxpagesize=300';
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
    $time_ago = date("Y-m-d", strtotime("-2 days"));

    // Build the query to filter open Purchase Requests older than 2 weeks
    $query = urlencode("DocumentStatus eq 'O' and CreationDate lt '$time_ago'");
    echo "Query: " . $query . "\n";
    

    // Full URL with query
    $url = $base_url . $requests_url . $query;

    //echo "Request url: " . $url . "\n";

    // Send the GET request
    $response = $this->sendRequest("GET", $url);
    /*
    $file = fopen('response_sap_abiertas.json','w+');
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
    
            // Calculate the date range for the past 2 months
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

        $all_results = [];

        //$all_results = array_merge($all_results, $response['value']);

        $file = fopen('response.json','w+');
        fwrite($file, json_encode($response));
        fclose($file);
            
        if (!$response || !isset($response['value'])) {
            throw new Exception("Failed to retrieve open purchase requests.");
         } else{
            return $response;
         }
        }

        public function get_item_location_in_warehouse($item_code){
            // Base URL for the SAP Service Layer (replace with your actual URL)
            $base_url = $this->serviceLayerUrl;
            $requests_url = "/InventoryPostings?\$filter=";
            #$requests_url = "/PurchaseRequests/\$metadata";
        
                // Calculate the date range for the past 2 months
           // $start_date = date("Y-m-d", strtotime("-1 months")); // Two months ago
            //$end_date = date("Y-m-d"); // Today
    
            // Build the query to filter closed purchase requests based on the date they were closed (UpdateDate)
            $query = urlencode("ItemCode eq '$item_code'");
            echo "Query: " . $query . "\n";
            
        
            // Full URL with query
            $url = $base_url .  $requests_url . $query; //. "&\$expand=ItemWarehouseInfoCollection";
        
            echo "Request url: " . $url . "\n";
        
            // Send the GET request
            $response = $this->sendRequest("GET", $url);
    
            $all_results = [];
    
            //$all_results = array_merge($all_results, $response['value']);
    
            $file = fopen('response_ITEM_LOCATION_2.json','w+');
            fwrite($file, json_encode($response));
            fclose($file);
                
            if (!$response || !isset($response['value'])) {
                throw new Exception("Failed to retrieve open purchase requests.");
             } else{
                return $response;
             }
            }

}
?>
