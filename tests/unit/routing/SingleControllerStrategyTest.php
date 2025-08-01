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

namespace gordonmcvey\WarpCore\test\unit\routing;

use gordonmcvey\WarpCore\routing\SingleControllerStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SingleControllerStrategyTest extends TestCase
{
    #[Test]
    public function itReturnsValidRoutes(): void
    {
        $strategy = new SingleControllerStrategy("RoutedController");

        $this->assertSame("RoutedController", $strategy->route("/foo"));
        $this->assertSame("RoutedController", $strategy->route("/foo/bar"));
        $this->assertSame("RoutedController", $strategy->route("/foo/bar/baz"));
    }
}
