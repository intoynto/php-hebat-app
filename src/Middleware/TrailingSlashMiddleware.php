<?php

declare(strict_types=1);

namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseFactoryInterface as Factory;
use Slim\Routing\RouteContext;

class TrailingSlashMiddleware {
    /**
     * Factory
     * @var Factory
     */
    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory=$factory;        
    }
    
    public function __invoke(Request $request, Handler $handler)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $context=RouteContext::fromRequest($request);
        $basePath=$context->getBasePath();
        $is_base_path=$basePath!=="/";
        $current_path=$path;
        if($is_base_path)
        {
            $current_path=substr($path,strlen($basePath));
        }        

        if ($current_path != '/' && substr($current_path, -1) == '/') {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));

            if($request->getMethod() == 'GET'){
                $response=$this->factory->createResponse(301);
                return $response->withHeader("Location",(string)$uri);
            }
            else {
                return $handler->handle($request);
            }
        }

        return $handler->handle($request);
    }
}