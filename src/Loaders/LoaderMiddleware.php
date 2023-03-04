<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Psr\Container\ContainerInterface;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Intoy\HebatFactory\Loader;
use Intoy\HebatApp\Middleware\GuardMiddleware;
use Intoy\HebatApp\Helper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class LoaderMiddleware extends Loader
{
    protected function createInputMiddlewareResolvable(string $class)
    {
        $prefix='logger.web';
        return function(ContainerInterface $c) use ($class,$prefix){
            return new $class($c->get($prefix));
        };
    }    

    public function boot()
    {        
        $middleware=[
            ...$this->kernel->middleware,            
        ];

        $groupMiddleware=[
            'global'=>[],
        ];

        $all_middleware=[];
        
        foreach($middleware as $class)
        {
            $true=class_exists($class) || $class==='csrf';
            if($true)
            {
                $groupMiddleware['global'][]=$class;
                $all_middleware[]=$class;
            }
        }

        // looping middlewarGroup Kernel
        foreach($this->kernel->middlewareGroups as $key => $mids)
        {
            foreach($mids as $class)
            {
                $true=class_exists($class) || $class==='csrf';
                if($true)
                {
                    if(!in_array($key,array_keys($groupMiddleware)))
                    {
                        $groupMiddleware[$key]=[];
                    }
                    $groupMiddleware[$key][]=$class;
                    $all_middleware[]=$class;
                }
            }
        }

        foreach($all_middleware as $mid)
        {
            $alias=$mid;
            if($mid===TwigMiddleware::class)
            {
                $guard=TwigMiddleware::createFromContainer($this->app);                
            }               
            elseif($mid===ErrorMiddleware::class)     
            {
                $guard=new ErrorMiddleware(
                    $this->app->getCallableResolver(),
                    $this->app->resolve(\Psr\Http\Message\ResponseFactoryInterface::class),
                    true,
                    true,
                    true,
                    $this->app->resolve('logger.'.$this->prefix)
                );
                $errorHandle=$guard->getDefaultErrorHandler();
                if($errorHandle instanceof \Slim\Handlers\ErrorHandler)
                {
                    $errorHandle->forceContentType('text/html');
                }
            }
            else {
                $guard=\DI\autowire($mid);
            }  
            $this->app->bind($alias,$guard);
        }
        //Re-register in container
        $this->app->bind('middleware',$groupMiddleware);
    }

    protected function afterBoot()
    {
        if($this->app->has(GuardMiddleware::class))
        {
            $app=$this->app;
            $getGuard=function() use($app):GuardMiddleware
            {
                return $app->resolve(GuardMiddleware::class);
            };
            $mid=$getGuard();
            $handlerFailure=function(Request $request, Handler $handler) :Response 
            {
                $contentType=Helper::determineContentType($request);
                $isJson=in_array($contentType,['text/json','application/json']);
                $msg='Invalid Cross-site request forgeries';
                if($isJson)
                {
                    throw new \Slim\Exception\HttpForbiddenException($request,$msg);
                }

                session()->flashSet([$msg],'message');
                return back();
            };
            $mid->setFailurHandler($handlerFailure);
        }
    }
}