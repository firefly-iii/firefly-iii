<?php
/*
 * TransactionJournalFactory.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Database\Factories\FireflyIII\Models;

use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionJournal::class;

    /**
     * @return Factory
     */
    public function brokenOpeningBalance()
    {
        return $this->state(
            function () {
                return [
                    'transaction_type_id' => 4,
                ];
            }
        )->afterCreating(
            function (TransactionJournal $journal) {
                $ob1 = Account::factory(Account::class)->initialBalance()->create();
                $ob2 = Account::factory(Account::class)->initialBalance()->create();

                Transaction::factory()->create(
                    [
                        'account_id'             => $ob1->id,
                        'transaction_journal_id' => $journal->id,
                        'amount'                 => '5',
                    ]
                );
                Transaction::factory()->create(
                    [
                        'account_id'             => $ob2->id,
                        'transaction_journal_id' => $journal->id,
                        'amount'                 => '5',
                    ]
                );

            }
        );
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'             => 1,
            'transaction_type_id' => 1,
            'description'         => $this->faker->words(3, true),
            'tag_count'           => 0,
            'date'                => $this->faker->date('Y-m-d'),
        ];
    }

    /**
     * @return Factory
     */
    public function openingBalance()
    {
        return $this
            ->state(fn() => ['transaction_type_id' => 4])
            ->afterCreating(
                function (TransactionJournal $journal) {
                    // fix factory
                    $obAccount    = Account::factory(Account::class)->initialBalance()->create();
                    $assetAccount = Account::factory(Account::class)->asset()->create();
                    Transaction::factory()->create(
                        [
                            'account_id'             => $obAccount->id,
                            'transaction_journal_id' => $journal->id,
                            'amount'                 => '5',
                        ]
                    );
                    Transaction::factory()->create(
                        [
                            'account_id'             => $assetAccount->id,
                            'transaction_journal_id' => $journal->id,
                            'amount'                 => '-5',
                        ]
                    );
                }
            );
    }
}
