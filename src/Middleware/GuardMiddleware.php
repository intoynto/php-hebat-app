<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Intoy\HebatFactory\Foundation\Guard;

class GuardMiddleware implements MiddlewareInterface 
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $factory;

    /**
     * @var Guard
     */
    protected $guard;

    /**
     * @var bool
     */
    protected $persistentTokenMode=true;


    /**
     * Callable to be executed if the CSRF validation fails
     * It must return a ResponseInterface
     *
     * @var callable|null
     */
    protected $failureHandler;


    public function __construct(ResponseFactoryInterface $facktory, Guard $guard, ?callable $failureHandler = null)
    {
        $this->factory=$facktory;
        $this->guard=$guard;
        $this->failureHandler=$failureHandler;
    }


    public function setPersistenTokenMode(bool $persistentTokenMode)
    {
        $this->persistentTokenMode=$persistentTokenMode;
    }

    public function setFailurHandler(?callable $failureHandler = null)
    {
        $this->failureHandler=$failureHandler;
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $body = $request->getParsedBody();
        $name = null;
        $value = null;

        if (is_array($body)) {
            $name = $body[$this->guard->getTokenNameKey()] ?? null;
            $value = $body[$this->guard->getTokenValueKey()] ?? null;
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $isValid = $this->guard->validateToken((string) $name, (string) $value);
            if ($isValid && !$this->persistentTokenMode) {
                // successfully validated token, so delete it if not in persistentTokenMode
                $this->guard->removeTokenFromStorage($name);
            }

            if ($name === null || $value === null || !$isValid) {
                $request = $this->appendNewTokenToRequest($request);
                return $this->handleFailure($request, $handler);
            }
        } else {
            // Method is GET/OPTIONS/HEAD/etc, so do not accept the token in the body of this request
            if ($name !== null) {
                return $this->handleFailure($request, $handler);
            }
        }

        return $handler->handle($request);
    }


    /**
     * @param ServerRequestInterface $request*
     * @return ServerRequestInterface*
     * @throws Exception
     */
    public function appendNewTokenToRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $token = $this->guard->generateToken();
        return $this->appendTokenToRequest($request, $token);
    }


    /**
     * @param ServerRequestInterface $request
     * @param array $pair*
     * @return ServerRequestInterface
     */
    protected function appendTokenToRequest(ServerRequestInterface $request, array $pair): ServerRequestInterface
    {
        $name = $this->guard->getTokenNameKey();
        $value = $this->guard->getTokenValueKey();
        return $request
            ->withAttribute($name, $pair[$name])
            ->withAttribute($value, $pair[$value]);
    }


    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function handleFailure(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        if (!is_callable($this->failureHandler)) 
        {
            $response = $this->factory->createResponse();
            $body = $response->getBody();
            $body->write('Failed CSRF check!');
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'text/plain')
                ->withBody($body);
        }

        return call_user_func($this->failureHandler, $request, $handler);
    }
}