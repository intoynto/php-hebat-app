<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Slim\Interfaces\RouteCollectorProxyInterface;
use Intoy\HebatFactory\App;

class RouteGroup
{
    /**
     * App
     * @var App
     */
    protected $app;

    /**
     * GroupPrefix
     * @var string
     */
    protected $groupPrefix;

    /**
     * Filename Require
     * @var string
     */
    protected $fileName;

    /**
     * routes.controllers name
     * @var string
     */
    protected $configRouteName='';

    /**
     * Middleware
     * @var array
     */
    protected $middleware=[];

    public function __construct(App $app)
    {
        $this->app=$app;        
    }

    public function setGroupPrefix(string $groupPrefix):self
    {
        $this->groupPrefix=$groupPrefix;
        return $this;
    }

    public function setFileName(string $fileName):self
    {
        $this->fileName=$fileName;
        return $this;
    }

    public function setMiddleware(array $middleware):self
    {
        $this->middleware=$middleware;
        return $this;
    }

    public function setConfigRouteName(string $configRouteName):self
    {
        $this->configRouteName=$configRouteName;
        return $this;
    }

    public function routing()
    {
        $fileName=$this->fileName;
        $configRouteName=$this->configRouteName;
        $routeGroup=$this->app->group($this->groupPrefix,function(RouteCollectorProxyInterface $group) use ($fileName,$configRouteName){
            Route::storeApp($group);
            Route::storeConfigRouteName($configRouteName);
            require_once $fileName;
        });

        //dd($this->middleware);
        foreach($this->middleware as $mid)
        {
            $middleware=$this->app->resolve($mid);
            $routeGroup->add($middleware);
        }
        //re cache app
        Route::storeApp($this->app);
    }
}