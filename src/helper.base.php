<?php

use Intoy\HebatFactory\App;
use Intoy\HebatFactory\AppFactory;
use Intoy\HebatFactory\Redirect;
use Intoy\HebatFactory\InputRequest;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\DBManager;
use Intoy\HebatSupport\Optional;
use Psr\Http\Message\ResponseInterface as Response;
use Intoy\HebatApp\Session;

/**
 * Callable function
 * =============================
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
 * response
 * responseJson
 
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
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        $simfony_dumper="Symfony\Component\VarDumper\VarDumper";
        if(class_exists($simfony_dumper))
        {
            array_map(function($content) use ($simfony_dumper){
                call_user_func_array($simfony_dumper."::dump",[$content]);
            },func_get_args());
            exit(1);            
        }
        array_map(function ($content) {
            echo "<pre>";
            var_dump($content);
            echo "</pre>";
            echo "<hr>";
        }, func_get_args());
        exit(1);
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

        $env=strtolower((string)$env);
        return in_array($env,["production","prod"]);
    }
}

/**
 * real path untuk folder root untuk website 
 */
if(!function_exists('path_base')){      function path_base      ($path=''){ return realpath(__DIR__."/../").DIRECTORY_SEPARATOR.$path; }}


/**
 * real path directory vendor autoload
 * jika struktur folder vendor autoload : "htdocs/vendor"
 * maka path_vendor adalah "htdocs" bukan absolute merujuk langsung ke folder vendor-nya
 * RouteContextMiddleware akan mengakses route context melalu class finder disini
 */
if(!function_exists('path_vendor')){    function path_vendor    ($path=''){ return realpath(__DIR__."/../").DIRECTORY_SEPARATOR.$path; }}


/** 
 * real path name space "App"
 */
if(!function_exists('path_app')){       function path_app       ($path=''):string { return path_base("app".DIRECTORY_SEPARATOR."{$path}"); }}

/**
 * real path for public directory 
 * yang biasanya berisi file atau folder assets, images etc,..
 * yang bisa diakses secara publik melalu browser
 */
if(!function_exists('path_public')){    function path_public    ($path=''):string { return path_app("public".DIRECTORY_SEPARATOR."{$path}"); }}

/**
 * real path for assets directory 
 * access for manifest.json etc,..
 * for TwigHelperMiddleware
 */
if(!function_exists('path_assets')){    function path_assets    ($path=''):string { return path_public("assets".DIRECTORY_SEPARATOR."{$path}"); }}


/**
 * real path for folder config
 * access for loader config
 */
if(!function_exists('path_config')){    function path_config    ($path=''):string { return path_app("config".DIRECTORY_SEPARATOR."{$path}"); }}

/**
 * real path for forder routes
 * access for route context
 */
if(!function_exists('path_routes')){    function path_routes    ($path=''):string { return path_app("routing".DIRECTORY_SEPARATOR."{$path}"); }}

/**
 * real path for view
 * access for template engine
 */
if(!function_exists('path_view')){      function path_view      ($path=''):string { return path_app("views".DIRECTORY_SEPARATOR."{$path}"); }}


/**
 * twig function resolvable
 */
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

/**
 * twig function resolvable
 */
if(!function_exists('url_public')){   function url_public   ($path=''):string { return url_base("public/{$path}"); }}


/**
 * twig function resolvable
 */
if(!function_exists('url_asset')){    function url_asset    ($path=''):string { return url_public("assets/{$path}"); }}


/**
 * twig function resolvable
 */
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


/**
 * twig function resolvable
 */
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
        $route=app()->resolve(InputRequest::class);
        $back=$route->getCurrentUri();        
        return redirect($back);
    }
}

/** response */
if(!function_exists('response'))
{
  /**
     * Create a new response.
     * ===================== 
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    function response(int $code = 200, string $reasonPhrase = '')
    {
        return app()->getResponseFactory()->createResponse($code,$reasonPhrase);
    }
}

/** responseJson */
if(!function_exists('responseJson'))
{
    /**
     * Create a new response.
     *
     * @param mixed 
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.   
     * @return ResponseInterface
     */
    function responseJson($data=null, int $code = 200, string $reasonPhrase = '')
    {
        $res=app()->getResponseFactory()->createResponse($code, $reasonPhrase);
        $res->getBody()->write(json_encode($data));
        return $res->withHeader("content-type","application/json");
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