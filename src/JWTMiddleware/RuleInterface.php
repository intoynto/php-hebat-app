<?php

declare (strict_types=1);


namespace Intoy\HebatApp\JWTMiddleware;

use Psr\Http\Message\ServerRequestInterface as Request;

interface RuleInterface
{
    public function __invoke(Request $request): bool;
}