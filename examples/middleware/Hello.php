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

declare(strict_types=1);

namespace gordonmcvey\WarpCore\examples\middleware;

use gordonmcvey\httpsupport\enum\statuscodes\SuccessCodes;
use gordonmcvey\httpsupport\interface\request\RequestInterface;
use gordonmcvey\httpsupport\interface\response\ResponseInterface;
use gordonmcvey\httpsupport\response\Response;
use gordonmcvey\WarpCore\middleware\MiddlewareProviderTrait;
use gordonmcvey\WarpCore\sdk\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\sdk\interface\middleware\MiddlewareProviderInterface;
use stdClass;

/**
 * Example controller class
 */
class Hello implements MiddlewareProviderInterface, RequestHandlerInterface
{
    use MiddlewareProviderTrait;

    /**
     * Hello, World!
     */
    public function dispatch(RequestInterface $request): ?ResponseInterface
    {
        error_log(message: sprintf(
            "%s: handling request %s",
            __METHOD__,
            $request->header("X-Middleware-Profile-Request-Id"),
        ));

        return new Response(
            SuccessCodes::OK,
            (string) json_encode(new stdClass()),
        );
    }
}
