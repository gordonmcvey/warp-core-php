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
use gordonmcvey\WarpCore\middleware\Slot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class SlotTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itDispatchesARequest(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $middleware
            ->expects($this->once())
            ->method("handle")
            ->with($request, $nextHandler)
            ->willReturn($expectedResponse)
        ;

        $slot = new Slot($middleware, $nextHandler);
        $actualResponse = $slot->dispatch($request);

        $this->assertSame($expectedResponse, $actualResponse);
    }
}
