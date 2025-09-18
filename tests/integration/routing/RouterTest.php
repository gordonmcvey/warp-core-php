<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\test\integration\routing;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\WarpCore\exception\routing\MethodNotAllowed;
use gordonmcvey\WarpCore\exception\routing\NoRouteToController;
use gordonmcvey\WarpCore\routing\PathNamespaceStrategy;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\StaticStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider("provideUrisForSingleStrategy")]
    public function itRoutesWithASingleStrategy(string $uri, string $route): void
    {
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())->method("uri")->willReturn($uri);
        $request->expects($this->any())->method("verb")->willReturn(Verbs::GET);

        $router = new Router(new RequestPathValidator(), new PathNamespaceStrategy(__NAMESPACE__, Verbs::GET));

        $this->assertSame($route, $router->route($request));
    }

    /**
     * @return iterable<string, array{
     *     uri: string,
     *     route: string
     * }>
     */
    public static function provideUrisForSingleStrategy(): iterable
    {
        yield "Typical routing without path" => [
            "uri"   => "https://www.example.com/",
            "route" => __NAMESPACE__ . "\\",
        ];

        yield "Typical routing with path" => [
            "uri"   => "https://www.example.com/foo/bar/baz/quux",
            "route" => __NAMESPACE__ . "\\Foo\\Bar\\Baz\\Quux",
        ];

        yield "Routing with hyphens" => [
            "uri"   => "https://www.example.com/foo-bar-baz-quux",
            "route" => __NAMESPACE__ . "\\FooBarBazQuux",
        ];

        yield "Routing with underscores" => [
            "uri"   => "https://www.example.com/foo_bar_baz_quux",
            "route" => __NAMESPACE__ . "\\FooBarBazQuux",
        ];

        yield "Routing with hyphens and underscores" => [
            "uri"   => "https://www.example.com/foo-bar_baz-quux",
            "route" => __NAMESPACE__ . "\\FooBarBazQuux",
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider("provideUrisForRoutePathTesting")]
    public function itGeneratesTheCorrectRouteForThePath(string $uri, string $route): void
    {
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())->method("uri")->willReturn($uri);
        $request->expects($this->any())->method("verb")->willReturn(Verbs::GET);

        $router = new Router(
            new RequestPathValidator(),
            new StaticStrategy(["/foo" => "Foo"], Verbs::GET),
            new StaticStrategy(["/foo/bar" => "Foo\\Bar"], Verbs::GET),
            new StaticStrategy(["/foo/bar/baz" => "Foo\\Bar\\Baz"], Verbs::GET),
            new StaticStrategy(["/foo/bar/baz/quux" => "Foo\\Bar\\Baz\\Quux"], Verbs::GET),
        );

        $this->assertSame($route, $router->route($request));
    }

    /**
     * @return iterable<string, array{
     *     uri: string,
     *     route: string
     * }>
     */
    public static function provideUrisForRoutePathTesting(): iterable
    {
        yield "Foo" => [
            "uri"   => "https://www.example.com/foo",
            "route" =>  "Foo",
        ];

        yield "Bar" => [
            "uri"   => "https://www.example.com/foo/bar",
            "route" =>  "Foo\\Bar",
        ];

        yield "Baz" => [
            "uri"   => "https://www.example.com/foo/bar/baz",
            "route" =>  "Foo\\Bar\\Baz",
        ];

        yield "Quux" => [
            "uri"   => "https://www.example.com/foo/bar/baz/quux",
            "route" =>  "Foo\\Bar\\Baz\\Quux",
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider("provideVerbsForVerbRouteTesting")]
    public function itGeneratesTheCorrectRouteForTheVerb(Verbs $verb, string $route): void
    {
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())->method("uri")->willReturn("http://www.example.com/");
        $request->expects($this->any())->method("verb")->willReturn($verb);

        $router = new Router(
            new RequestPathValidator(),
            new StaticStrategy(["/" => Verbs::GET->value], Verbs::GET),
            new StaticStrategy(["/" => Verbs::HEAD->value], Verbs::HEAD),
            new StaticStrategy(["/" => Verbs::POST->value], Verbs::POST),
            new StaticStrategy(["/" => Verbs::PUT->value], Verbs::PUT),
            new StaticStrategy(["/" => Verbs::DELETE->value], Verbs::DELETE),
            new StaticStrategy(["/" => Verbs::OPTIONS->value], Verbs::OPTIONS),
            new StaticStrategy(["/" => Verbs::TRACE->value], Verbs::TRACE),
            new StaticStrategy(["/" => Verbs::PATCH->value], Verbs::PATCH),
        );

        $this->assertSame($route, $router->route($request));
    }

    /**
     * @return iterable<string, array{
     *     verb: Verbs,
     *     route: string
     * }>
     */
    public static function provideVerbsForVerbRouteTesting(): iterable
    {
        yield Verbs::GET->value => [
            "verb"  => Verbs::GET,
            "route" => Verbs::GET->value,
        ];

        yield Verbs::POST->value => [
            "verb"  => Verbs::POST,
            "route" => Verbs::POST->value,
        ];

        yield Verbs::HEAD->value => [
            "verb"  => Verbs::HEAD,
            "route" => Verbs::HEAD->value,
        ];

        yield Verbs::PUT->value => [
            "verb"  => Verbs::PUT,
            "route" => Verbs::PUT->value,
        ];

        yield Verbs::DELETE->value => [
            "verb"  => Verbs::DELETE,
            "route" => Verbs::DELETE->value,
        ];

        yield Verbs::TRACE->value => [
            "verb"   => Verbs::TRACE,
            "route" =>  Verbs::TRACE->value,
        ];

        yield Verbs::OPTIONS->value => [
            "verb"   => Verbs::OPTIONS,
            "route" =>  Verbs::OPTIONS->value,
        ];

        yield Verbs::PATCH->value => [
            "verb"   => Verbs::PATCH,
            "route" =>  Verbs::PATCH->value,
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsNoRouteIfNoPathMatchesFound(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())->method("uri")->willReturn("http://www.example.com/");
        $request->expects($this->any())->method("verb")->willReturn(Verbs::GET);

        $router = new Router(
            new RequestPathValidator(),
            new StaticStrategy(["/foo/bar" => Verbs::GET->value], Verbs::GET),
        );

        $this->expectException(NoRouteToController::class);
        $this->expectExceptionCode(ClientErrorCodes::NOT_FOUND->value);

        $router->route($request);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itThrowsMethodNotAllowedIfNoVerbMatchesFound(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())->method("uri")->willReturn("http://www.example.com/");
        $request->expects($this->any())->method("verb")->willReturn(Verbs::GET);

        $router = new Router(
            new RequestPathValidator(),
            new StaticStrategy(["/" => Verbs::GET->value], Verbs::PATCH),
        );

        $this->expectException(MethodNotAllowed::class);
        $this->expectExceptionCode(ClientErrorCodes::METHOD_NOT_ALLOWED->value);

        $router->route($request);
    }
}
