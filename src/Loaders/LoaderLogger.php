<?php

declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Intoy\HebatFactory\Loader;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Handler\StreamHandler;

class LoaderLogger extends Loader
{
    protected function setupLogger(string $prefix)
    {
        $this->app->bind("logger.{$prefix}",function() use ($prefix) {
            $processor=new UidProcessor();
            $logger=new Logger("App");
            $logger->pushProcessor($processor);

            $level=is_production()?\Monolog\Logger::DEBUG:\Monolog\Logger::INFO;
            $handler=new StreamHandler(config("logger.path")."/{$prefix}.log",$level);
            $logger->pushHandler($handler);
            return $logger;
        });
    }

    public function boot()
    {
        $this->setupLogger('app');
    }
}