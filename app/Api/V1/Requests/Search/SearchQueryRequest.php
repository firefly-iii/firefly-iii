<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Search;

use FireflyIII\Api\V1\Requests\ApiRequest;
use Illuminate\Contracts\Validation\Validator;

class SearchQueryRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['query' => sprintf('min:0|max:500|%s', $this->required)];
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
