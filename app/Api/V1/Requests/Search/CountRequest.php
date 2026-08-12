<?php




declare(strict_types=1);



namespace FireflyIII\Api\V1\Requests\Search;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Rules\IsBoolean;
use Illuminate\Contracts\Validation\Validator;
use Override;

class CountRequest extends AggregateFormRequest
{
    public function rules(): array
    {
        return [
            'notes'               => ['string', 'min:1', 'max:255'],
            'external_identifier' => ['string', 'min:1', 'max:255'],
            'description'         => ['string', 'min:1', 'max:255'],
            'internal_reference'  => ['string', 'min:1', 'max:255'],
            'include_deleted'     => new IsBoolean(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (count($validator->failed()) > 0) {
                return;
            }
            $this->attributes->set('include_deleted', $this->convertBoolean($this->input('include_deleted', 'false')));
            $this->attributes->set('notes', $this->convertString('notes'));
            $this->attributes->set('external_identifier', $this->convertString('external_identifier'));
            $this->attributes->set('description', $this->convertString('description'));
            $this->attributes->set('internal_reference', $this->convertString('internal_reference'));
        });
    }

    #[Override]
    protected function getRequests(): array
    {
        return [];
    }
}
