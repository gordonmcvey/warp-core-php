<?php

/**
 * Copyright © 2025 Gordon McVey
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace gordonmcvey\JAPI\middleware;

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\JAPI\interface\controller\RequestHandlerInterface;
use gordonmcvey\JAPI\interface\middleware\MiddlewareInterface;

/**
 * Slot class
 *
 * This class represents a slot in a middleware call stack
 *
 * @internal This class is used by the Middleware call stack, you're not meant to instantiate it in your applications
 */
readonly class Slot implements RequestHandlerInterface
{
    public function __construct(private MiddlewareInterface $middleware, private RequestHandlerInterface $next)
    {
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        return $this->middleware->handle($request, $this->next);
    }
}
