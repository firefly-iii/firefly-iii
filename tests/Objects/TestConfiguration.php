<?php


namespace Tests\Objects;

use Faker\Factory;
use RuntimeException;

/**
 * Class TestConfiguration
 */
class TestConfiguration
{
    public array  $mandatoryFieldSets;
    public array  $optionalFieldSets;
    private array $submission;
    protected const MAX_ITERATIONS = 3;
    public array $ignores;

    /**
     * TestConfiguration constructor.
     */
    public function __construct()
    {
        $this->submission         = [];
        $this->mandatoryFieldSets = [];
        $this->optionalFieldSets  = [];
        $this->ignores            = [];
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
     * @param array $submissions
     *
     * @return array
     */
    public function generateExpected(array $submissions): array
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
                        die('cannot handle closure');
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
     * @param FieldSet $set
     *
     * @return array
     */
    private function toArray(FieldSet $set): array
    {
        $ignore = [];
        $result = [];
        /** @var Field $field */
        foreach ($set->fields as $field) {
            $result = $this->parseField($result, $field);
            $ignore = array_unique($ignore + $field->ignorableFields);
        }
        $this->ignores[] = $ignore;

        return $result;
    }

    /**
     * @return array
     */
    public function generateSubmissions(): array
    {
        // first generate standard submissions:
        $this->submission = [];

        // loop each standard submission:
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

                        $custom = $this->toArray($set);
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
                                $custom = $this->parseField($custom, $field);
                                // for each field, add the ignores to the current index (+1!) of
                                // ignores.
                                if (null !== $field->ignorableFields && count($field->ignorableFields) > 0) {
                                    $count                 = count($this->submission);
                                    $currentIgnoreSet      = $this->ignores[$count] ?? [];
                                    $this->ignores[$count] = array_unique(array_values(array_merge($currentIgnoreSet, $field->ignorableFields)));
                                }
                            }
                        }
                        $this->submission[] = $custom;
                    }
                }
            }
        }

        return $this->submission;
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
            case 'random-liability-type':
                return $faker->randomElement(['loan', 'debt', 'mortgage']);
            case 'random-amount':
                return number_format($faker->randomFloat(2, 10, 100), 2);
            case 'random-percentage':
                return $faker->randomFloat(2, 1, 99);
            case 'random-interest-period':
                return $faker->randomElement(['daily', 'monthly', 'yearly']);
            case 'random-past-date':
                return $faker->dateTimeBetween('-3 years', '-1 years')->format('Y-m-d');
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
        }
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
     * @param FieldSet $optionalFieldSet
     */
    public function setOptionalFieldSet(FieldSet $optionalFieldSet): void
    {
        $this->optionalFieldSet = $optionalFieldSet;
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