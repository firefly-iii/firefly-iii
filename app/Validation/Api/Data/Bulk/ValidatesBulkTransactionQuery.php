<?php

namespace FireflyIII\Validation\Api\Data\Bulk;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Validation\Validator;

/**
 *
 */
trait ValidatesBulkTransactionQuery
{
    /**
     * @param Validator $validator
     */
    protected function validateTransactionQuery(Validator $validator): void
    {
        $data = $validator->getData();
        // assumption is all validation has already taken place
        // and the query key exists.
        $json = json_decode($data['query'], true, 8);

        if (array_key_exists('source_account_id', $json['where'])
            && array_key_exists('destination_account_id', $json['update'])
        ) {
            // find both accounts
            // must be same type.
            // already validated: belongs to this user.
            $repository = app(AccountRepositoryInterface::class);
            $source     = $repository->find((int)$json['where']['source_account_id']);
            $dest       = $repository->find((int)$json['update']['destination_account_id']);
            if (null === $source) {
                $validator->errors()->add('query', sprintf((string)trans('validation.invalid_query_data'), 'where', 'source_account_id'));

                return;
            }
            if (null === $dest) {
                $validator->errors()->add('query', sprintf((string)trans('validation.invalid_query_data'), 'update', 'destination_account_id'));

                return;
            }
            if ($source->accountType->type !== $dest->accountType->type) {
                $validator->errors()->add('query', (string)trans('validation.invalid_query_account_type'));
                return;
            }
            // must have same currency:
            if($repository->getAccountCurrency($source)->id !== $repository->getAccountCurrency($dest)->id) {
                $validator->errors()->add('query', (string)trans('validation.invalid_query_currency'));
            }
        }
    }

}