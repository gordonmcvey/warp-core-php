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

namespace gordonmcvey\WarpCore\test\unit\routing;

use gordonmcvey\httpsupport\enum\statuscodes\ClientErrorCodes;
use gordonmcvey\WarpCore\exception\routing\InvalidPath;
use gordonmcvey\WarpCore\routing\RequestPathValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestPathValidatorTest extends TestCase
{
    /**
     * @throws InvalidPath
     */
    #[Test]
    #[DataProvider("provideValidUris")]
    public function itReturnsPathForValidInputs(string $uri, string $path): void
    {
        $router = new RequestPathValidator();
        $this->assertSame($path, $router->getPath($uri));
    }

    /**
     * @return iterable<string, array{
     *     uri: string,
     *     path: string,
     * }>
     */
    public static function provideValidUris(): iterable
    {
        yield "Typical URI" => [
            "uri"  => "https://www.example.com/",
            "path" => "/",
        ];

        yield "Typical URI with path" => [
            "uri"  => "https://www.example.com/foo/bar/baz/quux",
            "path" => "/foo/bar/baz/quux",
        ];

        yield "Path includes hyphens" => [
            "uri"  => "https://www.example.com/foo-bar-baz-quux",
            "path" => "/foo-bar-baz-quux",
        ];

        yield "Path includes underscores" => [
            "uri"  => "https://www.example.com/foo_bar_baz_quux",
            "path" => "/foo_bar_baz_quux",
        ];

        yield "Path includes hyphens and underscores" => [
            "uri"  => "https://www.example.com/foo-bar_baz-quux",
            "path" => "/foo-bar_baz-quux",
        ];

        yield "Path only" => [
            "uri"  => "/foo/bar/baz/quux",
            "path" => "/foo/bar/baz/quux",
        ];
    }

    #[Test]
    #[DataProvider("provideInvalidUris")]
    public function itThrowsForInvalidInputs(string $uri): void
    {
        $router = new RequestPathValidator();

        $this->expectException(InvalidPath::class);
        $this->expectExceptionCode(ClientErrorCodes::BAD_REQUEST->value);

        $router->getPath($uri);
    }

    /**
     * @return iterable<string, array{
     *     uri: string,
     * }>
     */
    public static function provideInvalidUris(): iterable
    {
        yield "Unparsable URI" => [
            "uri" => "http:///example.com/",
        ];

        yield "Invalid characters in GET param" => [
            "uri" => "https://www.example.com/foo/bar=baz/quux",
        ];

        yield "Repeating slash" => [
            "uri" => "https://www.example.com/foo/bar//baz/quux",
        ];

        yield "repeating hyphen" => [
            "uri" => "https://www.example.com/foo/bar--baz/quux",
        ];

        yield "Repeating underscore" => [
            "uri" => "https://www.example.com/foo/bar__baz/quux",
        ];

        yield "Hyphen underscore sequence" => [
            "uri" => "https://www.example.com/foo/bar-_baz/quux",
        ];

        yield "Underscore hyphen sequence" => [
            "uri" => "https://www.example.com/foo/bar_-baz/quux",
        ];

        yield "Empty path" => [
            "uri" => "",
        ];
    }
}
