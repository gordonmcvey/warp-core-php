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

namespace gordonmcvey\WarpCore\test\unit\routing\strategy;

use gordonmcvey\WarpCore\routing\strategy\PlaceholdersStrategy;
use gordonmcvey\WarpCore\sdk\dto\routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

class PlaceholdersStrategyTest extends TestCase
{
    /**
     * @param array<array-key, scalar> $params
     */
    #[Test]
    #[DataProvider('provideMatchingPaths')]
    public function itReturnsValidRoutes(string $routeFormat, string $path, array $params): void
    {
        $strategy = new PlaceholdersStrategy($routeFormat, "RoutedController");
        $route = $strategy->route($path);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals("RoutedController", $route->controllerClass);
        $this->assertEquals($params, $route->pathParams);
    }

    /**
     * @return iterable<string, array{
     *     routeFormat: string,
     *     path: string,
     *     params: array<array-key, scalar>
     * }>
     */
    public static function provideMatchingPaths(): iterable
    {
        yield "One placeholder at the end" => [
            "routeFormat" => "/foo/bar/{id}",
            "path"        => "/foo/bar/123",
            "params"      => [
                "id" => "123",
            ],
        ];

        yield "One placeholder at the end with trailing slash" => [
            "routeFormat" => "/foo/bar/{id}",
            "path"        => "/foo/bar/123/",
            "params"      => [
                "id" => "123",
            ],
        ];

        yield "One placeholder in the middle" => [
            "routeFormat" => "/foo/bar/{id}/baz",
            "path"        => "/foo/bar/123/baz",
            "params"      => [
                "id" => "123",
            ],
        ];

        yield "Consecutive placeholders" => [
            "routeFormat" => "/foo/bar/{foo}/{bar}",
            "path"        => "/foo/bar/123/abc",
            "params"      => [
                "foo" => "123",
                "bar" => "abc",
            ],
        ];

        yield "Non-consecutive placeholders" => [
            "routeFormat" => "/foo/bar/{foo}/baz/{baz}",
            "path"        => "/foo/bar/123/baz/abc",
            "params"      => [
                "foo" => "123",
                "baz" => "abc",
            ],
        ];

        // @todo Implement typed placeholders
//        yield "typed placeholder" => [
//            "routeFormat" => "/foo/bar/{foo:string}/baz/{baz:int}",
//            "path"        => "/foo/bar/123/baz/456",
//            "params"      => [
//                "foo" => "123",
//                "baz" => 456",
//            ],
//        ];
    }

    #[Test]
    #[DataProvider('provideNonMatchingPaths')]
    public function itReturnsNullForInvalidRoutes(string $routeFormat, string $path): void
    {
        $strategy = new PlaceholdersStrategy($routeFormat, "RoutedController");
        $this->assertNull($strategy->route($path));
    }

    /**
     * @return iterable<string, array{
     *     routeFormat: string,
     *     path: string,
     * }>
     */
    public static function provideNonMatchingPaths(): iterable
    {
        yield "Too long" => [
            "routeFormat" => "/foo/bar/{id}",
            "path"        => "/foo/bar/123/baz",
        ];

        yield "Too short" => [
            "routeFormat" => "/foo/bar/{id}/baz",
            "path"        => "/foo/bar/123",
        ];

        yield "Wrong position" => [
            "routeFormat" => "/foo/bar/{id}/baz",
            "path"        => "/foo/bar/baz/123",
        ];
    }

    #[Test]
    public function itThrowsForInvalidRouteFormats(): void
    {
        $this->expectException(ValueError::class);
        new PlaceholdersStrategy("/foo/bar;baz", "RoutedController");
    }
}
