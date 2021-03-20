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
        foreach ($response as $key => $value) {
            if (is_array($value) && array_key_exists($key, $expected) && is_array($expected[$key])) {
                $this->comparePOSTArray($submission, $value, $expected[$key], $ignore[$key] ?? []);
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
        $submission   = $content['submission'];
        $ignore       = $content['ignore'];
        $response     = $this->put($route, $submission, ['Accept' => 'application/json']);
        $responseBody = $response->content();
        $responseJson = json_decode($responseBody, true);
        $status       = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Submission:\n%s\nResponse: %s", json_encode($submission), $responseBody));
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        // get return and compare each field
        $responseAttributes = $responseJson['data']['attributes'];
        $this->comparePUTArray($route, $submission, $responseAttributes, $ignore);
    }

    /**
     * @param string $url
     * @param array  $submission
     * @param array  $response
     * @param array  $ignore
     */
    private function comparePUTArray(string $url, array $submission, array $response, array $ignore): void
    {

        foreach ($response as $key => $value) {
            if (is_array($value) && array_key_exists($key, $submission) && is_array($submission[$key])) {
                $this->comparePUTArray($url, $submission[$key], $value, $ignore[$key] ?? []);
            }

            if (isset($submission[$key])) {
                if (in_array($key, $ignore, true)) {
                    continue;
                }
                if (!in_array($key, $ignore, true)) {
                    $message = sprintf(
                        "Field '%s' with value %s is expected to be %s.\nSubmitted:\n%s\nIgnored: %s\nReturned\n%s\nURL: %s",
                        $key,
                        var_export($value, true),
                        var_export($submission[$key], true),
                        json_encode($submission),
                        json_encode($ignore),
                        json_encode($response),
                        $url
                    );

                    $this->assertEquals($value, $submission[$key], $message);
                }
            }
        }
    }
}
