<?php

declare (strict_types=1);


namespace Intoy\HebatApp\JWTMiddleware;

use DomainException;
use InvalidArgumentException;
use Exception;
use RuntimeException;
use SplStack;

use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Dflydev\FigCookies\FigRequestCookies;

use Intoy\HebatFactory\Psr17Factory as ResponseFactory;

class JWTMiddleware implements MiddlewareInterface
{
    use Psr7PassTrait;

    /**
     * PSR-3 compliant logger.
     * @var LoggerInterface|null
     */
    private $logger;


    /**
     * Last error message.
     * @var string
     */
    private $message;


    /**
     * Stores all the options passed to the middleware.
     * @var mixed[]
     */
    private $options = [
        "secure" => true,
        "relaxed" => ["localhost", "127.0.0.1"],
        "algorithm" => ["HS256", "HS512", "HS384"],
        "header" => "Authorization",
        "regexp" => "/Bearer\s+(.*)$/i",
        "cookie" => "token",
        "attribute" => "token",
        'leeway'=>null,
        "path" => "/",
        "ignore" => null,
        "before" => null,
        "after" => null,
        "error" => null
    ];


    private $paramOptions=[];


    /**
     * The rules stack.
     * @var SplStack<RuleInterface>
     */
    private $rules;

    public function __construct(array $options=[])
    {
        if(empty($options))
        {
            $options=config('routes.jwt')?:[];
        }

        $this->rules=new \SplStack();
        $this->paramOptions=$options;
        $this->setupOptions($options);

        if(!isset($options['rules']))
        {
            $this->rules->push(new RequestMethodRule([
                "ignore" => ["OPTIONS"]
            ]));
            $this->rules->push(new RequestPathRule([
                "path" => $this->options["path"],
                "ignore" => $this->options["ignore"]
            ]));
        }
    }

    public function process(Request $request, Handler $handler): Response
    {
        $scheme = $request->getUri()->getScheme();
        $host = $request->getUri()->getHost();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $handler->handle($request);
        }


        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($host, $this->options["relaxed"])) {
                $message = sprintf("Insecure use of middleware over %s denied by configuration.",strtoupper($scheme));
                throw new RuntimeException($message);
            }
        }


        /* If token cannot be found or decoded return with 401 Unauthorized. */
        try {
            if(isset($this->options['leeway']) && !\is_null($this->options['leeway']))
            {
                \Firebase\JWT\JWT::$leeway=$this->options['leeway'];
            }
            $token = $this->fetchToken($request);
            $decoded = $this->decodeToken($token);
        } catch (RuntimeException | DomainException $exception) {
            $response = (new ResponseFactory)->createResponse(401, $exception->getMessage());
            return $this->processError($response, [
                "message" => $exception->getMessage(),
                "uri" => (string)$request->getUri()
            ]);
        }

        $params = [
            "decoded" => $decoded,
            "token" => $token,
        ];

        /* Add decoded token to request as attribute when requested. */
        if ($this->options["attribute"]) {
            $request = $request->withAttribute($this->options["attribute"], $decoded);
        }

        /* Modify $request before calling next middleware. */
        if (is_callable($this->options["before"])) {
            $response = (new ResponseFactory)->createResponse(200);
            $beforeRequest = $this->options["before"]($request, $params);
            if ($beforeRequest instanceof Request) 
            {
                $request = $beforeRequest;
            }
        }

        /* Everything ok, call next middleware. */
        $response = $handler->handle($request);

        /* Modify $response before returning. */
        if (is_callable($this->options["after"])) {
            $afterResponse = $this->options["after"]($response, $params);
            if ($afterResponse instanceof Response) {
                return $afterResponse;
            }
        }

        return $response;
    }


    /**
     * Check if middleware should authenticate.
     */
    private function shouldAuthenticate(Request $request): bool
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($request)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Fetch the access token.
     */
    private function fetchToken(Request $request): string
    {
        /* Check for token in header. */
        $header = $request->getHeaderLine($this->options["header"]);

        if (false === empty($header)) {
            if (preg_match($this->options["regexp"], $header, $matches)) 
            {               
                return $matches[1];
            }
        }

        /* Token not found in header try a cookie. */
        $cookie=FigRequestCookies::get($request,$this->options['cookie']);
        $cookieValue=$cookie->getValue();

        if (!empty($cookieValue)) 
        {            
            if (preg_match($this->options["regexp"], $cookieValue, $matches)) {
                return $matches[1];
            }
            return $cookieValue;
        };

        /* If everything fails log and throw. */        
        throw new RuntimeException("Authorization Required");
    }


    /**
     * Decode the token.
     *
     * @return mixed[]
     */
    private function decodeToken(string $token): array
    {
        try {
            $decoded = JWT::decode(
                $token,
                $this->options["secret"],
                (array) $this->options["algorithm"]
            );
            return (array) $decoded;
        } catch (Exception $exception) {
            throw $exception;
        }
    }


    /**
     * Call the error handler if it exists.
     *
     * @param mixed[] $arguments
     */
    private function processError(Response $response, array $arguments): Response
    {
        if (is_callable($this->options["error"])) {
            $handlerResponse = $this->options["error"]($response, $arguments);
            if ($handlerResponse instanceof Response) {
                return $handlerResponse;
            }
        }
        return $response;
    }



    /**
     * Logs with an arbitrary level.
     *
     * @param mixed[] $context
     */
    private function log(string $message, string $level=LogLevel::WARNING, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }


    protected function setupOptions(array $options)
    {
        foreach($options as $key => $value)
        {
            $key = str_replace(".", " ", $key);
            $method = lcfirst(ucwords($key));
            $method = str_replace(" ", "", $method);
            if (method_exists($this, "set".ucfirst($method))) {
                /* Try to use setter */
                /** @phpstan-ignore-next-line */
                call_user_func([$this, "set".ucfirst($method)], $value);
            } else {
                /* Or fallback to setting option directly */
                $this->options[$key] = $value;
            }
        }
    }

    public function getOptions():array { return $this->options; }

    public function getParamOptions():array { return $this->paramOptions; }

    public function setLogger(LoggerInterface $logger=null)
    {
        $this->logger=$logger;
    }

    /**
     * Set path where middleware should bind to.
     *
     * @param string|string[] $path
     */
    private function setPath($path): void
    {
        $this->options["path"] = (array) $path;
    }


    /**
     * Set path which middleware ignores.
     *
     * @param string|string[] $ignore
     */
    private function setIgnore($ignore): void
    {
        $this->options["ignore"] = (array) $ignore;
    }


    /**
     * Set the cookie name where to search the token from.
     */
    private function setCookie(string $cookie): void
    {
        $this->options["cookie"] = $cookie;
    }


    /**
     * Set the secure flag.
     */
    private function setSecure(bool $secure): void
    {
        $this->options["secure"] = $secure;
    }


    /**
     * Set the header where token is searched from.
     */
    private function setHeader(string $header): void
    {
        $this->options["header"] = $header;
    }


    /**
     * Set the regexp used to extract token from header or environment.
     */
    private function setRegexp(string $regexp): void
    {
        $this->options["regexp"] = $regexp;
    }

    /**
     * Set the attribute name used to attach decoded token to request.
     */
    private function setAttribute(string $attribute): void
    {
        $this->options["attribute"] = $attribute;
    }


    /**
     * Set the allowed algorithms
     *
     * @param string|string[] $algorithm
     */
    private function setAlgorithm($algorithm): void
    {
        $this->options["algorithm"] = (array) $algorithm;
    }

    /**
     * Set the secret key.
     *
     * @param string|string[] $secret
     */
    private function setSecret($secret): void
    {
        if (false === is_array($secret) && false === is_string($secret) && !$secret instanceof \ArrayAccess) {
            throw new InvalidArgumentException(
                'Secret must be either a string or an array of "kid" => "secret" pairs'
            );
        }
        $this->options["secret"] = $secret;
    }


    /**
     * Set the rules.
     * @param RuleInterface[] $rules
     */
    private function setRules(array $rules): void
    {
        foreach ($rules as $callable) {
            $this->rules->push($callable);
        }
    }
}