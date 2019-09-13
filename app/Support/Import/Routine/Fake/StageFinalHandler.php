<?php

/**
 * StageFinalHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\Fake;

use Carbon\Carbon;
use FireflyIII\Models\ImportJob;

/**
 * @codeCoverageIgnore
 * Class StageFinalHandler
 *
 */
class StageFinalHandler
{
    /** @var ImportJob */
    private $importJob;

    /**
     * @return array
     * @throws \Exception
     */
    public function getTransactions(): array
    {
        $transactions = [];

        for ($i = 0; $i < 5; $i++) {
            $transaction = [
                'type'               => 'withdrawal',
                'date'               => Carbon::now()->format('Y-m-d'),
                'tags'               => '',
                'user'               => $this->importJob->user_id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some random description #' . random_int(1, 10000),
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,
                'original-source'    => sprintf('fake-import-v%s', config('firefly.version')),

                // transaction data:
                'transactions'       => [
                    [
                        'type'             => 'withdrawal',
                        'date'             => Carbon::now()->format('Y-m-d'),
                        'currency_id'      => null,
                        'currency_code'    => 'EUR',
                        'description'      => 'Some random description #' . random_int(1, 10000),
                        'amount'           => random_int(500, 5000) / 100,
                        'tags'             => [],
                        'user'             => $this->importJob->user_id,
                        'budget_id'        => null,
                        'budget_name'      => null,
                        'category_id'      => null,
                        'category_name'    => null,
                        'source_id'        => null,
                        'source_name'      => 'Checking Account',
                        'destination_id'   => null,
                        'destination_name' => 'Random expense account #' . random_int(1, 10000),
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
            'user'               => $this->importJob->user_id,

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
                    'type'                  => 'transfer',
                    'user'                  => $this->importJob->user_id,
                    'date'                  => '2017-02-28',
                    'currency_id'           => null,
                    'currency_code'         => 'EUR',
                    'tags'                  => [],
                    'description'           => 'Saving money for February',
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

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
    }

}
