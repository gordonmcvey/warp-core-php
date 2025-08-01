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

namespace gordonmcvey\WarpCore\interface\routing;

use gordonmcvey\httpsupport\request\RequestInterface;

/**
 * Interface for Router implementations
 *
 * The standard Router is flexible enough for most typical use cases, but if you have more specific needs, you can
 * implement this class to make a drop-in replacement router.
 */
interface RouterInterface
{
    public function route(RequestInterface $request): string;
}
