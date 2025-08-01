<?php

/**
 * Copyright 2015 Docnet
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

use Exception;
use gordonmcvey\httpsupport\enum\statuscodes\ServerErrorCodes;
use gordonmcvey\httpsupport\enum\statuscodes\SuccessCodes;
use gordonmcvey\httpsupport\request\RequestInterface;
use gordonmcvey\httpsupport\response\Response;
use gordonmcvey\httpsupport\response\ResponseInterface;
use gordonmcvey\httpsupport\response\sender\ResponseSenderInterface;
use gordonmcvey\WarpCore\interface\controller\RequestHandlerInterface;
use gordonmcvey\WarpCore\interface\error\ErrorHandlerInterface;
use gordonmcvey\WarpCore\interface\middleware\MiddlewareProviderInterface;
use gordonmcvey\WarpCore\middleware\CallStackFactory;
use gordonmcvey\WarpCore\middleware\MiddlewareProviderTrait;
use Psr\Log\LoggerAwareInterface;
use Throwable;

/**
 * Front controller for our JSON APIs
 */
class FrontController implements MiddlewareProviderInterface, LoggerAwareInterface
{
    use MiddlewareProviderTrait;
    use HasLogger;

    /**
     * Hook up the shutdown function so we always send nice JSON error responses
     */
    public function __construct(
        private readonly CallStackFactory $callStackFactory,
        private readonly ErrorHandlerInterface $errorHandler,
        private readonly ResponseSenderInterface $responseSender,
    ) {
    }

    /**
     * Bootstrap and dispatch a controller from the given request
     */
    public function bootstrap(RequestHandlerInterface|callable $controllerSource, RequestInterface $request): void
    {
        try {
            $controller = $this->getController($controllerSource, $request);
            $response = $this->dispatch($controller, $request);
        } catch (Throwable $e) {
            $this->getLogger()->error("[Core] [{$e->getCode()}] Error: {$e->getMessage()}");
            $response = $this->errorHandler->handle($e);
        }
        $this->responseSender->send($response);
    }

    /**
     * @throws Exception
     */
    private function getController(
        RequestHandlerInterface|callable $controllerSource,
        RequestInterface $request,
    ): RequestHandlerInterface {
        $controller = is_callable($controllerSource) ? $controllerSource($request) : $controllerSource;
        if (!$controller instanceof RequestHandlerInterface) {
            // @todo Replace with a semantic exception
            throw new Exception('Unable to bootstrap', ServerErrorCodes::INTERNAL_SERVER_ERROR->value);
        }

        return $controller;
    }

    /**
     * Go, Johnny, Go!
     *
     * If the controller to be dispatched implements MiddlewareProviderInterface, then its middleware will be added to
     * the call stack on creation, then the global middleware will be added.  Otherwise, only the global§ middleware is
     * added to the call stack.
     */
    private function dispatch(RequestHandlerInterface $controller, RequestInterface $request): ResponseInterface
    {
        $callStack = $this->callStackFactory->make($controller, $this);
        return $callStack->dispatch($request) ?? new Response(SuccessCodes::NO_CONTENT, '');
    }
}
