<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Rules\Account\IsValidAccountTypeList;
use FireflyIII\Rules\TransactionType\IsValidTransactionTypeList;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use Illuminate\Contracts\Validation\Validator;
use Override;
use RuntimeException;

class ObjectTypeApiRequest extends ApiRequest
{
    use AccountFilter;
    use TransactionFilter;

    private ?string $objectType = null;

    #[Override]
    public function handleConfig(array $config): void
    {
        parent::handleConfig($config);

        $this->objectType = $config['object_type'] ?? null;

        if (null === $this->objectType) {
            throw new RuntimeException('ObjectTypeApiRequest requires a object_type config');
        }
    }

    public function rules(): array
    {
        $rule  = null;
        if (Account::class === $this->objectType) {
            $rule = new IsValidAccountTypeList();
        }
        if (Transaction::class === $this->objectType) {
            $rule = new IsValidTransactionTypeList();
        }
        $rules = ['types' => [$rule]];
        if ('' !== $this->required) {
            $rules['types'][] = $this->required;
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (count($validator->failed()) > 0) {
                return;
            }
            $type = $this->convertString('types', 'all');
            $this->attributes->set('type', $type);

            switch ($this->objectType) {
                default:
                    $this->attributes->set('types', []);

                    // no break
                case Account::class:
                    $types = $this->mapAccountTypes($type);

                    // remove system account types because autocomplete doesn't need them.
                    $types = array_values(array_diff($types, [
                        AccountTypeEnum::INITIAL_BALANCE->value,
                        AccountTypeEnum::RECONCILIATION->value,
                        AccountTypeEnum::LIABILITY_CREDIT->value,
                    ]));
                    $this->attributes->set('types', $types);

                    break;

                case Transaction::class:
                    $this->attributes->set('types', $this->mapTransactionTypes($type));

                    break;
            }
        });
    }
}
