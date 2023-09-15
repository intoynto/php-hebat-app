<?php
namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

class CorsMiddleware 
{
    public function __invoke(Request $request, Handler $handler):Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();

        // get request headers
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $requestHeaders = $requestHeaders?$requestHeaders:'X-Requested-With, Content-Type, Accept, Origin, Authorization';

        // handle next response
        $response = $handler->handle($request);

        // check setup cors
        $origin=config('app.cors_origin');
        if(!is_null($origin))
        {
            // set of allow origin
            $response=$response->withHeader('Access-Control-Allow-Origin',$origin);
        }   
        
        $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
        $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);

        // Optional: Allow Ajax CORS requests with Authorization header
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}