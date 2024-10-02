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
