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
use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
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

        // create recurrence meta (tags)
        if (\count($data['meta']['tags']) > 0) {
            // todo move to factory
            $tags = implode(',', $data['meta']['tags']);
            if ('' !== $tags) {
                $metaValue = RecurrenceMeta::create(
                    [
                        'recurrence_id' => $recurrence->id,
                        'name'          => 'tags',
                        'value'         => $tags,
                    ]
                );
            }
        }
        // create recurrence meta (piggy bank ID):
        if ($data['meta']['piggy_bank_id'] > 0) {
            // todo move to factory
            $metaValue = RecurrenceMeta::create(
                [
                    'recurrence_id' => $recurrence->id,
                    'name'          => 'piggy_bank_id',
                    'value'         => $data['meta']['piggy_bank_id'],
                ]
            );
        }

        // store recurrence repetitions:
        foreach ($data['repetitions'] as $repArray) {
            // todo move to factory
            $repetition = RecurrenceRepetition::create(
                [
                    'recurrence_id'     => $recurrence->id,
                    'repetition_type'   => $repArray['type'],
                    'repetition_moment' => $repArray['moment'],
                    'repetition_skip'   => $repArray['skip'],
                ]
            );
        }

        // create recurrence transactions
        foreach ($data['transactions'] as $trArray) {
            // todo move to factory
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
                case TransactionType::DEPOSIT:
                    $source      = $this->findAccount(AccountType::REVENUE, null, $trArray['source_account_name']);
                    $destination = $this->findAccount(AccountType::ASSET, $trArray['destination_account_id'], null);
                    break;
                case TransactionType::TRANSFER:
                    $source      = $this->findAccount(AccountType::ASSET, $trArray['source_account_id'], null);
                    $destination = $this->findAccount(AccountType::ASSET, $trArray['destination_account_id'], null);
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

            // create recurrence transaction meta:
            // todo move to factory
            if ($trArray['budget_id'] > 0) {
                $trMeta = RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'budget_id',
                        'value' => $trArray['budget_id'],
                    ]
                );
            }
            if ('' !== (string)$trArray['category_name']) {
                $trMeta = RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'category_name',
                        'value' => $trArray['category_name'],
                    ]
                );
            }
        }

        return $recurrence;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}