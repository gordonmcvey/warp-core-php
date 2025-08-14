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
use gordonmcvey\WarpCore\Exceptions\AccessDenied;
use gordonmcvey\WarpCore\Exceptions\Auth;
use gordonmcvey\WarpCore\Exceptions\Routing;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\error\ErrorHandlerInterface;
use gordonmcvey\WarpCore\middleware\CallStack;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class FrontControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycle(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockController->dispatch($mockRequest));

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithControllerFactoryFunction(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockController->dispatch($mockRequest));

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap(fn() => $mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesATypicalDispatchCycleWithControllerFactoryObject(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockController->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockResponse)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willReturn($mockController->dispatch($mockRequest));

        $mockErrorHandler->expects($this->never())
            ->method("handle")
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

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
            $mockRequest,
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesABootstrappingError(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(\Exception::class))
            ->willReturn($mockResponse)
        ;

        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap(fn() => "Hello", $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesARoutingError(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(Routing::class))
            ->willReturn($mockResponse)
        ;

        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap(fn() => throw new Routing(), $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAuthError(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new Auth())
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(Auth::class))
            ->willReturn($mockResponse)
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesAccessDeniedError(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new AccessDenied())
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(AccessDenied::class))
            ->willReturn($mockResponse);

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralError(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new RuntimeException(code: 12345))
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(RuntimeException::class))
            ->willReturn($mockResponse)
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesGeneralErrorWithValidErrorCode(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockCallStack = $this->createMock(CallStack::class);
        $mockController = $this->createMock(RequestHandlerInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockCallStackFactory->expects($this->once())
            ->method("make")
            ->with($mockController, $frontController)
            ->willReturn($mockCallStack)
        ;

        $mockCallStack->expects($this->once())
            ->method("dispatch")
            ->with($mockRequest)
            ->willThrowException(new RuntimeException(code: ClientErrorCodes::UNAVAILABLE_FOR_LEGAL_REASONS->value))
        ;

        $mockErrorHandler->expects($this->once())
            ->method("handle")
            ->with($this->isInstanceOf(RuntimeException::class))
            ->willReturn($mockResponse)
        ;

        $mockSender->expects($this->once())
            ->method("send")
            ->with($mockResponse)
            ->willReturnSelf()
        ;

        $frontController->bootstrap($mockController, $mockRequest);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itLogsErrorsIfGivenALogger(): void
    {
        $mockCallStackFactory = $this->createMock(CallStackFactory::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockErrorHandler = $this->createMock(ErrorHandlerInterface::class);
        $mockSender = $this->createMock(ResponseSenderInterface::class);

        $frontController = new FrontController($mockCallStackFactory, $mockErrorHandler, $mockSender);

        $mockLogger->expects($this->once())
            ->method("error")
            ->with("[Core] [500] Error: Unable to bootstrap")
        ;

        $frontController->setLogger($mockLogger);
        $frontController->bootstrap(fn() => "Hello", $mockRequest);
    }
}
