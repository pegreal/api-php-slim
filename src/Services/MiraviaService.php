<?php

namespace Services;

use DateTime;
use DateInterval;

class MiraviaService
{
    private $dbService;
    private $miraviaConfig;

    private $access_token;

    private $path = "https://api.miravia.es/rest"; 
    //DOC: https://open.miravia.com/apps/doc/getting_started?spm=euspain.27008829.0.0.44387c73slpDIO

    private $marketState = [
        'confirm' => '',
        'pending' => 'pending',
        'shipped' => 'shipped',
        'cancel' => 'canceled'
    ];
 

    public function __construct(DatabaseService $dbService, array $miraviaConfig)
    {
        $this->dbService = $dbService;
        $this->miraviaConfig = $miraviaConfig;


    }

    //Guarda los credenciales en DB
    private function updateMiraviaCredential($accesToken, $accesTokenExpiration, $refreshToken, $refreshTokenExpiration) {
        
        $accion="UPDATE tblAmzAccess 
        SET access_token = '$accesToken',
            access_token_expiration = '$accesTokenExpiration', 
            refresh_token = '$refreshToken', 
            refresh_token_expiration = '$refreshTokenExpiration' 
        WHERE id = 30";
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

    public function compararFechas($fechaGuardada, $secure)
    {
        $fechaActual = new DateTime();
        
        $fechaExpiracion = new DateTime($fechaGuardada);
        // Sumar x días a la fecha expiracion para tener margen de token
        if($secure){
            $fechaSeguridad = (clone $fechaExpiracion)->sub(new DateInterval('P3D'));
        }
        else{
            $fechaSeguridad = $fechaExpiracion;
        }
        

        if ($fechaActual > $fechaSeguridad) {
            return true; //expired
        } else {
            return false; //valid
        }
    }

    public function refreshToken($refresh_token){
        
        $requestParams = $this->getParams([]);
        $requestParams['refresh_token'] = $refresh_token;

        $sign = $this->generateSign($requestParams, '/auth/token/refresh');
        $requestParams['sign'] = $sign;

        $url = $this->path.'/auth/token/refresh';
        $headers = array(
            "Content-Type: application/json;charset=utf-8",
            "Accept: application/json"
        );

        $response = $this->apiRequest("POST", $url, $headers, json_encode($requestParams));

            $objetoJSON = json_decode($response['response']);
            $access_token = $objetoJSON->access_token;
            $this->access_token = $access_token;
            $expires_in = $this->secondsAddCurrentDate($objetoJSON->expires_in);
            $refresh_token = $objetoJSON->refresh_token;
            $refresh_expires_in = $this->secondsAddCurrentDate($objetoJSON->refresh_expires_in);
            $this->updateMiraviaCredential($access_token, $expires_in, $refresh_token, $refresh_expires_in);

                       
        return $access_token;

    }

    private function loadCredentials() {

        //Recuperar desde DB
        $id = 30; // ID Amazon Credentials
        $consulta = "SELECT * FROM tblAmzAccess WHERE id = $id";
        $resultado = $this->dbService->ejecutarConsulta($consulta);
        if (count($resultado) > 0) {
            $fila = $resultado[0];
            $this->access_token = $fila['access_token'];
            $expiration = $fila['access_token_expiration'];
            $refresh_token = $fila['refresh_token'];
            $refreshExpiration = $fila['refresh_token_expiration'];
            
            //Si expiration < que current time, actualizar credenciale
            $itsAccessTokenExpired = $this->compararFechas($expiration, false);
            $itsRefreshTokenExpired = $this->compararFechas($refreshExpiration, true);
            if($itsAccessTokenExpired || $itsRefreshTokenExpired){
                $this->access_token = $this->refreshToken($refresh_token);
            }
            else{
                //El Access Token es Valido
            }
            
        } else {
            echo "No se encontraron resultados para ID = $id";
        }

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
        $params['app_key'] = $this->miraviaConfig['client_key'];
        $params['timestamp'] = $this->timestamp();
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
        $orderState = $this->marketState[$state];

        $parametros = array(
            "status" => $orderState,  //unpaid, pending, canceled, ready_to_ship, delivered, returned, shipped and failed.
            "limt" => $max ? $max : 50,
            "offset" => $offset ? $offset : 0,
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
                $orderData['tipoCliente'] = 1;
                
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
            return array("status"=> "error","details"=> 'No orders found');

        }

    }

    public function getCarrierData($label, $market){

        $consulta = "SELECT * FROM tblcarriers WHERE strLabel = '$label' AND idMarket = '$market'";
        $resultado = $this->dbService->ejecutarConsulta($consulta);
        if (count($resultado) > 0) {
            $fila = $resultado[0];
            return $fila;
        }
        else return false;

    }

    public function orderItemList($order){
       
        $consulta="SELECT * FROM tblPedidosAPI WHERE strNumPedido = '$order'";
        $resultado= $this->dbService->ejecutarConsulta($consulta);

        $order_item_id_list = array();
                    foreach ($resultado as $orderLine) {
                        $order_item_id_list[] = (int)$orderLine['strInfoCarrier5'];
                    }

        return $order_item_id_list;
        
        
    }


    public function sendConfirm($order, $carrier, $tracking){

  

        $this->loadCredentials();

        $itemList = $this->orderItemList($order);
        $carrierData = $this->getCarrierData($carrier, '30');
        $carrierCode = $carrierData['strCode'];
        
        $requestParams = $this->getParams([]);
        $requestParams['payload'] = json_encode(array("order_id"=> $order,
                                           "order_item_id_list" => $itemList,
                                           "shipping_provider_code"=> $carrierCode,
                                           "tracking_number" => $tracking));
        
        $sign = $this->generateSign($requestParams, '/v2/order/fulfill');
        $requestParams['sign'] = $sign;

        $url = $this->path.'/v2/order/fulfill';

        $headers = array(
            "Content-Type: application/json;charset=utf-8",
            "Accept: application/json"
        );

        $request = $this->apiRequest('POST', $url, $headers, json_encode($requestParams));
        if ($request['error']) {
            return array("status"=> "error","details"=> $request['error']);
          } else {
  
          return array("status"=> "success","details"=> array("response" => json_decode($request['response']), "code"=> $request['httpcode']));
              
          }

    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
