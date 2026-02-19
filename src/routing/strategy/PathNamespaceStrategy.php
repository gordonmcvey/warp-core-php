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

namespace gordonmcvey\WarpCore\routing\strategy;

use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\WarpCore\sdk\interface\routing\RoutingStrategyInterface;

/**
 * Path to namespace routing strategy
 *
 * This class works on the assumption that a path has a direct mapping to a controller in a corresponding namespace.
 * For example, the path /foo/bar/baz would map to a Baz controller in the \Foo\Bar namespace.
 *
 * You can also (and are strongly encouraged to), specify a prefix, so for example the /foo/bar/baz path could be made
 * to map to \Vendor\Application\Controllers\Foo\Bar\Baz by specifying \Vendor\Application\Controllers as the root
 * namespace for controller classes.
 *
 * The mapping is done according to CamelCasing rules, with the /, - and _ characters treated as word delimiters, so
 * /foo/bar/baz will map to \Foo\Bar\Baz
 *
 * This strategy can theoretically return a valid controller for any path so long as that path fits the criteria for
 * your application's namespace without having to specify an explicit mapping, hence why defining a root namespace is
 * so strongly recommended (whilst unlikely, you wouldn't want to make it possible to expose non-controller classes to
 * the user!).  It also has to do a bit of string processing, so for smaller applications, static routing may be
 * preferable.
 */
readonly class PathNamespaceStrategy implements RoutingStrategyInterface
{
    /**
     * @var array<Verbs>
     */
    private array $verbs;

    /**
     * @param string $controllerNamespace The namespace controllers will be located under, eg vendor\project\controllers
     */
    public function __construct(private string $controllerNamespace = "", Verbs ...$verbs)
    {
        $this->verbs = $verbs;
    }

    public function route(string $path): ?string
    {
        return sprintf(
            "%s%s",
            $this->controllerNamespace,
            str_replace(
                [" ", "\t"],
                ["\\", ""],
                ucwords(str_replace(
                    ["/", "-", "_"],
                    [" ", "\t", "\t"],
                    strtolower($path)
                ))
            ),
        );
    }

    public function forVerbs(): array
    {
        return $this->verbs;
    }
}
