<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Psr\Container\ContainerInterface;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Intoy\HebatFactory\Loader;
use Intoy\HebatApp\Middleware\GuardMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

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
            $app=$this->app;
            $getGuard=function() use($app):GuardMiddleware
            {
                return $app->resolve(GuardMiddleware::class);
            };
            $mid=$getGuard();

            $determineContentType=function(Request $request)
            {
                $errorRenderers=[
                    'application/json',
                    'application/xml',
                    'text/xml',
                    'text/html',
                    'text/plain',
                ];
                $acceptHeader = $request->getHeaderLine('Accept');
                $selectedContentTypes = array_intersect(
                    explode(',', $acceptHeader),
                    $errorRenderers
                );
                $count = count($selectedContentTypes);

                if ($count) {
                    $current = current($selectedContentTypes);

                    /**
                     * Ensure other supported content types take precedence over text/plain
                     * when multiple content types are provided via Accept header.
                     */
                    if ($current === 'text/plain' && $count > 1) {
                        $next = next($selectedContentTypes);
                        if (is_string($next)) {
                            return $next;
                        }
                    }

                    if (is_string($current)) {
                        return $current;
                    }
                }

                if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
                    $mediaType = 'application/' . $matches[1];
                    if (array_key_exists($mediaType, $this->errorRenderers)) {
                        return $mediaType;
                    }
                }

                return null;
            };

            $handlerFailure=function(Request $request, Handler $handler) use ($determineContentType) :Response 
            {
                $contentType=$determineContentType($request);
                $isJson=in_array($contentType,['text/json','application/json']);
                $msg="Invalid Cross-site request forgeries";
                if($isJson)
                {
                    throw new \Slim\Exception\HttpForbiddenException($request,$msg);
                }

                session()->flashSet([$msg],"message");
                return back();
            };
            $mid->setFailurHandler($handlerFailure);
        }
    }
}