<?php

use Intoy\HebatFactory\App;
use Intoy\HebatFactory\AppFactory;
use Intoy\HebatFactory\Redirect;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\DBManager;
use Intoy\HebatSupport\Optional;
use Psr\Http\Message\ResponseInterface as Response;
use Intoy\HebatApp\Session;

/**
 * throw_when
 * dd (die dump)
 * class_name
 * class_basename
 * 
 * app
 * config
 * is_production
 * path_base
 * path_app
 * path_domain
 * path_config
 * path_routes
  
 * url_base
 * url_asset
 * full_asset
 * full_url_asset
  
 * routeFor
 * redirect
 * redirectFor
 * back
 
 * session
 * connection
 * write_log
 * optional
 */

if(!function_exists('throw_when'))
{
    function throw_when(bool $fails,string $message, string $exception=Exception::class)
    {
        if(!$fails) return; throw new $exception($message);
    }
}

if(!function_exists('dd')){
    function dd()
    {
        array_map(function ($content) {
            echo "<pre>";
            var_dump($content);
            echo "</pre>";
            echo "<hr>";
        }, func_get_args());
        die;
    }
}

if(!function_exists('class_name'))
{
    /**
     * class_name
     * @param mixed|string|object
     * @return string
     */
    function class_name($class):string
    {
        $obj=is_object($class)?get_class($class):gettype($class);
        $path=explode('\\',$obj);
        return array_pop($path);
    }
}


if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

/**
 * Aplication resolvable
 */

if(!function_exists('app'))
{
    /**
     * @return App
     */
    function app():App
    {
        return AppFactory::$app;
    }
}

if (!function_exists('config'))
{
    function config($path = null, $value = null)
    {
        $config = app()->resolve('config');

        if (is_null($value)) {
            return data_get($config, $path);
        }

        data_set($config, $path, $value);

        app()->bind('config', $config);
    }
}

if(!function_exists('is_production')){  
    function is_production  ():bool 
    {
        $env="production";
        if(app()->has("config"))
        {
            $env=(string)config("app.env");
        }
        else {
            $env=env("APP_ENV","production");
        }
        return strtolower((string)$env)==="production";
    }
}
if(!function_exists('path_base')){      function path_base      ($path=''){ return realpath(__DIR__."/../").DIRECTORY_SEPARATOR.$path; }}
if(!function_exists('path_app')){       function path_app       ($path=''):string { return path_base("app".DIRECTORY_SEPARATOR."{$path}"); }}
if(!function_exists('path_domain')){    function path_domian    ($path=''):string { return path_base("domain".DIRECTORY_SEPARATOR."{$path}"); }}
if(!function_exists('path_config')){    function path_config    ($path=''):string { return path_domian("config".DIRECTORY_SEPARATOR."{$path}"); }}
if(!function_exists('path_routes')){    function path_routes    ($path=''):string { return path_domian("routing".DIRECTORY_SEPARATOR."{$path}"); }}
if(!function_exists('path_view')){      function path_view      ($path=''):string { return path_domian("views".DIRECTORY_SEPARATOR."{$path}"); }}


if(!function_exists('url_base'))
 {
    /**
     * Relative url
     * Url base berpengaruh pada cookie
     * @return string
     */
    function url_base($path = '')
    {
        return ltrim($path, "/");
    }
}

if(!function_exists('url_public')){   function url_public   ($path=''):string { return url_base("public/{$path}"); }}
if(!function_exists('url_asset')){    function url_asset    ($path=''):string { return url_public("assets/{$path}"); }}

if(!function_exists('full_url'))
{
    /**
     * Full url asset
     * @param string $path
     * @return string 
     */
    function full_url($path='')
    {       
        $url=env("APP_URL","/");
        $url=rtrim($url,"/");
        return "{$url}/{$path}";
    }
}

if(!function_exists('full_url_asset'))
{
    /**
     * Full url asset
     * @param string $path
     * @return string 
     */
    function full_url_asset($path='')
    {
        return full_url("public/assets/{$path}");
    }
}


if(!function_exists('routeFor'))
{
    /**
     * Callbac Get route by route name / alias
     * @return string $routeName
     */
    function routeFor(string $routeName, array $data = [], array $queryParams = [])
    {        
        $routeParser=app()->getRouteCollector()->getRouteParser();
        return $routeParser->urlFor($routeName,$data,$queryParams);
    }
}

if(!function_exists('redirect'))
{
    /**
     * Callbac Redirect
     * @param string $to
     * @return Response
     */
    function redirect(string $to)
    {   
        return app()->resolve(Redirect::class)($to);
    }
}


if(!function_exists('redirectFor'))
{
    /**
     * Callbac Redirect
     * @param string $routeName
     * @return Response
     */
    function redirectFor(string $routeName, array $data = [], array $queryParams = [])
    {        
        $routeParser=app()->getRouteCollector()->getRouteParser();
        $to=$routeParser->urlFor($routeName,$data,$queryParams);
        return redirect($to);
    }
}

if(!function_exists('back'))
{
    /**
     * Callbac Redirect Back
     * @return Response
     */
    function back()
    {
        $route=app()->resolve(\App\Factory\InputRequest::class);
        $back=$route->getCurrentUri();        
        return redirect($back);
    }
}

// session
if(!function_exists('session'))
{
    /**
     * Session
     * @return Session
     */
    function session():Session
    {
        return app()->resolve(Session::class);
    }
}

if(!function_exists('connection'))
{
    /**
     * @return Connection
     */
    function connection($name=null):Connection
    {
        $getDBManager=function():DBManager
        {
            return app()->resolve(DBManager::class);
        };
        $manager=$getDBManager();
        return $manager->connection($name);
    }
}


if(!function_exists('write_log'))
{
    /**
     * Write log
     * @param string $message
     * @param array $context
     */
    function write_log(string $message,array $context=[])
    {
        app()->resolve("logger.app")->info($message,$context);
    }
}


if (! function_exists('optional')) {
    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function optional($value = null, callable $callback = null)
    {
        if (is_null($callback)) {
            return new Optional($value);
        } elseif (! is_null($value)) {
            return $callback($value);
        }
    }
}