<?php

namespace Services;

class KuantoService
{
    private $dbService;
    private $kuantoConfig;

    private $access_token;

    private $path = "https://seller.kuantokusta.pt/api";
    //DOC: https://seller.kuantokusta.pt/api/kms/#/
 

    public function __construct(DatabaseService $dbService, array $kuantoConfig)
    {
        $this->dbService = $dbService;
        $this->kuantoConfig = $kuantoConfig;


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

        $url = $this->path."/kms/orders?maxResultsPerPage=".$max."&orderState=".$state;

        $headers = array(
            'Accept: application/json',
            'x-api-key: '.$this->kuantoConfig['client_token']  
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
                
                $orderId = $order->orderId;
                $orderData['orderId'] = $orderId;

                $orderLines = $order->products;
                        
                $orderData['total'] = $order->totalPrice;
                $orderData['logistics'] = $order->shippingsPrice;
                
                $orderData['CIF'] = $order->billingAddress->vat;
                               
                
                //Direccion ENVIO
                $truncateName = $this->truncateName($order->deliveryAddress->customerName);
                $orderData['firstnameEnvio'] = $truncateName['firstname'];
                $orderData['lastnameEnvio'] = $truncateName['lastname'];
                   
                $orderData['companyEnvio'] = '';
                $orderData['phoneEnvio'] = $order->deliveryAddress->contact;
                if(!$orderData['phoneEnvio']) $orderData['phoneEnvio'] = '';
                $street = $order->deliveryAddress->address1;
                $street2 = $order->deliveryAddress->address2;
                $orderData['streetEnvio'] = $street.' '.$street2;
                $orderData['cityEnvio'] = $order->deliveryAddress->city;
                $orderData['zipcodeEnvio'] = $order->deliveryAddress->zipCode;
                $orderData['provinciaEnvio'] = '';
                $country = $order->deliveryAddress->country;
                if($country == 'Portugal') $countryCode = 'PT';
                if($country == null) $countryCode = 'PT';
                if($country == 'España') $countryCode = 'ES';
                $orderData['countryEnvio'] = $countryCode;
                
                //Direccion Facturacion
                $truncateNameFact = $this->truncateName($order->billingAddress->customerName);

                $orderData['firstnameFact'] = $truncateNameFact['firstname'];
                $orderData['lastnameFact'] = $truncateNameFact['lastname'];
                
                $orderData['companyFact'] = '';
                $orderData['phoneFact'] = $order->billingAddress->contact;

                $street = $order->billingAddress->address1;
                $street2 = $order->billingAddress->address2;
                $orderData['streetFact'] = $street.' '.$street2;
                $orderData['cityFact'] = $order->billingAddress->city;
                $orderData['zipcodeFact'] = $order->billingAddress->zipCode;
                $orderData['provinciaFact'] = '';
                               
                $orderData['isPro'] = null;
                $orderData['sector'] = null;
                $orderData['tipoCliente'] = 1;
                $orderData['email'] = $orderData['phoneEnvio'].'@kuantokusta.pt';
                
                $orderData['success'] = $order->createdAt;
      
                foreach($orderLines as $orderLine)
                {   

                    $orderData['idLine'] = null;
                    $orderData['title'] = $orderLine->name;
                    $orderData['sku'] = $this->obtenerSku($orderData['title']);
                    $orderData['ean'] = $orderData['sku'];
                    $orderData['totalLine'] = $orderLine->price;
                    $orderData['quantity'] = $orderLine->quantity;
                
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

    public function truncateName ($string){
	
        $arrayText = explode(" ", $string);
        $texto1 = '';
        $texto2 = '';
        $resultado = array();
        
        foreach($arrayText as $word)
        {
            
            if(!strlen($texto1))
            {
                $texto1 = $word;
            }
            else 
            {
                $texto2 .= $word.' ';
            }
        }
        
        $resultado['firstname'] = $texto1;
        $resultado['lastname'] = $texto2;
        
        return $resultado;
    }
    public function obtenerSku($cadena) {
        // Localiza la posición del último guion
        $posicion = strrpos($cadena, '-');
    
        // Si no hay guion en la cadena, devuelve una cadena vacía
        if ($posicion === false) {
            return '';
        }
    
        // Obtén el texto después del último guion y elimina los espacios alrededor
        $resultado = trim(substr($cadena, $posicion + 1));
    
        return $resultado;
    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
