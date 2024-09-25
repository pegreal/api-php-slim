<?php

namespace Services;

class KauflandService
{
    private $dbService;
    private $kauflandConfig;

    private $kauflandPath = 'https://sellerapi.kaufland.com/v2/';

  
      
 
    public function __construct(DatabaseService $dbService, array $kauflandConfig)
    {
        $this->dbService = $dbService;
        $this->kauflandConfig = $kauflandConfig;
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

        return hash_hmac('sha256', $string, $this->kauflandConfig['client_secret']);
    }
    
 
    /* ---------------------------------
                   Facturas
     --------------------------------- */


    public function sendInvoice($idOrderMarket, $country, $invoice){

        $country = strtolower($country);
        $fileName = 'invoice-'.$idOrderMarket.'.pdf';
        $postfields = array('original_name'=> $fileName,
                        'mime_type' => 'application/pdf',
						'data'=> base64_encode($invoice));


        $body = json_encode($postfields);

        $url = $this->kauflandPath.'order-invoices/'.$idOrderMarket;

        $timestamp = $this->timestamp();

        $signature = $this->signRequest('POST', $url, $body, $timestamp );

        $userAgent = 'prismica-api';

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
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Shop-Client-Key: ' . $this->kauflandConfig['client_key'],
                'Shop-Signature: ' . $signature,
                'Shop-Timestamp: ' . $timestamp,
                'User-Agent: ' . $userAgent
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
        return array("status"=> "error","details"=> $err);
        } else {
            return array("status"=> "success","details"=> array("response" => $response, "code"=> $httpcode));
            
        }
        
        
    }

    
    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */

    
}
