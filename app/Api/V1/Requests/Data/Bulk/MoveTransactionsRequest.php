<?php
declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Data\Bulk;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Class MoveTransactionsRequest
 */
class MoveTransactionsRequest extends FormRequest
{
    use ChecksLogin, ConvertsDataTypes;

    /**
     * @return array
     */
    public function getAll(): array
    {
        return [
            'original_account'    => $this->integer('original_account'),
            'destination_account' => $this->integer('destination_account'),
        ];
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'original_account'    => 'required|different:destination_account|belongsToUser:accounts,id',
            'destination_account' => 'required|different:original_account|belongsToUser:accounts,id',
        ];
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     *
     * @param Validator $validator
     * TODO duplicate code.
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                // validate start before end only if both are there.
                $data = $validator->getData();
                if (array_key_exists('original_account', $data) && array_key_exists('destination_account', $data)) {
                    $repository = app(AccountRepositoryInterface::class);
                    $repository->setUser(auth()->user());
                    $original    = $repository->findNull((int)$data['original_account']);
                    $destination = $repository->findNull((int)$data['destination_account']);
                    if ($original->accountType->type !== $destination->accountType->type) {
                        $validator->errors()->add('title', (string)trans('validation.same_account_type'));

                        return;
                    }
                    // get currency pref:
                    $originalCurrency    = $repository->getAccountCurrency($original);
                    $destinationCurrency = $repository->getAccountCurrency($destination);
                    if (null === $originalCurrency xor null === $destinationCurrency) {
                        $validator->errors()->add('title', (string)trans('validation.same_account_currency'));

                        return;
                    }
                    if (null === $originalCurrency && null === $destinationCurrency) {
                        // this is OK
                        return;
                    }
                    if ($originalCurrency->code !== $destinationCurrency->code) {
                        $validator->errors()->add('title', (string)trans('validation.same_account_currency'));

                        return;
                    }
                }
            }
        );
    }
}
