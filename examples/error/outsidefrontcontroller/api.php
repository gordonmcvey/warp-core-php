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

namespace gordonmcvey\WarpCore\examples\error\outsidefrontcontroller;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\enum\Verbs;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\request\Request;
use gordonmcvey\httpsupport\response\sender\ResponseSender;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\ErrorToException;
use gordonmcvey\WarpCore\examples\controllers\Hello;
use gordonmcvey\WarpCore\FrontController;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use gordonmcvey\WarpCore\routing\Router;
use gordonmcvey\WarpCore\routing\SingleControllerStrategy;
use gordonmcvey\WarpCore\ShutdownHandler;

/**
 * Example of error handling when a non-throwable error occurs.  This sets up an error handler that converts old-style
 * PHP errors to ErrorExceptions.  It also adds a shutdown handler to produce the desired error output if an error
 * doesn't occur inside the front controller's try/catch dispatch block.
 */

define('BASE_PATH', dirname(__DIR__, 3));

require_once BASE_PATH . '/vendor/autoload.php';

// For live you don't want any error output.  You might want to use different values here for local development/testing
error_reporting(0);
ini_set('display_errors', false);

// This is not necessary in an actual application
$_SERVER["REQUEST_METHOD"] = "GET";

// Demo
set_error_handler(new errorToException(), E_ERROR ^ E_USER_ERROR ^ E_COMPILE_ERROR);
$responseSender = new ResponseSender();
$errorHandler = new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true);
register_shutdown_function(new ShutdownHandler($responseSender, $errorHandler));

/*
 * Simulated error, in theory, any kind of error can be handled from the point the error handler and shutdown function
 * have been registered until script execution ends. However, unbuffered script output may result in an error being
 * appended to standard script output should an error occur after data starts streaming.
 */
trigger_error("whoops", E_USER_ERROR);

(new FrontController(new CallStackFactory(), $errorHandler, $responseSender))
    ->bootstrap(
        function (RequestInterface $request): RequestHandlerInterface {
            $router = new Router(new RequestPathValidator(), new SingleControllerStrategy(Hello::class, Verbs::GET));

            /** @var RequestHandlerInterface $controller */
            $controller = new ($router->route($request));

            return $controller;
        },
        Request::fromSuperGlobals()
    )
;
