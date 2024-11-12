<?php

namespace Services;
use DateTime;
use DateInterval;

class AnkorService
{
    private $dbService;
    private $ankorConfig;

    private $access_token;
    private $access_token_expiration;

    private $path = "https://www.ankorstore.com/api/v1/";
    //DOC: https://ankorstore.github.io/api-docs/#tag/How-to-work-with-API
 

    public function __construct(DatabaseService $dbService, array $ankorConfig)
    {
        $this->dbService = $dbService;
        $this->ankorConfig = $ankorConfig;


    }

    private function getRefreshToken(){
        
        $clientId = $this->ankorConfig['client_key'];
        $clientSecret = $this->ankorConfig['client_secret'];

        $url = 'https://www.ankorstore.com/oauth/token';
        $body = 'grant_type=client_credentials&client_id='.$clientId.'&client_secret='.$clientSecret.'&scope=*';
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        );

        $response = $this->apiRequest('POST', $url, $headers, $body);

       
        $objetoJSON = json_decode($response['response']);
        $this->access_token = $objetoJSON->access_token;
        $this->updateAnkorCredential('access_token', $objetoJSON->access_token);
        $expiration_date = $this->secondsAddCurrentDate($objetoJSON->expires_in);
        $this->access_token_expiration = $expiration_date;
        $this->updateAnkorCredential('access_token_expiration', $expiration_date);
        
    }

    //Guarda los credenciales en DB
    private function updateAnkorCredential($credential, $credentialValue) {
            
        $accion="UPDATE tblAmzAccess SET $credential = '$credentialValue' WHERE id = 32";
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
        
        $id = 32; // ID Amazon Credentials
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
                $this->getRefreshToken();
            }
            
        } else {
            echo "No se encontraron resultados para ID = $id";
        }

    }

       
    public function apiRequest($method, $url, $headers, $body) {

       
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
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers
        ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            return array("response" => $response,
                        "error" => $err,
                        "httpcode" => $httpcode);

    }
    


    /* ---------------------------------
                   Facturas
     --------------------------------- */

    //No API Service
    
    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */
     /* ---------------------------------
                Orders
     --------------------------------- */


    public function getOrders($state, $max, $offset){

  

        $this->loadCredentials();
        
        $url = $this->path.'orders?'.urlencode('filter[status]').'='.$state.'&'.urlencode('page[limit]').'='.$max;

        $headers = array(
            'Accept: application/vnd.api+json',
            'Authorization: Bearer '.$this->access_token
        );

        $request = $this->apiRequest('GET', $url, $headers, '');
        if ($request['error']) {
            return array("status"=> "error","details"=> $request['error']);
          } else {
  
          return array("status"=> "success","details"=> array("response" => json_decode($request['response']), "code"=> $request['httpcode']));
              
          }

    }

    
    //Get Order Details
    public function getOrderItems($orderId){
        
        $this->loadCredentials();
        $url = $this->path.'orders/'.$orderId.'?include=retailer,orderItems.productOption';
        
        $headers = array(
            'Accept: application/vnd.api+json',
            'Authorization: Bearer '.$this->access_token
        );

        $request = $this->apiRequest('GET', $url, $headers, '');
        if ($request['error']) {
            return array("status"=> "error","details"=> $request['error']);
          } else {
  
          return array("status"=> "success","details"=> array("response" => json_decode($request['response']), "code"=> $request['httpcode']));
              
          }
    }

    //Mapping Orders Fields con Template
    public function processOrders($idMarket, $country, $orders){

        if(count($orders) > 0){

            $processedOrders = array();

            foreach($orders as $order){
                $orderData = null;
                $orderUuid = $order->id;
                $orderId = $order->attributes->reference;
                $orderData['orderId'] = $orderId;

                $orderDetailResponse = $this->getOrderItems($orderUuid);
                $oderDetail = $orderDetailResponse['details']['response'];

                $retailerInfo = $this->getRetailerInfo($oderDetail->included);
                $linesData = $oderDetail->included;

                $oderDetail = $oderDetail->data;
                $orderLines = $oderDetail->relationships->orderItems->data;

                        
                $orderData['total'] = $oderDetail->attributes->brandTotalAmountWithVat / 100;
                $orderData['logistics'] = 0;
                $orderData['email'] = $retailerInfo->email;
                $orderData['CIF'] = $retailerInfo->vatNumber;
                
                
                
                //Direccion ENVIO
                
                $orderData['firstnameEnvio'] = $oderDetail->attributes->shippingOverview->shipToAddress->name;
                $orderData['lastnameEnvio'] = $oderDetail->attributes->shippingOverview->shipToAddress->organisationName;
                $orderData['companyEnvio'] = '';
                $orderData['phoneEnvio'] = $retailerInfo->phoneNumberE164;
                $orderData['streetEnvio'] = $oderDetail->attributes->shippingOverview->shipToAddress->street;
                $orderData['cityEnvio'] = $oderDetail->attributes->shippingOverview->shipToAddress->city;
                $orderData['zipcodeEnvio'] = $oderDetail->attributes->shippingOverview->shipToAddress->postalCode;
                $orderData['provinciaEnvio'] = '';
                $country = $oderDetail->attributes->shippingOverview->shipToAddress->countryCode;
                $orderData['countryEnvio'] = $country;
                
                
                //Direccion Facturacion

                $orderData['firstnameFact'] = $oderDetail->attributes->billingName;
                $orderData['lastnameFact'] = ' ';
                
                $orderData['companyFact'] = $oderDetail->attributes->billingOrganisationName;
                $orderData['phoneFact'] = $retailerInfo->phoneNumberE164;
                $orderData['streetFact'] = $oderDetail->attributes->billingStreet;
                $orderData['cityFact'] = $oderDetail->attributes->billingCity;
                $orderData['zipcodeFact'] = $oderDetail->attributes->billingPostalCode;
                $orderData['provinciaFact'] = '';
                               
                $orderData['isPro'] = 'yes';
                $orderData['sector'] = 7;
                $orderData['tipoCliente'] = 3;
                
                $orderData['success'] = $oderDetail->attributes->createdAt;
      
                foreach($orderLines as $orderLine)
                {   
                    $orderData['idLine'] = $orderUuid;
                    $linePriceQuantity = $this->getAttributesById($linesData, $orderLine->id);
                    $lineProductInfo = $this->getAttributesById($linesData, $linePriceQuantity->relationships->productOption->data->id);
                    
                    $orderData['title'] = $lineProductInfo->attributes->name;
                    $orderData['sku'] = $lineProductInfo->attributes->sku;
                    $orderData['ean'] = $lineProductInfo->attributes->ian;
                    $orderData['totalLine'] = $linePriceQuantity->attributes->brandUnitPrice /100;
                    $orderData['quantity'] = $linePriceQuantity->attributes->quantity;
                
                //insertar en la DB cada linea
                $processedOrders[$orderId][] = $orderData;
                
                }


            }
            //return $processedOrders;
            return array("status"=> "success","details"=> array("response" => $processedOrders));

        }
        else{
            return array("status"=> "error","details"=> 'No orders found');

        }

    }

    private function getRetailerInfo ($array)
    {
        
        foreach ($array as $objeto) {
            if (isset($objeto->type) && $objeto->type === 'retailers') {
                return $objeto->attributes;
            }
        }

        return null; // Si no se encuentra el objeto, devuelve null
    }
    
    private function getAttributesById($array, $id)
    {
        
        foreach ($array as $objeto) {
            if (isset($objeto->id) && $objeto->id === $id) {
                return $objeto;
            }
        }

        return null; // Si no se encuentra el objeto, devuelve null
    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
