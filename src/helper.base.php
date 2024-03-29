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

use Intoy\HebatSupport\Validation\Interfaces\ValidatorInterface;
use Intoy\HebatSupport\Validation\Validator;

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
 * path_vendor
 * path_app
 * path_public
 * path_assets
 * path_webpack_manifest
 * path_config
 * path_routes
 * path_view
  
 * url_base
 * url_public
 * url_asset
 * full_url
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
 * 
 * validator
 * folder_make_path
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

            $cors_origin=config("app.cors_origin");
            $is_global_origin=$cors_origin==="*" || !is_production();
            
            if($is_global_origin)
            {
                // set origin cors flight
                header('Access-Control-Allow-Origin:*');
                header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, PATCH, OPTIONS');
            }
        }

        $simfony_dumper='Symfony\Component\VarDumper\VarDumper';
        if(class_exists($simfony_dumper))
        {
            array_map(function($content) use ($simfony_dumper){
                call_user_func_array($simfony_dumper.'::dump',[$content]);
            },func_get_args());
            exit(1);            
        }
        array_map(function ($content) {
            echo '<pre>';
            var_dump($content);
            echo '</pre>';
            echo '<hr>';
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
     * @return App|null
     */
    function app():App|null
    {
        return AppFactory::$app;
    }
}

if (!function_exists('config'))
{
    function config($path = null, $value = null)
    {
        $app=app();
        if($app)
        {
            $config=$app && $app->has('config')?$app->resolve('config'):null;

            if (is_null($value)) {
                return data_get($config, $path);
            }

            // rebind value
            data_set($config, $path, $value);
            $app->bind('config', $config);
        }
        else {
            return null;
        }
    }
}

if(!function_exists('is_production')){  
    function is_production  ():bool 
    {
        $env='production';
        if(app())
        {
            if(app()->has('config'))
            {
                $env=(string)config('app.env');
            }
        }
        else {
            $env=env('APP_ENV','production');
        }

        $env=strtolower((string)$env);
        return in_array($env,['production','prod']);
    }
}

/**
 * real path untuk folder root untuk kernel 
 */
if(!function_exists('path_base')){  
    /**
     * Get real path base of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */
    function path_base ($path=''){ return realpath(__DIR__."/../").DIRECTORY_SEPARATOR.$path; }
}


/**
 * real path directory vendor autoload
 * jika struktur folder vendor autoload : "htdocs/vendor"
 * maka path_vendor adalah "htdocs" bukan absolute merujuk langsung ke folder vendor-nya
 * RouteContextMiddleware akan mengakses route context melalu class finder disini
 */
if(!function_exists('path_vendor')){    
    /**
     * Get real real path "vendor" dir of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */
    function path_vendor ($path=''){ return realpath(__DIR__.'/../').DIRECTORY_SEPARATOR.$path; }
}


/** 
 * real path name space "App"
 */
if(!function_exists('path_app')){       
    /**
     * Get real path of "app" dir of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */
    function path_app ($path=''):string { return path_base('app'.DIRECTORY_SEPARATOR.$path); }
}

/**
 * real path for public directory 
 * yang biasanya berisi file atau folder assets, images etc,..
 * yang bisa diakses secara publik melalu browser
 */
if(!function_exists('path_public')){   
     /**
     * Get real "public" path dir of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */ 
    function path_public ($path=''):string { return path_app('public'.DIRECTORY_SEPARATOR.$path); }
}

/**
 * real path for assets directory 
 * access for folder in : public/assets  etc,..
 * for TwigHelperMiddleware
 */
if(!function_exists('path_assets')){  
    /**
     * Get real "assets" path dir of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */   
    function path_assets ($path=''):string { return path_public('assets'.DIRECTORY_SEPARATOR.$path); }
}


/**
 * real path for assets directory 
 * access for wbpack manifest.json etc,..
 * for TwigHelperMiddleware
 */
if(!function_exists('path_webpack_manifest')){  
    /**
     * Get real "assets" path dir of App Win/Linux directory of server machine
     * @param string $path
     * @return string
     */   
    function path_webpack_manifest ($path=''):string { return path_assets($path); }
}


/**
 * real path for folder config
 * access for loader config
 */
if(!function_exists('path_config')){    
    /**
     * Get real "config" path dir of config app Win/Linux directory of server machine
     * @param string $path
     * @return string
     */   
    function path_config ($path=''):string { return path_app('config'.DIRECTORY_SEPARATOR.$path); }
}

/**
 * real path for forder routes
 * access for route context
 */
if(!function_exists('path_routes')){ 
    /**
     * Get real "routes" path dir of routes app Win/Linux directory of server machine
     * @param string $path
     * @return string
     */     
    function path_routes ($path=''):string { return path_app('Routing'.DIRECTORY_SEPARATOR.$path); }
}

/**
 * real path for view
 * access for template engine
 */
if(!function_exists('path_view')){      
    /**
     * Get real "views" path dir of view template engine Win/Linux directory of server machine
     * @param string $path
     * @return string
     */   
    function path_view ($path=''):string { return path_app('views'.DIRECTORY_SEPARATOR.$path); }
}


/**
 * twig function resolvable
 */
if(!function_exists('url_base'))
 {
    /**
     * Relative url
     * @param string $path
     * @return string
     */
    function url_base($path = '')
    {
        return ltrim($path, '/');
    }
}

/**
 * twig function resolvable
 */
if(!function_exists('url_public'))
{   
    /**
     * Relative url public client browser
     * @param string $path
     * @return string
     */
    function url_public ($path=''):string { return url_base('public/'.$path); }
}


/**
 * twig function resolvable
 */
if(!function_exists('url_asset')){    
    /**
     * Relative url assets client browser
     * @param string $path
     * @return string
     */
    function url_asset ($path=''):string { return url_public('assets/'.$path); }
}


/**
 * twig function resolvable
 */
if(!function_exists('full_url'))
{
    /**
     * Full url client browser
     * @param string $path
     * @return string 
     */
    function full_url($path='')
    {       
        $url='';
        // resolve from config if exists "base_url"
        if(app()->has('config'))
        {
            $url=config('app.base_url');
            if($url)
            {
                $url=trim((string)$url);
                $url=rtrim($url,'/');
                return $url.'/'.$path;
            }
        }

        /**
         * @param string $route_name_of_home
         * @return string|null
         */
        $resolveFromInputRequest=function($route_name_of_home='home'){
            try
            {
              $input=app()->resolve(InputRequest::class);
              $parser=app()->getRouteCollector()->getRouteParser();
              return $parser->fullUrlFor($input->getCurrentUri(),$route_name_of_home); 
            }
            catch(\Exception $e)
            {
                return null;
            }
        };

        // concurent from inputRequest
        $test=$resolveFromInputRequest();
        if($test)
        {
            $url=rtrim($test,'/');
            return $url.'/'.$path;
        }       
        
        /**
         * resolve from server global
         * @return string|null
         */
        $resolveFromServerGlobal=function(){
            try 
            {
                $request=app()->has(\Psr\Http\Message\ServerRequestInterface::class);
                if(!$request)
                {
                    $request=\Intoy\HebatFactory\Psr17Factory::getServerRequestCreator()->createServerRequestFromGlobals();
                }
                if($request)
                {
                    $server=$request->getServerParams();
                    
                    $phpSapi=PHP_SAPI;
                    $basePath=null;
                    if($phpSapi==='cli-server')
                    {
                        $basePath=\Intoy\HebatFactory\Context::resolveBasePathFromScriptName($server);
                    }
                    else {
                        $basePath=\Intoy\HebatFactory\Context::resolveBasepathFromRequestUri($server);
                    }
                    if(!$basePath)
                    {
                        $basePath=\Intoy\HebatFactory\Context::resolveBasepathRelativeScriptName($server);
                    }

                    $scheme = $request->getUri()->getScheme();
                    $authority = $request->getUri()->getAuthority();
                    $http = ($scheme ? $scheme . ':' : '') . ($authority ? '//' . $authority : '');
                    if($basePath)
                    {
                        $http=rtrim($http,'/').'/'.ltrim($basePath,'/');
                    }
                    return $http;                    
                }

                // nothing
                return null;
            }
            catch(\Exception $e)
            {
                return null;
            }
        };

        // concurent from server global
        $test=$resolveFromServerGlobal();
        if($test)
        {
            $url=rtrim($test,'/');
            return $url.'/'.$path;
        }    

        // full empty
        if($path)
        {
            $path=ltrim($path,'/');
        }

        return $path;
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
        return full_url('public/assets/'.$path);
    }
}


if(!function_exists('routeFor'))
{
    /**
     * Callback Get route by route name / alias
     * Return string by route name
     * @return string
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
     * Callbac Redirect. Return : \Psr\Http\Message\ResponseInterface 
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
     * @return Response
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
     * @return Response
     */
    function responseJson($data=null, int $code = 200, string $reasonPhrase = '')
    {
        $res=app()->getResponseFactory()->createResponse($code, $reasonPhrase);
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('content-type','application/json');
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
        app()->resolve('logger.app')->info($message,$context);
    }
}


if (!function_exists('optional')) {
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

if(!function_exists('folder_make_path'))
{
    /**
     * @param string|string[] $folders
     * @param string $error Output variable jika ada error
     * @param string $savePath Output variable hasil path
     * @param string $path_public start/awal folder. Gunakan fungsi : path_public(), path_asset(). Jika null path_public diambil dari fungsi path_public()
     * @return bool jika berhasil hasil akhir $savePath sudah ditambahkan DIRECTORY_SEPARATOR
     */
    function folder_make_path($folders, &$error='', &$savePath='', $path_public=null)
    {
        if((is_string($folders) && strlen($folders)<1)
          || (is_array($folders) && count($folders)<1)
        )
        {
            $error=sprintf('Human readable parameter resolve folder must be valid string or string[]');
            return false;
        }

        // validasi path_public
        if(!is_null($path_public))
        {
            $real_path=realpath($path_public);
            $path_public=$real_path?$real_path:path_public();            
        }
        else {
            $path_public=path_public();
        }

        $folders=is_string($folders)?[$folders]:$folders;
        foreach(array_values($folders) as $nameFolder)
        {
            if(substr($path_public,strlen($path_public)-1,1)!==DIRECTORY_SEPARATOR)
            {
                $path_public.=DIRECTORY_SEPARATOR;
            }
            $full_dir=$path_public.$nameFolder;
            if(!is_dir($full_dir) && !mkdir($full_dir))
            {
                $error=sprintf('Gagal membuat/mengakses folder %s.',$nameFolder);
                if(!is_production())
                {
                    $error.=' Target folder "'.$full_dir.'"';
                }
                $error.='. Hubungi Administrator';
                return false;
            }
            $path_public=$path_public.$nameFolder;
            $savePath=$path_public;
        }

        $savePath.=DIRECTORY_SEPARATOR;

        return true;
    }
}