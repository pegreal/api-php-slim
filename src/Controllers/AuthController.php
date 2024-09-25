<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Services\DatabaseService;
use Firebase\JWT\JWT;

class AuthController
{
    private $dbService;
    private $authConfig;

    public function __construct(DatabaseService $dbService, array $authConfig)
    {
        $this->dbService = $dbService;
        $this->authConfig = $authConfig;
    }

    public function signIn(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $usuario = $data['usuario'];
        $contrasena = $data['contrasena'];

        $userData = $this->dbService->ejecutarConsulta("SELECT * FROM tblussers WHERE strEmail = '$usuario'");

        $userData = $userData[0];
	    $hash = $userData['strPass'];

        if (password_verify($contrasena, $hash)) {
            $userId = $userData['idUsser'];
            $userName = $userData['strName'];
            $userPermise = $userData['intPermisos'];
            $userMail = $userData['strEmail'];
            $token = $this->generateToken($userId,$userName,$userPermise,$userMail);

            $response->getBody()->write(json_encode(['token' => $token]));
            return $response->withHeader('Content-Type', 'application/json');


        }else{
            // Password Incorrecto
            $response->getBody()->write('Invalid Data');
            return $response->withHeader('Content-Type', 'text/plain');
        }

        
    }

    private function generateToken($userId,$userName,$userPermise,$userMail)
    {
        $secret = $this->authConfig["secret"];
        $issuedAt = time();
        $expirationTime = $issuedAt + (3600 * 24 * 365); // 1 hora * 1 dia * 365dias
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => ['user_id' => $userId, 'user_name' => $userName, 'user_permision' => $userPermise, 'user_mail' => $userMail],
        ];

        // Genera el token JWT
        $token = JWT::encode($payload, $secret, 'HS256');
        return $token;
    }
}
