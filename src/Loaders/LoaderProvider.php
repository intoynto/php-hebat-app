<?php

namespace Intoy\HebatApp\Loaders;

use Intoy\HebatFactory\Loader;
use Intoy\HebatFactory\Provider;
use Intoy\HebatApp\Providers\RouteProvider;

class LoaderProvider extends Loader
{
    public function boot()
    {
        $app=$this->app;
        $kernel=$this->kernel;

        $appProviders=config('app.providers');

        if($appProviders && is_array($appProviders)){
            $providers=[...$appProviders,RouteProvider::class];
        }
        else {
            $providers=[RouteProvider::class];
        }
        // boot setup provider        
        Provider::setup($app,$kernel,$providers);
    }
}