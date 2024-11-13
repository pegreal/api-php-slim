<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Services\OrdersService;


class OrdersController
{
    
    private $ordersService;

    
    public function __construct(OrdersService $ordersService)
    {
        $this->ordersService = $ordersService;

    }
    
    public function getOrders(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {
             
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $queryParams = $request->getQueryParams();
            $market = $queryParams['market'];
            $limit = $queryParams['limit'];
            //$orders = $queryParams['orders'] || null;

            $ordersData = $this->ordersService->getOrders($market, $limit);
                                              
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'orders' => $ordersData)));
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
             
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $queryParams = $request->getQueryParams();
            $market = $queryParams['market'];
            $country = $queryParams['country'];
            $state = $queryParams['state'];
            $limit = $queryParams['limit'];
            $offset = $queryParams['offset'];

            $ordersData = $this->ordersService->sincroOrders($market, $country, $state, $limit, $offset);
                                              
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'orders' => $ordersData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }

    public function updateOrderState(Request $request, Response $response, $args)
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

            $orderData = $this->ordersService->updateOrderState($idOrderMarket, $market, $state);
                                   
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $orderData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }
    
    
    

    
}
