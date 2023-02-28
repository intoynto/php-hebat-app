<?php
namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseFactoryInterface as Factory;
use Slim\Routing\RouteContext;
use Slim\Routing\Route;

use Intoy\HebatFactory\Redirect;
use Intoy\HebatFactory\Context;
use Intoy\HebatFactory\InputRequest;
use Intoy\HebatApp\ClassFinder;

class RouteContextMiddleware 
{
    protected static function routeFromRequest(Request $request):Route
    {
        $context=RouteContext::fromRequest($request);        
        return \call_user_func([$context,"getRoute"]);
    }    

    public function __invoke(Request $request, Handler $handler):Response
    {
        $route=static::routeFromRequest($request);
        throw_when(empty($route),'Route not found in request');

        // register container untuk context
        $context=new Context();
        $context->storeRequest($request);
        app()->bind(Context::class,$context);

        // Register input request untuk callable global function for "back" in container
        app()->bind(InputRequest::class,function() use ($request, $route){
            return new InputRequest($request,$route);
        });

        // register Redirect class untuk Redirect
        app()->bind(Redirect::class,fn()=>new Redirect(app()->resolve(Factory::class)));

        $requests=[];
        $nameSpaceRequest=config("routes.request");
        if($nameSpaceRequest)
        {
            $cf=new ClassFinder();
            $requests=$cf->getClassesInNameSpaces($nameSpaceRequest);            
        }
        
        $requests=array_values($requests);
        if(count($requests)>0)
        {            
            foreach($requests as $inputClass)
            {
                app()->bind($inputClass,function() use ($inputClass,$request,$route){
                    $input=new $inputClass($request,$route);
                    $input->validate();
                    return $input;
                });
            }
        }
        
        return $handler->handle($request);
    }
}