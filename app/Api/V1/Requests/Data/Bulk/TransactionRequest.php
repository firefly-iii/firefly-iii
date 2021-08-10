<?php

namespace FireflyIII\Api\V1\Requests\Data\Bulk;

use FireflyIII\Enums\ClauseType;
use FireflyIII\Rules\IsValidBulkClause;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Validation\Api\Data\Bulk\ValidatesBulkTransactionQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use JsonException;
use Log;

/**
 * Class TransactionRequest
 */
class TransactionRequest extends FormRequest
{
    use ChecksLogin, ConvertsDataTypes, ValidatesBulkTransactionQuery;

    /**
     * @return array
     */
    public function getAll(): array
    {
        $data = [];
        try {
            $data = [
                'query' => json_decode($this->get('query'), true, 8, JSON_THROW_ON_ERROR),
            ];
        } catch (JsonException $e) {
            // dont really care. the validation should catch invalid json.
            Log::error($e->getMessage());
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'min:1', 'max:255', 'json', new IsValidBulkClause(ClauseType::TRANSACTION)],
        ];
    }

    /**
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                // validate transaction query data.
                $this->validateTransactionQuery($validator);
            }
        );
    }
}