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

namespace gordonmcvey\WarpCore\routing;

use gordonmcvey\WarpCore\exception\routing\InvalidPath;

class RequestPathValidator
{
    /**
     * Regex that URI paths are validated against
     *
     * @link https://regex101.com/r/IsPSk2/1
     */
    private const string SAFE_PATH = "/^(?:(?:\/[\w-]+)+|\/)$/";

    /**
     * Additional regex to detect potentially suspicious character sequences
     *
     * @link https://regex101.com/r/mXCVyB/1
     */
    private const string ILLEGAL_CHARACTER_SEQUENCE = "/[_-]{2,}/";

    /**
     * @throws InvalidPath
     */
    public function getPath(string $url): string
    {
        $path = $this->extractPath($url);
        $this->validatePath($path);

        return $path;
    }

    /**
     * Extract the path portion of the given URI
     *
     * @throws InvalidPath
     */
    private function extractPath(string $uri): string
    {
        $parsed = parse_url($uri);
        if (!$parsed) {
            throw new InvalidPath(
                sprintf("Unable to parse URI path '%s'", $uri),
            );
        }

        return (string) $parsed["path"];
    }

    /**
     * Validate that the path is safe
     *
     * As the path is user-supplied, it can't be trusted, so we'll check it for anything that looks nefarious and bail
     * out if anything looks like it may be problematic
     *
     * @throws InvalidPath
     */
    private function validatePath(string $path): void
    {
        if ("/" === $path) {
            return;
        }

        if (
            !preg_match(self::SAFE_PATH, $path)
            || preg_match(self::ILLEGAL_CHARACTER_SEQUENCE, $path)
        ) {
            throw new InvalidPath(
                sprintf(
                    "Invalid characters or sequences in URI path %s",
                    $path,
                ),
            );
        }
    }
}
