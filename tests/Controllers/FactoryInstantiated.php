<?php

namespace gordonmcvey\JAPI\test\Controllers;

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\JAPI\interface\controller\RequestHandlerInterface;

readonly class FactoryInstantiated implements RequestHandlerInterface
{
    public function __construct(public ?string $arg1 = null, public ?int $arg2 = null, public ?bool $arg3 = null)
    {
    }

    public function dispatch(RequestInterface $request): ?ResponseInterface
    {
        return null;
    }
}
