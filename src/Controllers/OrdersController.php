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

            $ordersData = $this->ordersService->getOrders($userId);
                                              
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'orders' => $ordersData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }
    
    
    

    
}
