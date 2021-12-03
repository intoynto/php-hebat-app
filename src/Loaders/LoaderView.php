<?php

declare (strict_types=1);

namespace Intoy\HebatFactory\Loaders;

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
            $app=[
                'name'=>config('app.name'),
                'title'=>config('app.title'),
                'instansi'=>config("app.instansi"),
                'version'=>config("app.version"),
                'is_production'=>$is_production,
                'is_debug'=>!$is_production,
                'path'=>url_base()
            ];       

            $twig_settings=config('twig.twig');
            $twig_path=config('twig.path');
            $loader=new FilesystemLoader($twig_path);

            $tw=new Twig($loader,$twig_settings);
            $tw->getEnvironment()->addGlobal('app',(object)$app);
            $tw->addExtension(new TwigDebugExtension());            
            
            return $tw;
        };

        $tw=$callback();
        // register in container
        $this->app->bind(Twig::class,$tw); // twig by class
        $this->app->bind('view',$tw); // alias
    }
}