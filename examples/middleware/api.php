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

namespace gordonmcvey\WarpCore\examples\middleware;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\request\Request;
use gordonmcvey\httpsupport\response\sender\ResponseSender;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\SingleControllerStrategy;

/**
 * Example using custom bootstrap with middleware
 */

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/vendor/autoload.php';

// This is not necessary in an actual application
$_SERVER["REQUEST_METHOD"] = "GET";

// Demo
(new FrontController(
    new CallStackFactory(),
    new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
    new ResponseSender(),
))
    ->addMiddleware(new AddParameter("globalMessage1", "Hello"))
    ->addMiddleware(new AddParameter("globalMessage2", "World"))
    ->addMiddleware(new AddParameter("globalMessage3", "Hello, World!"))
    ->addMiddleware(new RandomDelay())
    ->addMiddleware(new Profiler())
    ->bootstrap(
        function (RequestInterface $request): RequestHandlerInterface {
            $router = new Router(new RequestPathValidator(), new SingleControllerStrategy(Hello::class, Verbs::GET));
            /** @var RequestHandlerInterface&MiddlewareProviderInterface $controller */
            $controller = new ($router->route($request))();

            $controller
                ->addMiddleware(new AddParameter("controllerMessage1", "Hello"))
                ->addMiddleware(new AddParameter("controllerMessage2", "World"))
                ->addMiddleware(new AddParameter("controllerMessage3", "Hello, World!"))
                ->addMiddleware(new AddParameter("addedBy", __FUNCTION__))
                ->addMiddleware(new RandomDelay())
            ;
            return $controller;
        },
        Request::fromSuperGlobals(),
    )
;
