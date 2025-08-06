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

namespace gordonmcvey\WarpCore;

use ErrorException;
use gordonmcvey\httpsupport\enum\statuscodes\ServerErrorCodes;
use gordonmcvey\httpsupport\interface\response\ResponseSenderInterface;
use gordonmcvey\WarpCore\interface\error\ErrorHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Callable shutdown handler
 *
 * This class handles any clean-up tasks that need to be done after a request has been served.  Currently, its only
 * function is dealing with uncaught errors and exceptions, though it may do other tasks as well in the future.
 *
 * It can be registered as a PHP shutdown function
 */
readonly class ShutdownHandler
{
    private const int REPORTED_ERROR_TYPES = E_ERROR ^ E_USER_ERROR ^ E_COMPILE_ERROR;

    public function __construct(
        private ResponseSenderInterface $responseSender,
        private ErrorHandlerInterface $errorHandler,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(): void
    {
        $error = $this->getLastError();
        if (($error["type"] ?? 0) & self::REPORTED_ERROR_TYPES) {
            $this->logger?->debug(__METHOD__ . ": Detected an error");
            $this->outputError($error);
        }
    }

    /**
     * Protected in order to facilitate testing.  Do not override!
     *
     * @return array{
     *     message: string,
     *     type: int,
     *     file: string,
     *     line: int,
     * }|null
     * @codeCoverageIgnore
     */
    protected function getLastError(): ?array
    {
        return error_get_last();
    }

    /**
     * Protected in order to facilitate testing.  Do not override!
     *
     * @codeCoverageIgnore
     */
    protected function flushBuffers(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * @param array{
     *     message: string,
     *     type: int,
     *     file: string,
     *     line: int,
     * } $error
     */
    private function outputError(array $error): void
    {
        $response = $this->errorHandler->handle(new ErrorException(
            message: $error["message"],
            code: ServerErrorCodes::INTERNAL_SERVER_ERROR->value,
            severity: $error["type"],
            filename: $error["file"],
            line: $error["line"],
        ));

        $this->flushBuffers();

        if (!headers_sent()) {
            $this->responseSender->sendHeaders($response);
        }

        $this->responseSender->sendBody($response);
    }
}
