<?php
declare (strict_types=1);

namespace Intoy\HebatFactory\Loaders;

use Intoy\HebatFactory\Loader;
use Intoy\HebatFactory\Session;
use Intoy\HebatFactory\Foundation\Guard;
use Psr\Container\ContainerInterface;

class LoaderSession extends Loader
{
    public function boot()
    {
        $fn=new Session();       
        $fn->start();
        $this->app->bind(Session::class,$fn);
        $this->app->bind('session',$fn); // alias */

        $this->app->bind(Guard::class,function(ContainerInterface $container)
        {
            $session=$container->get(Session::class);
            $guard=new Guard($session);
            return $guard;
        });
    }
}