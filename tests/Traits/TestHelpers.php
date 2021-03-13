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

use Exception;
use Log;

/**
 * Trait TestHelpers
 */
trait TestHelpers
{
    /**
     * @param array $minimalSets
     * @param array $startOptionalSets
     * @param array $regenConfig
     *
     * @return array
     */
    protected function genericDataProvider(array $minimalSets, array $startOptionalSets, array $regenConfig): array
    {
        $submissions = [];
        foreach ($minimalSets as $set) {
            $body = [];
            foreach ($set['fields'] as $field => $value) {
                $body[$field] = $value;
            }
            // minimal set is part of all submissions:
            $submissions[] = [$body];

            // then loop and add fields:
            $optionalSets = $startOptionalSets;
            $keys         = array_keys($optionalSets);
            $submissions  = [];
            for ($i = 1; $i <= count($keys); $i++) {
                $combinations = $this->combinationsOf($i, $keys);
                // expand body with N extra fields:
                foreach ($combinations as $extraFields) {
                    $second = $body;
                    foreach ($extraFields as $extraField) {
                        // now loop optional sets on $extraField and add whatever the config is:
                        foreach ($optionalSets[$extraField]['fields'] as $newField => $newValue) {
                            $second[$newField] = $newValue;
                        }
                    }

                    $second        = $this->regenerateValues($second, $regenConfig);
                    $submissions[] = [$second];
                }
            }
            unset($second);
        }

        return $submissions;
    }


    /**
     * @return int
     */
    public function randomInt(): int
    {
        $result = 4;
        try {
            $result = random_int(1, 100000);
        } catch (Exception $e) {
            Log::debug(sprintf('Could not generate random number: %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * @param $set
     * @param $opts
     *
     * @return array
     */
    protected function regenerateValues($set, $opts): array
    {
        foreach ($opts as $key => $func) {
            if (array_key_exists($key, $set)) {
                $set[$key] = $func();
            }
        }

        return $set;
    }

    protected function submitAndCompare(string $route, array $submission): void {
        // submit!
        $response     = $this->post(route($route), $submission, ['Accept' => 'application/json']);
        $responseBody = $response->content();
        $responseJson = json_decode($responseBody, true);
        $message      = sprintf('Status code is %d and body is %s', $response->getStatusCode(), $responseBody);
        $this->assertEquals($response->getStatusCode(), 200, $message);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        // compare results:
        foreach ($responseJson['data']['attributes'] as $returnName => $returnValue) {
            if (array_key_exists($returnName, $submission)) {
                if ($this->ignoreCombination('store-account', $submission['type'], $returnName)) {
                    continue;
                }

                $message = sprintf(
                    "Return value '%s' of key '%s' does not match submitted value '%s'.\n%s\n%s", $returnValue, $returnName, $submission[$returnName],
                    json_encode($submission), $responseBody
                );
                $this->assertEquals($returnValue, $submission[$returnName], $message);

            }
        }
    }

    /**
     * @param string $route
     * @param array  $minimalSets
     * @param array  $startOptionalSets
     * @param array  $regenConfig
     */
    protected function runBasicStoreTest(string $route, array $minimalSets, array $startOptionalSets, array $regenConfig): void
    {
        // test API
        foreach ($minimalSets as $set) {
            $body = [];
            foreach ($set['fields'] as $field => $value) {
                $body[$field] = $value;
            }
            // submit minimal set:
            Log::debug(sprintf('Submitting: %s', json_encode($body)));
            $response = $this->post(route($route), $body, ['Accept' => 'application/json']);
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/vnd.api+json');

            // then loop and add fields:
            $optionalSets = $startOptionalSets;
            $keys         = array_keys($optionalSets);
            $submissions  = [];
            for ($i = 1; $i <= count($keys); $i++) {
                $combinations = $this->combinationsOf($i, $keys);
                // expand body with N extra fields:
                foreach ($combinations as $extraFields) {
                    $second = $body;
                    foreach ($extraFields as $extraField) {
                        // now loop optional sets on $extraField and add whatever the config is:
                        foreach ($optionalSets[$extraField]['fields'] as $newField => $newValue) {
                            $second[$newField] = $newValue;
                        }
                    }

                    $second        = $this->regenerateValues($second, $regenConfig);
                    $submissions[] = $second;
                }
            }
            unset($second);

            // count and progress maybe

            // all submissions counted and submitted:
            foreach ($submissions as $submission) {
                Log::debug(sprintf('Submitting: %s', json_encode($submission)));

                // submit again!
                $response     = $this->post(route($route), $submission, ['Accept' => 'application/json']);
                $responseBody = $response->content();
                $responseJson = json_decode($responseBody, true);
                $message      = sprintf('Status code is %d and body is %s', $response->getStatusCode(), $responseBody);
                $this->assertEquals($response->getStatusCode(), 200, $message);
                $response->assertHeader('Content-Type', 'application/vnd.api+json');

                // compare results:
                foreach ($responseJson['data']['attributes'] as $returnName => $returnValue) {
                    if (array_key_exists($returnName, $submission)) {
                        if ($this->ignoreCombination('store-account', $submission['type'], $returnName)) {
                            continue;
                        }

                        $message = sprintf(
                            "Return value '%s' of key '%s' does not match submitted value '%s'.\n%s\n%s", $returnValue, $returnName, $submission[$returnName],
                            json_encode($submission), $responseBody
                        );
                        $this->assertEquals($returnValue, $submission[$returnName], $message);

                    }
                }
            }

        }
    }

}
