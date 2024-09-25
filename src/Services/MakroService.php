<?php

namespace Services;

use CURLFile;
use DateTime;

class MakroService
{
    private $dbService;
    private $makroConfig;

    private $makroPath;

    private $paths = [
        'orders' => 'https://app-order-management.prod.de.metro-marketplace.cloud/openapi/v2/'
    ];

      
 
    public function __construct(DatabaseService $dbService, array $makroConfig)
    {
        $this->dbService = $dbService;
        $this->makroConfig = $makroConfig;
    }

     private function loadmakroPath($makroEndpoint) {

        $this->makroPath = $this->paths[$makroEndpoint];

    }

    private function timestamp()
	{
        return time();
    }

    private function signRequest($method, $uri, $body, $timestamp)
    {
        
        $string = implode("\n", [
            $method,
            $uri,
            $body,
            $timestamp,
        ]);

        return hash_hmac('sha256', $string, $this->makroConfig['client_secret']);
    }

    private function getHeaders($timestamp, $signature)
	{
        return array(
            'X-Client-Id: '.$this->makroConfig['client_key'],
            'X-Timestamp: '.$timestamp,
            'X-Signature: '.$signature,
            'Accept: application/json'
        );
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

    public function getOrderIdLine($idOrderMarket){

        $dataOrder = $this->getOrder($idOrderMarket);
        if($dataOrder['status'] == 'error'){
            return array("status" => "error", "details" => $dataOrder['details']);
        }
        else{
            $orderDetails = json_decode($dataOrder['details']);
            $orderLines = $orderDetails->orderLines;

            $data= array();
            foreach($orderLines as $line)
            {
                $data[] = $line->orderLineId;
            }    
            return array("status" => "success", "details" => $data);
        }
        
        
    }

    public function sendInvoice($idOrderMarket, $country, $invoice){

        //Obtener el orderId (No es el mismo que el orderIdMarket)
        $orderData = $this->searchOrderID($idOrderMarket);

        if(!$orderData){
            return array("status" => "error", "details" => "Order not found!!");
        }

        $this->loadmakroPath('orders');

        $country = strtolower($country);

        $fileName = 'invoice-'.$idOrderMarket.'.pdf';

        // Preparar el array de archivos y datos
        $postfields = [
            'invoice' => new CURLFile('data://application/pdf;base64,' . base64_encode($invoice), 'application/pdf', $fileName),
            
        ];
        //Hay que enviar factura por cada línea de pedido
        $orderLineIds = $orderData->orderLines;
        $resume = array();

        foreach($orderLineIds as $orderLine) {
            $orderLineId = $orderLine->orderLineId;
            $url = $this->makroPath.'order-lines/'.$orderLineId.'/invoice';

            $timestamp = $this->timestamp();
            $signature = $this->signRequest('POST', $url, "", $timestamp );

            $headers = $this->getHeaders($timestamp, $signature);

            $request = $this->apiRequest('POST', $url, $headers, $postfields);

            if ($request['error']) {
            return array("status"=> "error","details"=> $request['error']);
            } else {
                $resume[] = $request['response'];
                
            }
        }
        return array("status"=> "success","details"=> array("response" => $resume));
    }


    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */
    /* ---------------------------------
                Orders
     --------------------------------- */

    //Localiza un pedido con el marketOrderId
    public function searchOrderID($marketOrderId){

        $requestOrders = $this->getOrders(15);
        if($requestOrders['status'] == 'error'){
            return array("status" => "error", "details" => $requestOrders['details']);
        }

        $dataOrders = json_decode($requestOrders['details']);
        $orders = $dataOrders->items;

        $findOrder = null;

        foreach ($orders as $order) {
            //Obtener Detalles del pedido donde aparece el orderNumber
            $orderData = $this->getOrder($order->orderId);
            if($orderData['status'] == 'error'){
                return array("status" => "error", "details" => $orderData['details']);
            }
            $orderDetails = json_decode($orderData['details']);

            if ($orderDetails->orderNumber == $marketOrderId) {
                $findOrder = $orderDetails;
                break;
            }
        }
        if ($findOrder) {
            return $findOrder;
        }
        else{
            return false;
        }

    


    }
    public function getOrder($orderId)
    {
        $this->loadmakroPath('orders');
        $url = $this->makroPath . 'orders/' . $orderId;

        $timestamp = $this->timestamp();
        $signature = $this->signRequest('GET', $url, "",
            $timestamp
        );

        $headers = $headers = $this->getHeaders($timestamp, $signature);

        $request = $this->apiRequest('GET',
                $url,
                $headers,
                ''
            );

        if ($request['error']) {
            return array("status" => "error", "details" => $request['error']);
        } else {
            return array("status" => "success", "details" => $request['response']);
        }
    }

    public function getOrders($days)
    {
        $this->loadmakroPath('orders');
        $url = $this->makroPath . 'orders';

        $fechaActual = new DateTime();
        $fechaActual->modify("-$days days");
        $createdFrom = $fechaActual->format(DateTime::ATOM);

        $path = 'filter[created][from]='.$createdFrom;
        $url.= '?'.urlencode($path);

        $timestamp = $this->timestamp();
        $signature = $this->signRequest('GET', $url, "",
            $timestamp
        );

        $headers = $headers = $this->getHeaders($timestamp, $signature);

        $request = $this->apiRequest('GET',
                $url,
                $headers,
                ''
            );

        if ($request['error']) {
            return array("status" => "error", "details" => $request['error']);
        } else {
            return array("status" => "success", "details" => $request['response']);
        }
    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
