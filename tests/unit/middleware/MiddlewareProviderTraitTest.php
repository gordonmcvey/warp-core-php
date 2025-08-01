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

use gordonmcvey\WarpCore\interface\middleware\MiddlewareInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;
use gordonmcvey\WarpCore\middleware\MiddlewareProviderTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class MiddlewareProviderTraitTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itAddsSingleMiddleware(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $provider = $this->setupMiddlewareProvider()->addMiddleware($middleware);
        $this->assertContains($middleware, $provider->getAllMiddleware());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itAddsMultipleMiddlewares(): void
    {
        $middlewares = [
            $this->createMock(MiddlewareInterface::class),
            $this->createMock(MiddlewareInterface::class),
            $this->createMock(MiddlewareInterface::class),
        ];

        $provider = $this->setupMiddlewareProvider()->addMultipleMiddleware(...$middlewares);
        $this->assertSame($middlewares, $provider->getAllMiddleware());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itResetsMiddleware(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $provider = $this->setupMiddlewareProvider()->addMiddleware($middleware);
        $this->assertContains($middleware, $provider->getAllMiddleware());

        $provider->resetMiddleware();
        $this->assertNotContains($middleware, $provider->getAllMiddleware());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itReplacesMiddleware(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $provider = $this->setupMiddlewareProvider()->addMiddleware($middleware1);
        $this->assertContains($middleware1, $provider->getAllMiddleware());
        $this->assertNotContains($middleware2, $provider->getAllMiddleware());

        $provider->replaceMiddlewareWith($middleware2);
        $this->assertNotContains($middleware1, $provider->getAllMiddleware());
        $this->assertContains($middleware2, $provider->getAllMiddleware());
    }

    private function setupMiddlewareProvider(): MiddlewareProviderInterface
    {
        return new class () implements MiddlewareProviderInterface {
            use MiddlewareProviderTrait;
        };
    }
}
