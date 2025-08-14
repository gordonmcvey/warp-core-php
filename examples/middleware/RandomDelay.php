<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\examples\middleware;

use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;

/**
 * Middleware to add a randomised delay into the request/response cycle of 0 .. 1 second
 *
 * This exists basically to demonstrate the Profiler middleware
 */
class RandomDelay implements MiddlewareInterface
{
    public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $delay = mt_rand(0, 1000000);

        error_log(message: sprintf("%s: %d", __METHOD__, $delay));
        usleep($delay);
        return $handler->dispatch($request);
    }
}
