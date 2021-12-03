<?php

declare (strict_types=1);


namespace Intoy\HebatApp\JWTMiddleware;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Rule to decide by HTTP verb whether the request should be authenticated or not.
 */
final class RequestMethodRule implements RuleInterface
{

    /**
     * Stores all the options passed to the rule.
     * @var mixed[]
     */
    private $options = [
        "ignore" => ["OPTIONS"]
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(Request $request): bool
    {
        return !in_array($request->getMethod(), $this->options["ignore"]);
    }
}