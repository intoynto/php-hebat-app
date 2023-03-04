<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Intoy\HebatApp\Session;
use Intoy\HebatFactory\Loader;
use Intoy\HebatFactory\Foundation\Guard;
use Psr\Container\ContainerInterface;

class LoaderSession extends Loader
{
    public function boot()
    {
        $fn=new Session();  
        // custom session in php     
        $fn->setOptions(config('session')); // parameter session php
        $fn->start(); // start new session
        
        $this->app->bind(Session::class,$fn);
        $this->app->bind('session',$fn); // alias

        $this->app->bind(Guard::class,function(ContainerInterface $container)
        {
            $session=$container->get(Session::class);
            $guard=new Guard($session);
            return $guard;
        });
    }
}