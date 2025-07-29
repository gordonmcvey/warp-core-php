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

use gordonmcvey\JAPI\interface\middleware\MiddlewareInterface;

/**
 * Standard MiddlewareProvider implementation
 *
 * This trait exists to eliminate the need to implement the same logic for middleware providers over and over.  You are,
 * of course, free to ignore it and make your own implementations if you need more than standard functionality, but in
 * most cases, this implementation should suffice.
 */
trait MiddlewareProviderTrait
{
    /**
     * @var array<array-key, MiddlewareInterface>
     */
    private array $middleware = [];

    public function addMiddleware(MiddlewareInterface $newMiddleware): self
    {
        $this->middleware[] = $newMiddleware;
        return $this;
    }

    public function addMultipleMiddleware(MiddlewareInterface ...$middleware): self
    {
        foreach ($middleware as $newMiddleware) {
            $this->addMiddleware($newMiddleware);
        }

        return $this;
    }

    public function resetMiddleware(): self
    {
        $this->middleware = [];
        return $this;
    }

    public function replaceMiddlewareWith(MiddlewareInterface $middleware): self
    {
        return $this->resetMiddleware()->addMiddleware($middleware);
    }

    /**
     * @return array<array-key, MiddlewareInterface>
     */
    public function getAllMiddleware(): array
    {
        return $this->middleware;
    }
}
