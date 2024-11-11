<?php

//Debbugger path ""
//xampp path "/api-php-slim"
$path = $_ENV['PROJECT_PATH']; 
$config = [
    
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'usuario' => $_ENV['DB_USER'],
        'contrasena' => $_ENV['DB_PASS'],
    ],
 
    // Configurar el middleware de autenticación JWT
    'authjwt' => [
        "secure" => false, //  (true para HTTPS, false para HTTP)
        "secret" => $_ENV['JWT_SECRET'],
        "attribute" => "decoded_token_data",
        "algorithm" => ["HS256"],
        "path" => [ $path."/orders/", $path."/templates/", $path."/invoices/"], // Rutas que requerirán autenticación
        "ignore" => ["/auth/signin"], // Rutas que no requerirán autenticación
    ],
    'amzConfig' =>[
        'refresh_token'=> $_ENV['AMZ_LWA_TOKEN'],
        'lwa_client' => $_ENV['AMZ_LWA_CLIENT'],
        'lwa_secret' => $_ENV['AMZ_LWA_SECRET']

    ],
    'miraklConfig' =>[
        'leroy_fr' => ['token'=>$_ENV['LEROY_FR_TOKEN'], 'idShop' => $_ENV['LEROY_FR_SHOP']],
        'leroy_es' => ['token'=>$_ENV['LEROY_ES_TOKEN'], 'idShop' => $_ENV['LEROY_ES_SHOP']],
        'leroy_it' => ['token'=>$_ENV['LEROY_IT_TOKEN'], 'idShop' => $_ENV['LEROY_IT_SHOP']],
        'leroy_pt' => ['token'=>$_ENV['LEROY_PT_TOKEN'], 'idShop' => $_ENV['LEROY_PT_SHOP']],
        'carrefour' => ['token'=>$_ENV['CARREFOUR_TOKEN'], 'idShop' => $_ENV['CARREFOUR_SHOP']]

    ],
    'makroConfig' => [
        'client_key' => $_ENV['MAKRO_KEY'],
        'client_secret' => $_ENV['MAKRO_SECRET']
    ],
    'kauflandConfig' => [
        'client_key' => $_ENV['KAUFLAND_KEY'],
        'client_secret' => $_ENV['KAUFLAND_SECRET']
    ],
    'miraviaConfig' => [
        'client_key' => $_ENV['MIRAVIA_KEY'],
        'client_secret' => $_ENV['MIRAVIA_SECRET']
    ],
    'mailConfig' => [
        'host' => $_ENV['MAIL_HOST'],
        'port' => $_ENV['MAIL_PORT'],
        'username' => $_ENV['MAIL_USER'],
        'password' => $_ENV['MAIL_PASS'],
        'mail' => $_ENV['MAIL_FROM'],
        'name' => 'Prismica SL Manomano'

    ]

];
    


