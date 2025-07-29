<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     title="Resto API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk aplikasi Resto",
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Localhost API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class SwaggerSetup {}
