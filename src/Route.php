<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteInterface;

class Route 
{

    /**
     * @var string
     */
    protected const pattern1 = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
    protected const pattern2 = '!^([^\@]+)\@([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /**
     * @var RouteCollectorProxyInterface
     */
    protected static $app;

    /**
     * Key config.route.controller.name
     * @var string
     */
    protected static $configRouteName;


    public static function storeApp(RouteCollectorProxyInterface $app)
    {
        static::$app=$app;
        return $app;
    }

    public static function storeConfigRouteName(string $configRouteName)
    {
        static::$configRouteName=$configRouteName;
    }

    /**
     * Add GET route
     *
     * @param  string          $routeMethod  GET ..etc
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    protected static function resolveController(string $routeMethod, string $pattern, $callable):RouteInterface
    {
        $newCallable=static::resolveCallable($callable);
        return static::$app->$routeMethod($pattern,$newCallable);
    }

    /**
     * @param callable|array|string $callable
     * @return callable|array|string 
     */
    protected static function resolveCallable($callable)
    {
        if(is_callable($callable) || is_array($callable))
        {
            return $callable;
        }

        $matches=static::resolveNotation($callable);
        
        $class=$callable;
        $method='';
        if(!empty($matches))
        {
            [$class,$method]=$matches?[$matches[0],$matches[1]?$matches[1]:'']:[$callable,null];
        }
        
        $class_exists=class_exists($class);
        $namespace=null;
        
        if(!$class_exists)
        {
            $namespace=static::resolveNameSpaces($class);           
            $class=$namespace.$class;
        }
        $class.=$method?":".$method:'';

        return $class;
    }


    /**
     * Revole notataion
     */
    protected static function resolveNotation(string $toResolve):array
    {
        preg_match(static::pattern1, $toResolve, $matches);
        if($matches)
        {
            return [$matches[1], $matches[2]];
        }

        preg_match(static::pattern2, $toResolve, $matches);
        if($matches){
            return [$matches[1],$matches[2]];
        }

        return [];
    }


    protected static function resolveNameSpaces(string $class)
    {
        $key=static::$configRouteName;

        $namespaces=config('routes.controllers'); 
        $spase_key=data_get($namespaces,$key);
        if($spase_key)
        {
            $namespaces=$spase_key;
        }

        if($namespaces && !is_array($namespaces))
        {
            $namespaces=[$namespaces];
        }

        if($namespaces)
        {
            /**
             * @param array $spaces
             * @param string $target of className
             * @param callable $next
             * @return string|null
             */
            $resolveNameSpaces=function($spaces,$target,$next)
            {
                foreach($spaces as $key => $space)
                {
                    $to=$key;
                    if(is_numeric($key))
                    {
                        $to=$space;
                    }      

                    if(is_array($to))
                    {
                        return $next($to,$target);
                    }
                    elseif(class_exists($to.$target))
                    {
                        return $to;
                    }
                }
            };

            return $resolveNameSpaces($namespaces,$class,$resolveNameSpaces);            
        }

        return null;
    }

    /**
     * Add GET route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function get(string $pattern, $callable):RouteInterface
    {
        return static::resolveController('get',$pattern,$callable);
    }


    /**
     * Add PUT route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function put(string $pattern, $callable):RouteInterface
    {
        return static::resolveController('put',$pattern,$callable);
    }


    /**
     * Add POST route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function post(string $pattern, $callable):RouteInterface
    {
        return static::resolveController('post',$pattern,$callable);
    }


    /**
     * Add DELETE route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function delete(string $pattern, $callable): RouteInterface
    {
        return static::resolveController('delete',$pattern,$callable);
    }



    /**
     * Add OPTIONS route
     *
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function options(string $pattern, $callable): RouteInterface
    {
        return static::resolveController('options',$pattern,$callable);
    }


    /**
     * Add MAP route
     *
     * @param  string[]        $methods  Numeric array of HTTP method names
     * @param  string          $pattern  The route URI pattern
     * @param  callable|string $callable The route callback routine
     *
     * @return RouteInterface
     */
    public static function map(array $methods, string $pattern, $callable): RouteInterface
    {
        $newCallable=static::resolveCallable($callable);
        $routeMethod='map';
        $originMethods=$methods;
        $methods=[];
        $allows=['GET','POST','PUT','DELETE'];
        foreach(array_values($originMethods) as $val)
        {
            $method=strtoupper(trim((string)$val));
            if(in_array($method,$allows)){
                $methods[]=$method;
            }
        }
        if(count($methods)<1){
            $methods=['OPTIONS'];
        }
        
        return static::$app->$routeMethod($methods,$pattern,$newCallable);
    }
}