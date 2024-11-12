<?php

use DI\Container;
use Services\DatabaseService;
use Services\MailService;

use Controllers\AuthController;
use Controllers\OrdersController;
use Controllers\InvoicesController;

use Services\OrdersService;
use Services\InvoicesService;


use Services\AmazonService;
use Services\MiraklService;
use Services\MakroService;
use Services\KauflandService;
use Services\ManomanoService;
use Services\MiraviaService;
use Services\KuantoService;
use Services\AnkorService;


return function (Container $container) {


    // Configurar DatabaseService
    $container->set(DatabaseService::class, function () {
        require __DIR__ . '/../../config.php';
        $dbConfig = $config['database']; 
        return new DatabaseService($dbConfig['host'], $dbConfig['dbname'], $dbConfig['usuario'], $dbConfig['contrasena']);
    });

    // Configurar el array de configuración adicional
    $container->set('authConfig', function () {
        require __DIR__ . '/../../config.php';
        $authConfig = $config['authjwt']; 
        return $authConfig;
    });

    // Configurar AuthController
    $container->set(AuthController::class, function ($container) {
        
        return new AuthController(
            $container->get(DatabaseService::class),
            $container->get('authConfig')
        );
    });

  
    // Configurar ordersController
    $container->set(OrdersController::class, function ($container) {
        return new OrdersController($container->get(OrdersService::class));
    });

   

     // Configurar InvoicesController
     $container->set(InvoicesController::class, function ($container) {
        return new InvoicesController($container->get(InvoicesService::class));
    });

    // Configurar el array de configuración adicional
     $container->set('amzConfig', function () {
        require __DIR__ . '/../../config.php';
        $amzConfig = $config['amzConfig']; 
        return $amzConfig;
    }); 

    // Configurar AmazonService
    $container->set(AmazonService::class, function ($container) {
        
        return new AmazonService(
            $container->get(DatabaseService::class),
            $container->get('amzConfig')
        );
    });

    // Configurar el array de configuración adicional
    $container->set('miraklConfig', function () {
        require __DIR__ . '/../../config.php';
        $miraklConfig = $config['miraklConfig']; 
        return $miraklConfig;
    }); 

    // Configurar MiraklService
    $container->set(MiraklService::class, function ($container) {
        
        return new MiraklService(
            $container->get(DatabaseService::class),
            $container->get('miraklConfig')
        );
    });

     // Configurar el array de configuración adicional
     $container->set('makroConfig', function () {
        require __DIR__ . '/../../config.php';
        $makroConfig = $config['makroConfig']; 
        return $makroConfig;
    }); 

    // Configurar MakroService
    $container->set(MakroService::class, function ($container) {
        
        return new MakroService(
            $container->get(DatabaseService::class),
            $container->get('makroConfig')
        );
    });
    // Configurar el array de configuración adicional
    $container->set('kauflandConfig', function () {
        require __DIR__ . '/../../config.php';
        $kauflandConfig = $config['kauflandConfig']; 
        return $kauflandConfig;
    }); 

    // Configurar KauflandService
    $container->set(KauflandService::class, function ($container) {
        
        return new KauflandService(
            $container->get(DatabaseService::class),
            $container->get('kauflandConfig')
        );
    });

    // Configurar el array de configuración adicional
    $container->set('mailConfig', function () {
        require __DIR__ . '/../../config.php';
        $mailConfig = $config['mailConfig']; 
        return $mailConfig;
    }); 

    
    // Configurar MailService
    $container->set(MailService::class, function ($container) {
        return new MailService(
            $container->get('mailConfig')
        );
    });

    // Configurar ManomanoService
    $container->set(ManomanoService::class, function ($container) {
        
        return new ManomanoService(
            $container->get(DatabaseService::class),
            $container->get(MailService::class)
        );
    });

    // Configurar el array de configuración adicional
    $container->set('miraviaConfig', function () {
        require __DIR__ . '/../../config.php';
        $miraviaConfig = $config['miraviaConfig']; 
        return $miraviaConfig;
    }); 

     // Configurar MiraviaService
     $container->set(MiraviaService::class, function ($container) {
        
        return new MiraviaService(
            $container->get(DatabaseService::class),
            $container->get('miraviaConfig')
        );
    });

     // Kuantokusta Configurar el array de configuración adicional
     $container->set('kuantoConfig', function () {
        require __DIR__ . '/../../config.php';
        $kuantoConfig = $config['kuantoConfig']; 
        return $kuantoConfig;
    }); 

    // Configurar KauflandService
    $container->set(KuantoService::class, function ($container) {
        
        return new KuantoService(
            $container->get(DatabaseService::class),
            $container->get('kuantoConfig')
        );
    });

    // Ankorstore Configurar el array de configuración adicional
    $container->set('ankorConfig', function () {
        require __DIR__ . '/../../config.php';
        $ankorConfig = $config['ankorConfig']; 
        return $ankorConfig;
    }); 

    // Configurar Ankorstore Service
    $container->set(AnkorService::class, function ($container) {
        
        return new AnkorService(
            $container->get(DatabaseService::class),
            $container->get('ankorConfig')
        );
    });



    // Configurar InvoicesService
    $container->set(InvoicesService::class, function ($container) {
        return new InvoicesService(
            $container->get(DatabaseService::class),
            $container->get(AmazonService::class),
            $container->get(MiraklService::class),
            $container->get(MakroService::class),
            $container->get(KauflandService::class),
            $container->get(ManomanoService::class),
        );
    });

     // Configurar ordersService
     $container->set(OrdersService::class, function ($container) {
        return new OrdersService(
            $container->get(DatabaseService::class),
            $container->get(MiraklService::class),
            $container->get(MakroService::class),
            $container->get(MiraviaService::class),
            $container->get(KuantoService::class),
            $container->get(AnkorService::class),
        );
    });

    

};