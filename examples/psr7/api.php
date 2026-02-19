<?php

/** @noinspection ALL */

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

namespace gordonmcvey\WarpCore\examples\psr7;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\request\psr7\ServerRequestAdaptor;
use gordonmcvey\httpsupport\response\sender\ResponseSender;
use gordonmcvey\WarpCore\Bootstrap;
use gordonmcvey\WarpCore\controller\ControllerFactory;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\examples\controllers\Hello;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\strategy\SingleControllerStrategy;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

/**
 * Example for processing a PSR-7 compatible request
 */

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/vendor/autoload.php';

// Demo
(new FrontController(
    new CallStackFactory(),
    new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
    new ResponseSender()
))->addMiddleware(new RequestLogger())
    ->bootstrap(
        new Bootstrap(
            new Router(new RequestPathValidator(), new SingleControllerStrategy(Hello::class, Verbs::GET)),
            new ControllerFactory(),
        ),
        new ServerRequestAdaptor(
            new ServerRequest(
                Verbs::GET->value,
                "https://example.com/",
                [],
                Utils::streamFor("This is the request!"),
            )
        ),
    )
;
