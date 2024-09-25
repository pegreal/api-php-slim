<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Services\InvoicesService;


class InvoicesController
{
    
    private $invoicesService;

    
    public function __construct(InvoicesService $invoicesService)
    {
        $this->invoicesService = $invoicesService;

    }
    
    public function getInvoices(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            //Query Parameters
            $queryParams = $request->getQueryParams();
            $marketId = $queryParams['idMarket'];
             
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $invoicesData = $this->invoicesService->getInvoices($marketId);
                    
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'invoices' => $invoicesData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
    }

    public function updateInvoiceState(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            $data = $request->getParsedBody();
            $idOrderMarket = $data['idOrderMarket'];
            $market = $data['market'];
            $state = $data['state'];

            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $invoicesData = $this->invoicesService->updateInvoiceState($idOrderMarket, $market, $state);
                                   
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $invoicesData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }

    public function sendInvoice(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            $data = $request->getParsedBody();
            $idOrderMarket = $data['idOrderMarket'];
            $market = $data['market'];
            $country = $data['country'];

            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            // Obtener archivo adjunto
            $file = $request->getUploadedFiles()['file'] ?? null;
            $fileContent = $file->getStream()->getContents();



            $invoicesData = $this->invoicesService->sendInvoice($market, $idOrderMarket,$country, $fileContent);
                                   
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $invoicesData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }

    public function sincroOrders(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            //Query Parameters
            $queryParams = $request->getQueryParams();
            $markets = $queryParams['markets'];
            $from = $queryParams['from'];

            //Argumentos
            //$marketId = $args['idMarket'];
             
             // Acceder al ID del usuario desde el token
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $ordersData = $this->invoicesService->sincroOrders($markets, $from);
            
          
                        
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $ordersData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            // El token no contiene la información esperada
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }

    public function businessOrders(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            //Query Parameters
            $queryParams = $request->getQueryParams();
            $market = $queryParams['market'];
            $from = $queryParams['from'];

            //Argumentos
            //$marketId = $args['idMarket'];
             
             // Acceder al ID del usuario desde el token
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $ordersData = $this->invoicesService->businessOrders($market, $from);
            
          
                        
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $ordersData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            // El token no contiene la información esperada
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }
    
    
    

    
}
