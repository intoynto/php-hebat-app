<?php
namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;

use Intoy\HebatApp\Twig\TwigHelperExtension;
use Intoy\HebatApp\Twig\TwigStringExtension;
use Intoy\HebatApp\Twig\WebpackExtension;

class TwigHelperMiddleware 
{
    public function __invoke(Request $request, Handler $handler):Response
    {
        $twig=app()->resolve(Twig::class);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        //register route_name 
        $twig->getEnvironment()->addGlobal("route_name",$route?$route->getName():"");

        // global helper extension
        $twig->addExtension(new TwigHelperExtension());
        
        // add string extension
        $twig->addExtension(new TwigStringExtension());

        // webpack extension
        $extension=new WebpackExtension(
            /// Realpath manifest file
            path_asset("manifest.json"),
            
            // url_base
            url_base(),
        );

        $twig->addExtension($extension);

        //response
        $response=$handler->handle($request);
        return $response;
    }
}