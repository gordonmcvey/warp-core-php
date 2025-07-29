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

namespace gordonmcvey\JAPI\test\unit\routing;

use gordonmcvey\JAPI\routing\PathNamespaceStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathNamespaceStrategyTest extends TestCase
{
    #[Test]
    #[DataProvider("providePaths")]
    public function itReturnsValidRoutes(string $namespace, string $path, string $expectation): void
    {
        $strategy = new PathNamespaceStrategy($namespace);

        $this->assertSame($expectation, $strategy->route($path));
    }

    /**
     * @return iterable<string, array{
     *     namespace: string,
     *     path: string,
     *     expectation: string,
     * }>
     */
    public static function providePaths(): iterable
    {
        yield "Simple path without a namespace" => [
            "namespace"   => "",
            "path"        => "/foo/bar/baz/quux",
            "expectation" => "\\Foo\\Bar\\Baz\\Quux",
        ];

        yield "Simple path plus namespace" => [
            "namespace"   => "\\namespace\\prefix",
            "path"        => "/foo/bar/baz/quux",
            "expectation" => "\\namespace\\prefix\\Foo\\Bar\\Baz\\Quux",
        ];

        yield "Path containing hyphens without a namespace" => [
            "namespace"   => "",
            "path"        => "/foo-bar-baz-quux",
            "expectation" => "\\FooBarBazQuux",
        ];

        yield "Path containing hyphens plus namespace" => [
            "namespace"   => "\\namespace\\prefix",
            "path"        => "/foo-bar-baz-quux",
            "expectation" => "\\namespace\\prefix\\FooBarBazQuux",
        ];

        yield "Path containing underscores without a namespace" => [
            "namespace"   => "",
            "path"        => "/foo_bar_baz_quux",
            "expectation" => "\\FooBarBazQuux",
        ];

        yield "Path containing underscores plus namespace" => [
            "namespace"   => "\\namespace\\prefix",
            "path"        => "/foo_bar_baz_quux",
            "expectation" => "\\namespace\\prefix\\FooBarBazQuux",
        ];

        yield "Path containing hyphens and underscores without a namespace" => [
            "namespace"   => "",
            "path"        => "/foo_bar-baz_quux",
            "expectation" => "\\FooBarBazQuux",
        ];

        yield "Path containing hyphens and underscores plus namespace" => [
            "namespace"   => "\\namespace\\prefix",
            "path"        => "/foo_bar-baz_quux",
            "expectation" => "\\namespace\\prefix\\FooBarBazQuux",
        ];

        yield "Unusual casing still resolve to a properly cased class name" => [
            "namespace"   => "",
            "path"        => "/foo/BAR/bAZ/QuuX",
            "expectation" => "\\Foo\\Bar\\Baz\\Quux",
        ];
    }
}
