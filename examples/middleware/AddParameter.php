<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\examples\middleware;

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;

class AddParameter implements MiddlewareInterface
{
    public function __construct(private readonly string $key, private readonly string $value)
    {
    }

    public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        error_log(message: sprintf("%s: %s, %s", __METHOD__, $this->key, $this->value));

        $response = $handler->dispatch($request);
        $payload = json_decode($response->body());
        $payload->{$this->key} = $this->value;

        return $response->setBody((string) json_encode($payload, JSON_PRETTY_PRINT));
    }
}
