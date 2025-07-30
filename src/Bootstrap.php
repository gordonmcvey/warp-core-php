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

use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\WarpCore\interface\controller\ControllerFactoryInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\routing\RouterInterface;

/**
 * Simple bootstrap implementation
 *
 * Developers are free to use any method of bootstrapping they like, so long as they return a class that implements
 * RequestHandlerInterface, but in most cases this basic bootstrap class should suffice.
 */
readonly class Bootstrap
{
    public function __construct(
        private RouterInterface $router,
        private ControllerFactoryInterface $controllerFactory,
    ) {
    }

    public function __invoke(RequestInterface $request): RequestHandlerInterface
    {
        return $this->controllerFactory->make($this->router->route($request));
    }
}
