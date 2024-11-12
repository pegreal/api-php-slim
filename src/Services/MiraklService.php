<?php

namespace Services;

use CURLFile;

class MiraklService
{
    private $dbService;
    private $miraklConfig;

    private $access_token;
    private $miraklPath;
    private $miraklIdShop;

    private $paths = [
        'leroy' => 'https://leroymerlin-marketplace.mirakl.net/api/',
        'carrefour' => 'https://carrefoures-prod.mirakl.net/api/'
    ];

    //DOC: (loggin) https://help.mirakl.net/help/api-doc/seller/mmp.html

    private $countryLeroy = [
        '001' => 'fr',
        '002' => 'es',
        '003' => 'pt',
        '005' => 'it'

    ];

    public function __construct(DatabaseService $dbService, array $miraklConfig)
    {
        $this->dbService = $dbService;
        $this->miraklConfig = $miraklConfig;


    }

    private function loadCredentials($miraklMarket) {

        $this->access_token = $this->miraklConfig[$miraklMarket]['token'];

    }
    private function loadMiraklPath($miraklMarket) {

        $this->miraklPath = $this->paths[$miraklMarket];

    }

    private function loadMiraklIdShop($miraklMarket) {

        $this->miraklIdShop = $this->miraklConfig[$miraklMarket]['idShop'];

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

    public function sendInvoice($miraklMarket, $idOrderMarket, $country, $invoice){

        $this->loadMiraklPath($miraklMarket);

        $country = strtolower($country);

        //Leroy
        if($miraklMarket == 'leroy') {
            $prefixLeroy = substr($idOrderMarket, 0, 3);
            $countryLeroy = $this->countryLeroy[$prefixLeroy];
            $miraklMarket = 'leroy_'.$countryLeroy;
        }
        $this->loadMiraklIdShop($miraklMarket);

        $this->loadCredentials($miraklMarket);

        $token = $this->access_token;

        // Datos JSON para el campo 'order_documents'
        $fileName = "invoice-".$idOrderMarket.".pdf";
        $orderDocuments = [
            "order_documents" => [
                [
                    "file_name" => $fileName,
                    "type_code" => "CUSTOMER_INVOICE"
                ]
            ]
        ];

        $jsonOrderDocuments = json_encode($orderDocuments);

        $postfields = [
            'files' => new CURLFile('data://application/pdf;base64,' . base64_encode($invoice), 'application/pdf', $fileName),
            'order_documents' => $jsonOrderDocuments
        ];

        $url = $this->miraklPath.'orders/'.$idOrderMarket.'/documents?shop_id='.$this->miraklIdShop;

        $headers = array(
            'Authorization: ' . $token,
            'Accept: application/json',
            'Content-Type: multipart/form-data'
        );

        $request = $this->apiRequest('POST', $url, $headers, $postfields);
        /*
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
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => array(
          'Authorization: ' . $token,
          'Accept: application/json',
          'Content-Type: multipart/form-data'
        ),
      ));

	$response = curl_exec($curl);
        $err = curl_error($curl);
	$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
      */
        if ($request['error']) {
          return array("status"=> "error","details"=> $request['error']);
        } else {

        return array("status"=> "success","details"=> array("response" => $request['response'], "code"=> $request['httpcode']));
            
        }
        
    }

    
    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */
     /* ---------------------------------
                Orders
     --------------------------------- */


    public function getOrders($miraklMarket, $country, $state, $max, $offset){

        $this->loadMiraklPath($miraklMarket);

        $country = strtolower($country);

        //Leroy
        if($miraklMarket == 'leroy') {
            $miraklMarket = 'leroy_'.$country;
        }
        $this->loadMiraklIdShop($miraklMarket);

        $this->loadCredentials($miraklMarket);

        $token = $this->access_token;
        $url = $this->miraklPath.'orders?order_state_codes='.$state.'&max='.$max.'&offset='.$offset.'&shop_id='.$this->miraklIdShop;
        $headers = array(
            'Authorization: ' . $token,
            'Accept: application/json'
        );

        $request = $this->apiRequest('GET', $url, $headers, '');
        if ($request['error']) {
            return array("status"=> "error","details"=> $request['error']);
          } else {
  
          return array("status"=> "success","details"=> array("response" => json_decode($request['response']), "code"=> $request['httpcode']));
              
          }

    }

    //Mapping Orders Fields con Template
    public function processOrders($miraklMarket, $idMarket, $country, $orders){

        if(count($orders) > 0){

            $processedOrders = array();

            foreach($orders as $order){
                $orderData = null;
                $orderLines = $order->order_lines;
                $orderId = $order->order_id;
                $orderData['orderId'] = $orderId;
                        
                $orderData['total'] = $order->total_price;
                $orderData['logistics'] = $order->shipping_price;
                
                $orderData['email'] = $order->customer_notification_email;
                if(isset($order->order_additional_fields->order_additional_field)){
                    $orderData['CIF'] = $order->order_additional_fields->order_additional_field->value;
                }
                else{
                    $orderData['CIF'] = '';
                }
                
                
                //Direccion ENVIO
                $orderData['firstnameEnvio'] = $order->customer->shipping_address->firstname;
                $orderData['lastnameEnvio'] = $order->customer->shipping_address->lastname;
                $orderData['companyEnvio'] = $order->customer->shipping_address->company;
                $orderData['phoneEnvio'] = $order->customer->shipping_address->phone;
                if(!$orderData['phoneEnvio']) $orderData['phoneEnvio'] = $order->customer->shipping_address->phone_secondary;
                $street = $order->customer->shipping_address->street_1;
                $street2 = $order->customer->shipping_address->street_2;
                $orderData['streetEnvio'] = $street.' '.$street2;
                $orderData['cityEnvio'] = $order->customer->shipping_address->city;
                $orderData['zipcodeEnvio'] = $order->customer->shipping_address->zip_code;
                $orderData['provinciaEnvio'] = $order->customer->shipping_address->state;
                $orderData['countryEnvio'] = $order->customer->shipping_address->country;
                
                
                //Direccion Facturacion
                $orderData['firstnameFact'] = $order->customer->billing_address->firstname;
                $orderData['lastnameFact'] = $order->customer->billing_address->lastname;
                $orderData['companyFact'] = $order->customer->billing_address->company;
                $orderData['phoneFact'] = $orderData['phoneEnvio'];
                //if(!$orderData['phoneFact']) $orderData['phoneFact'] = $order->customer->billing_address->phone_secondary;
                //if($orderData['phoneFact'==''])$orderData['phoneEnvio'];
                $street = $order->customer->billing_address->street_1;
                $street2 = $order->customer->billing_address->street_2;
                $orderData['streetFact'] = $street.' '.$street2;
                $orderData['cityFact'] = $order->customer->billing_address->city;
                $orderData['zipcodeFact'] = $order->customer->billing_address->zip_code;
                $orderData['provinciaFact'] = $order->customer->billing_address->state;
                               
                $orderData['isPro'] = null;
                $orderData['sector'] = null;
                $orderData['tipoCliente'] = 1;
                
                $orderData['success'] = $order->last_updated_date;;
      
                foreach($orderLines as $orderLine)
                {
                    $orderData['idLine'] = ''; //Pending, not in use
                    $orderData['title'] = $orderLine->product_title;
                    $orderData['sku'] = $orderLine->offer_sku;
                    $orderData['ean'] = $orderLine->offer_sku;
                    $orderData['totalLine'] = $orderLine->price_unit;
                    $orderData['quantity'] = $orderLine->quantity;
                
                //insertar en la DB cada linea
                $processedOrders[$orderId][] = $orderData;
                
                }


            }
            //return $processedOrders;
            return array("status"=> "success","details"=> array("response" => $processedOrders));

        }
        else{
            return array("status"=> "success","details"=> "No orders found");
        }

    }

     /* ---------------------------------
                FIN   Orders
     --------------------------------- */

    
}
