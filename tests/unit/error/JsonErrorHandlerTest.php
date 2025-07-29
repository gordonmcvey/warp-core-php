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

namespace gordonmcvey\JAPI\test\unit\error;

use ErrorException;
use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\JAPI\error\JsonErrorHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class JsonErrorHandlerTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesException(): void
    {
        $exception = new RuntimeException("I am an exception", ClientErrorCodes::BAD_REQUEST->value);
        $statusCodeFactory = $this->createMock(StatusCodeFactory::class);

        $statusCodeFactory
            ->expects($this->once())
            ->method("fromThrowable")
            ->with($exception)
            ->willReturn(ClientErrorCodes::BAD_REQUEST)
        ;

        $handler = new JsonErrorHandler(statusCodeFactory: $statusCodeFactory, exposeDetails: false);
        $response = $handler->handle($exception);

        $this->assertEquals("application/json", $response->header("Content-Type"));
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "code" => ClientErrorCodes::BAD_REQUEST->value,
                "msg"  => "Exception",
            ]),
            $response->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesErrorException(): void
    {
        $exception = new ErrorException("I am an exception", ClientErrorCodes::BAD_REQUEST->value);
        $statusCodeFactory = $this->createMock(StatusCodeFactory::class);

        $statusCodeFactory
            ->expects($this->once())
            ->method("fromThrowable")
            ->with($exception)
            ->willReturn(ClientErrorCodes::BAD_REQUEST)
        ;

        $handler = new JsonErrorHandler(statusCodeFactory: $statusCodeFactory, exposeDetails: false);
        $response = $handler->handle($exception);

        $this->assertEquals("application/json", $response->header("Content-Type"));
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "code" => ClientErrorCodes::BAD_REQUEST->value,
                "msg"  => "Internal Error",
            ]),
            $response->body(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesExceptionWithDetails(): void
    {
        $exception = new RuntimeException("I am an exception", ClientErrorCodes::BAD_REQUEST->value);
        $statusCodeFactory = $this->createMock(StatusCodeFactory::class);

        $statusCodeFactory
            ->expects($this->once())
            ->method("fromThrowable")
            ->with($exception)
            ->willReturn(ClientErrorCodes::BAD_REQUEST)
        ;

        $handler = new JsonErrorHandler(statusCodeFactory: $statusCodeFactory, exposeDetails: true);
        $response = $handler->handle($exception);
        $responseBody = json_decode($response->body());

        $this->assertEquals("application/json", $response->header("Content-Type"));
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertIsString($responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertGreaterThan(0, $responseBody->line);

        // These can vary between machines and with changes to the test file, so we'll remove them from the output
        unset($responseBody->file, $responseBody->line);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "code"   => ClientErrorCodes::BAD_REQUEST->value,
                "msg"    => "Exception",
                "detail" => "RuntimeException: I am an exception",
            ]),
            json_encode($responseBody),
        );
    }

    #[Test]
    public function itHandlesErrorExceptionWithDetails(): void
    {
        $exception = new ErrorException("I am an exception", ClientErrorCodes::BAD_REQUEST->value);
        $statusCodeFactory = $this->createMock(StatusCodeFactory::class);

        $statusCodeFactory
            ->expects($this->once())
            ->method("fromThrowable")
            ->with($exception)
            ->willReturn(ClientErrorCodes::BAD_REQUEST)
        ;

        $handler = new JsonErrorHandler(statusCodeFactory: $statusCodeFactory, exposeDetails: true);
        $response = $handler->handle($exception);
        $responseBody = json_decode($response->body());

        $this->assertEquals("application/json", $response->header("Content-Type"));
        $this->assertObjectHasProperty("file", $responseBody);
        $this->assertObjectHasProperty("line", $responseBody);
        $this->assertIsString($responseBody->file);
        $this->assertIsInt($responseBody->line);
        $this->assertGreaterThan(0, $responseBody->line);

        // These can vary between machines and with changes to the test file, so we'll remove them from the output
        unset($responseBody->file, $responseBody->line);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "code"   => ClientErrorCodes::BAD_REQUEST->value,
                "msg"    => "Internal Error",
                "detail" => "ErrorException: I am an exception",
            ]),
            json_encode($responseBody),
        );
    }
}
