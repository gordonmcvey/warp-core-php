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

namespace gordonmcvey\WarpCore\examples\japierrorcatching;

use gordonmcvey\httpsupport\enum\factory\StatusCodeFactory;
use gordonmcvey\httpsupport\request\Request;
use gordonmcvey\httpsupport\response\sender\ResponseSender;
use gordonmcvey\WarpCore\error\JsonErrorHandler;
use gordonmcvey\WarpCore\JAPI;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use stdClass;

/**
 * Example of error handling when a problem occurs inside JAPI's dispatch cycle.  Note that standard handling can only
 * deal with \Throwable errors.  Things outside this (like warnings, deprecation notices, etc) require additional logic
 * to handle and are dealt with in the japierroruncaught example.
 */

// Includes or Auto-loader
define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/vendor/autoload.php';

// Demo
(new JAPI(
    new CallStackFactory(),
    new JsonErrorHandler(new StatusCodeFactory(), exposeDetails: true),
    new ResponseSender(),
))->bootstrap(
    function () {
        return new stdClass();
    },
    Request::fromSuperGlobals()
);
