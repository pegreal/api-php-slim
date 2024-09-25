<?php
date_default_timezone_set('Europe/Madrid');
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\ErrorMiddleware;
use Tuupola\Middleware\JwtAuthentication;

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

require __DIR__ . '/config.php';


// Crear el contenedor de PHP-DI
$container = new Container();

// Configurar dependencias
$dependencies = require __DIR__ . '/src/DependencyInjection/Dependencies.php';
$dependencies($container);


// Configurar el middleware de autenticación JWT
$jwtConfig = $config['authjwt'];
$jwtMiddleware = new JwtAuthentication($jwtConfig);

// Configurar Slim para usar el contenedor
AppFactory::setContainer($container);

$app = AppFactory::create();
if($path !== '')
$app->setBasePath($path);


//CORS Policy
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


// Middleware para analizar el cuerpo JSON
$app->addBodyParsingMiddleware();

// Manejar errores
$errorMiddleware = new ErrorMiddleware(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    true,
    false,
    false
);
$app->add($errorMiddleware);

$app->add($jwtMiddleware);


// Importar y cargar las rutas
$routes = require __DIR__ . '/src/routes.php';
$routes($app);


$app->run();