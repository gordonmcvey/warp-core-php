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

namespace gordonmcvey\JAPI\interface\middleware;

/**
 * Middleware Provider Interface
 *
 * This interface allows the implementing class to provide a bundle of middleware that will be added to the call stack
 * when passed in.  The expected use case for this is if you want a controller to specify what middleware gets run when
 * it's invoked independent of any other middleware that may already be present in the stack.
 *
 * The usual rules on execution order apply to providers as apply to the middleware stack as a whole.
 */
interface MiddlewareProviderInterface
{
    /**
     * Add middleware to the provider
     */
    public function addMiddleware(MiddlewareInterface $newMiddleware): self;

    public function addMultipleMiddleware(MiddlewareInterface ...$newMiddleware): self;

    /**
     * Clear all middleware from the provider
     */
    public function resetMiddleware(): self;

    /**
     * Clear all middleware and replace it with the specified middleware
     */
    public function replaceMiddlewareWith(MiddlewareInterface $middleware): self;

    /**
     * Get the middleware that will be provided
     *
     * @return array<array-key, MiddlewareInterface>
     */
    public function getAllMiddleware(): array;
}
