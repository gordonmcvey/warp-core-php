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

namespace gordonmcvey\JAPI\controller;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\JAPI\Exceptions\Routing;
use gordonmcvey\JAPI\interface\controller\ControllerFactoryInterface;
use gordonmcvey\JAPI\interface\controller\RequestHandlerInterface;

/**
 * Simple Controller factory
 */
class ControllerFactory implements ControllerFactoryInterface
{
    /**
     * Arguments that will be passed to the controller's constructor
     *
     * @var array<array-key, mixed> $arguments
     */
    private array $arguments = [];

    /**
     * @throws Routing
     */
    public function make(string $path): RequestHandlerInterface
    {
        $checkedPath = $this->checkControllerExists($path);
        $controller = new $checkedPath(...$this->arguments);
        return $this->checkIsController($controller, $path);
    }

    public function withArguments(...$arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @throws Routing
     */
    protected function checkControllerExists(string $path): string
    {
        if (!class_exists($path)) {
            throw new Routing(
                sprintf("No controller found for URI path %s", $path),
                ClientErrorCodes::NOT_FOUND->value,
            );
        }

        return $path;
    }

    /**
     * @throws Routing
     */
    protected function checkIsController(object $controller, string $path): RequestHandlerInterface
    {
        if (!$controller instanceof RequestHandlerInterface) {
            throw new Routing(
                sprintf("URI path %s does not correspond to a controller", $path),
                ClientErrorCodes::BAD_REQUEST->value,
            );
        }

        return $controller;
    }
}
