<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'REST API E Commerce',
    description: 'REST API for E Commerce Portfolio Project'
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Local Development'
)]
class OpenApiSpec {}
