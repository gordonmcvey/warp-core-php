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

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\WarpCore\Exceptions\Routing;
use gordonmcvey\WarpCore\interface\routing\RouterInterface;
use gordonmcvey\WarpCore\interface\routing\RoutingStrategyInterface;

/**
 * Router class
 *
 * The Router class is responsible for determining the correct request handler (controller) for a given request.  It
 * does this by applying routing strategies until a strategy finds the name of an approporiate class to handle the
 * request.  This can then be used by a factory to instantiate the actual request handler.
 */
readonly class Router implements RouterInterface
{
    /**
     * Regex that URI paths are validated against
     *
     * @link https://regex101.com/r/IsPSk2/1
     */
    private const string SAFE_PATH = "/^(?:(?:\/[\w-]+)+|\/)$/";

    /**
     * Additiona regex to detect potentially suspicious character sequences
     *
     * @link https://regex101.com/r/mXCVyB/1
     */
    private const string ILLEGAL_CHARACTER_SEQUENCE = "/[_-]{2,}/";

    /**
     * @var array<array-key, RoutingStrategyInterface>
     */
    private array $routers;

    public function __construct(RoutingStrategyInterface ...$routers)
    {
        $this->routers = $routers;
    }

    /**
     * @throws Routing
     */
    public function route(RequestInterface $request): string
    {
        $path = $this->extractPath($request->uri());
        $this->validatePath($path);

        foreach ($this->routers as $router) {
            $controllerClass = $router->route($path);

            if (null !== $controllerClass) {
                return $controllerClass;
            }
        }

        throw new Routing(
            sprintf("No controller found for URI path %s", $request->uri()),
            ClientErrorCodes::NOT_FOUND->value,
        );
    }

    /**
     * Extract the path portion of the given URI
     *
     * @throws Routing
     */
    private function extractPath(string $uri): string
    {
        $parsed = parse_url($uri, PHP_URL_PATH);
        if (!$parsed) {
            throw new Routing(
                sprintf("Unable to parse URI path '%s'", $uri),
                ClientErrorCodes::BAD_REQUEST->value
            );
        }

        return (string) $parsed;
    }

    /**
     * Validate that the path is safe
     *
     * As the path is user-supplied, it can't be trusted, so we'll check it for anything that looks nefarious and bail
     * out if anything looks like it may be problematic
     *
     * @throws Routing
     */
    private function validatePath(string $path): void
    {
        if ("" === $path) {
            throw new Routing("No URI path", ClientErrorCodes::BAD_REQUEST->value);
        }

        if ("/" === $path) {
            return;
        }

        if (
            !preg_match(self::SAFE_PATH, $path)
            || preg_match(self::ILLEGAL_CHARACTER_SEQUENCE, $path)
        ) {
            throw new Routing(
                sprintf(
                    "Invalid characters or sequences in URI path %s",
                    $path,
                ),
                ClientErrorCodes::BAD_REQUEST->value,
            );
        }
    }
}
