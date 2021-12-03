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
    protected function getPrefixFiles(string $prefix)
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

    public function apiRouteGroup()
    {
        $files=$this->getPrefixFiles('api');
        sort($files);
        $add=$this->resolve('middleware');
        foreach($files as $file)
        {
            $filename=path_routes($file);
            $routeGroup=$this->getRouteGroup();
            $routeGroup
                ->setFileName($filename)
                ->setGroupPrefix('/api')
                ->setConfigRouteName('api')
                ->setMiddleware([
                    ...$add['api'],
                    ...$add['global']
                ]);
            $routeGroup->routing();        
        }                
    }


    public function webRouteGroup()
    {
        $files=$this->getPrefixFiles('web');
        sort($files);
        $add=$this->resolve('middleware');
        foreach($files as $file)
        {
            $filename=path_routes($file);
            $routeGroup=$this->getRouteGroup();
            $routeGroup
                ->setFileName($filename)
                ->setGroupPrefix('')
                ->setConfigRouteName('web')
                ->setMiddleware([
                    ...$add['web'],
                    ...$add['global']
                ]);
            $routeGroup->routing();        
        }      
    }

    protected function boot()
    {        
        $this->apiRouteGroup();
        $this->webRouteGroup();        
    }
}