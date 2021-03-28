<?php
/*
 * TestHelpers.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Tests\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait TestHelpers
 */
trait TestHelpers
{
    /**
     * @param string $route
     * @param array  $content
     */
    protected function assertPOST(string $route, array $content): void
    {
        Log::debug('Now in assertPOST()');
        $submission = $content['submission'];
        $expected   = $content['expected'];
        $ignore     = $content['ignore'];

        // submit body
        $response     = $this->post($route, $submission, ['Accept' => 'application/json']);
        $responseBody = $response->content();
        $responseJson = json_decode($responseBody, true);
        $status       = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Submission:\n%s\nResponse: %s", json_encode($submission), $responseBody));
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        // get return and compare each field
        $responseAttributes = $responseJson['data']['attributes'];
        $this->comparePOSTArray($submission, $responseAttributes, $expected, $ignore);

        // ignore fields too!
    }

    /**
     * @param array $submission
     * @param array $response
     * @param array $expected
     * @param array $ignore
     */
    private function comparePOSTArray(array $submission, array $response, array $expected, array $ignore): void
    {
        Log::debug('Now in comparePOSTArray()');
        Log::debug(sprintf('Submission : %s', json_encode($submission)));
        Log::debug(sprintf('Response   : %s', json_encode($response)));
        Log::debug(sprintf('Expected   : %s', json_encode($expected)));
        Log::debug(sprintf('Ignore     :  %s', json_encode($ignore)));
        foreach ($response as $key => $value) {
            Log::debug(sprintf('Now working on (sub)response key ["%s"]', $key));
            if (is_array($value) && array_key_exists($key, $expected) && is_array($expected[$key])) {
                Log::debug(sprintf('(Sub)response key ["%s"] is an array!', $key));
                $this->comparePOSTArray($submission, $value, $expected[$key], $ignore[$key] ?? []);
                continue;
            }
            if (isset($expected[$key])) {
                if (in_array($key, $ignore, true)) {
                    continue;
                }
                if (!in_array($key, $ignore, true)) {
                    $message = sprintf(
                        "Field '%s' with value %s is expected to be %s.\nSubmitted:\n%s\nIgnored: %s\nReturned\n%s",
                        $key,
                        var_export($value, true),
                        var_export($expected[$key], true),
                        json_encode($submission),
                        json_encode($ignore),
                        json_encode($response)
                    );

                    $this->assertEquals($value, $expected[$key], $message);
                }
            }
        }

    }
    /**
     * @param string $route
     * @param array  $content
     */
    protected function assertPUT(string $route, array $content): void
    {
        $submission = $content['submission'];
        $ignore     = $content['ignore'];
        $expected   = $content['expected'];
        Log::debug('Now in assertPUT()');

        // get the original first:
        $original           = $this->get($route, ['Accept' => 'application/json']);
        $originalBody       = $original->content();
        $originalJson       = json_decode($originalBody, true);
        $originalAttributes = $originalJson['data']['attributes'];
        $status             = $original->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Response: %s", json_encode($originalBody)));

        $response     = $this->put($route, $submission, ['Accept' => 'application/json']);
        $responseBody = $response->content();
        $responseJson = json_decode($responseBody, true);
        $status       = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Submission:\n%s\nResponse: %s", json_encode($submission), $responseBody));
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        // get return and compare each field
        $responseAttributes = $responseJson['data']['attributes'];
        $this->comparePUTArray($route, $submission, $responseAttributes, $expected, $ignore, $originalAttributes);
    }

    /**
     * @param string $url
     * @param array  $submission
     * @param array  $response
     * @param array  $expected
     * @param array  $ignore
     * @param array  $original
     */
    private function comparePUTArray(string $url, array $submission, array $response, array $expected, array $ignore, array $original): void
    {
        Log::debug('Now in comparePUTArray()');
        Log::debug(sprintf('Submission : %s', json_encode($submission)));
        Log::debug(sprintf('Response   : %s', json_encode($response)));
        Log::debug(sprintf('Expected   : %s', json_encode($expected)));
        Log::debug(sprintf('Ignore     :  %s', json_encode($ignore)));
        Log::debug(sprintf('Original   :  %s', json_encode($original)));
        $extraIgnore = ['created_at', 'updated_at', 'id'];
        foreach ($response as $key => $value) {
            if (is_array($value) && array_key_exists($key, $submission) && is_array($submission[$key])) {
                if (in_array($key, $ignore, true)) {
                    continue;
                }
                $this->comparePUTArray($url, $submission[$key], $value, $expected[$key], $ignore[$key] ?? [], $original[$key] ?? []);
                continue;
            }

            if (isset($submission[$key])) {
                if (in_array($key, $ignore, true)) {
                    continue;
                }
                if (!in_array($key, $ignore, true)) {
                    $message = sprintf(
                        "Field '%s' with value %s is expected to be %s.\nSubmitted:  %s\nIgnored:    %s\nExpected:   %s\nReturned:   %s\nURL: %s",
                        $key,
                        var_export($value, true),
                        var_export($expected[$key], true),
                        json_encode($submission),
                        json_encode($ignore),
                        json_encode($expected),
                        json_encode($response),
                        $url
                    );

                    $this->assertEquals($value, $expected[$key], $message);
                }
            }
            // if not set, compare to original to see if it's the same:
            if (
                !isset($submission[$key])
                && isset($response[$key])
                && isset($original[$key])
                && !in_array($key, $ignore, true)
                && !in_array($key, $extraIgnore, true)) {

                $message = sprintf(
                    "Field '%s' was unexpectedly changed from %s to %s.\nSubmitted:  %s\nIgnored:    %s\nExpected:   %s\nReturned:   %s\nURL: %s",
                    $key,
                    json_encode($original[$key]),
                    json_encode($response[$key]),
                    json_encode($submission),
                    json_encode($ignore),
                    json_encode($expected),
                    json_encode($response),
                    $url
                );
                $this->assertEquals($response[$key], $original[$key], $message);
                continue;
            }
        }
    }
}
