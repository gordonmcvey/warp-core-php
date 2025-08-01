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

namespace gordonmcvey\WarpCore\error;

use ErrorException;
use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\response\Response;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\WarpCore\interface\error\ErrorHandlerInterface;
use Throwable;

/**
 * JSON error handler
 *
 * This class generates a suitable JSON payload in response to errors passed to it
 */
readonly class JsonErrorHandler implements ErrorHandlerInterface
{
    private const string CONTENT_TYPE = "application/json";

    /**
     * @param int $jsonFlags Bitmask affecting the JSON output.  Takes the same flags as the json_encode() method
     * @param bool $exposeDetails If true then additional debugging information is included in the payload
     */
    public function __construct(
        private StatusCodeFactory $statusCodeFactory,
        private int $jsonFlags = 0,
        private bool $exposeDetails = false,
    ) {
    }

    public function handle(Throwable $e): ResponseInterface
    {
        $code = $this->statusCodeFactory->fromThrowable($e);

        $payload = [
            "code" => $code->value,
            "msg"  => ($e instanceof ErrorException ? "Internal Error" : "Exception")
        ];

        if ($this->exposeDetails) {
            $payload["detail"] = sprintf("%s: %s", get_class($e), $e->getMessage());
            $payload["file"] = $e->getFile();
            $payload["line"] = $e->getLine();
        }

        return new Response(
            responseCode: $code,
            body: (string) json_encode($payload, $this->jsonFlags),
            contentType: self::CONTENT_TYPE,
        );
    }
}
