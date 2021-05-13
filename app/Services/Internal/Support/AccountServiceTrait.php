<?php
/**
 * AccountServiceTrait.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\DuplicateTransactionException;
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use JsonException;
use Log;
use Validator;

/**
 * Trait AccountServiceTrait
 *
 */
trait AccountServiceTrait
{
    protected AccountRepositoryInterface $accountRepository;

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
     * Returns true if the data in the array is submitted but empty.
     *
     * @param array $data
     *
     * @return bool
     */
    public function isEmptyOBData(array $data): bool
    {
        if (!array_key_exists('opening_balance', $data)
            && !array_key_exists('opening_balance_date', $data)
        ) {
            // not set, so false.
            return false;
        }
        // if is set, but is empty:
        if (
            (array_key_exists('opening_balance', $data) && '' === $data['opening_balance'])
            || (array_key_exists('opening_balance_date', $data) && '' === $data['opening_balance_date'])
        ) {
            return true;
        }

        return false;
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

        // the account role may not be set in the data but we may have it already:
        if (!array_key_exists('account_role', $data)) {
            $data['account_role'] = null;
        }
        if (null === $data['account_role']) {
            $data['account_role'] = $this->accountRepository->getMetaValue($account, 'account_role');
        }

        // only asset account may have a role:
        if ($account->accountType->type !== AccountType::ASSET) {
            $data['account_role'] = '';
        }

        if ($account->accountType->type === AccountType::ASSET && array_key_exists('account_role', $data) && 'ccAsset' === $data['account_role']) {
            $fields = $this->validCCFields;
        }
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            // if the field is set but NULL, skip it.
            // if the field is set but "", update it.
            if (array_key_exists($field, $data) && null !== $data[$field]) {

                // convert boolean value:
                if (is_bool($data[$field]) && false === $data[$field]) {
                    $data[$field] = 0;
                }
                if (is_bool($data[$field]) && true === $data[$field]) {
                    $data[$field] = 1;
                }

                $factory->crud($account, $field, (string)$data[$field]);
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
                } catch (Exception $e) { // @phpstan-ignore-line
                    // @ignoreException
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
        if ('' !== $data['opening_balance'] && array_key_exists('opening_balance_date', $data) && '' !== $data['opening_balance_date']
            && $data['opening_balance_date'] instanceof Carbon) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
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
     * Delete TransactionGroup with liability credit in it.
     *
     * @param Account $account
     */
    protected function deleteCreditTransaction(Account $account): void
    {
        Log::debug(sprintf('deleteCreditTransaction() for account #%d', $account->id));
        $creditGroup = $this->getCreditTransaction($account);

        if (null !== $creditGroup) {
            Log::debug('Credit journal found, delete journal.');
            /** @var TransactionGroupDestroyService $service */
            $service = app(TransactionGroupDestroyService::class);
            $service->destroy($creditGroup);
        }
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
     * Returns the credit transaction group, or NULL if it does not exist.
     *
     * @param Account $account
     *
     * @return TransactionGroup|null
     */
    protected function getCreditTransaction(Account $account): ?TransactionGroup
    {
        Log::debug(sprintf('Now at %s', __METHOD__));

        return $this->accountRepository->getCreditTransactionGroup($account);
    }

    /**
     * @param int    $currencyId
     * @param string $currencyCode
     *
     * @return TransactionCurrency
     * @throws JsonException
     */
    protected function getCurrency(int $currencyId, string $currencyCode): TransactionCurrency
    {
        // find currency, or use default currency instead.
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency|null $currency */
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
     * Update or create the opening balance group.
     * Since opening balance and date can still be empty strings, it may fail.
     *
     * @param Account $account
     * @param string  $openingBalance
     * @param Carbon  $openingBalanceDate
     *
     * @return TransactionGroup
     * @throws FireflyException
     */
    protected function updateOBGroupV2(Account $account, string $openingBalance, Carbon $openingBalanceDate): TransactionGroup
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        // create if not exists:
        $obGroup = $this->getOBGroup($account);
        if (null === $obGroup) {
            return $this->createOBGroupV2($account, $openingBalance, $openingBalanceDate);
        }

        // if exists, update:
        $currency = $this->accountRepository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = app('default')->getDefaultCurrencyByUser($account->user);
        }

        // simply grab the first journal and change it:
        $journal            = $this->getObJournal($obGroup);
        $obTransaction      = $this->getOBTransaction($journal, $account);
        $accountTransaction = $this->getNotOBTransaction($journal, $account);
        $journal->date      = $openingBalanceDate;
        $journal->transactionCurrency()->associate($currency);


        // if amount is negative:
        if (1 === bccomp('0', $openingBalance)) {
            // account transaction loses money:
            $accountTransaction->amount                  = app('steam')->negative($openingBalance);
            $accountTransaction->transaction_currency_id = $currency->id;

            // OB account transaction gains money
            $obTransaction->amount                  = app('steam')->positive($openingBalance);
            $obTransaction->transaction_currency_id = $currency->id;
        }
        if (-1 === bccomp('0', $openingBalance)) {
            // account gains money:
            $accountTransaction->amount                  = app('steam')->positive($openingBalance);
            $accountTransaction->transaction_currency_id = $currency->id;

            // OB account loses money:
            $obTransaction->amount                  = app('steam')->negative($openingBalance);
            $obTransaction->transaction_currency_id = $currency->id;
        }
        // save both
        $accountTransaction->save();
        $obTransaction->save();
        $journal->save();
        $obGroup->refresh();

        return $obGroup;
    }

    /**
     * Create the opposing "credit liability" transaction for credit liabilities.
     *
     * @param Account $account
     * @param string  $openingBalance
     * @param Carbon  $openingBalanceDate
     *
     * @return TransactionGroup
     * @throws FireflyException
     */
    protected function updateCreditTransaction(Account $account, string $openingBalance, Carbon $openingBalanceDate): TransactionGroup
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        if (0 === bccomp($openingBalance, '0')) {
            Log::debug('Amount is zero, so will not update liability credit group.');
            throw new FireflyException('Amount for update liability credit was unexpectedly 0.');
        }

        // create if not exists:
        $clGroup = $this->getCreditTransaction($account);
        if (null === $clGroup) {
            return $this->createCreditTransaction($account, $openingBalance, $openingBalanceDate);
        }
        // if exists, update:
        $currency = $this->accountRepository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = app('default')->getDefaultCurrencyByUser($account->user);
        }

        // simply grab the first journal and change it:
        $journal            = $this->getObJournal($clGroup);
        $clTransaction      = $this->getOBTransaction($journal, $account);
        $accountTransaction = $this->getNotOBTransaction($journal, $account);
        $journal->date      = $openingBalanceDate;
        $journal->transactionCurrency()->associate($currency);

        // account always gains money:
        $accountTransaction->amount                  = app('steam')->positive($openingBalance);
        $accountTransaction->transaction_currency_id = $currency->id;

        // CL account always loses money:
        $clTransaction->amount                  = app('steam')->negative($openingBalance);
        $clTransaction->transaction_currency_id = $currency->id;
        // save both
        $accountTransaction->save();
        $clTransaction->save();
        $journal->save();
        $clGroup->refresh();

        return $clGroup;
    }

    /**
     * TODO rename to "getOpposingTransaction"
     *
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     * @throws FireflyException
     */
    private function getOBTransaction(TransactionJournal $journal, Account $account): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->where('account_id', '!=', $account->id)->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Could not get OB transaction for journal #%d', $journal->id));
        }

        return $transaction;
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     * @throws FireflyException
     */
    private function getNotOBTransaction(TransactionJournal $journal, Account $account): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Could not get non-OB transaction for journal #%d', $journal->id));
        }

        return $transaction;
    }

    /**
     * @param Account $account
     * @param string  $openingBalance
     * @param Carbon  $openingBalanceDate
     *
     * @return TransactionGroup
     * @throws FireflyException
     */
    protected function createOBGroupV2(Account $account, string $openingBalance, Carbon $openingBalanceDate): TransactionGroup
    {
        Log::debug('Now going to create an OB group.');
        $language   = app('preferences')->getForUser($account->user, 'language', 'en_US')->data;
        $sourceId   = null;
        $sourceName = null;
        $destId     = null;
        $destName   = null;

        // amount is positive.
        if (1 === bccomp($openingBalance, '0')) {
            Log::debug(sprintf('Amount is %s, which is positive. Source is a new IB account, destination is #%d', $openingBalance, $account->id));
            $sourceName = trans('firefly.initial_balance_description', ['account' => $account->name], $language);
            $destId     = $account->id;
        }
        // amount is not positive
        if (-1 === bccomp($openingBalance, '0')) {
            Log::debug(sprintf('Amount is %s, which is negative. Destination is a new IB account, source is #%d', $openingBalance, $account->id));
            $destName = trans('firefly.initial_balance_account', ['account' => $account->name], $language);
            $sourceId = $account->id;
        }
        // amount is 0
        if (0 === bccomp($openingBalance, '0')) {
            Log::debug('Amount is zero, so will not make an OB group.');
            throw new FireflyException('Amount for new opening balance was unexpectedly 0.');
        }

        // make amount positive, regardless:
        $amount = app('steam')->positive($openingBalance);

        // get or grab currency:
        $currency = $this->accountRepository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = app('default')->getDefaultCurrencyByUser($account->user);
        }

        // submit to factory:
        $submission = [
            'group_title'  => null,
            'user'         => $account->user_id,
            'transactions' => [
                [
                    'type'             => 'Opening balance',
                    'date'             => $openingBalanceDate,
                    'source_id'        => $sourceId,
                    'source_name'      => $sourceName,
                    'destination_id'   => $destId,
                    'destination_name' => $destName,
                    'user'             => $account->user_id,
                    'currency_id'      => $currency->id,
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

        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($account->user);

        try {
            $group = $factory->create($submission);
        } catch (DuplicateTransactionException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $group;
    }

    /**
     * @param Account $account
     * @param string  $openingBalance
     * @param Carbon  $openingBalanceDate
     *
     * @return TransactionGroup
     * @throws FireflyException
     */
    protected function createCreditTransaction(Account $account, string $openingBalance, Carbon $openingBalanceDate): TransactionGroup
    {
        Log::debug('Now going to create an createCreditTransaction.');

        if (0 === bccomp($openingBalance, '0')) {
            Log::debug('Amount is zero, so will not make an liability credit group.');
            throw new FireflyException('Amount for new liability credit was unexpectedly 0.');
        }

        $language = app('preferences')->getForUser($account->user, 'language', 'en_US')->data;
        $amount   = app('steam')->positive($openingBalance);

        // get or grab currency:
        $currency = $this->accountRepository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = app('default')->getDefaultCurrencyByUser($account->user);
        }

        // submit to factory:
        $submission = [
            'group_title'  => null,
            'user'         => $account->user_id,
            'transactions' => [
                [
                    'type'             => 'Liability credit',
                    'date'             => $openingBalanceDate,
                    'source_id'        => null,
                    'source_name'      => trans('firefly.liability_credit_description', ['account' => $account->name], $language),
                    'destination_id'   => $account->id,
                    'destination_name' => null,
                    'user'             => $account->user_id,
                    'currency_id'      => $currency->id,
                    'order'            => 0,
                    'amount'           => $amount,
                    'foreign_amount'   => null,
                    'description'      => trans('firefly.liability_credit_description', ['account' => $account->name]),
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

        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($account->user);

        try {
            $group = $factory->create($submission);
        } catch (DuplicateTransactionException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $group;
    }


    /**
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionGroup
     * @throws FireflyException
     * @deprecated
     */
    protected function createOBGroup(Account $account, array $data): TransactionGroup
    {
        Log::debug('Now going to create an OB group.');
        $language   = app('preferences')->getForUser($account->user, 'language', 'en_US')->data;
        $sourceId   = null;
        $sourceName = null;
        $destId     = null;
        $destName   = null;
        $amount     = array_key_exists('opening_balance', $data) ? $data['opening_balance'] : '0';

        // amount is positive.
        if (1 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount is %s, which is positive. Source is a new IB account, destination is #%d', $amount, $account->id));
            $sourceName = trans('firefly.initial_balance_description', ['account' => $account->name], $language);
            $destId     = $account->id;
        }
        // amount is not positive
        if (-1 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount is %s, which is negative. Destination is a new IB account, source is #%d', $amount, $account->id));
            $destName = trans('firefly.initial_balance_account', ['account' => $account->name], $language);
            $sourceId = $account->id;
        }
        // amount is 0
        if (0 === bccomp($amount, '0')) {
            Log::debug('Amount is zero, so will not make an OB group.');
            throw new FireflyException('Amount for new opening balance was unexpectedly 0.');
        }

        // make amount positive, regardless:
        $amount = app('steam')->positive($amount);

        // get or grab currency:
        $currency = $this->accountRepository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = app('default')->getDefaultCurrencyByUser($account->user);
        }

        // submit to factory:
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
                    'currency_id'      => $currency->id,
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

        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($account->user);

        try {
            $group = $factory->create($submission);
        } catch (DuplicateTransactionException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $group;
    }

    /**
     * TODO Refactor to "getFirstJournal"
     *
     * @param TransactionGroup $group
     *
     * @return TransactionJournal
     * @throws FireflyException
     */
    private function getObJournal(TransactionGroup $group): TransactionJournal
    {
        /** @var TransactionJournal $journal */
        $journal = $group->transactionJournals()->first();
        if (null === $journal) {
            throw new FireflyException(sprintf('Group #%d has no OB journal', $group->id));
        }

        return $journal;
    }
}
