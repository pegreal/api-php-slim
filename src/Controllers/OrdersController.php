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
            $market = isset($queryParams['market']) ? $queryParams['market'] : "0";
            $limit = isset($queryParams['limit']) ? $queryParams['limit'] : "10";
            $orders = isset($queryParams['orders']) ? $queryParams['orders'] : false;

            if($orders) {
                $ordersData = $this->ordersService->getOrderData($orders);
            }
            else {
                $ordersData = $this->ordersService->getOrders($market, $limit);
            } 
                                              
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
            $country = isset($queryParams['country']) ? $queryParams['country'] : null;
            $state = $queryParams['state'];
            $limit = $queryParams['limit'];
            $offset = isset($queryParams['offset']) ? $queryParams['offset'] : null;

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

    public function ordersFile(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            $data = $request->getParsedBody();
            $orders = $data['orders'];

            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $orderData = $this->ordersService->createOrdersFile($orders);
            
            $response = $response->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response = $response->withHeader('Content-Disposition', 'attachment; filename="file.xlsx"');

            $response->getBody()->write(stream_get_contents($orderData));
            //Cerrar flujo de la memoria
            fclose($orderData);
            return $response;
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }
    public function ordersSendData(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            $data = $request->getParsedBody();
            $orders = $data['orders'];

            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $orderData = $this->ordersService->ordersSendData($orders);
            
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'orders' => $orderData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }

    public function ordersSend(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            $data = $request->getParsedBody();
            $orders = $data['orders'];

            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $orderData = $this->ordersService->ordersSend($orders);
            
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'orders' => $orderData)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }
    
    
    

    
}
