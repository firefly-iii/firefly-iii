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
use JsonException;
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

    /**
     * @param string $route
     * @param array  $submission
     *
     * @throws JsonException
     */
    protected function updateAndCompare(string $route, array $submission, array $ignored): void
    {
        // get original values:
        $response = $this->get($route, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $originalString     = $response->content();
        $originalArray      = json_decode($originalString, true, 512, JSON_THROW_ON_ERROR);
        $originalAttributes = $originalArray['data']['attributes'];
        // submit whatever is in submission:
        // loop the fields we will update in Firefly III:
        $submissionArray = [];
        $fieldsToUpdate  = array_keys($submission['fields']);
        foreach ($fieldsToUpdate as $currentFieldName) {
            $submissionArray[$currentFieldName] = $submission['fields'][$currentFieldName]['test_value'];
        }

        Log::debug(sprintf('Will PUT %s to %s', json_encode($submissionArray), $route));

        $response       = $this->put($route, $submissionArray, ['Accept' => 'application/json']);
        $responseString = $response->content();
        $response->assertStatus(200);
        $responseArray      = json_decode($responseString, true, 512, JSON_THROW_ON_ERROR);
        $responseAttributes = $responseArray['data']['attributes'] ?? [];

        Log::debug(sprintf('Before: %s', json_encode($originalAttributes)));
        Log::debug(sprintf('AFTER : %s', json_encode($responseAttributes)));

        // loop it and compare:
        foreach ($responseAttributes as $rKey => $rValue) {
            // field should be ignored?
            if (in_array($rKey, $ignored) || in_array($rKey, $submission['extra_ignore'])) {
                continue;
            }
            // field in response was also in body:
            if (array_key_exists($rKey, $submissionArray)) {
                if ($submissionArray[$rKey] !== $rValue) {

                    $message = sprintf(
                        "Expected field '%s' to be %s but its %s\nOriginal: %s\nSubmission: %s\nResult: %s",
                        $rKey,
                        var_export($submissionArray[$rKey], true),
                        var_export($rValue, true),
                        $originalString,
                        json_encode($submissionArray),
                        $responseString
                    );
                    $this->assertTrue(false, $message);
                    continue;
                }
                continue;
            }
            // field in response was not in body, but should be the same:
            if (!array_key_exists($rKey, $submissionArray)) {
                // original has this key too:
                if (array_key_exists($rKey, $originalAttributes)) {
                    // but we can ignore it!
                    if(in_array($rKey, $submission['extra_ignore'])) {
                        continue;
                    }
                    // but it is different?
                    if ($originalAttributes[$rKey] !== $rValue) {
                        $message = sprintf(
                            "Untouched field '%s' should still be %s but changed to %s\nOriginal: %s\nSubmission: %s\nResult: %s",
                            $rKey,
                            var_export($originalAttributes[$rKey], true),
                            var_export($rValue, true),
                            $originalString,
                            json_encode($submissionArray),
                            $responseString
                        );



                        $this->assertTrue(false, $message);
                    }
                }
                continue;
            }


            //            if (!compareResult($uValue, $currentProperties[$uKey]) && !in_array($uKey, $fieldsToUpdate)) {
            //                if (!is_array($currentProperties[$uKey]) && !is_array($uValue)) {
            //                    $log->warning(
            //                        sprintf('Field %s is updated from <code>%s</code> to <code>%s</code> but shouldnt be.', $uKey, $currentProperties[$uKey], $uValue)
            //                    );
            //                } else {
            //                    $log->warning(
            //                        sprintf('Field %s is updated from <code>(array)</code> to <code>(array)</code> but shouldnt be.', $uKey)
            //                    );
            //                }
            //                $log->debug(json_encode($currentProperties));
            //                $log->debug(json_encode($updatedAttributes));
            //            }
            //
            //            if (in_array($uKey, $fieldsToUpdate) && compareResult($uValue, $testBody[$uKey])) {
            //                $log->debug(sprintf('Field %s is updated and this is OK.', $uKey));
            //            }
            //            if (in_array($uKey, $fieldsToUpdate) && !compareResult($uValue, $testBody[$uKey])) {
            //                if (!is_array($uValue) && !is_array($testBody[$uKey])) {
            //                    $log->warning(sprintf('Field "%s" is different: %s but must be %s!', $uKey, var_export($uValue, true), var_export($testBody[$uKey], true)));
            //                    $log->debug(json_encode($currentProperties));
            //                    $log->debug(json_encode($updatedAttributes));
            //                } else {
            //                    $log->warning(sprintf('Field "%s" is different!', $uKey));
            //                    $log->debug(json_encode(filterArray($currentProperties)));
            //                    $log->debug(json_encode(filterArray($updatedAttributes)));
            //                }
            //
            //            }
        }


        //        // OLD
        //
        //
        //        $updatedResponseBody = json_decode($updateResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        //        $updatedAttributes   = $updatedResponseBody['data']['attributes'];
        //        if (array_key_exists('key', $endpoint) && array_key_exists('level', $endpoint)) {
        //            $key               = $endpoint['key'];
        //            $level             = $endpoint['level'];
        //            $updatedAttributes = $updatedResponseBody['data']['attributes'][$key][$level];
        //        }
        //
        //        // END OLD
        //
        //        var_dump($submissionJson);
        //        exit;
    }

    /**
     * @param string $route
     * @param array  $submission
     */
    protected function submitAndCompare(string $route, array $submission): void
    {
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
                // TODO still based on account routine:
                if ($this->ignoreCombination($route, $submission['type'] ?? 'blank', $returnName)) {
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
     * Some specials:
     *
     * @param string $area
     * @param string $left
     * @param string $right
     *
     * @return bool
     */
    protected function ignoreCombination(string $area, string $left, string $right): bool
    {
        if ('api.v1.attachments.store' === $area) {
            if ('expense' === $left
                && in_array($right, ['virtual_balance', 'opening_balance', 'opening_balance_date'])) {
                return true;
            }
        }

        return false;
    }
}
