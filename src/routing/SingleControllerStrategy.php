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

namespace gordonmcvey\JAPI\routing;

use gordonmcvey\JAPI\interface\routing\RoutingStrategyInterface;

/**
 * Single Controller Strategy
 *
 * This basically routes any request to the same controller regardless of its value.  This can be handy for very simple
 * applications, or as a "last resort" strategy when all the usual routing approaches have failed to find a controller.
 */
readonly class SingleControllerStrategy implements RoutingStrategyInterface
{
    public function __construct(private string $controllerClass)
    {
    }

    public function route(string $path): ?string
    {
        return $this->controllerClass;
    }
}
