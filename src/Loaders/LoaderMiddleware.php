<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Psr\Container\ContainerInterface;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Intoy\HebatFactory\Loader;
use Intoy\HebatApp\Middleware\GuardMiddleware;

class LoaderMiddleware extends Loader
{
    protected function createInputMiddlewareResolvable(string $class)
    {
        $prefix="logger.web";
        return function(ContainerInterface $c) use ($class,$prefix){
            return new $class($c->get($prefix));
        };
    }    

    public function boot()
    {        
        $this->prefix="web";

        $middleware=[
            ...$this->kernel->middleware,
            ...$this->kernel->middlewareGroups['web'],
        ];
        
        
        $all_middleware=array_filter($middleware,function($class){
            return class_exists($class) || $class==='csrf';
        });


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
                    $this->app->resolve("logger.{$this->prefix}")
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
        $this->app->bind('middleware',fn()=>[
            'global'=>$this->kernel->middleware,
            'api'=>$this->kernel->middlewareGroups['api'],
            'web'=>$this->kernel->middlewareGroups['web'],
        ]);
    }

    protected function afterBoot()
    {
        if($this->app->has(GuardMiddleware::class))
        {
            $mid=$this->app->resolve(GuardMiddleware::class);
            $mid->setFailurHandler(function()
            {
                session()->flashSet(["Invalid Cross-site request forgeries."],"message");
                return back();
            });
        }
    }
}