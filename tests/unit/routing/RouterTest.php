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
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\WarpCore\exception\Routing;
use gordonmcvey\WarpCore\exception\routing\InvalidPath;
use gordonmcvey\WarpCore\interface\routing\RoutingStrategyInterface;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
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
    public function itRoutesForValidPaths(string $uri, string $path, string $controller): void
    {
        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $pathValidator = $this->createMock(RequestPathValidator::class);

        $request->expects($this->once())->method("uri")->willReturn($uri);
        $request->expects($this->once())->method("verb")->willReturn(Verbs::GET);

        $pathValidator->expects($this->once())->method("getPath")->with($uri)->willReturn($path);

        $strategy
            ->expects($this->once())
            ->method("route")
            ->with($path)
            ->willReturn($controller)
        ;
        $strategy->expects($this->once())->method("forVerbs")->willReturn([Verbs::GET]);

        $router = new Router($pathValidator, $strategy);

        $this->assertSame($controller, $router->route($request));
    }

    /**
     * @return iterable<string, array{
     *     uri: string,
     *     path: string,
     *     controller: string,
     * }>
     */
    public static function provideValidPaths(): iterable
    {
        yield "Typical routing" => [
            "uri"        => "https://www.example.com/foo/bar/baz/quux",
            "path"       => "/foo/bar/baz/quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with hyphens" => [
            "uri"        => "https://www.example.com/foo-bar-baz-quux",
            "path"       => "/foo-bar-baz-quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with underscores" => [
            "uri"        => "https://www.example.com/foo_bar_baz_quux",
            "path"       => "/foo_bar_baz_quux",
            "controller" => "RoutedController",
        ];

        yield "Routing with hyphens and underscores" => [
            "uri"        => "https://www.example.com/foo-bar_baz-quux",
            "path"       => "/foo-bar_baz-quux",
            "controller" => "RoutedController",
        ];

        yield "Root path" => [
            "uri"        => "https://www.example.com/",
            "path"       => "/",
            "controller" => "RoutedController",
        ];
    }

    /**
     * @throws Exception
     * @throws Routing
     */
    #[Test]
    public function itRoutesToTheCorrectControllerForTheVerb(): void
    {
        $requests = [];
        $strategies = [];

        foreach (Verbs::cases() as $verb) {
            $newRequest = $this->createMock(RequestInterface::class);
            $newStrategy = $this->createMock(RoutingStrategyInterface::class);

            $newRequest->expects($this->once())->method("uri")->willReturn("/");
            $newRequest->expects($this->once())->method("verb")->willReturn($verb);

            $newStrategy->expects($this->any())->method("route")->with("/")->willReturn($verb->value);
            $newStrategy->expects($this->any())->method("forVerbs")->willReturn([$verb]);

            $requests[$verb->value] = $newRequest;
            $strategies[$verb->value] = $newStrategy;
        }

        $pathValidator = $this->createMock(RequestPathValidator::class);
        $pathValidator->expects($this->any())->method("getPath")->with("/")->willReturn("/");

        $router = new Router($pathValidator, ...$strategies);

        foreach ($requests as $expectedVerb => $request) {
            $this->assertSame($expectedVerb, $router->route($request));
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsBadRequestForInvalidPaths(): void
    {
        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $pathValidator = $this->createMock(RequestPathValidator::class);

        $request->expects($this->atLeastOnce())->method("uri")->willReturn("https://www.example.com/foo/bar");
        $strategy->expects($this->never())->method("route");
        $pathValidator
            ->expects($this->any())
            ->method("getPath")
            ->with("https://www.example.com/foo/bar")
            ->willThrowException(new InvalidPath())
        ;

        $router = new Router($pathValidator, $strategy);

        $this->expectException(InvalidPath::class);
        $this->expectExceptionCode(ClientErrorCodes::BAD_REQUEST->value);

        $router->route($request);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsNotFoundForPathThatDoesntRoute(): void
    {
        $url = "https://www.example.com/foo/bar/baz";
        $path = "/foo/bar/baz";

        $strategy = $this->createMock(RoutingStrategyInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $pathValidator = $this->createMock(RequestPathValidator::class);

        $request->expects($this->atLeastOnce())->method("uri")->willReturn($url);
        $pathValidator->expects($this->once())->method("getPath")->with($url)->willReturn($path);
        $strategy->expects($this->once())->method("route")->with($path)->willReturn(null);

        $router = new Router($pathValidator, $strategy);

        $this->expectException(Routing::class);
        $this->expectExceptionCode(ClientErrorCodes::NOT_FOUND->value);

        $router->route($request);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsMethodNotAllowedForMethodThatDoesntMatch(): void
    {
        $uri = "https://www.example.com/foo/bar/baz";
        $path = "/foo/bar/baz";

        $getStrategy = $this->createMock(RoutingStrategyInterface::class);
        $postStrategy = $this->createMock(RoutingStrategyInterface::class);
        $putRequest = $this->createMock(RequestInterface::class);
        $pathValidator = $this->createMock(RequestPathValidator::class);

        $putRequest->expects($this->any())->method("uri")->willReturn($uri);
        $putRequest->expects($this->any())->method("verb")->willReturn(Verbs::PUT);

        $pathValidator->expects($this->once())->method("getPath")->with($uri)->willReturn($path);

        $getStrategy->expects($this->any())->method("route")->with($path)->willReturn("GetController");
        $getStrategy->expects($this->any())->method("forVerbs")->willReturn([Verbs::GET]);

        $postStrategy->expects($this->any())->method("route")->with($path)->willReturn("PostController");
        $postStrategy->expects($this->any())->method("forVerbs")->willReturn([Verbs::POST]);

        $router = new Router($pathValidator, $getStrategy, $postStrategy);

        $this->expectException(Routing::class);
        $this->expectExceptionCode(ClientErrorCodes::METHOD_NOT_ALLOWED->value);

        $router->route($putRequest);
    }
}
