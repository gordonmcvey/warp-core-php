<?php

/**
 * Copyright © 2026 Gordon McVey
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

namespace gordonmcvey\WarpCore\routing\strategy;

use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\WarpCore\sdk\interface\routing\RoutingStrategyInterface;
use ValueError;

readonly class PlaceholdersStrategy implements RoutingStrategyInterface
{
    use ForVerbsTrait;

    private const string PLACEHOLDER_REGEX = ";{[^}]+};";
    private const string PLACEHOLDER_REPLACEMENT = "[^/]+";
    private const string PATTERN_TEMPLATE = ";^%s/?$;";

    private string $pattern;

    /**
     * @throws ValueError
     */
    public function __construct(private string $routeFormat, private string $controllerClass, Verbs ...$verbs)
    {
        if (str_contains($this->routeFormat, ";")) {
            throw new ValueError("Pattern cannot contain delimiter character ;");
        }
        $this->verbs = $verbs;
        $this->pattern = sprintf(self::PATTERN_TEMPLATE, preg_replace(
            self::PLACEHOLDER_REGEX,
            self::PLACEHOLDER_REPLACEMENT,
            $this->routeFormat
        ));
    }

    public function route(string $path): ?string
    {
        return preg_match($this->pattern, $path) ? $this->controllerClass : null;
    }
}
