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
use gordonmcvey\WarpCore\sdk\dto\routing\Route;
use gordonmcvey\WarpCore\sdk\interface\routing\RoutingStrategyInterface;
use ValueError;

class PlaceholdersStrategy implements RoutingStrategyInterface
{
    use ForVerbsTrait;

    // The regex we use to find placeholders in the path template (they're labels delimited with {} brackets)
    private const string PLACEHOLDER_REGEX = ";{([^}]+)};";

    // The regex we replace the placeholders with when matching against an actual path (we match any sequence that
    // terminates with either a / or the end of the string
    private const string PLACEHOLDER_REPLACEMENT = "([^/]+)";

    // The template used to build the path matching regex
    private const string PATTERN_TEMPLATE = ";^%s/?$;";

    // The generated path matching regex, we defer generating the actual regex until the first time route() is called
    private ?string $pathPattern = null;

    public function __construct(private readonly string $pathTemplate, private readonly string $controllerClass, Verbs ...$verbs)
    {
        if (str_contains($this->pathTemplate, ";")) {
            throw new ValueError("Path template cannot contain delimiter character ;");
        }

        $this->verbs = $verbs;
    }

    public function route(string $path): ?Route
    {
        if (null === $this->pathPattern) {
            $this->pathPattern = sprintf(self::PATTERN_TEMPLATE, preg_replace(
                self::PLACEHOLDER_REGEX,
                self::PLACEHOLDER_REPLACEMENT,
                $this->pathTemplate
            ));
        }

        $keys = [];
        $values = [];

        if (!preg_match_all(self::PLACEHOLDER_REGEX, $this->pathTemplate, $keys)) {
            return null;
        }
        preg_match($this->pathPattern, $path, $values);

        // At this point, $keys[0] contains raw matches and $keys[1] contains the extracted key names.
        // $values contains the strings values that occupy the placeholders, and element 0 contains the entire path
        //
        // As we don't need keys[0] or the first element of $values, we discard them
        $keys = $keys[1];
        $values = array_slice($values, 1);

        if (count($keys) !== count($values)) {
            return null;
        }

        return new Route($this->controllerClass, array_combine($keys, $values));
    }
}
