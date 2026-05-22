# Slim PHP API Marketplace

## Título y Descripción

**Slim PHP API Marketplace** es una API ligera y robusta construida con **Slim Framework 4** y orientada a funcionar como un middleware/backend centralizado. Su objetivo principal es la gestión, consolidación y sincronización bidireccional de pedidos y facturas provenientes de una amplia variedad de canales de venta y marketplaces europeos e internacionales.

El proyecto implementa una arquitectura fuertemente desacoplada basada en controladores, servicios e inyección de dependencias, garantizando alta escalabilidad. Además, asegura las comunicaciones mediante JSON Web Tokens (JWT) y provee soporte nativo para la manipulación de archivos Excel y el envío de correos electrónicos transaccionales.

## Características principales (Features)

- **Integración Multi-Marketplace:** Conectores nativos para sincronizar información con Amazon, Manomano, Mirakl, Kaufland, Makro, Miravia, Kuanto y Ankor.
- **Autenticación Segura:** Rutas protegidas mediante JWT (JSON Web Tokens).
- **Inyección de Dependencias (DI):** Gestión eficiente de instancias a través de PHP-DI.
- **Procesamiento de Archivos:** Lectura y escritura de documentos Excel para importación/exportación masiva de datos de pedidos y estados.
- **Gestión de Estados:** Endpoints dedicados para transicionar y confirmar el estado de pedidos y facturas.
- **Notificaciones por Correo:** Envío automático de emails transaccionales a través de PHPMailer.
- **CORS Configurado:** Listo para consumirse por aplicaciones Frontend tipo SPA (Single Page Application).

## Tecnologías utilizadas

| Tecnología / Librería | Versión | Descripción |
| :--- | :--- | :--- |
| **PHP** | `>= 7.4` | Lenguaje base del proyecto. |
| **Slim Framework** | `4.*` | Micro-framework para el enrutamiento y middleware. |
| **Slim PSR-7** | `^1.6` | Implementación de las interfaces HTTP PSR-7. |
| **PHP-DI** | `^7.0` | Contenedor de Inyección de Dependencias (PSR-11). |
| **tuupola/slim-jwt-auth** | `^3.8` | Middleware para validación de tokens JWT. |
| **PhpSpreadsheet** | `^3.4` | Librería para lectura/escritura de archivos Excel. |
| **PHPMailer** | `^6.9` | Servicio robusto para el envío de correos electrónicos. |
| **vlucas/phpdotenv** | `^5.6` | Carga de variables de entorno desde archivos `.env`. |
| **MySQL** | - | Motor de base de datos relacional para persistencia. |

## Requisitos previos

Antes de instalar y ejecutar el proyecto, asegúrate de contar con el siguiente entorno:

- **PHP 7.4** o una versión superior.
- **Composer** (Gestor de dependencias de PHP).
- Servidor Web (Apache con `mod_rewrite` habilitado o Nginx).
- Motor de base de datos **MySQL**.
- Extensiones de PHP activas: `pdo_mysql`, `gd`, `zip`, `xml` (necesarias para la manipulación de base de datos y archivos Excel).

## Guía de instalación y configuración

1. **Clonar el repositorio:**
   ```bash
   git clone <url-del-repositorio> api-php-slim
   cd api-php-slim
   ```

2. **Instalar las dependencias:**
   ```bash
   composer install
   ```

3. **Configurar las variables de entorno:**
   El proyecto busca el archivo `.env` en el nivel superior al directorio raíz (`../`). Debes crear un archivo `.env` en el directorio padre (fuera de la carpeta del proyecto) o modificar la ruta de carga en `index.php` si deseas mantenerlo en el mismo nivel.
   
   Ejemplo de contenido para un `.env.example`:
   ```dotenv
   # Configuración de Base de Datos
   DB_HOST=127.0.0.1
   DB_NAME=nombre_base_datos
   DB_USER=usuario
   DB_PASS=contraseña

   # Configuración de JWT
   JWT_SECRET=tu_super_secreto_jwt
   
   # Configuración de SMTP (Correos)
   SMTP_HOST=smtp.ejemplo.com
   SMTP_USER=usuario@ejemplo.com
   SMTP_PASS=tu_password_smtp
   SMTP_PORT=587
   ```

4. **Configuración del Servidor Web:**
   Asegúrate de apuntar el `DocumentRoot` de tu servidor al directorio de este proyecto (donde se encuentra `index.php`) y permite la lectura del archivo `.htaccess` proporcionado.

## Uso o ejemplos

Una vez configurado y levantado el servidor, la API expondrá diversos endpoints estructurados por dominios. 

**Obtener Token de Acceso:**
```bash
curl -X POST http://tu-dominio.com/auth/signin \
     -H "Content-Type: application/json" \
     -d '{"username": "admin", "password": "password"}'
```

**Consultar Pedidos (Requiere Header Authorization):**
```bash
curl -X GET http://tu-dominio.com/orders/ \
     -H "Authorization: Bearer <TU_TOKEN_JWT>"
```

### Endpoints Principales:
- `POST /auth/signin` - Inicio de sesión y generación de token.
- `GET /orders/` - Listado general de pedidos integrados.
- `GET /orders/sincro` - Disparador de sincronización de pedidos con los marketplaces.
- `GET /invoices/` - Listado general de facturas.
- `GET /templates/` - Gestión de plantillas para comunicaciones.

## Estructura de directorios

```text
📁 api-php-slim/
├── 📄 .htaccess               # Reglas de reescritura para servidor Apache
├── 📄 composer.json           # Declaración de dependencias del proyecto
├── 📄 config.php              # Configuraciones base de la aplicación
├── 📄 index.php               # Punto de entrada (Front Controller) y configuración principal
└── 📁 src/                    # Código fuente principal
    ├── 📁 Controllers/        # Manejadores de las peticiones HTTP (Orders, Invoices, etc.)
    ├── 📁 DependencyInjection/# Configuración e inicialización del contenedor PHP-DI
    ├── 📁 Services/           # Lógica de negocio y clientes de API para cada Marketplace
    └── 📄 routes.php          # Definición de rutas y endpoints del API
```

## Contribución

¡Las contribuciones son bienvenidas! Para aportar a este proyecto, por favor sigue estos pasos:

1. Realiza un *Fork* del repositorio.
2. Crea una rama para tu nueva característica o corrección de error (`git checkout -b feature/nueva-caracteristica`).
3. Realiza tus cambios y haz commits descriptivos (`git commit -m 'Añade nueva integración con marketplace X'`).
4. Haz push a la rama (`git push origin feature/nueva-caracteristica`).
5. Abre un **Pull Request** detallando los cambios introducidos.

Por favor, asegúrate de seguir los estándares de codificación PSR-12.

## Licencia

Este software se distribuye bajo una licencia **Privada** (Propietario / Private), de uso exclusivo y restringido corporativo. Está estrictamente prohibida su copia, distribución o modificación sin autorización explícita de su autor.