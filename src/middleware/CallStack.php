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

namespace gordonmcvey\WarpCore\middleware;

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;

/**
 * Middleware call stack
 *
 * As middleware processing is handled in a stack, it is run in the reverse order in which it was added and returns in
 * the opposite order from that.  The upshot of this is that middleware that processes the request does the processing
 * in last-to-first order, and middleware that processes the response is run in first-to-last order (which makes sense
 * if you think about it, though it's not immideately obvious).  You can think of it as a request going from the
 * "outside" (The entry point) to the "inside" (the root of the stack) during the request phase, then back to the
 * "outside" during the response phase.  Bear this in mind when deciding on the order in which you add middleware to
 * the stack (eg, middleware that handles authentication should be added last as we'd want it to run first)
 */
class CallStack implements RequestHandlerInterface
{
    private RequestHandlerInterface $entryPoint;

    public function __construct(private readonly RequestHandlerInterface $root)
    {
        $this->entryPoint = $this->root;

        if ($this->root instanceof MiddlewareProviderInterface) {
            $this->fromProvider($this->root);
        }
    }

    /**
     * Add additional middleware to the call stack
     */
    public function add(MiddlewareInterface $newMiddleware): self
    {
        $this->entryPoint = new Slot($newMiddleware, $this->entryPoint);

        return $this;
    }

    /**
     * Add multiple middlewares to the call stack in one go
     */
    public function addMulti(MiddlewareInterface ...$middleware): self
    {
        foreach ($middleware as $newMiddleware) {
            $this->add($newMiddleware);
        }

        return $this;
    }

    /**
     * Clear the middleware stack except for the root item
     */
    public function reset(): self
    {
        $this->entryPoint = $this->root;
        return $this;
    }

    /**
     * Replace any existing middleware in the call stack with the provided middleware
     */
    public function replaceWith(MiddlewareInterface $middleware): self
    {
        return $this->reset()->add($middleware);
    }

    /**
     * Add all middleware from a provider to the call stack
     */
    public function fromProvider(MiddlewareProviderInterface $provider): self
    {
        $this->addMulti(...$provider->getAllMiddleware());
        return $this;
    }

    /**
     * Dispatch a request via the call stack and return a response
     */
    public function dispatch(RequestInterface $request): ?ResponseInterface
    {
        return $this->entryPoint->dispatch($request);
    }
}
