<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

use Intoy\HebatFactory\App;
use Intoy\HebatFactory\Psr17Factory;
use Intoy\HebatFactory\AppFactoryBridge as AppFactory;
use Intoy\HebatApp\HttpKernel;

class Boot 
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    /**
     * @var App
     */
    private static $app;

    /**
     * @return ContainerInterface
     */
    public static function createContainer()
    {
        if(static::$container)
        {
            return static::$container;
        }

        $builder=new ContainerBuilder();
        $builder->addDefinitions([
            Psr17Factory::class=>function()
            {
                return new Psr17Factory();
            },
            ResponseFactoryInterface::class=>function(ContainerInterface $c):ResponseFactoryInterface
            {
                return $c->get(Psr17Factory::class);
            }
        ]);

        static::$container=$builder->build();
        return static::$container;
    }

    /**
     * @return App
     */
    public static function createApp()
    {
        if(static::$app) return static::$app;

        static::$app=AppFactory::createFromContainer(static::createContainer());

        // store in kernel
        $kernel=new HttpKernel(static::$app);

        // return app;
        return static::$app;
    }
}