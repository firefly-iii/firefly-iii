<?php


namespace Tests\Objects;

use Faker\Factory;
use RuntimeException;

/**
 * Class TestConfiguration
 */
class TestConfiguration
{
    protected const MAX_ITERATIONS = 3;
    public const    SHOW_DEBUG     = false;
    public array  $ignores;
    public array  $mandatoryFieldSets;
    public array  $optionalFieldSets;
    public array  $parameters;
    private array $expected;
    private array $submission;

    /**
     * TestConfiguration constructor.
     */
    public function __construct()
    {
        $this->submission         = [];
        $this->mandatoryFieldSets = [];
        $this->optionalFieldSets  = [];
        $this->ignores            = [];
        $this->parameters         = [];
        $this->expected           = [];
    }

    /**
     * @param FieldSet $set
     */
    public function addMandatoryFieldSet(FieldSet $set)
    {
        $this->mandatoryFieldSets[] = $set;
    }

    public function addOptionalFieldSet(string $key, FieldSet $set)
    {
        $this->optionalFieldSets[$key] = $set;
    }

    public function generateAll(): array
    {
        $this->debugMsg('Now in generateAll()');
        // generate submissions
        $array      = $this->generateSubmissions();
        $parameters = $this->parameters;
        $ignored    = $this->ignores;
        $expected   = $this->expected;

        $this->debugMsg(sprintf('Now validating %d ignored() values.', count($ignored)));

        // update ignored parameters:
        $newIgnored = [];
        foreach ($ignored as $index => $currentIgnored) {
            $this->debugMsg(sprintf(' Value #%d is %s', $index, json_encode($currentIgnored)));
            $updated = [];
            foreach ($currentIgnored as $key => $value) {
                if (!is_array($value)) {
                    $positions = explode('/', $value);
                    $count     = count($positions);
                    if (1 === $count) {
                        $updated[$key] = $updated[$key] ? $updated[$key] : $value;
                        continue;
                    }
                    if (3 === $count) {
                        $root                     = $positions[0];
                        $count                    = (int)$positions[1];
                        $final                    = $positions[2];
                        $updated[$root][$count][] = $final;
                        continue;
                    }
                }
                if (is_array($value)) {
                    $updated[$key] = $value;
                }
            }
            $newIgnored[$index] = $updated;
            $this->debugMsg(sprintf(' Updated value #%d is %s', $index, json_encode($updated)));
        }

        // now create a combination for each submission and associated data:
        $final = [];
        foreach ($array as $index => $submission) {
            $final[] = [[
                            'submission' => $submission,
                            'expected'   => $expected[$index] ?? $submission,
                            'ignore'     => $newIgnored[$index] ?? [],
                            'parameters' => $parameters[$index] ?? [],
                        ]];
        }

        return $final;
    }

    /**
     * @param string $message
     */
    private function debugMsg(string $message): void
    {
        if (true === self::SHOW_DEBUG) {
            echo sprintf("%s\n", $message);
        }
    }

    /**
     * @return array
     */
    private function generateSubmissions(): array
    {
        $this->debugMsg('Now in generateSubmissions()');
        // first generate standard submissions:
        $this->submission = [];
        // loop each standard submission:
        $this->debugMsg(sprintf('There are %d mandatory field sets', count($this->mandatoryFieldSets)));
        /** @var FieldSet $set */
        foreach ($this->mandatoryFieldSets as $set) {
            $this->submission[] = $this->toArray($set);

            // expand the standard submission with extra sets from the optional field set.
            $setCount = count($this->optionalFieldSets);
            $this->debugMsg('Just created a standard set');
            if (0 !== $setCount) {
                $keys = array_keys($this->optionalFieldSets);
                $this->debugMsg(sprintf(' keys to consider are: %s', join(', ', $keys)));
                $maxCount = count($keys) > self::MAX_ITERATIONS ? self::MAX_ITERATIONS : count($keys);
                for ($i = 1; $i <= $maxCount; $i++) {
                    $combinationSets = $this->combinationsOf($i, $keys);
                    $this->debugMsg(sprintf(' will create %d extra sets.', count($combinationSets)));
                    foreach ($combinationSets as $ii => $combinationSet) {
                        $this->debugMsg(sprintf('Set %d/%d will consist of:', ($ii + 1), count($combinationSets)));
                        // the custom set is born!
                        $customFields = [];
                        $custom       = $this->toArray($set);
                        $this->debugMsg(' refreshed!');
                        $this->debugMsg(sprintf(' %s', json_encode($custom)));
                        foreach ($combinationSet as $combination) {
                            $this->debugMsg(sprintf('   %s', $combination));
                            // here we start adding stuff to a copy of the standard submission.
                            /** @var FieldSet $customSet */
                            $customSet = $this->optionalFieldSets[$combination] ?? false;
                            $this->debugMsg(sprintf('   there are %d field(s) in this custom set', count(array_keys($customSet->fields))));
                            // loop each field in this custom set and add them, nothing more.
                            /** @var Field $field */
                            foreach ($customSet->fields as $field) {
                                $this->debugMsg(sprintf('   added field %s from custom set %s', $field->fieldTitle, $combination));
                                $custom         = $this->parseField($custom, $field);
                                $customFields[] = $field;
                            }
                        }
                        $this->submission[] = $custom;
                        // at this point we can update the ignorable fields because we know the position
                        // of the submission in the array
                        $index = count($this->submission) - 1;
                        $this->updateIgnorables($index, $customFields);
                        $this->updateExpected($index, $customFields);
                    }
                }
            }
        }

        $totalCount = 0;
        // no mandatory sets? Loop the optional sets:
        if (0 === count($this->mandatoryFieldSets)) {
            // expand the standard submission with extra sets from the optional field set.
            $setCount = count($this->optionalFieldSets);
            $this->debugMsg(sprintf('there are %d optional field sets', $setCount));
            if (0 !== $setCount) {
                $keys = array_keys($this->optionalFieldSets);
                $this->debugMsg(sprintf(' keys to consider are: %s', join(', ', $keys)));
                $maxCount = count($keys) > self::MAX_ITERATIONS ? self::MAX_ITERATIONS : count($keys);
                $this->debugMsg(sprintf(' max count is %d', $maxCount));
                for ($i = 1; $i <= $maxCount; $i++) {
                    $combinationSets = $this->combinationsOf($i, $keys);
                    $this->debugMsg(sprintf(' will create %d extra sets.', count($combinationSets)));
                    foreach ($combinationSets as $ii => $combinationSet) {
                        $totalCount++;
                        $this->debugMsg(sprintf('  Set #%d will consist of:', $totalCount));
                        // the custom set is born!
                        $custom   = [];
                        $expected = [];
                        foreach ($combinationSet as $combination) {
                            $this->debugMsg(sprintf('   %s', $combination));
                            // here we start adding stuff to a copy of the standard submission.
                            /** @var FieldSet $customSet */
                            $customSet = $this->optionalFieldSets[$combination] ?? false;
                            $this->debugMsg(sprintf(sprintf('   there are %d field(s) in this custom set', count(array_keys($customSet->fields)))));
                            // loop each field in this custom set and add them, nothing more.
                            /** @var Field $field */
                            foreach ($customSet->fields as $field) {
                                $this->debugMsg(sprintf('     added field "%s" from custom set "%s"', $field->fieldTitle, $combination));
                                $custom   = $this->parseField($custom, $field);
                                $expected = $this->parseExpected($expected, $field, $custom);
                                // for each field, add the ignores to the current index (+1!) of
                                // ignores.
                                $count = count($this->submission);
                                if (null !== $field->ignorableFields && count($field->ignorableFields) > 0) {
                                    $this->debugMsg(sprintf('     This field also has ignore things! %s', json_encode($field->ignorableFields)));
                                    $currentIgnoreSet = $this->ignores[$count] ?? [];
                                    $newIgnoreSet     = [];
                                    foreach ($field->ignorableFields as $ignorableField) {
                                        $positions = explode('/', $ignorableField);
                                        $posCount  = count($positions);

                                        if (1 === $posCount) {
                                            $newIgnoreSet[] = $ignorableField;
                                        }
                                        if (3 === $posCount) {
                                            $root                          = $positions[0];
                                            $index                         = (int)$positions[1];
                                            $final                         = $positions[2];
                                            $newIgnoreSet[$root]           = array_key_exists($root, $newIgnoreSet) ? $newIgnoreSet[$root] : [];
                                            $newIgnoreSet[$root][$index]   = array_key_exists($index, $newIgnoreSet[$root]) ? $newIgnoreSet[$root][$index] : [];
                                            $newIgnoreSet[$root][$index][] = $final;
                                        }
                                    }
                                    $this->debugMsg(sprintf('     %s + %s', json_encode($currentIgnoreSet), json_encode($newIgnoreSet)));
                                    $mergedArray = $this->mergeIgnoreArray($currentIgnoreSet, $newIgnoreSet);
                                    //$this->ignores[$count] = $currentIgnoreSet + $newIgnoreSet;
                                    $this->ignores[$count] = $mergedArray;//array_merge_recursive($currentIgnoreSet, $newIgnoreSet);

                                    $this->debugMsg(sprintf('     New set of ignore things (%d) is: %s', $count, json_encode($this->ignores[$count])));
                                }
                                $this->expected[$count] = $expected;
                            }
                            $count                    = count($this->submission);
                            $this->parameters[$count] = $customSet->parameters ?? [];
                        }
                        $count              = count($this->submission);
                        $this->submission[] = $custom;
                        $this->debugMsg(sprintf('  Created set #%d on index %d', $totalCount, $count));
                        $this->debugMsg(sprintf('  Will submit: %s', json_encode($custom)));
                        $this->debugMsg(sprintf('  Will ignore: %s', json_encode($this->ignores[$count] ?? [])));
                        $this->debugMsg(sprintf('  Will expect: %s', json_encode($this->expected[$count] ?? [])));
                    }
                }
            }
        }
        $this->debugMsg('Done!');

        return $this->submission;
    }

    /**
     * @param $left
     * @param $right
     *
     * @return array
     */
    private function mergeIgnoreArray($left, $right): array
    {
        // if both empty just return empty:
        if (0 === count($left) && 0 === count($right)) {
            $this->debugMsg('Return empty array');

            return [];
        }
        // if left is empty return right
        if (0 === count($left)) {
            $this->debugMsg('Return right');

            return $right;
        }
        // if right is empty return left
        if (0 === count($right)) {
            $this->debugMsg('Return left');

            return $left;
        }
        $this->debugMsg('Loop right');
        $result = [];
        foreach ($right as $key => $value) {
            $this->debugMsg(sprintf('Now at right key %s with value %s', json_encode($key), json_encode($value)));
            if (is_array($value) && array_key_exists($key, $left)) {
                $this->debugMsg(sprintf('Key %s exists in both, go one level deeper.', $key));
                $result[$key] = $this->mergeIgnoreArray($right[$key], $left[$key]);
                continue;
            }
            if (is_array($value) && !array_key_exists($key, $left)) {
                $this->debugMsg(sprintf('Key %s exists in right only, keep it as it is.', $key));
                $result[$key] = $right[$key];
            }
            // value is not an array, can be appended to result (ignore the key):
            $this->debugMsg(sprintf('Key %s is a string (%s), just append it and return it later.', $key, $value));
            $result[] = $value;
        }
        // loop left:
        $this->debugMsg('Loop left');
        foreach ($left as $key => $value) {
            $this->debugMsg(sprintf('Now at left key %s with value %s', json_encode($key), json_encode($value)));
            if (is_array($value) && array_key_exists($key, $right)) {
                $this->debugMsg(sprintf('Key %s exists in both, go one level deeper.', $key));
                $result[$key] = $this->mergeIgnoreArray($left[$key], $right[$key]);
                continue;
            }
            if (is_array($value) && !array_key_exists($key, $right)) {
                $result[$key] = $left[$key];
            }
            // value is not an array, can be appended to result (ignore the key):
            $result[] = $value;
        }

        //
        return $result;
    }

    /**
     * @param FieldSet $set
     *
     * @return array
     */
    private function toArray(FieldSet $set): array
    {
        $ignore   = [];
        $result   = [];
        $expected = [];
        /** @var Field $field */
        foreach ($set->fields as $field) {
            // this is what we will submit:
            $result   = $this->parseField($result, $field);
            $expected = $this->parseExpected($expected, $field, $result);

            // this is what we will ignore:
            $newIgnore = array_unique($ignore + $field->ignorableFields);
            $ignore    = $newIgnore;
            $this->debugMsg(sprintf('Merged! ignores %s + %s = %s', json_encode($ignore), json_encode($field->ignorableFields), json_encode($newIgnore)));

        }
        $this->ignores[]    = array_values($ignore);
        $this->expected[]   = $expected;
        $this->parameters[] = $set->parameters ?? [];

        return $result;
    }

    /**
     * @param array $current
     * @param Field $field
     *
     * @return array
     */
    private function parseField(array $current, Field $field): array
    {
        // fieldTitle indicates the position:
        $positions = explode('/', $field->fieldTitle);
        $count     = count($positions);

        if (1 === $count) {
            $current[$field->fieldTitle] = $this->generateFieldValue($field->fieldType);

            return $current;
        }
        if (3 === $count) {
            $root                           = $positions[0];
            $count                          = (int)$positions[1];
            $final                          = $positions[2];
            $current[$root]                 = array_key_exists($root, $current) ? $current[$root] : [];
            $current[$root][$count]         = array_key_exists($count, $current[$root]) ? $current[$root][$count] : [];
            $current[$root][$count][$final] = $this->generateFieldValue($field->fieldType);

            return $current;
        }
        throw new RuntimeException(sprintf('Did not expect count %d from fieldTitle "%s".', $count, $field->fieldTitle));
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    private function generateFieldValue(string $type)
    {
        $faker = Factory::create();
        switch ($type) {
            default:
                throw new RuntimeException(sprintf('Cannot handle field "%s"', $type));
            case 'uuid':
                return $faker->uuid;
            case 'static-asset':
                return 'asset';
            case 'static-expense':
                return 'expense';
            case 'static-liabilities':
                return 'liabilities';
            case 'static-ccAsset':
                return 'ccAsset';
            case 'static-monthlyFull':
                return 'monthlyFull';
            case 'static-one':
                return 1;
            case 'static-journal-type':
                return 'TransactionJournal';
            case 'random-liability-type':
                return $faker->randomElement(['loan', 'debt', 'mortgage']);
            case 'random-journal-id':
                return $faker->numberBetween(1, 25);
            case 'random-amount':
                return number_format($faker->randomFloat(2, 10, 100), 2);
            case 'random-percentage':
                return $faker->randomFloat(2, 1, 99);
            case 'random-interest-period':
                return $faker->randomElement(['daily', 'monthly', 'yearly']);
            case 'random-bill-repeat-freq':
                return $faker->randomElement(['half-year', 'weekly', 'monthly', 'yearly']);
            case 'random-past-date':
                return $faker->dateTimeBetween('-3 years', '-1 years')->format('Y-m-d');
            case 'random-future-date':
                return $faker->dateTimeBetween('1 years', '3 years')->format('Y-m-d');
            case 'random-date-two-year':
                return $faker->dateTimeBetween('-2 years', '-1 years')->format('Y-m-d');
            case 'random-date-one-year':
                return $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d');
            case 'random-asset-accountRole':
                return $faker->randomElement(['defaultAsset', 'savingAsset']);
            case 'random-transactionType':
                return $faker->randomElement(['withdrawal', 'deposit', 'transfer']);
            case 'boolean':
                return $faker->boolean;
            case 'iban':
            case 'account_number':
                return $faker->iban();
            case 'bic':
                return $faker->swiftBicNumber;
            case 'random-currency-id':
                return $faker->numberBetween(1, 10);
            case 'random-currency-code':
                return $faker->randomElement(['EUR', 'USD', 'HUF', 'GBP']);
            case 'order':
                return $faker->numberBetween(1, 5);
            case 'latitude':
                return $faker->latitude;
            case 'longitude':
                return $faker->longitude;
            case 'random-zoom_level':
                return $faker->numberBetween(1, 12);
            case 'null':
                return null;
            case 'random-attachment-type':
                return $faker->randomElement(['Account', 'Budget', 'Bill', 'TransactionJournal', 'PiggyBank', 'Tag',]);
            case 'random-amount-min':
                return number_format($faker->randomFloat(2, 10, 50), 2);
            case 'random-amount-max':
                return number_format($faker->randomFloat(2, 50, 100), 2);
            case 'random-skip':
                return $faker->numberBetween(0, 4);
            case 'random-budget-id':
            case 'random-category-id':
            case 'random-rule-group-id':
            case 'random-piggy-id':
            case 'low-order':
            case 'random-og-id':
                return $faker->numberBetween(1, 2);
            case 'random-tags':
                return $faker->randomElements(['a', 'b', 'c', 'd', 'ef', 'gh', 'sasas', '38sksl'], 5);
            case 'random-auto-type':
                return $faker->randomElement(['rollover', 'reset']);
            case 'random-auto-period':
                return $faker->randomElement(['weekly', 'monthly', 'yearly']);
            case 'static-auto-none':
                return 'none';
            case 'random-piggy-account':
                return $faker->numberBetween(1, 3);
            case 'static-withdrawal':
                return 'withdrawal';
            case 'static-ndom':
                return 'ndom';
            case 'moment-ndom':
                return sprintf('%d,%d', $faker->numberBetween(1, 4), $faker->numberBetween(1, 7));
            case 'static-monthly':
                return 'monthly';
            case 'moment-monthly':
                return $faker->numberBetween(1, 28);
            case 'static-yearly':
                return 'yearly';
            case 'static-deposit':
                return 'deposit';
            case 'static-transfer':
                return 'transfer';
            case 'static-type-weekly':
                return 'weekly';
            case 'random-nr-of-reps':
                return $faker->numberBetween(5, 12);
            case 'weekend':
                return $faker->numberBetween(1, 4);
            case 'random-asset-id':
                return $faker->randomElement([1, 2, 3]);
            case 'random-other-asset-id':
                return $faker->randomElement([4, 5, 6]);
            case 'random-expense-id':
                return $faker->randomElement([8, 11, 12]);
            case 'random-revenue-id':
                return $faker->randomElement([9, 10]);
            case 'random-trigger':
                return $faker->randomElement(['store-journal', 'update-journal']);
            case 'random-trigger-type':
                return $faker->randomElement(['from_account_starts', 'from_account_is', 'description_ends', 'description_is']);
            case 'random-action-type':
                return $faker->randomElement(['set_category', 'add_tag', 'set_description']);
            case 'random-rule-group-title':
                return $faker->randomElement(['Rule group 1', 'Rule group 2']);
        }
    }

    /**
     * @param array $expected
     * @param Field $field
     * @param array $result
     *
     * @return array
     */
    private function parseExpected(array $expected, Field $field, array $result): array
    {
        $this->debugMsg(sprintf('      Now parsing expected return values for field %s', $field->fieldTitle));
        // fieldTitle indicates the position:
        $positions = explode('/', $field->fieldTitle);
        $count     = count($positions);

        if (1 === $count && null === $field->expectedReturn) {
            $expected[$field->fieldTitle] = $result[$field->fieldTitle];
            $this->debugMsg(sprintf('     Expected result of field "%s" = "%s"', $field->fieldTitle, $expected[$field->fieldTitle]));

            return $expected;
        }
        if (1 === $count && null !== $field->expectedReturn) {
            // call the closure!
            $expected[$field->fieldTitle] = ($field->expectedReturn)($result[$field->fieldTitle]);

            return $expected;
        }

        if (3 === $count) {
            $root                            = $positions[0];
            $count                           = (int)$positions[1];
            $final                           = $positions[2];
            $expected[$root]                 = array_key_exists($root, $expected) ? $expected[$root] : [];
            $expected[$root][$count]         = array_key_exists($count, $expected[$root]) ? $expected[$root][$count] : [];
            $expected[$root][$count][$final] = null;
            $this->debugMsg(sprintf('      Field name is split, so will store the expected return in $expected[%s][%d][%s] = (here)', $root, $count, $final));

            if (null === $field->expectedReturn) {
                $this->debugMsg(sprintf('      Expected return is NULL, so will use the result for this point: %s', json_encode($result)));
                $expected[$root][$count][$final] = $result[$root][$count][$final];
            }
            if (null !== $field->expectedReturn) {
                $this->debugMsg(sprintf('      Expected return is not NULL, so will use a callback for this point: %s', json_encode($result)));
                $this->debugMsg(sprintf('      Root  : %s', json_encode($result[$root])));
                $this->debugMsg(sprintf('      Count : %s', json_encode($result[$root][$count])));
                $this->debugMsg(sprintf('      Final : %s', json_encode($result[$root][$count][$final])));
                $lastValue                       = $result[$root][$count][$final];
                $expected[$root][$count][$final] = ($field->expectedReturn)($lastValue);
            }

            return $expected;
        }
        throw new RuntimeException(sprintf('Did not expect count %d from fieldTitle "%s".', $count, $field->fieldTitle));
    }

    /**
     * @param $k
     * @param $xs
     *
     * @return array|array[]
     */
    private function combinationsOf($k, $xs): array
    {
        if ($k === 0) {
            return [[]];
        }
        if (count($xs) === 0) {
            return [];
        }
        $x    = $xs[0];
        $xs1  = array_slice($xs, 1, count($xs) - 1);
        $res1 = $this->combinationsOf($k - 1, $xs1);
        for ($i = 0; $i < count($res1); $i++) {
            array_splice($res1[$i], 0, 0, $x);
        }
        $res2 = $this->combinationsOf($k, $xs1);

        return array_merge($res1, $res2);
    }

    /**
     * @param int   $index
     * @param array $customFields
     */
    function updateIgnorables(int $index, array $customFields): void
    {
        if (count($customFields) > 0) {
            /** @var Field $field */
            foreach ($customFields as $field) {
                if (0 !== count($field->ignorableFields)) {
                    $this->ignores[$index] = array_values(array_unique(array_values(array_merge($this->ignores[$index], $field->ignorableFields))));
                }
            }
        }
    }

    /**
     * @param int   $index
     * @param array $customFields
     */
    private function updateExpected(int $index, array $customFields): void
    {
        $this->debugMsg('Now parsing expected return values for this set.');
        if (count($customFields) > 0) {
            /** @var Field $field */
            foreach ($customFields as $field) {
                // fieldTitle indicates the position:
                $positions = explode('/', $field->fieldTitle);
                $count     = count($positions);
                if (1 === $count) {
                    if (null === $field->expectedReturn) {
                        $this->expected[$index][$field->fieldTitle] = $this->submission[$index][$field->fieldTitle];
                    }
                    if (null !== $field->expectedReturn) {
                        $this->expected[$index][$field->fieldTitle] = ($field->expectedReturn)($this->submission[$index][$field->fieldTitle]);
                    }
                }
                if (3 === $count) {
                    $root                                          = $positions[0];
                    $count                                         = (int)$positions[1];
                    $final                                         = $positions[2];
                    $this->expected[$index][$root]                 = array_key_exists($root, $this->expected[$index]) ? $this->expected[$index][$root] : [];
                    $this->expected[$index][$root][$count]         = array_key_exists($count, $this->expected[$index][$root])
                        ? $this->expected[$index][$root][$count] : [];
                    $this->expected[$index][$root][$count][$final] = $this->submission[$index][$root][$count][$final];
                }
            }
        }
    }

    /**
     * @param FieldSet $optionalFieldSet
     */
    public function setOptionalFieldSet(FieldSet $optionalFieldSet): void
    {
        $this->optionalFieldSet = $optionalFieldSet;
    }

}