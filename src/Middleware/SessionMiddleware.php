<?php
namespace Intoy\HebatApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class SessionMiddleware 
{
    public function __invoke(Request $request, Handler $handler):Response
    {        
        if(!session()->isStarted())
        {
            session()->start();
        }
        $response=$handler->handle($request);
        session()->save();
        session()->flashAll(); // clear save flash
        return $response;
    }
}