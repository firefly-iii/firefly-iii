<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Models\TransactionCurrency;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Validation\FireflyValidator;

class CurrencyCodeRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['code' => sprintf('exists:transaction_currencies,code|%s', $this->required)];
    }

    public function withValidator(FireflyValidator $validator): void
    {
        $validator->after(function (FireflyValidator $validator): void {
            if (0 === count($validator->valid())) {
                return;
            }
            $code = $this->convertString('code', '');
            $this->attributes->set('code', $code);
        });
    }
}
