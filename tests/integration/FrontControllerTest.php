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

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\interface\response\ResponseSenderInterface;
use gordonmcvey\WarpCore\exception\AccessDenied;
use gordonmcvey\WarpCore\exception\Auth;
use gordonmcvey\WarpCore\exception\controller\BootstrapFailure;
use gordonmcvey\WarpCore\exception\routing\MethodNotAllowed;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\error\ErrorHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
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
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithFactoryFunction(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap(fn() => $mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithFactoryObject(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap(
            new readonly class ($mockController) {
                public function __construct(private RequestHandlerInterface $controller)
                {
                }
                public function __invoke(): RequestHandlerInterface
                {
                    return $this->controller;
                }
            },
            $mockRequest
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithGlobalMiddleware(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockRequest->expects($this->once())
            ->method("setHeader")
            ->with("foo", "bar")
            ->willReturnSelf()
        ;

        $mockResponse->expects($this->once())
            ->method("setHeader")
            ->with("baz", "quux")
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

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

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->addMiddleware($middleware)->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithControllerMiddleware(): void
    {
        /** @var RequestHandlerInterface&MiddlewareProviderInterface&MockObject $mockController */
        $mockController = $this->createMockForIntersectionOfInterfaces([
            RequestHandlerInterface::class,
            MiddlewareProviderInterface::class,
        ]);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockRequest->expects($this->once())
            ->method("setHeader")
            ->with("foo", "bar")
            ->willReturnSelf()
        ;

        $mockResponse->expects($this->once())
            ->method("setHeader")
            ->with("baz", "quux")
            ->willReturnSelf()
        ;

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

        $mockController->expects($this->once())
            ->method("getAllMiddleware")
            ->willReturn([$middleware])
        ;

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesABootStrappingError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(BootstrapFailure::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap(fn() => "Hello", $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesARoutingError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(MethodNotAllowed::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap(fn() => throw new MethodNotAllowed(), $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAuthError(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new Auth())
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(Auth::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAccessDeniedError(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new AccessDenied())
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(AccessDenied::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralError(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new RuntimeException(code: 12345))
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(RuntimeException::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralErrorWithValidErrorCode(): void
    {
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new RuntimeException(code: ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS->value))
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(RuntimeException::class))
            ->willReturn($mockResponse)
        ;

        $frontController = new FrontController(new CallStackFactory(), $mockErrorHandler, $mockSender);
        $frontController->bootstrap($mockController, $mockRequest);
    }
}
