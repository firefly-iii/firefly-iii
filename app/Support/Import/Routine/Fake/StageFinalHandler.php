<?php

namespace FireflyIII\Support\Import\Routine\Fake;

use Carbon\Carbon;

/**
 * Class StageFinalHandler
 *
 * @package FireflyIII\Support\Import\Routine\Fake
 */
class StageFinalHandler
{

    private $job;

    /**
     * @param mixed $job
     */
    public function setJob($job): void
    {
        $this->job = $job;
    }


    /**
     * @return array
     */
    public function getTransactions(): array
    {
        $transactions = [];

        for ($i = 0; $i < 5; $i++) {
            $transaction = [
                'type'               => 'withdrawal',
                'date'               => Carbon::create()->format('Y-m-d'),
                'tags'               => '',
                'user'               => $this->job->user_id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some random description #' . random_int(1, 10000),
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => random_int(500, 5000) / 100,
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => null,
                        'source_name'           => 'Checking Account',
                        'destination_id'        => null,
                        'destination_name'      => 'Random expense account #' . random_int(1, 10000),
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];

            $transactions[] = $transaction;
        }

        // add a transfer I know exists already
        $transactions[] = [
            'type'               => 'transfer',
            'date'               => '2017-02-28',
            'tags'               => '',
            'user'               => $this->job->user_id,

            // all custom fields:
            'internal_reference' => null,
            'notes'              => null,

            // journal data:
            'description'        => 'Saving money for February',
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,

            // transaction data:
            'transactions'       => [
                [
                    'currency_id'           => null,
                    'currency_code'         => 'EUR',
                    'description'           => null,
                    'amount'                => '140',
                    'budget_id'             => null,
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => null,
                    'source_id'             => 1,
                    'source_name'           => 'Checking Account',
                    'destination_id'        => 2,
                    'destination_name'      => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];


        return $transactions;

    }

}