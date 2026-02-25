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

namespace gordonmcvey\WarpCore\test\unit;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\interface\response\ResponseSenderInterface;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\middleware\CallStack;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\sdk\dto\routing\Route;
use gordonmcvey\WarpCore\sdk\exception\AccessDenied;
use gordonmcvey\WarpCore\sdk\exception\Auth;
use gordonmcvey\WarpCore\sdk\exception\routing\MethodNotAllowed;
use gordonmcvey\WarpCore\sdk\interface\controller\ControllerFactoryInterface;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\sdk\interface\error\ErrorHandlerInterface;
use gordonmcvey\WarpCore\sdk\interface\routing\RouterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class FrontControllerTest extends TestCase
{
    private RouterInterface&MockObject $router;

    private ControllerFactoryInterface&MockObject $controllerFactory;

    private CallStackFactory&MockObject $callStackFactory;

    private ErrorHandlerInterface&MockObject $errorHandler;

    private ResponseSenderInterface&MockObject $sender;

    public function setUp(): void
    {
        parent::setUp();
        $this->router = $this->createMock(RouterInterface::class);
        $this->controllerFactory = $this->createMock(ControllerFactoryInterface::class);
        $this->callStackFactory = $this->createMock(CallStackFactory::class);
        $this->errorHandler = $this->createMock(ErrorHandlerInterface::class);
        $this->sender = $this->createMock(ResponseSenderInterface::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycle(): void
    {
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willReturn(new Route(RequestHandlerInterface::class))
        ;

        $this->controllerFactory
            ->expects($this->once())
            ->method('make')
            ->with(RequestHandlerInterface::class)
            ->willReturn($mockController)
        ;

        $this->callStackFactory
            ->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockCallStack
            ->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturnCallback(fn() => $mockController->dispatch($mockRequest))
        ;

        $this->errorHandler
            ->expects($this->never())
            ->method("handle")
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $frontController->bootstrap($mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesARoutingError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willThrowException(new MethodNotAllowed())
        ;

        $this->controllerFactory
            ->expects($this->never())
            ->method('make')
        ;

        $this->callStackFactory
            ->expects($this->never())
            ->method("make")
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(MethodNotAllowed::class))
            ->willReturn($mockResponse)
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAuthError(): void
    {
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willReturn(new Route(RequestHandlerInterface::class))
        ;

        $this->callStackFactory
            ->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $this->controllerFactory
            ->expects($this->once())
            ->method('make')
            ->with(RequestHandlerInterface::class)
            ->willReturn($mockController)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(Auth::class))
            ->willReturn($mockResponse)
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockCallStack
            ->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturnCallback(fn() => $mockController->dispatch($mockRequest))
        ;

        $mockController
            ->expects($this->once())
            ->method('dispatch')
            ->with($mockRequest)
            ->willThrowException(new Auth())
        ;

        $frontController->bootstrap($mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAccessDeniedError(): void
    {
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willReturn(new Route(RequestHandlerInterface::class))
        ;

        $this->callStackFactory
            ->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $this->controllerFactory
            ->expects($this->once())
            ->method('make')
            ->with(RequestHandlerInterface::class)
            ->willReturn($mockController)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(AccessDenied::class))
            ->willReturn($mockResponse)
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockCallStack
            ->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturnCallback(fn() => $mockController->dispatch($mockRequest))
        ;

        $mockController
            ->expects($this->once())
            ->method('dispatch')
            ->with($mockRequest)
            ->willThrowException(new AccessDenied())
        ;

        $frontController->bootstrap($mockRequest);

    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralError(): void
    {
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willReturn(new Route(RequestHandlerInterface::class))
        ;

        $this->callStackFactory
            ->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $this->controllerFactory
            ->expects($this->once())
            ->method('make')
            ->with(RequestHandlerInterface::class)
            ->willReturn($mockController)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(AccessDenied::class))
            ->willReturn($mockResponse)
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockCallStack
            ->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturnCallback(fn() => $mockController->dispatch($mockRequest))
        ;

        $mockController
            ->expects($this->once())
            ->method('dispatch')
            ->with($mockRequest)
            ->willThrowException(new AccessDenied())
        ;

        $frontController->bootstrap($mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralErrorWithValidErrorCode(): void
    {
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router
            ->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->willReturn(new Route(RequestHandlerInterface::class))
        ;

        $this->callStackFactory
            ->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $this->controllerFactory
            ->expects($this->once())
            ->method('make')
            ->with(RequestHandlerInterface::class)
            ->willReturn($mockController)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->callback(
                fn($e): bool => $e instanceof RuntimeException
                    && ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS->value === $e->getCode()
            ))
            ->willReturn($mockResponse)
        ;

        $this->sender
            ->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $mockCallStack
            ->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturnCallback(fn() => $mockController->dispatch($mockRequest))
        ;

        $mockController
            ->expects($this->once())
            ->method('dispatch')
            ->with($mockRequest)
            ->willThrowException(new RuntimeException(code: ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS->value))
        ;

        $frontController->bootstrap($mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itLogsErrorsIfGivenALogger(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $frontController = new FrontController(
            $this->router,
            $this->controllerFactory,
            $this->callStackFactory,
            $this->errorHandler,
            $this->sender,
        );

        $this->router->method('route')->willThrowException(new RuntimeException());

        $mockLogger->expects($this->once())
            ->method("error")
            ->with("[Core] [500] Error: Unable to bootstrap")
        ;

        $frontController->setLogger($mockLogger);
        $frontController->bootstrap($mockRequest);
    }
}
