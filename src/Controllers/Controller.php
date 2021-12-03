<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Controllers;

use Psr\Container\ContainerInterface as Container;
use Intoy\HebatApp\InputFormRequest;

class Controller {
    /**
     * Container
     * @var Container
     */
    protected $container;


    public function __construct(Container $c)
    {        
        $this->container=$c;
        $this->onCreated();
    }

    protected function onCreated()
    {

    }

    protected function resolveInput(string $class=InputFormRequest::class):InputFormRequest
    {
        return $this->container->get($class);
    }

    /**
     * Magic resolve 
     */
    public function __get($name)
    {
        if($this->container->has($name)){
            return $this->container->get($name);
        }
    }
}