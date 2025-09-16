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

namespace gordonmcvey\WarpCore\test\unit\controller;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\WarpCore\controller\ControllerFactory;
use gordonmcvey\WarpCore\exception\controller\ControllerNotFound;
use gordonmcvey\WarpCore\exception\controller\NotAController;
use gordonmcvey\WarpCore\exception\Routing;
use gordonmcvey\WarpCore\test\Controllers\FactoryInstantiated;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class ControllerFactoryTest extends TestCase
{
    /**
     * @throws Routing
     */
    #[Test]
    public function itMakesAController(): void
    {
        $factory = new ControllerFactory();

        $controller = $factory->make(FactoryInstantiated::class);

        $this->assertInstanceOf(FactoryInstantiated::class, $controller);
        $this->assertNull($controller->arg1);
        $this->assertNull($controller->arg2);
        $this->assertNull($controller->arg3);
    }

    /**
     * @throws Routing
     */
    #[Test]
    public function itMakesAControllerWithArguments(): void
    {
        $factory = new ControllerFactory();

        $controller = $factory
            ->withArguments("String argument", 42, true)
            ->make(FactoryInstantiated::class)
        ;

        $this->assertInstanceOf(FactoryInstantiated::class, $controller);
        $this->assertSame("String argument", $controller->arg1);
        $this->assertSame(42, $controller->arg2);
        $this->assertTrue($controller->arg3);
    }

    #[Test]
    public function itDoesntMakeANonControllerForNonControllerClasses(): void
    {
        $factory = new ControllerFactory();

        $this->expectException(NotAController::class);
        $this->expectExceptionCode(ClientErrorCodes::BAD_REQUEST->value);
        $factory->make(stdClass::class);
    }

    #[Test]
    public function itDoesntMakeANonControllerForNonExistantClass(): void
    {
        $factory = new ControllerFactory();

        $this->expectException(ControllerNotFound::class);
        $this->expectExceptionCode(ClientErrorCodes::NOT_FOUND->value);
        $factory->make(__NAMESPACE__ . "NonExistentClass");
    }
}
