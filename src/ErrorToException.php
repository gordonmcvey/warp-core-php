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

/**
 * Callable class to catch PHP errors and convert them to exceptions
 *
 * It can be registered as a PHP error handler
 */
readonly class ErrorToException
{
    /**
     * @throws ErrorException
     */
    public function __invoke(
        int     $errorNumber,
        string  $errorString,
        ?string $file = null,
        ?int    $line = null,
    ): bool {
        if (!($this->getErrorReporting() & $errorNumber)) {
            // This error code is not included in error_reporting, so let it fall through
            return false;
        }

        throw new ErrorException(message: $errorString, code: $errorNumber, filename: $file, line: $line);
    }

    /**
     * Protected in order to facilitate testing.  Do not override!
     *
     * @codeCoverageIgnore
     */
    protected function getErrorReporting(): int
    {
        return error_reporting();
    }
}
