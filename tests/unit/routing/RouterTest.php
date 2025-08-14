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

namespace gordonmcvey\WarpCore\test\unit\routing;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\WarpCore\Exceptions\Routing;
use gordonmcvey\WarpCore\interface\routing\RoutingStrategyInterface;
use gordonmcvey\WarpCore\routing\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /**
     * @throws Exception
     * @throws Routing
     */
    #[Test]
    #[DataProvider("provideValidPaths")]
    public function itRoutesForValidPaths(string $path, string $controller): void
    {
        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->once())->method("uri")->willReturn($path);
        $strategy
            ->expects($this->once())
            ->method("route")
            ->with($path)
            ->willReturn($controller)
        ;

        $router = new Router($strategy);

        $this->assertSame($controller, $router->route($request));
    }

    /**
     * @return iterable<string, array{
     *     path: string,
     *     controller: string,
     * }>
     */
    public static function provideValidPaths(): iterable
    {
        yield "Typical routing" => [
            "path"       => "/foo/bar/baz/quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with hyphens" => [
            "path"       => "/foo-bar-baz-quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with underscores" => [
            "path"       => "/foo_bar_baz_quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with hyphens and underscores" => [
            "path"       => "/foo-bar_baz-quux",
            "controller" => "RoutedController",
        ];

        yield "Root path" => [
            "path"       => "/",
            "controller" => "RoutedController",
        ];
    }

    /**
     * @throws Exception
     * @throws Routing
     */
    #[Test]
    public function itStopsRoutingWhenItFindsAResult(): void
    {
        $strategy1 = $this->createMock(RoutingStrategyInterface::class);
        $strategy2 = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->once())->method("uri")->willReturn("/foo/bar");
        $strategy1
            ->expects($this->once())
            ->method("route")
            ->with("/foo/bar")
            ->willReturn("RoutedController")
        ;

        $strategy2->expects($this->never())->method("route");

        $router = new Router($strategy1, $strategy2);

        $this->assertSame("RoutedController", $router->route($request));
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider("provideInvalidPaths")]
    public function itThrowsBadRequestForInvalidPaths(string $path, int $code): void
    {
        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->atLeastOnce())->method("uri")->willReturn($path);
        $strategy->expects($this->never())->method("route");

        $router = new Router($strategy);

        $this->expectException(Routing::class);
        $this->expectExceptionCode($code);

        $router->route($request);
    }

    /**
     * @return iterable<string, array{
     *     path: string,
     *     code: int,
     * }>
     */
    public static function provideInvalidPaths(): iterable
    {
        yield "Invalid characters" => [
            "path" => "/foo/bar=baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "Double-slash" => [
            "path" => "/foo/bar//baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "repeating hyphen" => [
            "path" => "/foo/bar--baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "Repeating underscore" => [
            "path" => "/foo/bar__baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "Hyphen underscore sequence" => [
            "path" => "/foo/bar-_baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "Underscore hyphen sequence" => [
            "path" => "/foo/bar_-baz/quux",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];

        yield "Empty path" => [
            "path" => "",
            "code" => ClientErrorCodes::BAD_REQUEST->value,
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsNotFoundForPathThatDoesntRoute(): void
    {
        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->atLeastOnce())->method("uri")->willReturn("/foo/bar/baz");
        $strategy->expects($this->once())
            ->method("route")
            ->with("/foo/bar/baz")
            ->willReturn(null)
        ;

        $router = new Router($strategy);

        $this->expectException(Routing::class);
        $this->expectExceptionCode(ClientErrorCodes::NOT_FOUND->value);

        $router->route($request);
    }
}
