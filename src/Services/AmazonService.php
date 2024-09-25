<?php

namespace Services;

use DateTime;
use DateInterval;

class AmazonService
{
    private $dbService;
    private $amzConfig;

    private $access_token;
    private $access_token_expiration;

    private $ID_Amazon = array (
    
        'ES' => 'A1RKKUPIHCS9HS',
        'FR' => 'A13V1IB3VIYZZH',
        'IT' => 'APJ6JRA9NG5V4',
        'DE' => 'A1PA6795UKMFR9',
        'GB' => 'A1F83G8C2ARO7P',
        'NL' => 'A1805IZSGTT6HS',
        'BE' => 'AMEN7PMS3EDWL',
        'PL' => 'A1C3SOZRARQ6R3'
        
    );


    public function __construct(DatabaseService $dbService, array $amzConfig)
    {
        $this->dbService = $dbService;
        $this->amzConfig = $amzConfig;
    }

    private function timestamp()
    {
        return gmdate('Ymd\THis\Z');
    }

    //API request V2 NO IAM (No son necesarios credenciales temporales)
    public function apiRequestV2($method, $path, $query, $body ) {
        
        
        $url = 'https://sellingpartnerapi-eu.amazon.com';
        $host = 'sellingpartnerapi-eu.amazon.com';
        
        if($query){
        ksort($query);
        }
        if($query){
        $queryString = http_build_query($query);
        }
        $url .= $path;
        
        if($method == 'GET' && $query) $url .='?'.$queryString;
        
               
        $isoDate = $this->timestamp(); 
        $shortTime = substr($isoDate, 0, 8);
         
        
        $headers = array(
            'Content-Type: application/json',
            'host: '.$host,
            'user-agent: Prismica/2.0 (Language=php/7.3; Platform=Windows/10)',
            'x-amz-access-token: '.$this->access_token,
            'X-Amz-Date: '.$isoDate,
              );
                
        $curl_settup = array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_HTTPHEADER => $headers,
        );
        
        if ($method === "POST") {
            $curl_settup[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        
        $curl = curl_init();
        //Cargamos la configuracion de curl
        curl_setopt_array($curl, $curl_settup);

        $response = curl_exec($curl);
        
        $err = curl_error($curl);
        
        
        
         if ($err) {
            return "cURL Error #:" . $err;
        } 

        else {
            return $response;
                     
        }
        curl_close($curl);
        
    }

  /* ---------------------------------
                Credenciales
     --------------------------------- */

   //Genera un nuevo token refresh
   public function getRefreshToken() {
        
              
        $url_auth = 'https://api.amazon.com/auth/o2/token';
        
        $postdata_auth = array(
            "grant_type" => "refresh_token",
            "refresh_token" => $this->amzConfig['refresh_token'],
            "client_id" => $this->amzConfig['lwa_client'], 
            "client_secret" => $this->amzConfig['lwa_secret']
        );
        
        $postdata_encoded = http_build_query($postdata_auth);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url_auth,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postdata_encoded,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        

        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } 

        else {
            $objetoJSON = json_decode($response);
            $this->access_token = $objetoJSON->access_token;
            $this->updateAmzCredential('access_token', $objetoJSON->access_token);
            $expiration_date = $this->secondsAddCurrentDate($objetoJSON->expires_in);
            $this->access_token_expiration = $expiration_date;
            $this->updateAmzCredential('access_token_expiration', $expiration_date);
            
        }
        
        
    }

    //Guarda los credenciales en DB
    private function updateAmzCredential($credential, $credentialValue) {
        
        $accion="UPDATE tblAmzAccess SET $credential = '$credentialValue' WHERE id = 1";
        $consulta_= $this->dbService->ejecutarConsulta($accion);
       
        return '';
    }

    //Suma los segundos a la fecha actual
    private function secondsAddCurrentDate($segundos) {
        $fechaActual = new DateTime();
        $fechaActual->add(new DateInterval('PT' . $segundos . 'S'));
        $fechaMySQL = $fechaActual->format('Y-m-d H:i:s');
        return $fechaMySQL;
    }

    public function compararFechas($fechaGuardada)
    {
        $fechaActual = new DateTime();
        $date2 = new DateTime($fechaGuardada);
        if ($fechaActual > $date2) {
            return true;
        } else {
            return false;
        }
    }

    //Recupera los credenciales almacenados
    public function loadCredentials() {
        
        $id = 1; // ID Amazon Credentials
        $consulta = "SELECT * FROM tblAmzAccess WHERE id = $id";
        $resultado = $this->dbService->ejecutarConsulta($consulta);
        if (count($resultado) > 0) {
            $fila = $resultado[0];
            $this->access_token = $fila['access_token'];
            $this->access_token_expiration = $fila['access_token_expiration']; 
            
            //Si expiration < que current time, actualizar credenciale
            $itsAccessTokenExpired = $this->compararFechas($this->access_token_expiration);
            if($itsAccessTokenExpired){
                $this->getRefreshToken();
            }
            else{
                //El Access Token es Valido
            }
            
        } else {
            echo "No se encontraron resultados para ID = $id";
        }

    }

    /* ---------------------------------
               FIN  Credenciales
     --------------------------------- */



    /* ---------------------------------
                   Facturas
     --------------------------------- */

    public function sendInvoice($idOrderMarket, $country, $invoice){

        ////Recuperar url y feedDocumentId (Upload Data)
        $uploadData = $this->createInvoiceDocument();
        $uploadData = json_decode($uploadData);

        if($uploadData->url && $uploadData->feedDocumentId){
            $url = $uploadData->url;
		    $feedDocumentId = $uploadData->feedDocumentId;
            //Cargar la factura
            $uploadStatus = $this->uploadInvoice($url, $invoice);

            if($uploadStatus == 200){
                //Crear feed
                $marketplaceId = $this->ID_Amazon[$country];
                $feedOptions = array('metadata:orderid' => $idOrderMarket, 'metadata:invoicenumber'=> 'invoice_'.$idOrderMarket, 'metadata:documenttype' => 'Invoice');
                $createInvoice = $this->createFeed($feedDocumentId, 'UPLOAD_VAT_INVOICE', [$marketplaceId], $feedOptions);              
                $createInvoice = json_decode($createInvoice);
                
                //si todo OK marcamos enviada en la db
                if($createInvoice->feedId){
                    return array("status"=> "success","details"=> $createInvoice);
                }
                else{
                    return array("status"=> "error","details"=> "Se ha produccido un error al procesar la factura.");
                }
                 
                
            }
            else{
                return array("status"=> "error","details"=> "Se ha produccido un error al cargar factura.");
            }
            
        }
        else{
            return array("status"=> "error","details"=> "Se ha produccido un error al recibir los datos de carga.");
        }

        

    }

    public function createInvoiceDocument()
    {
        $body = array(
            'contentType' => "application/pdf;charset=UTF-8"
        );
        
        $this->loadCredentials();
        return $this->apiRequestV2('POST', '/feeds/2021-06-30/documents', "", $body);
    }

    
    public function createFeed($feedDocumentId, $type, $marketplaceIds, $feedOptions = false) {
        
        $body = array(
            'feedType' => $type,
            'marketplaceIds' => $marketplaceIds,
            'inputFeedDocumentId' => $feedDocumentId
            
                        
        );
        if($feedOptions){
            $body['feedOptions'] = $feedOptions;
        }
        
        $this->loadCredentials();
        
        return $this->apiRequestV2('POST', '/feeds/2021-06-30/feeds', "", $body);
        
    }
    
     private function uploadInvoice($url, $dataStream) {
	
         $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
		  CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $dataStream,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/pdf;charset=UTF-8",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        
        if ($err) {
            return false;
        } 

        else {

            return $http_status;
            
        }
         
         
     }

    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */
    /* ---------------------------------
                    Orders
    --------------------------------- */

    //Descarga de pedidos
    public function getOrders($query) {

        $this->loadCredentials();
        return $this->apiRequestV2('GET', '/orders/v0/orders', $query, "");
        
    }

    public function businessOrders($from) {
        
        $query = array(
            'CreatedAfter' => $from."T00:00:30Z",
            'OrderStatuses' => 'Shipped', 
            "MarketplaceIds" => 'A1RKKUPIHCS9HS,A13V1IB3VIYZZH,A1PA6795UKMFR9,APJ6JRA9NG5V4,A1F83G8C2ARO7P,A1805IZSGTT6HS,AMEN7PMS3EDWL');

        $ordersData = $this->getOrders($query);
        $ordersData = json_decode($ordersData);
        $orders = $ordersData->payload->Orders;
        $nextToken = $ordersData->payload->NextToken;

        $businessOrders = array();

        $ordersFiltered= $this->filterBusinessOrders($orders);
        foreach($ordersFiltered as $order){
            $businessOrders[] = $order->AmazonOrderId;
        }

        if($nextToken){
            while($nextToken)
            {
                $query = array(
                'NextToken' => $nextToken,
                );

                $ordersData = $this->getOrders($query);
                $ordersData = json_decode($ordersData);
                $nextToken = null;
                $orders = $ordersData->payload->Orders;

                if(isset($ordersData->payload->NextToken)){
                    $nextToken = $ordersData->payload->NextToken;
                }

                $ordersFiltered= $this->filterBusinessOrders($orders);

                foreach($ordersFiltered as $order){
                    $businessOrders[] = $order->AmazonOrderId;
                }
            }
        }

        return $businessOrders;


    }

    public function filterBusinessOrders($orders){

        $filteredData = array_filter($orders, function($item) {
            return $item->IsBusinessOrder === true;
        });

        return $filteredData;


    }
    /* ---------------------------------
            FIN   Orders
    --------------------------------- */

    
}
