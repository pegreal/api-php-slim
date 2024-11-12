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

        $requestOrders = $this->getOrders(true);
        if($requestOrders['status'] == 'error'){
            return array("status" => "error", "details" => $requestOrders['details']);
        }

        $dataOrders = $requestOrders['details'];
        $orders = $dataOrders->items;

        $findOrder = null;

        foreach ($orders as $order) {
            //Obtener Detalles del pedido donde aparece el orderNumber
            $orderData = $this->getOrder($order->orderId);
            if($orderData['status'] == 'error'){
                return array("status" => "error", "details" => $orderData['details']);
            }
            $orderDetails = $orderData['details'];

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
            return array("status" => "success", "details" => json_decode($request['response']));
        }
    }

    public function getOrders($search, $state=null, $limit=null, $offset=null)
    {
        $days = 30;
        $this->loadmakroPath('orders');
        $url = $this->makroPath . 'orders';

        $fechaActual = new DateTime();
        $fechaActual->modify("-$days days");
        $createdFrom = $fechaActual->format(DateTime::ATOM);

        if($search) $path = 'filter[created][from]='.$createdFrom;
        else $path = urlencode('filter[created][from]').'='.urlencode($createdFrom).'&'.urlencode('filter[status][]').'='.$state;
        if($limit) $path .= '&limit='.$limit;
        if($offset) $path .= '&offset='.$offset;
        $url.= '?'.$path;
        //$url.= '?filter%5Bstatus%5D%5B%5D=confirmed';

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
            return array("status" => "success", "details" => json_decode($request['response']));
        }
    }

    //Mapping Orders Fields con Template
    public function processOrders($idMarket, $country, $orders){

        if(count($orders) > 0){

            $processedOrders = array();

            foreach($orders as $order){
                $orderData = null;
                $makroOrderId = $order->orderId;
               
                $orderDetailResponse = $this->getOrder($makroOrderId);
                $oderDetail = $orderDetailResponse['details'];

                $orderId = $oderDetail->orderNumber;
                $orderData['orderId'] = $orderId;
                $orderLines = $oderDetail->orderLines;
                        
                $orderData['total'] = $oderDetail->total->amount;
                $orderData['logistics'] = $oderDetail->shippingCost->amount;
                $orderData['email'] = $oderDetail->buyerDetails->email;
                if(isset($oderDetail->buyerDetails->taxNumber)){
                    $orderData['CIF'] = $oderDetail->buyerDetails->taxNumber->value;
                }
                else $orderData['CIF'] = '';
                
                
                
                //Direccion ENVIO
                //En muchos casos el nombre completo viene en firstname
                $orderData['firstnameEnvio'] = $oderDetail->buyerDetails->address->shipping->firstName;
                $orderData['lastnameEnvio'] = $oderDetail->buyerDetails->address->shipping->lastName;
                
                $orderData['companyEnvio'] = '';
                $orderData['phoneEnvio'] = $oderDetail->buyerDetails->address->shipping->phone;
                if(!$orderData['phoneEnvio']) $orderData['phoneEnvio'] = '';
                $street = $oderDetail->buyerDetails->address->shipping->addressLine1;
                $street2 = $oderDetail->buyerDetails->address->shipping->addressLine2;
                $orderData['streetEnvio'] = $street.' '.$street2;
                $orderData['cityEnvio'] = $oderDetail->buyerDetails->address->shipping->city;
                $orderData['zipcodeEnvio'] = $oderDetail->buyerDetails->address->shipping->zipCode;
                $orderData['provinciaEnvio'] = '';
                $orderData['countryEnvio'] = $oderDetail->buyerDetails->address->shipping->country;
                
                
                //Direccion Facturacion
                //En muchos casos el nombre completo viene en firstname
                $orderData['firstnameFact'] = $oderDetail->buyerDetails->address->billing->firstName;
                $orderData['lastnameFact'] = $oderDetail->buyerDetails->address->billing->lastName;
                $orderData['companyFact'] = $oderDetail->buyerDetails->address->billing->companyName;
                $orderData['phoneFact'] = $oderDetail->buyerDetails->address->shipping->phone;

                $street = $oderDetail->buyerDetails->address->billing->addressLine1;
                $street2 = $oderDetail->buyerDetails->address->billing->addressLine2;
                $orderData['streetFact'] = $street.' '.$street2;
                $orderData['cityFact'] = $oderDetail->buyerDetails->address->billing->city;
                $orderData['zipcodeFact'] = $oderDetail->buyerDetails->address->billing->zipCode;
                $orderData['provinciaFact'] = '';
                
                $orderData['sector'] = null;
                $orderData['isPro'] = 'no';

                if($orderData['CIF']) {
                    $orderData['isPro'] = 'yes';
                    if($orderData['companyFact']){
                        $orderData['tipoCliente'] = 3; //Empresa
                        $orderData['sector'] = 7;
                    }
                    else $orderData['tipoCliente'] = 2; //Autonomo
                    
                }
                else $orderData['tipoCliente'] = 1; 

                
                
                
                $orderData['success'] = $oderDetail->created;
      
                foreach($orderLines as $orderLine)
                {   //No siempre viene el email y no siempre en todas las líneas
                   
                    $orderData['idLine'] = $orderLine->orderLineId;
                    $orderData['title'] = $orderLine->productName;
                    $orderData['sku'] = $orderLine->sku;
                    $orderData['ean'] = $orderLine->gtin;
                    $orderData['totalLine'] = $orderLine->pricePerItem->amount;;
                    $orderData['quantity'] = $orderLine->quantity;
                
                //insertar en la DB cada linea
                $processedOrders[$orderId][] = $orderData;
                
                }


            }
            //return $processedOrders;
            return array("status"=> "success","details"=> array("response" => $processedOrders));

        }
        else{
            return array("status"=> "error", "details"=> "No orders found");;
        }

    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
