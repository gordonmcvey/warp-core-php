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

namespace gordonmcvey\WarpCore\routing;

use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\WarpCore\sdk\interface\routing\RoutingStrategyInterface;

/**
 * Static routing strategy
 *
 * This routing strategy maps a path to a controller via a simple static array.  If the path matches an array key then
 * the controller name associated with that key is returned, otherwise the strategy returns null.
 *
 * This approach is limited to the paths you specify matches for, but is also potentially faster than more traditional
 * strategies.
 */
class StaticStrategy implements RoutingStrategyInterface
{
    /**
     * @var array<Verbs>
     */
    private readonly array $verbs;

    /**
     * @param array<string, string> $routes
     */
    public function __construct(private array $routes, Verbs ...$verbs)
    {
        $this->verbs = $verbs;
    }

    public function route(string $path): ?string
    {
        return $this->routes[$path] ?? null;
    }

    public function forVerbs(): array
    {
        return $this->verbs;
    }

    public function addRoute(string $route, string $controllerClass): self
    {
        $this->routes[$route] = $controllerClass;
        return $this;
    }
}
