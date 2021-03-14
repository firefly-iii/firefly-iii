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
        /**
         * @var string $i
         * @var array  $set
         */
        foreach ($minimalSets as $i => $set) {
            $body = [];
            foreach ($set['fields'] as $ii => $value) {
                $body[$ii] = $value;
            }
            // minimal set is part of all submissions:
            $submissions[] = [[
                                  'fields'     => $body,
                                  'parameters' => $set['parameters'] ?? [],
                                  'ignore'     => $set['ignore'] ?? [],
                              ]];

            // then loop and add fields:
            $optionalSets = $startOptionalSets;
            $keys         = array_keys($optionalSets);
            $count        = count($keys) > self::MAX_ITERATIONS ? self::MAX_ITERATIONS : count($keys);
            for ($iii = 1; $iii <= $count; $iii++) {
                $combinations = $this->combinationsOf($iii, $keys);
                // expand body with N extra fields:
                foreach ($combinations as $iv => $extraFields) {
                    $second = $body;
                    $ignore = $set['ignore'] ?? []; // unused atm.
                    foreach ($extraFields as $v => $extraField) {
                        // now loop optional sets on $extraField and add whatever the config is:
                        foreach ($optionalSets[$extraField]['fields'] as $vi => $newValue) {
                            // if the newValue is an array, we must merge it with whatever may
                            // or may not already be there. Its the optional field for one of the
                            // (maybe existing?) fields:
                            if (is_array($newValue) && array_key_exists($vi, $second) && is_array($second[$vi])) {
                                // loop $second[$vi] and merge it with whatever is in $newValue[$someIndex]
                                foreach ($second[$vi] as $vii => $iiValue) {
                                    $second[$vi][$vii] = $iiValue + $newValue[$vii];
                                }
                            }
                            if (!is_array($newValue)) {
                                $second[$vi] = $newValue;
                            }
                        }
                        if (array_key_exists('remove_fields', $optionalSets[$extraField])) {
                            foreach ($optionalSets[$extraField]['remove_fields'] as $removed) {
                                unset($second[$removed]);
                            }
                        }
                    }

                    $second        = $this->regenerateValues($second, $regenConfig);
                    $submissions[] = [[
                                          'fields'     => $second,
                                          'parameters' => $set['parameters'] ?? [],
                                          'ignore'     => $ignore,
                                      ]];
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
        foreach ($opts as $i => $func) {
            if (array_key_exists($i, $set)) {
                if(!is_array($set[$i])) {
                    $set[$i] = $func();
                }
                if(is_array($set[$i])) {
                    foreach($set[$i] as $ii => $lines) {
                        foreach($lines as $iii => $value) {
                            if(isset($opts[$i][$ii][$iii])) {
                                $set[$i][$ii][$iii] = $opts[$i][$ii][$iii]();
                            }
                        }
                    }
                }
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
        $status   = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf(sprintf('%s failed with 404.', $route)));
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
        $status         = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Submission: %s\nResponse: %s", json_encode($submissionArray), $responseString));
        //$response->assertStatus(200);
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
                    if (in_array($rKey, $submission['extra_ignore'])) {
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

        }
    }

    /**
     * @param string $route
     * @param array  $content
     */
    protected function storeAndCompare(string $route, array $content): void
    {
        $submission = $content['fields'];
        $parameters = $content['parameters'];
        $ignore     = $content['ignore'];
        // submit!
        $response     = $this->post(route($route, $parameters), $submission, ['Accept' => 'application/json']);
        $responseBody = $response->content();
        $responseJson = json_decode($responseBody, true);
        $status       = $response->getStatusCode();
        $this->assertEquals($status, 200, sprintf("Submission: %s\nResponse: %s", json_encode($submission), $responseBody));

        $response->assertHeader('Content-Type', 'application/vnd.api+json');

        // compare results:
        foreach ($responseJson['data']['attributes'] as $returnName => $returnValue) {
            if (array_key_exists($returnName, $submission) && !in_array($returnName, $ignore, true)) {
                // TODO still based on account routine:
                if ($this->ignoreCombination($route, $submission['type'] ?? 'blank', $returnName)) {
                    continue;
                }
                // check if is array, if so we need something smart:
                if (is_array($returnValue) && is_array($submission[$returnName])) {
                    $this->compareArray($returnName, $submission[$returnName], $returnValue);
                }
                if (!is_array($returnValue) && !is_array($submission[$returnName])) {
                    $message = sprintf(
                        "Main: Return value '%s' of key '%s' does not match submitted value '%s'.\n%s\n%s", $returnValue, $returnName, $submission[$returnName],
                        json_encode($submission), $responseBody
                    );
                    $this->assertEquals($returnValue, $submission[$returnName], $message);
                }

            }
        }
    }

    /**
     * @param string $key
     * @param array  $original
     * @param array  $returned
     */
    protected function compareArray(string $key, array $original, array $returned)
    {
        $ignore = ['id', 'created_at', 'updated_at'];
        foreach ($returned as $objectKey => $object) {
            // each object is a transaction, a rule trigger, a rule action, whatever.
            // assume the original also contains this key:
            if (!array_key_exists($objectKey, $original)) {
                $message = sprintf('Sub: Original array "%s" does not have returned key %d.', $key, $objectKey);
                $this->assertTrue(false, $message);
            }

            foreach ($object as $returnKey => $returnValue) {
                if (in_array($returnKey, $ignore, true)) {
                    continue;
                }
                if (array_key_exists($returnKey, $original[$objectKey])) {
                    $message = sprintf(
                        'Sub: sub-array "%s" returned value %s does not match sent X value %s.',
                        $key, var_export($returnValue, true), var_export($original[$objectKey][$returnKey], true)
                    );
                    $this->assertEquals($original[$objectKey][$returnKey], $returnValue, $message);
                }
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
        if ('api.v1.accounts.store' === $area) {
            if ('expense' === $left
                && in_array($right, ['order', 'virtual_balance', 'opening_balance', 'opening_balance_date'])) {
                return true;
            }
        }

        return false;
    }
}
