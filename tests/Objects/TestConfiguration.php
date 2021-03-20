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
        $array = $this->generateSubmissions();
        //$expected   = $this->generateExpected($array);
        $parameters = $this->parameters;
        $ignored    = $this->ignores;
        $expected   = $this->expected;

        // now create a combination for each submission and associated data:
        $final = [];
        foreach ($array as $index => $submission) {
            $final[] = [[
                            'submission' => $submission,
                            'expected'   => $expected[$index] ?? $submission,
                            'ignore'     => $ignored[$index] ?? [],
                            'parameters' => $parameters[$index] ?? [],
                        ]];
        }

        return $final;
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
            //echo "Just created a standard set\n";
            if (0 !== $setCount) {
                $keys = array_keys($this->optionalFieldSets);
                //echo " keys to consider are: " . join(', ', $keys) . "\n";
                $maxCount = count($keys) > self::MAX_ITERATIONS ? self::MAX_ITERATIONS : count($keys);
                for ($i = 1; $i <= $maxCount; $i++) {
                    $combinationSets = $this->combinationsOf($i, $keys);
                    //echo " will create " . count($combinationSets) . " extra sets.\n";
                    foreach ($combinationSets as $ii => $combinationSet) {
                        //echo "  Set " . ($ii + 1) . "/" . count($combinationSets) . " will consist of:\n";
                        // the custom set is born!
                        $customFields = [];
                        $custom       = $this->toArray($set);
                        //                        echo " refreshed!\n";
                        //                        echo " " . json_encode($custom) . "\n";
                        foreach ($combinationSet as $combination) {
                            //echo "   $combination\n";
                            // here we start adding stuff to a copy of the standard submission.
                            /** @var FieldSet $customSet */
                            $customSet = $this->optionalFieldSets[$combination] ?? false;
                            //                            echo "   there are " . count(array_keys($customSet->fields)) . " field(s) in this custom set\n";
                            // loop each field in this custom set and add them, nothing more.
                            /** @var Field $field */
                            foreach ($customSet->fields as $field) {
                                //echo "   added field ".$field->fieldTitle." from custom set ".$combination."\n";
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
                            //                            echo "   there are " . count(array_keys($customSet->fields)) . " field(s) in this custom set\n";
                            // loop each field in this custom set and add them, nothing more.
                            /** @var Field $field */
                            foreach ($customSet->fields as $field) {
                                $this->debugMsg(sprintf('     added field %s from custom set %s', $field->fieldTitle, $combination));
                                $custom   = $this->parseField($custom, $field);
                                $expected = $this->parseExpected($expected, $field, $custom);
                                // for each field, add the ignores to the current index (+1!) of
                                // ignores.
                                $count = count($this->submission);
                                if (null !== $field->ignorableFields && count($field->ignorableFields) > 0) {
                                    $currentIgnoreSet      = $this->ignores[$count] ?? [];
                                    $this->ignores[$count] = array_values(array_unique(array_values(array_merge($currentIgnoreSet, $field->ignorableFields))));
                                }
                                $this->expected[$count] = $expected;
                            }
                            $count                    = count($this->submission);
                            $this->parameters[$count] = $customSet->parameters ?? [];
                        }
                        $count              = count($this->submission);
                        $this->submission[] = $custom;
                        $this->debugMsg(sprintf('  Created set #%d', $totalCount));
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
            $current[$root][$count][$final] = $this->generateFieldValue($final);

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
            case 'random-og-id':
                return $faker->numberBetween(1, 2);
            case 'random-auto-type':
                return $faker->randomElement(['rollover', 'reset']);
            case 'random-auto-period':
                return $faker->randomElement(['weekly', 'monthly', 'yearly']);
            case 'static-auto-none':
                return 'none';
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

            if (null === $field->expectedReturn) {
                $expected[$root][$count][$final] = $result[$root][$count][$final] ?? false;
            }
            if (null !== $field->expectedReturn) {
                $expected[$root][$count][$final] = ($field->expectedReturn)($result[$root][$count][$final] ?? false);
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
    function updateExpected(int $index, array $customFields): void
    {
        if (count($customFields) > 0) {
            /** @var Field $field */
            foreach ($customFields as $field) {
                if (null === $field->expectedReturn) {
                    $this->expected[$index][$field->fieldTitle] = $this->submission[$index][$field->fieldTitle];
                }
                if (null !== $field->expectedReturn) {
                    $this->expected[$index][$field->fieldTitle] = ($field->expectedReturn)($this->submission[$index][$field->fieldTitle]);
                }

            }
        }
    }

    /**
     * @param array $submissions
     *
     * @return array
     */
    public function generateIgnores(array $submissions): array
    {
        $ignores = [];
        // loop each submission and find its expected return and create
        // a return array with the expected values.
        /** @var array $submission */
        foreach ($submissions as $index => $submission) {
            $ignores[$index] = [];
            // loop each field and use the "name" to find it.
            /**
             * @var string $fieldName
             * @var string $fieldValue
             */
            foreach ($submission as $fieldTitle => $fieldValue) {
                //echo "Now searching for field $fieldTitle on index $index.\n";
                $fieldObject = $this->findField($fieldTitle);
                if (null !== $fieldObject) {
                    if (0 !== count($fieldObject->ignorableFields)) {
                        /** @var string $ignorableField */
                        foreach ($fieldObject->ignorableFields as $ignorableField) {
                            // explode and put in the right position:
                            $positions = explode('/', $ignorableField);
                            if (1 === count($positions)) {
                                $ignores[$index][$ignorableField] = true;
                            }
                            if (3 === count($positions)) {
                                $root                                   = $positions[0];
                                $index                                  = (int)$positions[1];
                                $final                                  = $positions[2];
                                $ignores[$index][$root][$index][$final] = true;
                            }
                        }
                    }
                }
                if (null === $fieldObject) {
                    die('null field object :(');
                }
            }
        }

        return $ignores;
    }

    /**
     * @param array $submissions
     *
     * @return array
     */
    public function generateParameters(array $submissions): array
    {
        $params = [];
        $index  = 0;
        /** @var array $submission */
        foreach ($submissions as $submission) {
            // last one defined param
            $submission = array_reverse($submission);
            foreach ($submission as $key => $value) {
                if (array_key_exists($key, $this->optionalFieldSets)) {
                    /** @var FieldSet $fieldSet */
                    $fieldSet = $this->optionalFieldSets[$key];
                    if (null !== $fieldSet->parameters) {
                        $params[$index] = $fieldSet->parameters;
                        continue;
                    }
                    if (null !== $fieldSet->parameters) {
                        $params[$index] = [];
                    }
                }
            }
            $index++;
        }

        return $params;
    }

    /**
     * @param FieldSet $optionalFieldSet
     */
    public function setOptionalFieldSet(FieldSet $optionalFieldSet): void
    {
        $this->optionalFieldSet = $optionalFieldSet;
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
     * @param array $submissions
     *
     * @return array
     */
    private function generateExpected(array $submissions): array
    {
        $returns = [];
        // loop each submission and find its expected return and create
        // a return array with the expected values.
        /** @var array $submission */
        foreach ($submissions as $index => $submission) {
            $returns[$index] = [];
            // loop each field and use the "name" to find it.
            /**
             * @var string $fieldName
             * @var string $fieldValue
             */
            foreach ($submission as $fieldTitle => $fieldValue) {
                //echo "Now searching for field $fieldTitle on index $index.\n";
                $fieldObject = $this->findField($fieldTitle);
                if (null !== $fieldObject) {
                    if (null === $fieldObject->expectedReturn) {
                        $returns[$index][$fieldTitle] = $submissions[$index][$fieldTitle];
                    }
                    if (null !== $fieldObject->expectedReturn) {
                        $returns[$index][$fieldTitle] = 'hi there';//($fieldObject->expectedReturn)($submissions[$index][$fieldTitle]);
                    }
                }
                if (null === $fieldObject) {
                    die('null field object :(');
                }
            }
        }

        return $returns;
    }

    /**
     * @param int    $index
     * @param string $title
     *
     * @return Field|null
     */
    private function findField(string $title): ?Field
    {
        // since there is no index for optional field sets (they use ID)
        // reverse the set and loop them all:
        // reason we reverse them is because the last always overrules the first.
        $reversed = array_reverse($this->optionalFieldSets);
        foreach ($reversed as $fieldSet) {
            foreach ($fieldSet->fields as $field) {
                if ($title === $field->fieldTitle) {
                    //echo " found field $title in an optional field set.\n";

                    return $field;
                }
            }
        }
        $reversed = array_reverse($this->mandatoryFieldSets);
        foreach ($reversed as $fieldSet) {
            foreach ($fieldSet->fields as $field) {
                if ($title === $field->fieldTitle) {
                    //echo " found field $title in a mandatory field set.\n";

                    return $field;
                }
            }
        }


        return null;
    }

    /**
     * @param array $existing
     * @param array $config
     *
     * @return array
     */
    private function parseIgnorableFields(array $existing, array $config): array
    {
        foreach ($config as $field) {
            $parts = explode('/', $field);
            if (1 === count($parts)) {
                $existing[$parts[0]] = true;
            }
            if (3 === count($parts)) {
                $root                            = $parts[0];
                $index                           = (int)$parts[1];
                $final                           = $parts[2];
                $existing[$root][$index][$final] = true;
            }
            //if ('' !== $field->fieldPosition) {
            //$positions = explode('/', $field->fieldPosition);
        }

        return $existing;
    }

}