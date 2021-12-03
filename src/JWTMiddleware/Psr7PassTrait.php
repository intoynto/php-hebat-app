<?php

declare (strict_types=1);

namespace Intoy\HebatApp\JWTMiddleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as Handler;


final class CallableHandler implements Handler
{
    private $callable;
    private $response;

    public function __construct(callable $callable, Response $response)
    {
        $this->callable = $callable;
        $this->response = $response;
    }

    public function handle(Request $request): Response
    {
        return ($this->callable)($request, $this->response);
    }
}

trait Psr7PassTrait {
    public function __invoke(
        Request $request,
        Response $response,
        callable $next
    ): Response {
        return $this->process($request, new CallableHandler($next, $response));
    }
}