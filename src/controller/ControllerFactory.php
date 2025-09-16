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

namespace gordonmcvey\WarpCore\controller;

use gordonmcvey\WarpCore\exception\controller\ControllerNotFound;
use gordonmcvey\WarpCore\exception\controller\NotAController;
use gordonmcvey\WarpCore\interface\controller\ControllerFactoryInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;

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
     * @throws ControllerNotFound
     * @throws NotAController
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
     * @throws ControllerNotFound
     */
    protected function checkControllerExists(string $path): string
    {
        if (!class_exists($path)) {
            throw new ControllerNotFound(
                sprintf("No controller found for URI path %s", $path),
            );
        }

        return $path;
    }

    /**
     * @throws NotAController
     */
    protected function checkIsController(object $controller, string $path): RequestHandlerInterface
    {
        if (!$controller instanceof RequestHandlerInterface) {
            throw new NotAController(
                sprintf("URI path %s does not correspond to a controller", $path),
            );
        }

        return $controller;
    }
}
