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

        $providers=[...$appProviders,RouteProvider::class];
        
        Provider::setup($app,$kernel,$providers);
    }
}