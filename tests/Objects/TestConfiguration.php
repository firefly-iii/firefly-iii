<?php


namespace Tests\Objects;

use Faker\Factory;
use RuntimeException;

/**
 * Class TestConfiguration
 */
class TestConfiguration
{
    public FieldSet $mandatoryFieldSet;
    public FieldSet $optionalFieldSet;
    private array   $submission;

    /**
     * TestConfiguration constructor.
     */
    public function __construct()
    {
        $this->submission = [];
    }

    /**
     * @return array
     */
    public function generateSubmission(): array
    {
        // first generate standard submission:
        $this->submission = [];
        $standard         = [];
        /** @var Field $field */
        foreach ($this->mandatoryFieldSet->fields as $field) {
            $standard = $this->parseField($standard, $field);
        }
        $this->submission[] = $standard;

        // expand the standard submission with extra sets from the optional field set.
        $optionalCount = count($this->optionalFieldSet->fields);
        if (0 !== $optionalCount) {
            $keys = array_keys($this->optionalFieldSet->fields);
            for ($i = 1; $i <= count($keys); $i++) {
                $combinations = $this->combinationsOf($i, $keys);
                foreach ($combinations as $combination) {
                    $custom = $standard;
                    foreach ($combination as $key) {
                        // add field $key to the standard submission.
                        $custom = $this->parseField($custom, $this->optionalFieldSet->fields[$key]);
                    }
                    // add custom to $submission:
                    $this->submission[] = $custom;
                }
            }
        }

        return $this->submission;
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
            case 'random-asset-accountRole':
                return $faker->randomElement(['defaultAsset', 'savingsAsset']);
            case 'random-transactionType':
                return $faker->randomElement(['withdrawal', 'deposit', 'transfer']);
            case 'boolean':
                return $faker->boolean;
            case 'iban':
                return $faker->iban();
        }
    }

    /**
     * @param array $current
     * @param Field $field
     *
     * @return array
     */
    private function parseField(array $current, Field $field): array
    {
        if ('' === $field->fieldPosition) {
            $current[$field->fieldTitle] = $this->generateFieldValue($field->fieldType);
        }
        if ('' !== $field->fieldPosition) {
            $positions = explode('/', $field->fieldPosition);
            // since the "positions" array is almost 2 indexes deep at best, we can do some manual fiddling.
            $root                                       = $positions[0];
            $count                                      = (int)$positions[1];
            $current[$root]                             = array_key_exists($root, $current) ? $current[$root] : [];
            $current[$root][$count]                     = array_key_exists($count, $current[$root]) ? $current[$root][$count] : [];
            $current[$root][$count][$field->fieldTitle] = $this->generateFieldValue($field->fieldType);
        }

        return $current;
    }

}