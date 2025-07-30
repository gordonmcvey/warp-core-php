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

namespace gordonmcvey\WarpCore\test\unit\middleware;

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CallStackFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itMakesACallStack(): void
    {
        $controller = $this->createMock(RequestHandlerInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller
            ->expects($this->once())
            ->method("dispatch")
            ->with($request)
            ->willReturn($response)
        ;

        $factory = new CallStackFactory();
        $callStack = $factory->make($controller);

        $this->assertSame($response, $callStack->dispatch($request));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itMakesACallStackAndPopulatesItFromController(): void
    {
        /** @var RequestHandlerInterface&MiddlewareProviderInterface&MockObject $controller */
        $controller = $this->createMockForIntersectionOfInterfaces([
            RequestHandlerInterface::class,
            MiddlewareProviderInterface::class,
        ]);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);

        $middleware
            ->expects($this->once())
            ->method("handle")
            ->with($request, $controller)
            ->willReturnCallback(fn(RequestInterface $request): ?ResponseInterface => $controller->dispatch($request))
        ;

        $controller
            ->expects($this->once())
            ->method("getAllMiddleware")
            ->willReturn([$middleware])
        ;

        $controller
            ->expects($this->once())
            ->method("dispatch")
            ->with($request)
            ->willReturn($response)
        ;

        $factory = new CallStackFactory();
        $callStack = $factory->make($controller);

        $this->assertSame($response, $callStack->dispatch($request));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itMakesACallStackAndPopulatesItFromProvider(): void
    {
        /** @var RequestHandlerInterface&MiddlewareProviderInterface&MockObject $controller */
        $controller = $this->createMock(RequestHandlerInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $provider = $this->createMock(MiddlewareProviderInterface::class);

        $middleware
            ->expects($this->once())
            ->method("handle")
            ->with($request, $controller)
            ->willReturnCallback(fn(RequestInterface $request): ?ResponseInterface => $controller->dispatch($request))
        ;

        $provider->expects($this->once())
            ->method("getAllMiddleware")
            ->willReturn([$middleware])
        ;

        $controller
            ->expects($this->once())
            ->method("dispatch")
            ->with($request)
            ->willReturn($response)
        ;

        $factory = new CallStackFactory();
        $callStack = $factory->make($controller, $provider);

        $this->assertSame($response, $callStack->dispatch($request));
    }
}
