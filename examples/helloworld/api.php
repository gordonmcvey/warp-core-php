<?php

/**
 * Copyright © 2015 Docnet, 2025 Gordon McVey
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

namespace gordonmcvey\WarpCore\examples\helloworld;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\request\Request;
use gordonmcvey\httpsupport\response\sender\ResponseSender;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\examples\controllers\Hello;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\SingleControllerStrategy;

/**
 * Example using custom bootstrap function
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
))->bootstrap(
    function (RequestInterface $request): RequestHandlerInterface {
        $router = new Router(new RequestPathValidator(), new SingleControllerStrategy(Hello::class, Verbs::GET));

        /** @var RequestHandlerInterface $controller */
        $controller = new ($router->route($request));

        return $controller;
    },
    Request::fromSuperGlobals()
);
