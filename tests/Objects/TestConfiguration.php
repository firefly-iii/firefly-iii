<?php


namespace Tests\Objects;

use Faker\Factory;
use RuntimeException;

/**
 * Class TestConfiguration
 */
class TestConfiguration
{
    public TestMandatoryFieldSet $mandatoryFieldSet;
    private array                $submission;

    /**
     * TestConfiguration constructor.
     */
    public function __construct()
    {
        $this->submission = [];
    }

    public function generateSubmission(): array
    {
        $this->submission = [];
        /** @var TestMandatoryField $field */
        foreach ($this->mandatoryFieldSet->mandatoryFields as $field) {
            $this->parseField($field);
        }

        return $this->submission;
    }

    private function parseField(TestMandatoryField $field)
    {
        if ('' === $field->fieldPosition) {
            $this->submission[$field->fieldTitle] = $this->generateFieldValue($field->fieldType);
        }
        if ('' !== $field->fieldPosition) {
            $positions = explode('/', $field->fieldPosition);
            // since the "positions" array is almost 2 indexes deep at best, we can do some manual fiddling.
            $root                                                = $positions[0];
            $count                                               = (int)$positions[1];
            $this->submission[$root]                             = array_key_exists($root, $this->submission) ? $this->submission[$root] : [];
            $this->submission[$root][$count]                     = array_key_exists($count, $this->submission[$root]) ? $this->submission[$root][$count] : [];
            $this->submission[$root][$count][$field->fieldTitle] = $this->generateFieldValue($field->fieldType);
        }
    }

    /**
     * @param string $type
     *
     * @return mixed|string
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
        }
    }

}