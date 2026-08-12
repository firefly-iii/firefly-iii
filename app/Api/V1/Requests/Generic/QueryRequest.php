<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Contracts\Validation\Validator;

class QueryRequest extends ApiRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function rules(): array
    {
        return ['query' => sprintf('min:0|max:50|%s', $this->required)];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (count($validator->failed()) > 0) {
                return;
            }
            $query = $this->convertString('query');
            $this->attributes->set('query', $query);
        });
    }
}
