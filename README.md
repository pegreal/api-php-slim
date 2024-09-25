# Slim PHP API Marketplace

## Descripción
 API ligera construida con el framework [Slim PHP](https://www.slimframework.com/) con autenticación mediante [JWT (JSON Web Tokens)](https://jwt.io/). Backend rápido y seguro para aplicación ligera.

## Características
- Autenticación de usuarios utilizando JWT.
- Implementación ligera con el framework Slim PHP.
- Envío de correos electrónicos con un servicio de mail (PHPMailer).
- Estructura modular.
- Servicios para interacción con diferentes Marketplace: Amazon, Manomano, Mirakl, Kaufland, Makro...
- Apoyo en servicio DB mysql

## Requisitos previos
- PHP 7.4 o superior
- Composer (gestor de dependencias de PHP)
- Slim Framework 4
- Un servicio de correo SMTP o API (PHPMailer)
- Un servicio DB mysql

## Instalación

composer install
- Genera el archivo de entorno .env en el nivel superior.
