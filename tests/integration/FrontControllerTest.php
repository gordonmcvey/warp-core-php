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

namespace gordonmcvey\WarpCore\test\integration;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\enum\statuscodes\ServerErrorCodes;
use gordonmcvey\httpsupport\enum\statuscodes\SuccessCodes;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\WarpCore\Bootstrap;
use gordonmcvey\WarpCore\controller\ControllerFactory;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\exception\AccessDenied;
use gordonmcvey\WarpCore\exception\Auth;
use gordonmcvey\WarpCore\exception\controller\BootstrapFailure;
use gordonmcvey\WarpCore\exception\controller\ControllerNotFound;
use gordonmcvey\WarpCore\exception\routing\MethodNotAllowed;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\routing\PathNamespaceStrategy;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\SingleControllerStrategy;
use gordonmcvey\WarpCore\test\Controllers\ExampleController;
use gordonmcvey\WarpCore\test\Controllers\ExceptionController;
use gordonmcvey\WarpCore\test\Spies\SenderSpy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FrontControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycle(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(new ExampleController(), $mockRequest);

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithFactoryFunction(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(fn() => new ExampleController(), $mockRequest);

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithFactoryObject(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new readonly class {
                public function __invoke(): RequestHandlerInterface
                {
                    return new ExampleController();
                }
            },
            $mockRequest,
        );

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithFrameworkBootstrap(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $mockRequest->expects($this->any())->method("verb")->with()->willReturn(Verbs::GET);
        $mockRequest->expects($this->any())->method("uri")->willReturn("https://example.com/");

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new Bootstrap(
                new Router(
                    new RequestPathValidator(),
                    new SingleControllerStrategy(ExampleController::class, Verbs::GET)
                ),
                new ControllerFactory(),
            ),
            $mockRequest,
        );

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithGlobalMiddleware(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();
        $middleware = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $request->setHeader("foo", "bar");
                $response = $handler->dispatch($request);
                $response->setHeader("baz", "quux");

                return $response;
            }
        };

        $mockRequest->expects($this->once())
            ->method("setHeader")
            ->with("foo", "bar")
            ->willReturnSelf()
        ;

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->addMiddleware($middleware)->bootstrap(new ExampleController(), $mockRequest);

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertSame("quux", $senderSpy->lastResponse->header("baz"));
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithControllerMiddleware(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();
        $middleware = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $request->setHeader("foo", "bar");
                $response = $handler->dispatch($request);
                $response->setHeader("baz", "quux");

                return $response;
            }
        };

        $mockRequest->expects($this->once())
            ->method("setHeader")
            ->with("foo", "bar")
            ->willReturnSelf()
        ;

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap((new ExampleController())->addMiddleware($middleware), $mockRequest);

        $this->assertSame(SuccessCodes::OK, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());
        $this->assertSame("utf-8", $senderSpy->lastResponse->contentEncoding());
        $this->assertSame("quux", $senderSpy->lastResponse->header("baz"));
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(["message" => "Hello, World!"]),
            $senderSpy->lastResponse->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesABootStrappingError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(fn() => "Hello", $mockRequest);
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ServerErrorCodes::INTERNAL_SERVER_ERROR, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(500, $responseBody->code);
        $this->assertStringStartsWith(BootstrapFailure::class, $responseBody->detail);
        $this->assertStringEndsWith("FrontController.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesARoutingPathError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $mockRequest->expects($this->any())->method("verb")->with()->willReturn(Verbs::GET);
        $mockRequest->expects($this->any())->method("uri")->willReturn("https://example.com/");

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new Bootstrap(
                new Router(
                    new RequestPathValidator(),
                    new PathNamespaceStrategy(__NAMESPACE__, Verbs::GET),
                ),
                new ControllerFactory(),
            ),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ClientErrorCodes::NOT_FOUND, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(404, $responseBody->code);
        $this->assertStringStartsWith(ControllerNotFound::class, $responseBody->detail);
        $this->assertStringEndsWith("ControllerFactory.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesARoutingVerbError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $mockRequest->expects($this->any())->method("verb")->with()->willReturn(Verbs::PUT);
        $mockRequest->expects($this->any())->method("uri")->willReturn("https://example.com/");

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new Bootstrap(
                new Router(
                    new RequestPathValidator(),
                    new SingleControllerStrategy(ExampleController::class, Verbs::GET)
                ),
                new ControllerFactory(),
            ),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ClientErrorCodes::METHOD_NOT_ALLOWED, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(405, $responseBody->code);
        $this->assertStringStartsWith(MethodNotAllowed::class, $responseBody->detail);
        $this->assertStringEndsWith("Router.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAuthError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new ExceptionController(Auth::class, ClientErrorCodes::UNAUTHORIZED->value),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ClientErrorCodes::UNAUTHORIZED, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(401, $responseBody->code);
        $this->assertStringStartsWith(Auth::class, $responseBody->detail);
        $this->assertStringEndsWith("ExceptionController.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAccessDeniedError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new ExceptionController(AccessDenied::class, ClientErrorCodes::FORBIDDEN->value),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ClientErrorCodes::FORBIDDEN, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(403, $responseBody->code);
        $this->assertStringStartsWith(AccessDenied::class, $responseBody->detail);
        $this->assertStringEndsWith("ExceptionController.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new ExceptionController(RuntimeException::class, 12345),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ServerErrorCodes::INTERNAL_SERVER_ERROR, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(500, $responseBody->code);
        $this->assertStringStartsWith(RuntimeException::class, $responseBody->detail);
        $this->assertStringEndsWith("ExceptionController.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralErrorWithValidErrorCode(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $senderSpy = new SenderSpy();

        $frontController = new FrontController(
            new CallStackFactory(),
            new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
            $senderSpy,
        );

        $frontController->bootstrap(
            new ExceptionController(RuntimeException::class, ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS->value),
            $mockRequest,
        );
        $responseBody = json_decode($senderSpy->lastResponse->body());

        $this->assertSame(ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS, $senderSpy->lastResponse->responseCode());
        $this->assertSame("application/json", $senderSpy->lastResponse->contentType());

        $this->assertObjectHasProperty("code", $responseBody);
        $this->assertObjectHasProperty("detail", $responseBody);
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertObjectHasProperty("msg", $responseBody);

        $this->assertSame(451, $responseBody->code);
        $this->assertStringStartsWith(RuntimeException::class, $responseBody->detail);
        $this->assertStringEndsWith("ExceptionController.php", $responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertSame("Exception", $responseBody->msg);
    }
}
