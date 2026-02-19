<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\test\Controllers;

use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;
use Throwable;

readonly class ExceptionController implements RequestHandlerInterface
{
    public function __construct(private string $exceptionClass, private int $code)
    {
    }

    /**
     * @throws Throwable
     */
    public function dispatch(RequestInterface $request): ?ResponseInterface
    {
        /** @var Throwable $e */
        $e = new $this->exceptionClass(code: $this->code);
        throw $e;
    }
}
