<?php

declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Slim\Views\Twig;
use Twig\Extension\DebugExtension as TwigDebugExtension;
use Twig\Loader\FilesystemLoader;
use Intoy\HebatFactory\Loader;

class LoaderView extends Loader
{
    public function boot()
    {
        $callback=function()
        {
            $is_production=is_production();
            $config_app=config("app");

            $app=[];
            foreach(array_keys($config_app) as $key)
            {
                $value=data_get($config_app,$key);
                if(!is_object($value))
                {
                    $app[$key]=$value;
                }
            }
            $app["is_production"]=$is_production;
            $app["is_debug"]=!$is_production;
            $app["path"]=url_base();

            $twig_settings=config('twig.twig');
            $twig_path=config('twig.path');
            $loader=new FilesystemLoader($twig_path);

            $tw=new Twig($loader,$twig_settings);
            $tw->getEnvironment()->addGlobal('app',(object)$app); // assign in twig environment 
            $tw->addExtension(new TwigDebugExtension());            
            
            return $tw;
        };

        $tw=$callback();
        // register in container
        $this->app->bind(Twig::class,$tw); // twig by class
        $this->app->bind('view',$tw); // alias
    }
}