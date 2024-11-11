<?php

namespace Services;

use CURLFile;

class MiraviaService
{
    private $dbService;
    private $miraviaConfig;

    private $access_token;

    private $path = "https://api.miravia.es/rest"; 

 

    public function __construct(DatabaseService $dbService, array $miraviaConfig)
    {
        $this->dbService = $dbService;
        $this->miraviaConfig = $miraviaConfig;


    }

    private function loadCredentials() {

        $this->access_token = '50000300b26hgUYAoxsxE82mtgMsB0qkZcEQen1d18d53bzwDjGvnzgVxHsAo3N';

    }

    public function generateSign($params, $apiName) {

        ksort($params);
        $concatenatedString = '';

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                        $concatenatedString .= $key. json_encode($value);
            } else {
                $concatenatedString .= $key . $value;
            }
        }

        // Agregar el nombre de la API al principio de la cadena concatenada
        $concatenatedString = $apiName . $concatenatedString;
        return strtoupper(hash_hmac('sha256', $concatenatedString, $this->miraviaConfig['client_secret']));
        
    }

    private function generateURL($params)
	{
					
		$urlParams = '';
		foreach ($params as $k => $v)
		{
		    $urlParams .= "&".$k."=".$v;
        }
		unset($k, $v);
                $urlParams = substr($urlParams, 1);
		return $urlParams;
	}

    private function timestamp()
	{
        return time(). '000';
    }

    private function getParams($params)
	{
        $params['timestamp'] = $this->timestamp();
        $params['app_key'] = $this->miraviaConfig['client_key'];
        $params['access_token'] = $this->access_token;
        $params['sign_method'] = 'sha256';
        return $params;
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

        $hoy = date("Y-m-d");
        $date_past = strtotime('-30 day', strtotime($hoy));
        $date_past = date('Y-m-d', $date_past);

        $parametros = array(
            "status" => $state,  //unpaid, pending, canceled, ready_to_ship, delivered, returned, shipped and failed.
            "limt" => $max,
            "offset" => $offset,
            "created_after" => $date_past."T12:00:00+08:00"//'2017-02-10T09:00:00+08:00'//,
        );

        $requestParams = $this->getParams($parametros);
        
        $sign = $this->generateSign($requestParams, '/orders/get');

        $reqParams = $this->generateURL($requestParams);
        $reqParams = str_replace("+", "%2b", $reqParams);
        $reqParams .= "&sign=".$sign;

        $url = $this->path.'/orders/get?'.$reqParams;

        $headers = array(
            "Content-Type: application/json;charset=utf-8",
            "Accept: application/json"
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
        
        $params = array("order_id" => $orderId);
        $requestParams = $this->getParams($params);

        $sign = $this->generateSign($requestParams, '/order/items/get');
        
        $reqParams = $this->generateURL($requestParams);
        $reqParams = str_replace("+", "%2b", $reqParams);
        $reqParams .= "&sign=".$sign;

        $url = $this->path.'/order/items/get?'.$reqParams;
        
        $headers = array(
            "Content-Type: application/json;charset=utf-8",
            "Accept: application/json"
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
                
                $orderId = $order->order_number;
                $orderData['orderId'] = $orderId;

                $orderDetailResponse = $this->getOrderItems($orderId);
                $oderDetail = $orderDetailResponse['details']['response']->data;

                $orderLines = $oderDetail;
                        
                $orderData['total'] = $order->price;
                $orderData['logistics'] = $order->shipping_fee;
                
                $orderData['CIF'] = null;
                
                
                
                //Direccion ENVIO
                //En muchos casos el nombre completo viene en firstname
                if(strlen($order->address_shipping->last_name) == 0){
                    $pieces = explode(" ", $order->address_shipping->first_name);
                    $orderData['firstnameEnvio'] = $pieces[0];
                    $lastname = array_shift($pieces);
                    $orderData['lastnameEnvio'] = implode(" ", $pieces);
                }
                else{
                    $orderData['firstnameEnvio'] = $order->address_shipping->first_name;
                    $orderData['lastnameEnvio'] = $order->address_shipping->last_name;
                }
                
                $orderData['companyEnvio'] = '';
                $orderData['phoneEnvio'] = $order->address_shipping->phone;
                if(!$orderData['phoneEnvio']) $orderData['phoneEnvio'] = '';
                $street = $order->address_shipping->address1;
                $street2 = $order->address_shipping->address2;
                $orderData['streetEnvio'] = $street.' '.$street2;
                $orderData['cityEnvio'] = $order->address_shipping->city;
                $orderData['zipcodeEnvio'] = $order->address_shipping->post_code;
                $orderData['provinciaEnvio'] = '';
                $orderData['countryEnvio'] = $order->country;
                
                
                //Direccion Facturacion
                //En muchos casos el nombre completo viene en firstname
                if(strlen($order->address_billing->last_name) == 0){
                    $pieces = explode(" ", $order->address_billing->first_name);
                    $orderData['firstnameFact'] = $pieces[0];
                    $lastname = array_shift($pieces);
                    $orderData['lastnameFact'] = implode(" ", $pieces);
                }
                else{
                    $orderData['firstnameFact'] = $order->address_billing->first_name;
                    $orderData['lastnameFact'] = $order->address_billing->last_name;
                }
                $orderData['companyFact'] = '';
                $orderData['phoneFact'] = $order->address_billing->phone;

                $street = $order->address_billing->address1;
                $street2 = $order->address_billing->address2;
                $orderData['streetFact'] = $street.' '.$street2;
                $orderData['cityFact'] = $order->address_billing->city;
                $orderData['zipcodeFact'] = $order->address_billing->post_code;
                $orderData['provinciaFact'] = '';
                               
                $orderData['isPro'] = null;
                $orderData['sector'] = null;
                
                $orderData['success'] = $order->created_at;
      
                foreach($orderLines as $orderLine)
                {   //No siempre viene el email y no siempre en todas las líneas
                    if(strlen($orderLine->digital_delivery_info) == 0){
                        $orderData['email'] = '***'.$order->address_shipping->phone.'@miravia.market.com';
                    }
                    else{
                        $orderData['email'] = '***'.$orderLine->digital_delivery_info; 
                    }

                    $orderData['idLine'] = $orderLine->order_item_id;
                    $orderData['title'] = $orderLine->name;
                    $orderData['sku'] = $orderLine->sku;
                    $orderData['ean'] = $orderLine->sku;
                    $orderData['totalLine'] = $orderLine->paid_price;
                    $orderData['quantity'] = 1; //Cada unidad viene en una línea. Pendiente de validar
                
                //insertar en la DB cada linea
                $processedOrders[$orderId][] = $orderData;
                
                }


            }
            //return $processedOrders;
            return array("status"=> "success","details"=> array("response" => $processedOrders));

        }
        else{
            return;
        }

    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
