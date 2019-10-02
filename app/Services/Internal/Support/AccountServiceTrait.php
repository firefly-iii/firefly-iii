<?php
/**
 * AccountServiceTrait.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use Log;
use Validator;

/**
 * Trait AccountServiceTrait
 *
 */
trait AccountServiceTrait
{
    /**
     * @param null|string $iban
     *
     * @return null|string
     */
    public function filterIban(?string $iban): ?string
    {
        if (null === $iban) {
            return null;
        }
        $data      = ['iban' => $iban];
        $rules     = ['iban' => 'required|iban'];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Log::info(sprintf('Detected invalid IBAN ("%s"). Return NULL instead.', $iban));

            return null;
        }


        return $iban;
    }

    /**
     * Update meta data for account. Depends on type which fields are valid.
     *
     * TODO this method treats expense accounts and liabilities the same way (tries to save interest)
     *
     * @param Account $account
     * @param array   $data
     *
     */
    public function updateMetaData(Account $account, array $data): void
    {
        $fields = $this->validFields;

        if ($account->accountType->type === AccountType::ASSET) {
            $fields = $this->validAssetFields;
        }
        if ($account->accountType->type === AccountType::ASSET && isset($data['account_role']) && 'ccAsset' === $data['account_role']) {
            $fields = $this->validCCFields;
        }
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            // if the field is set but NULL, skip it.
            // if the field is set but "", update it.
            if (isset($data[$field]) && null !== $data[$field]) {
                $factory->crud($account, $field, (string)($data[$field] ?? ''));
            }
        }
    }

    /**
     * @param Account $account
     * @param string  $note
     *
     * @codeCoverageIgnore
     * @return bool
     */
    public function updateNote(Account $account, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $account->notes()->first();
            if (null !== $dbNote) {
                try {
                    $dbNote->delete();
                } catch (Exception $e) {
                    Log::debug($e->getMessage());
                }
            }

            return true;
        }
        $dbNote = $account->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($account);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }

    /**
     * Verify if array contains valid data to possibly store or update the opening balance.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validOBData(array $data): bool
    {
        $data['opening_balance'] = (string)($data['opening_balance'] ?? '');
        if ('' !== $data['opening_balance'] && 0 === bccomp($data['opening_balance'], '0')) {
            $data['opening_balance'] = '';
        }
        if ('' !== $data['opening_balance'] && isset($data['opening_balance'], $data['opening_balance_date'])) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionGroup|null
     */
    protected function createOBGroup(Account $account, array $data): ?TransactionGroup
    {
        Log::debug('Now going to create an OB group.');
        $language   = app('preferences')->getForUser($account->user, 'language', 'en_US')->data;
        $sourceId   = null;
        $sourceName = null;
        $destId     = null;
        $destName   = null;
        $amount     = $data['opening_balance'];
        if (1 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount is %s, which is positive. Source is a new IB account, destination is #%d', $amount, $account->id));
            // amount is positive.
            $sourceName = trans('firefly.initial_balance_description', ['account' => $account->name], $language);
            $destId     = $account->id;
        }
        if (-1 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount is %s, which is negative. Destination is a new IB account, source is #%d', $amount, $account->id));
            // amount is not positive
            $destName = trans('firefly.initial_balance_account', ['account' => $account->name], $language);
            $sourceId = $account->id;
        }
        if (0 === bccomp($amount, '0')) {
            Log::debug('Amount is zero, so will not make an OB group.');

            return null;
        }
        $amount     = app('steam')->positive($amount);
        $submission = [
            'group_title'  => null,
            'user'         => $account->user_id,
            'transactions' => [
                [
                    'type'             => 'Opening balance',
                    'date'             => $data['opening_balance_date'],
                    'source_id'        => $sourceId,
                    'source_name'      => $sourceName,
                    'destination_id'   => $destId,
                    'destination_name' => $destName,
                    'user'             => $account->user_id,
                    'currency_id'      => $data['currency_id'],
                    'order'            => 0,
                    'amount'           => $amount,
                    'foreign_amount'   => null,
                    'description'      => trans('firefly.initial_balance_description', ['account' => $account->name]),
                    'budget_id'        => null,
                    'budget_name'      => null,
                    'category_id'      => null,
                    'category_name'    => null,
                    'piggy_bank_id'    => null,
                    'piggy_bank_name'  => null,
                    'reconciled'       => false,
                    'notes'            => null,
                    'tags'             => [],
                ],
            ],
        ];
        Log::debug('Going for submission', $submission);
        $group = null;
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($account->user);

        try {
            $group = $factory->create($submission);
            // @codeCoverageIgnoreStart
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        // @codeCoverageIgnoreEnd

        return $group;
    }

    /**
     * Delete TransactionGroup with opening balance in it.
     *
     * @param Account $account
     */
    protected function deleteOBGroup(Account $account): void
    {
        Log::debug(sprintf('deleteOB() for account #%d', $account->id));
        $openingBalanceGroup = $this->getOBGroup($account);

        // opening balance data? update it!
        if (null !== $openingBalanceGroup) {
            Log::debug('Opening balance journal found, delete journal.');
            /** @var TransactionGroupDestroyService $service */
            $service = app(TransactionGroupDestroyService::class);
            $service->destroy($openingBalanceGroup);
        }
    }

    /**
     * @param int    $currencyId
     * @param string $currencyCode
     *
     * @return TransactionCurrency
     */
    protected function getCurrency(int $currencyId, string $currencyCode): TransactionCurrency
    {
        // find currency, or use default currency instead.
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find($currencyId, $currencyCode);

        if (null === $currency) {
            // use default currency:
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }
        $currency->enabled = true;
        $currency->save();

        return $currency;
    }

    /**
     * Returns the opening balance group, or NULL if it does not exist.
     *
     * @param Account $account
     *
     * @return TransactionGroup|null
     */
    protected function getOBGroup(Account $account): ?TransactionGroup
    {
        return $this->accountRepository->getOpeningBalanceGroup($account);
    }

    /**
     * Update or create the opening balance group. Assumes valid data in $data.
     *
     * Returns null if this fails.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionGroup|null
     * @codeCoverageIgnore
     */
    protected function updateOBGroup(Account $account, array $data): ?TransactionGroup
    {
        $obGroup = $this->getOBGroup($account);
        if (null === $obGroup) {
            return $this->createOBGroup($account, $data);
        }
        /** @var TransactionJournal $journal */
        $journal                          = $obGroup->transactionJournals()->first();
        $journal->date                    = $data['opening_balance_date'] ?? $journal->date;
        $journal->transaction_currency_id = $data['currency_id'];

        /** @var Transaction $obTransaction */
        $obTransaction = $journal->transactions()->where('account_id', '!=', $account->id)->first();
        /** @var Transaction $accountTransaction */
        $accountTransaction = $journal->transactions()->where('account_id', $account->id)->first();

        // if amount is negative:
        if (1 === bccomp('0', $data['opening_balance'])) {
            // account transaction loses money:
            $accountTransaction->amount                  = app('steam')->negative($data['opening_balance']);
            $accountTransaction->transaction_currency_id = $data['currency_id'];

            // OB account transaction gains money
            $obTransaction->amount                  = app('steam')->positive($data['opening_balance']);
            $obTransaction->transaction_currency_id = $data['currency_id'];
        }
        if (-1 === bccomp('0', $data['opening_balance'])) {
            // account gains money:
            $accountTransaction->amount                  = app('steam')->positive($data['opening_balance']);
            $accountTransaction->transaction_currency_id = $data['currency_id'];

            // OB account loses money:
            $obTransaction->amount                  = app('steam')->negative($data['opening_balance']);
            $obTransaction->transaction_currency_id = $data['currency_id'];
        }
        // save both
        $accountTransaction->save();
        $obTransaction->save();
        $journal->save();
        $obGroup->refresh();

        return $obGroup;
    }
}
