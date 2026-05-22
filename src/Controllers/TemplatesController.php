<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Services\TemplatesService;

class TemplatesController
{
    
    private $templatesService;

    
    public function __construct(TemplatesService $templatesService)
    {
        $this->templatesService = $templatesService;

    }
    
    public function getTemplates(Request $request, Response $response, $args)
    {

        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);
         if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {

            //Argumentos
            //$tareaId = $args['idTarea'];
             
             // Acceder al ID del usuario desde el token
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            $templates = $this->templatesService->getTemplates($userId);
            
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'templates' => $templates)));
            return $response->withHeader('Content-Type', 'application/json');
             
         }else {
            // El token no contiene la información esperada
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
        
        
        
        
    }

    public function getPublicTemplates(Request $request, Response $response, $args)
    {
        $templates = $this->templatesService->getTemplates();
        $response->getBody()->write(json_encode(array('user_id' => null, 'permision'=> null, 'templates' => $templates)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateTemplate(Request $request, Response $response, $args)
    {
       // Obtener datos del token decodificado desde el atributo de la solicitud
        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);

        // Verificar si el token decodificado contiene la información necesaria
        if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {
            // Acceder al ID del usuario desde el token
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];
            // Aquí puedes realizar acciones específicas para el usuario autenticado
             $data = $request->getParsedBody();
             $templateId = $data['idTemplate'];
             $title = $data['strName'];
             $body = $data['strBody'];
                                    

            //guardar en tabla tareas
            $templateResult = $this->templatesService->updateTemplate($templateId,$title,$body);
                       
            // Devolver una respuesta de ejemplo
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $templateResult)));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            // El token no contiene la información esperada
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }

    public function createTemplate(Request $request, Response $response, $args)
    {
       // Obtener datos del token decodificado desde el atributo de la solicitud
        $decodedTokenData = $request->getAttribute('decoded_token_data');
        $decodedTokenData = json_decode(json_encode($decodedTokenData), true);

        // Verificar si el token decodificado contiene la información necesaria
        if ($decodedTokenData && isset($decodedTokenData['data']['user_id'])) {
            // Acceder al ID del usuario desde el token
            $userId = $decodedTokenData['data']['user_id'];
            $userPermises = $decodedTokenData['data']['user_permision'];

            // Aquí puedes realizar acciones específicas para el usuario autenticado
             $data = $request->getParsedBody();
             $title = $data['strName'];
             $body = $data['strBody'];
                                    

            //guardar en tabla tareas
            $templateResult = $this->templatesService->createTemplate($title,$body);
                       
            // Devolver una respuesta de ejemplo
            $response->getBody()->write(json_encode(array('user_id' => $userId, 'permision'=> $userPermises, 'result' => $templateResult)));
            return $response->withHeader('Content-Type', 'application/json');;
        } else {
            // El token no contiene la información esperada
            $response->getBody()->write("Token inválido o falta información");
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
        }
    }
    
    
    

    
}