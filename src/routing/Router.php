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

use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\WarpCore\sdk\exception\routing\InvalidPath;
use gordonmcvey\WarpCore\sdk\exception\routing\MethodNotAllowed;
use gordonmcvey\WarpCore\sdk\exception\routing\NoRouteToController;
use gordonmcvey\WarpCore\sdk\interface\routing\RouterInterface;
use gordonmcvey\WarpCore\sdk\interface\routing\RoutingStrategyInterface;

/**
 * Router class
 *
 * The Router class is responsible for determining the correct request handler (controller) for a given request.  It
 * does this by applying routing strategies until a strategy finds the name of an approporiate class to handle the
 * request.  This can then be used by a factory to instantiate the actual request handler.
 */
class Router implements RouterInterface
{
    /**
     * @var array<array-key, RoutingStrategyInterface>
     */
    private readonly array $routingStrategies;

    /**
     * @var array<string, array<RouteSpec>>
     */
    private array $routeSpecCache = [];

    public function __construct(
        private readonly RequestPathValidator $pathValidator,
        RoutingStrategyInterface ...$routingStrategies,
    ) {
        $this->routingStrategies = $routingStrategies;
    }

    /**
     * @throws InvalidPath if the URI path cannot be parsed
     * @throws NoRouteToController If no strategies match the request path
     * @throws MethodNotAllowed if the request cannot be resolved to a controller class
     */
    public function route(RequestInterface $request): string
    {
        $uri = $request->uri();
        $path = $this->pathValidator->getPath($uri);
        $routesForPath = $this->getRoutesForPath($uri, $path);
        $verb = $request->verb();

        foreach ($routesForPath as $routeSpec) {
            if (in_array($verb, $routeSpec->verbs, true)) {
                return $routeSpec->controllerClass;
            }
        }

        throw new MethodNotAllowed(
            sprintf("No suitable controllers found for URI path %s that support method '%s'", $uri, $verb->value),
        );
    }

    /**
     * @return array<RouteSpec>
     * @throws NoRouteToController If no strategies match the request path
     */
    private function getRoutesForPath(string $uri, string $path): array
    {
        if (!isset($this->routeSpecCache[$path])) {
            $this->routeSpecCache[$path] = [];

            foreach ($this->routingStrategies as $strategy) {
                $className = $strategy->route($path);
                if (null !== $className) {
                    $this->routeSpecCache[$path][] = new RouteSpec($className, ...$strategy->forVerbs());
                }
            }
        }

        if (empty($this->routeSpecCache[$path])) {
            throw new NoRouteToController(
                sprintf("No suitable controller found for URI path '%s' (for any method)", $uri),
            );
        }

        return $this->routeSpecCache[$path];
    }
}
