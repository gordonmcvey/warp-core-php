<?php

declare(strict_types=1);

namespace gordonmcvey\WarpCore\test\unit;

use ErrorException;
use gordonmcvey\httpsupport\enum\statuscodes\ServerErrorCodes;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\interface\response\ResponseSenderInterface;
use gordonmcvey\WarpCore\sdk\interface\error\ErrorHandlerInterface;
use gordonmcvey\WarpCore\ShutdownHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ShutdownHandlerTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesNormalShutdown(): void
    {
        $responseSender = $this->createMock(ResponseSenderInterface::class);
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);

        $handler = $this
            ->getMockBuilder(ShutdownHandler::class)
            ->setConstructorArgs([$responseSender, $errorHandler])
            ->onlyMethods(["getLastError", "flushBuffers"])
            ->getMock()
        ;

        $handler->expects($this->once())->method("getLastError")->willReturn(null);
        $errorHandler->expects($this->never())->method("handle");

        $handler();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itHandlesSupportedErrors(): void
    {
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseSender = $this->createMock(ResponseSenderInterface::class);
        $handler = $this
            ->getMockBuilder(ShutdownHandler::class)
            ->setConstructorArgs([$responseSender, $errorHandler])
            ->onlyMethods(["getLastError", "flushBuffers"])
            ->getMock()
        ;

        $handler
            ->expects($this->once())
            ->method("getLastError")
            ->willReturn([
                "message" => "I'm a handled error",
                "type"    => E_USER_ERROR,
                "file"    => __FILE__,
                "line"    => 123,
            ])
        ;

        $errorHandler
            ->expects($this->once())
            ->method("handle")
            ->with($this->callback(
                fn($e): bool => $e instanceof ErrorException
                    && "I'm a handled error" === $e->getMessage()
                    && ServerErrorCodes::INTERNAL_SERVER_ERROR->value === $e->getCode()
                    && __FILE__ === $e->getFile()
                    && 123 === $e->getLine()
                    && E_USER_ERROR === $e->getSeverity()
            ))
            ->willReturn($response)
        ;

        $responseSender->expects($this->once())->method("sendHeaders")->willReturnSelf();
        $responseSender->expects($this->once())->method("sendBody")->willReturnself();

        $handler();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function itSkipsUnsupportedErrors(): void
    {
        $responseSender = $this->createMock(ResponseSenderInterface::class);
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);

        $handler = $this
            ->getMockBuilder(ShutdownHandler::class)
            ->setConstructorArgs([$responseSender, $errorHandler])
            ->onlyMethods(["getLastError", "flushBuffers"])
            ->getMock()
        ;

        $handler
            ->expects($this->once())
            ->method("getLastError")
            ->willReturn([
                "message" => "I'm an unhandled error",
                "type"    => E_USER_DEPRECATED,
                "file"    => __FILE__,
                "line"    => 123,
            ])
        ;

        $errorHandler
            ->expects($this->never())
            ->method("handle")
        ;

        $handler();
    }
}
