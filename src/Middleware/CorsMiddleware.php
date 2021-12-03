<?php
namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class CorsMiddleware 
{
    public function __invoke(Request $request, Handler $handler):Response
    {
        $response=$handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', env('CORS_ALLOWED_ORIGINS','*'))
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
}