<?php
/**
 * RecurrenceFactory.php
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

namespace FireflyIII\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;

/**
 * Class RecurrenceFactory
 */
class RecurrenceFactory
{
    use TransactionTypeTrait, TransactionServiceTrait;

    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @throws FireflyException
     * @return Recurrence
     */
    public function create(array $data): Recurrence
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        $type       = $this->findTransactionType(ucfirst($data['recurrence']['type']));
        $recurrence = new Recurrence(
            [
                'user_id'             => $this->user->id,
                'transaction_type_id' => $type->id,
                'title'               => $data['recurrence']['title'],
                'description'         => $data['recurrence']['description'],
                'first_date'          => $data['recurrence']['first_date']->format('Y-m-d'),
                'repeat_until'        => $data['recurrence']['repeat_until'],
                'latest_date'         => null,
                'repetitions'         => $data['recurrence']['repetitions'],
                'apply_rules'         => $data['recurrence']['apply_rules'],
                'active'              => $data['recurrence']['active'],
            ]
        );
        $recurrence->save();
        var_dump($recurrence->toArray());

        // create transactions
        foreach ($data['transactions'] as $trArray) {
            $source      = null;
            $destination = null;
            // search source account, depends on type
            switch ($type->type) {
                default:
                    throw new FireflyException(sprintf('Cannot create "%s".', $type->type));
                case TransactionType::WITHDRAWAL:
                    $source      = $this->findAccount(AccountType::ASSET, $trArray['source_account_id'], null);
                    $destination = $this->findAccount(AccountType::EXPENSE, null, $trArray['destination_account_name']);
                    break;
            }

            // search destination account

            $transaction = new RecurrenceTransaction(
                [
                    'recurrence_id'           => $recurrence->id,
                    'transaction_currency_id' => $trArray['transaction_currency_id'],
                    'foreign_currency_id'     => '' === (string)$trArray['foreign_amount'] ? null : $trArray['foreign_currency_id'],
                    'source_account_id'       => $source->id,
                    'destination_account_id'  => $destination->id,
                    'amount'                  => $trArray['amount'],
                    'foreign_amount'          => '' === (string)$trArray['foreign_amount'] ? null : (string)$trArray['foreign_amount'],
                    'description'             => $trArray['description'],
                ]
            );
            $transaction->save();
            var_dump($transaction->toArray());
        }

        // create meta data:
        if(\count($data['meta']['tags']) > 0) {
            // todo store tags
        }

        exit;

    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}