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

use ErrorException;
use gordonmcvey\WarpCore\ErrorToException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorToExceptionTest extends TestCase
{
    #[Test]
    public function itGeneratesExceptionForSupportedError(): void
    {
        $handler = $this
            ->getMockBuilder(ErrorToException::class)
            ->onlyMethods(["getErrorReporting"])
            ->getMock()
        ;

        $handler->expects($this->once())->method("getErrorReporting")->willReturn(E_ALL);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("I'm a handled error");
        $this->expectExceptionCode(E_USER_ERROR);

        $handler(E_USER_ERROR, "I'm a handled error", __FILE__, 123);
    }

    /**
     * @throws ErrorException
     */
    #[Test]
    public function itSkipsForUnsupportedError(): void
    {
        $handler = $this
            ->getMockBuilder(ErrorToException::class)
            ->onlyMethods(["getErrorReporting"])
            ->getMock()
        ;

        $handler->expects($this->once())->method("getErrorReporting")->willReturn(0);

        $this->assertFalse($handler(E_USER_ERROR, "I'm a handled error", __FILE__, 123));
    }
}
