<?php

namespace FireflyIII\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use JsonException;

/**
 * Class IsValidBulkClause
 */
class IsValidBulkClause implements Rule
{
    private array  $rules;
    private string $error;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->rules = config(sprintf('bulk.%s', $type));
        $this->error = (string)trans('firefly.belongs_user');
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $result = $this->basicValidation((string)$value);
        if (false === $result) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->error;
    }

    /**
     * Does basic rule based validation.
     *
     * @return bool
     */
    private function basicValidation(string $value): bool
    {
        try {
            $array = json_decode($value, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error = (string)trans('validation.json');

            return false;
        }
        $clauses = ['where', 'update'];
        foreach ($clauses as $clause) {
            if (!array_key_exists($clause, $array)) {
                $this->error = (string)trans(sprintf('validation.missing_%s', $clause));

                return false;
            }
            /**
             * @var string $arrayKey
             * @var mixed  $arrayValue
             */
            foreach ($array[$clause] as $arrayKey => $arrayValue) {
                if (!array_key_exists($arrayKey, $this->rules[$clause])) {
                    $this->error = (string)trans(sprintf('validation.invalid_%s_key', $clause));

                    return false;
                }
                // validate!
                $validator = Validator::make(['value' => $arrayValue], [
                    'value' => $this->rules[$clause][$arrayKey],
                ]);
                if ($validator->fails()) {
                    $this->error = sprintf('%s: %s: %s',$clause, $arrayKey, join(', ', ($validator->errors()->get('value'))));

                    return false;
                }
            }
        }

        return true;
    }
}