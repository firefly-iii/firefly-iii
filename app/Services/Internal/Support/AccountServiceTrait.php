<?php
/**
 * AccountServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use Log;
use Validator;

/**
 * Trait AccountServiceTrait
 *
 */
trait AccountServiceTrait
{
    /** @var AccountRepositoryInterface */
    protected $accountRepository;

    /** @var array */
    protected $validAssetFields = ['account_role', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
    /** @var array */
    protected $validCCFields = ['account_role', 'cc_monthly_payment_date', 'cc_type', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
    /** @var array */
    protected $validFields = ['account_number', 'currency_id', 'BIC', 'interest', 'interest_period', 'include_net_worth'];

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

//    /**
//     * @param User $user
//     * @param string $name
//     *
//     * @return Account
//     * @throws \FireflyIII\Exceptions\FireflyException
//     */
//    public function storeOpposingAccount(User $user, string $name): Account
//    {
//        $opposingAccountName = (string)trans('firefly.initial_balance_account', ['name' => $name]);
//        Log::debug('Going to create an opening balance opposing account.');
//        /** @var AccountFactory $factory */
//        $factory = app(AccountFactory::class);
//        $factory->setUser($user);
//
//        return $factory->findOrCreate($opposingAccountName, AccountType::INITIAL_BALANCE);
//    }

//    /**
//     * @param Account $account
//     * @param array $data
//     *
//     * @return bool
//     * @throws \FireflyIII\Exceptions\FireflyException
//     */
//    public function updateOB(Account $account, array $data): bool
//    {
//        Log::debug(sprintf('updateIB() for account #%d', $account->id));
//
//        $openingBalanceGroup = $this->getOBGroup($account);
//
//        // no opening balance journal? create it:
//        if (null === $openingBalanceGroup) {
//            Log::debug('No opening balance journal yet, create group.');
//            $this->storeOBGroup($account, $data);
//
//            return true;
//        }
//
//        // opening balance data? update it!
//        if (null !== $openingBalanceGroup->id) {
//            Log::debug('Opening balance group found, update group.');
//            $this->updateOBGroup($account, $openingBalanceGroup, $data);
//
//            return true;
//        }
//
//        return true; // @codeCoverageIgnore
//    }

    /**
     * Update meta data for account. Depends on type which fields are valid.
     *
     * TODO this method treats expense accounts and liabilities the same way (tries to save interest)
     *
     * @param Account $account
     * @param array $data
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function updateMetaData(Account $account, array $data): void
    {
        $fields = $this->validFields;

        if ($account->accountType->type === AccountType::ASSET) {
            $fields = $this->validAssetFields;
        }
        if ($account->accountType->type === AccountType::ASSET && 'ccAsset' === $data['account_role']) {
            $fields = $this->validCCFields;
        }
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            $factory->crud($account, $field, (string)($data[$field] ?? ''));
        }
    }

//    /**
//     * Find existing opening balance.
//     *
//     * @param Account $account
//     *
//     * @return TransactionJournal|null
//     */
//    public function getIBJournal(Account $account): ?TransactionJournal
//    {
//        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
//                                     ->where('transactions.account_id', $account->id)
//                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
//                                     ->first(['transaction_journals.*']);
//        if (null === $journal) {
//            Log::debug('Could not find a opening balance journal, return NULL.');
//
//            return null;
//        }
//        Log::debug(sprintf('Found opening balance: journal #%d.', $journal->id));
//
//        return $journal;
//    }

//    /**
//     * @param Account $account
//     * @param array   $data
//     *
//     * @return TransactionJournal|null
//     * @throws \FireflyIII\Exceptions\FireflyException
//     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
//     */
//    public function storeIBJournal(Account $account, array $data): ?TransactionJournal
//    {
//        $amount = (string)$data['openingBalance'];
//        Log::debug(sprintf('Submitted amount is %s', $amount));
//
//        if (0 === bccomp($amount, '0')) {
//            return null;
//        }
//
//        // store journal, without transactions:
//        $name        = $data['name'];
//        $currencyId  = $data['currency_id'];
//        $journalData = [
//            'type'                    => TransactionType::OPENING_BALANCE,
//            'user'                    => $account->user->id,
//            'transaction_currency_id' => $currencyId,
//            'description'             => (string)trans('firefly.initial_balance_description', ['account' => $account->name]),
//            'completed'               => true,
//            'date'                    => $data['openingBalanceDate'],
//            'bill_id'                 => null,
//            'bill_name'               => null,
//            'piggy_bank_id'           => null,
//            'piggy_bank_name'         => null,
//            'tags'                    => null,
//            'notes'                   => null,
//            'transactions'            => [],
//
//        ];
//        /** @var TransactionJournalFactory $factory */
//        $factory = app(TransactionJournalFactory::class);
//        $factory->setUser($account->user);
//        $journal  = $factory->create($journalData);
//        $opposing = $this->storeOpposingAccount($account->user, $name);
//        Log::notice(sprintf('Created new opening balance journal: #%d', $journal->id));
//
//        $firstAccount  = $account;
//        $secondAccount = $opposing;
//        $firstAmount   = $amount;
//        $secondAmount  = bcmul($amount, '-1');
//        Log::notice(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));
//
//        if (bccomp($amount, '0') === -1) {
//            Log::debug(sprintf('%s is a negative number.', $amount));
//            $firstAccount  = $opposing;
//            $secondAccount = $account;
//            $firstAmount   = bcmul($amount, '-1');
//            $secondAmount  = $amount;
//            Log::notice(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));
//        }
//        /** @var TransactionFactory $factory */
//        $factory = app(TransactionFactory::class);
//        $factory->setUser($account->user);
//        $factory->create(
//            [
//                'account'             => $firstAccount,
//                'transaction_journal' => $journal,
//                'amount'              => $firstAmount,
//                'currency_id'         => $currencyId,
//                'description'         => null,
//                'identifier'          => 0,
//                'foreign_amount'      => null,
//                'reconciled'          => false,
//            ]
//        );
//        $factory->create(
//            [
//                'account'             => $secondAccount,
//                'transaction_journal' => $journal,
//                'amount'              => $secondAmount,
//                'currency_id'         => $currencyId,
//                'description'         => null,
//                'identifier'          => 0,
//                'foreign_amount'      => null,
//                'reconciled'          => false,
//            ]
//        );
//
//        return $journal;
//    }

    /**
     * @param Account $account
     * @param string $note
     *
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
        if ('' !== $data['opening_balance'] && isset($data['opening_balance'], $data['opening_balance_date'])) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
    }

//    /**
//     * @param Account            $account
//     * @param TransactionJournal $journal
//     * @param array              $data
//     *
//     * @return bool
//     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
//     */
//    protected function updateIBJournal(Account $account, TransactionJournal $journal, array $data): bool
//    {
//        $date           = $data['openingBalanceDate'];
//        $amount         = (string)$data['openingBalance'];
//        $negativeAmount = bcmul($amount, '-1');
//        $currencyId     = (int)$data['currency_id'];
//        Log::debug(sprintf('Submitted amount for opening balance to update is "%s"', $amount));
//        if (0 === bccomp($amount, '0')) {
//            Log::notice(sprintf('Amount "%s" is zero, delete opening balance.', $amount));
//            /** @var JournalDestroyService $service */
//            $service = app(JournalDestroyService::class);
//            $service->destroy($journal);
//
//            return true;
//        }
//        $journal->date                    = $date;
//        $journal->transaction_currency_id = $currencyId;
//        $journal->save();
//        /** @var Transaction $transaction */
//        foreach ($journal->transactions()->get() as $transaction) {
//            if ((int)$account->id === (int)$transaction->account_id) {
//                Log::debug(sprintf('Will (eq) change transaction #%d amount from "%s" to "%s"', $transaction->id, $transaction->amount, $amount));
//                $transaction->amount                  = $amount;
//                $transaction->transaction_currency_id = $currencyId;
//                $transaction->save();
//            }
//            if (!((int)$account->id === (int)$transaction->account_id)) {
//                Log::debug(sprintf('Will (neq) change transaction #%d amount from "%s" to "%s"', $transaction->id, $transaction->amount, $negativeAmount));
//                $transaction->amount                  = $negativeAmount;
//                $transaction->transaction_currency_id = $currencyId;
//                $transaction->save();
//            }
//        }
//        Log::debug('Updated opening balance journal.');
//
//        return true;
//    }

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
     * @param Account $account
     * @param array $data
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
     * Update or create the opening balance group. Assumes valid data in $data.
     *
     * Returns null if this fails.
     *
     * @param Account $account
     * @param array $data
     * @return TransactionGroup|null
     */
    protected function updateOBGroup(Account $account, array $data): ?TransactionGroup
    {
        if (null === $this->getOBGroup($account)) {
            return $this->createOBGroup($account, $data);
        }

        // edit in this method
        die('cannot handle edit');
    }

    /**
     * Returns the opening balance group, or NULL if it does not exist.
     *
     * @param Account $account
     * @return TransactionGroup|null
     */
    protected function getOBGroup(Account $account): ?TransactionGroup
    {
        return $this->accountRepository->getOpeningBalanceGroup($account);
    }
}
