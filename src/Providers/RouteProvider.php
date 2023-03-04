<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Providers;

use Intoy\HebatFactory\Provider;
use Intoy\HebatApp\Route;
use Intoy\HebatApp\RouteGroup;
use Intoy\HebatSupport\Str;

class RouteProvider extends Provider
{
    protected function afterCreate()
    {
        // store app in route
        Route::storeApp($this->app);

        // register callable route group class
        $app=$this->app;
        $this->bind(RouteGroup::class,function() use ($app) {
            return new RouteGroup($app);
        });
    }

    protected function getRouteGroup():RouteGroup
    {
        return $this->resolve(RouteGroup::class);
    }


    /**
     * @return array
     */
    protected function scanFilesFromPrefix(string $prefix)
    {
        $config = [];
        $config_folder=path_routes();
        
        if(is_dir($config_folder))
        {
            $folder = scandir(path_routes());
            $config_files = array_slice($folder, 2, count($folder));
            foreach($config_files as $file)
            {
                throw_when(Str::afterLast($file,'.')!=='php','Config files must be .php files');                
                if(Str::startsWith($file,$prefix))
                {
                    //set in data                    
                    $config[]=$file;
                }
            }
        }
        return $config;
    }

    protected function boot()
    {        
        $prefixs=config('routes.prefix');
        $keys=[];
        $values=[];
        $setups=[];
        $empty_setup=null;

        foreach($prefixs as $key => $value)
        {
            if(!in_array($key,$keys))
            {
                $files=$this->scanFilesFromPrefix($key);
                sort($files);

                $kernelMiddlewares=$this->app->has('middleware')
                                   ?($this->app->resolve('middleware')??[])
                                   :[];
                $selfMiddleware=data_get($kernelMiddlewares,$key,[]);
                $globalMiddleware=data_get($kernelMiddlewares,'global',[]);
                $set=[
                    'path'=>$value,
                    'files'=>$files,
                    'middlewares'=>[...$selfMiddleware, ...$globalMiddleware],
                ];
                if(empty($value) && !$empty_setup)
                {
                    $empty_setup=$set;
                }                
                $setups[$key]=$set;
            }
            $keys[]=$key;
            $values[]=$value;
        }

        
        foreach($setups as $key => $option)
        {
            $this->setupRouteGroup($key,$option['path'],$option['files'],$option['middlewares']);
        }              

        if(!is_production())
        {
            $this->bind('native.routeGroups',fn()=>$setups);
        }
    }


    /**
     * @param string $prefix
     * @param string $routeGroup
     * @param array $files
     * @param array $middleware
     */
    protected function setupRouteGroup($prefix,$path,$files,$middlewares)
    {
        foreach($files as $file)
        {
            $filename=path_routes($file);
            $routeGroup=$this->getRouteGroup();
            
            $routeGroup->setFileName($filename)
                        ->setGroupPrefix($path)
                        ->setConfigRouteName($prefix)
                        ->setMiddleware($middlewares)
                        ;
            $routeGroup->routing();
        }
    }
}