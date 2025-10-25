<?php

namespace gordonmcvey\WarpCore\test\Controllers;

use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;

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
