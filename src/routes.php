<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Controllers\AuthController;
use Controllers\OrdersController;
use Controllers\InvoicesController;

return function (App $app) {
    
    // Endpoint en el root que devuelve un saludo
    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write("¡Hola! Bienvenido a la API.");
        return $response;
    });

    $app->group('/auth', function ($group) {
        $group->post('/signin', AuthController::class . ':signIn');
    });
    
    $app->group('/orders', function ($group) {
        //toDo
        $group->get('/', OrdersController::class . ':getOrders');
        $group->get('/sincro', OrdersController::class . ':sincroOrders');
    });

    $app->group('/invoices', function ($group) {
        $group->get('/', InvoicesController::class . ':getInvoices');
        $group->get('/sincro', InvoicesController::class . ':sincroOrders');
        $group->get('/business', InvoicesController::class . ':businessOrders');
        $group->post('/state', InvoicesController::class . ':updateInvoiceState');
        $group->post('/send', InvoicesController::class . ':sendInvoice');
    });

};


