<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\test\integration\middleware;

use gordonmcvey\httpsupport\enum\statuscodes\SuccessCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\request\payload\ArrayPayloadHandler;
use gordonmcvey\httpsupport\request\Request;
use gordonmcvey\httpsupport\response\Response;
use gordonmcvey\WarpCore\middleware\CallStack;
use gordonmcvey\WarpCore\middleware\MiddlewareProviderTrait;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\sdk\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\sdk\interface\middleware\MiddlewareProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CallStackTest extends TestCase
{
    #[Test]
    public function itSupportsMiddlewareChaining(): void
    {
        $controller = new class implements RequestHandlerInterface
        {
            public function dispatch(RequestInterface $request): ResponseInterface
            {
                return new Response(SuccessCodes::OK, "<p>I'm the controller</p>\n");
            }
        };

        $outer = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);
                return new Response(SuccessCodes::OK, $response->body() . "<p>I'm the outer middleware</p>\n");
            }
        };

        $inner = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);
                return new Response(SuccessCodes::OK, $response->body() . "<p>I'm the inner middleware</p>\n");
            }
        };

        $request = new Request([], [], [], [], new ArrayPayloadHandler([]));

        $callstack = new CallStack($controller);
        $callstack->add($inner)->add($outer);
        $response = $callstack->dispatch($request);

        // The stack should be called in the order outer -> inner -> controller
        // and should return in the order controller -> inner -> outer
        $this->assertSame("<p>I'm the controller</p>\n"
            . "<p>I'm the inner middleware</p>\n"
            . "<p>I'm the outer middleware</p>\n", $response->body());
    }

    #[Test]
    public function itSupportsMiddlewareShortCircuiting(): void
    {
        $controller = new class implements RequestHandlerInterface
        {
            public function dispatch(RequestInterface $request): ResponseInterface
            {
                return new Response(SuccessCodes::OK, "<p>I'm the controller</p>\n");
            }
        };

        $outer = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response(SuccessCodes::OK, "<p>I'm the outer middleware</p>\n");
            }
        };

        $inner = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);
                return new Response(SuccessCodes::OK, $response->body() . "<p>I'm the inner middleware</p>\n");
            }
        };

        $request = new Request([], [], [], [], new ArrayPayloadHandler([]));

        $callstack = new CallStack($controller);
        $callstack->add($inner)->add($outer);
        $response = $callstack->dispatch($request);

        // Only the outermost middleware should be executed
        $this->assertSame("<p>I'm the outer middleware</p>\n", $response->body());
    }

    #[Test]
    public function itSupportsMiddlewareConditionalShortCircuiting(): void
    {
        $controller = new class implements RequestHandlerInterface
        {
            public function dispatch(RequestInterface $request): ResponseInterface
            {
                return new Response(SuccessCodes::OK, "<p>I'm the controller</p>\n");
            }
        };

        $outer = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);

                if ("trigger" === $request->header("X-Outer-Condition")) {
                    $response = new Response(
                        SuccessCodes::OK,
                        "{$response->body()}<p>The outer middleware was triggered</p>\n",
                    );
                }

                return $response;
            }
        };

        $inner = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);

                if ("trigger" === $request->header("X-Inner-Condition")) {
                    $response = new Response(
                        SuccessCodes::OK,
                        "{$response->body()}<p>The inner middleware was triggered</p>\n",
                    );
                }

                return $response;
            }
        };

        $callstack = new CallStack($controller);
        $callstack->add($inner)->add($outer);

        $request = new Request([], [], [], [], new ArrayPayloadHandler([]));
        $response = $callstack->dispatch($request);
        $this->assertSame("<p>I'm the controller</p>\n", $response->body());

        $request = new Request([], [], [], [
            "HTTP_X_OUTER_CONDITION" => "trigger",
        ], new ArrayPayloadHandler([]));
        $response = $callstack->dispatch($request);
        $this->assertSame("<p>I'm the controller</p>\n"
            . "<p>The outer middleware was triggered</p>\n", $response->body());

        $request = new Request([], [], [], [
            "HTTP_X_INNER_CONDITION" => "trigger",
        ], new ArrayPayloadHandler([]));

        $response = $callstack->dispatch($request);

        $this->assertSame("<p>I'm the controller</p>\n"
            . "<p>The inner middleware was triggered</p>\n", $response->body());

        $request = new Request([], [], [], [
            "HTTP_X_OUTER_CONDITION" => "trigger",
            "HTTP_X_INNER_CONDITION" => "trigger",
        ], new ArrayPayloadHandler([]));

        $response = $callstack->dispatch($request);
        $this->assertSame("<p>I'm the controller</p>\n"
            . "<p>The inner middleware was triggered</p>\n"
            . "<p>The outer middleware was triggered</p>\n", $response->body());
    }

    #[Test]
    public function itSupportsMiddlewareFromAProvider(): void
    {
        $controller = new class implements RequestHandlerInterface
        {
            public function dispatch(RequestInterface $request): ResponseInterface
            {
                return new Response(SuccessCodes::OK, "<p>I'm the controller</p>\n");
            }
        };

        $outer = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);
                return new Response(SuccessCodes::OK, $response->body() . "<p>I'm the outer middleware</p>\n");
            }
        };

        $inner = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->dispatch($request);
                return new Response(SuccessCodes::OK, $response->body() . "<p>I'm the inner middleware</p>\n");
            }
        };

        $provider = new class implements MiddlewareProviderInterface
        {
            use MiddlewareProviderTrait;
        };

        $request = new Request([], [], [], [], new ArrayPayloadHandler([]));

        $callstack = new CallStack($controller);
        $callstack->fromProvider($provider->addMiddleware($inner)->addMiddleware($outer));
        $response = $callstack->dispatch($request);

        // The stack should be called in the order outer -> inner -> controller
        // and should return in the order controller -> inner -> outer
        $this->assertSame("<p>I'm the controller</p>\n"
            . "<p>I'm the inner middleware</p>\n"
            . "<p>I'm the outer middleware</p>\n", $response->body());
    }
}
