<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\test\Controllers;

use gordonmcvey\httpsupport\enum\statuscodes\SuccessCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\response\Response;
use gordonmcvey\WarpCore\middleware\MiddlewareProviderTrait;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\sdk\interface\middleware\MiddlewareProviderInterface;

class ExampleController implements RequestHandlerInterface, MiddlewareProviderInterface
{
    use MiddlewareProviderTrait;

    public function dispatch(RequestInterface $request): ?ResponseInterface
    {
        return new Response(
            responseCode: SuccessCodes::OK,
            body: (string) json_encode(["message" => "Hello, World!"]),
            contentType: "application/json",
            encoding: "utf-8",
        );
    }
}
