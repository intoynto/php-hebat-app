<?php
namespace Intoy\HebatApp\Middleware;

use Intoy\HebatFactory\Context;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class BasePathMiddleware 
{
    /**
     * @var string The PHP_SAPI value
     */
    private $phpSapi;


    protected function getBasePath(array $server, string $phpSapi = null)
    {
        $this->phpSapi=$phpSapi??PHP_SAPI;
        if($this->phpSapi==="cli-server")
        {
            $basePath=Context::resolveBasePathFromScriptName($server);
        }
        else {
            $basePath=Context::resolveBasepathFromRequestUri($server);
        }
        if(!$basePath)
        {
            $basePath=Context::resolveBasepathRelativeScriptName($server);
        }
        return $basePath;
    }

    public function __invoke(Request $request, Handler $handler):Response
    {
        $basePath=$this->getBasePath($request->getServerParams());
        app()->setBasePath($basePath);
        return $handler->handle($request);
    }
}